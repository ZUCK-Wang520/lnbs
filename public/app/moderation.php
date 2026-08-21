<?php

declare(strict_types=1);

/**
 * 发帖 / 回复前：调用 DeepSeek Chat Completions（OpenAI 兼容）做内容安全判断。
 *
 * @return array{enabled:bool,api_key:string,api_url:string,model:string,timeout:int,fail_open:bool,strictness:string}
 */
function moderation_config(): array
{
    $m = $GLOBALS['APP_CONFIG']['moderation'] ?? [];
    $strictness = trim((string) ($m['strictness'] ?? 'normal'));
    if (!in_array($strictness, ['lenient', 'normal', 'strict'], true)) {
        $strictness = 'normal';
    }

    return [
        'enabled' => !empty($m['enabled']),
        'api_key' => trim((string) ($m['api_key'] ?? '')),
        'api_url' => trim((string) ($m['api_url'] ?? 'https://api.deepseek.com/v1/chat/completions')),
        'model' => trim((string) ($m['model'] ?? 'deepseek-chat')),
        'timeout' => max(5, min(90, (int) ($m['timeout'] ?? 35))),
        'fail_open' => array_key_exists('fail_open', $m) ? (bool) $m['fail_open'] : true,
        'strictness' => $strictness,
    ];
}

/**
 * @return ?string 通过返回 null；拦截或异常时返回用户可见提示
 */
function moderation_check_user_content(string ...$chunks): ?string
{
    $cfg = moderation_config();
    if (!$cfg['enabled']) {
        return null;
    }
    if ($cfg['api_key'] === '') {
        return '内容审核未正确配置，请联系管理员。';
    }
    $parts = [];
    foreach ($chunks as $c) {
        $c = trim($c);
        if ($c !== '') {
            $parts[] = $c;
        }
    }
    if ($parts === []) {
        return null;
    }
    $text = implode("\n", $parts);
    $text = moderation_redact_image_links_for_review($text);
    if (mb_strlen($text) > 12000) {
        $text = mb_substr($text, 0, 12000);
    }

    return moderation_deepseek_review($text, $cfg);
}

/**
 * 送审前去掉插图 Markdown 与常见图片直链，避免模型把 COS/外链 URL 误判为违规。
 */
function moderation_redact_image_links_for_review(string $text): string
{
    $text = preg_replace('/!\[[^\]]*\]\([^)\s]+\)/u', '[插图]', $text) ?? $text;
    $text = preg_replace(
        '#https?://[^\s<>"\'\]]+\.(?:jpe?g|png|gif|webp)(?:\?[^\s<>"\'\]]*)?#iu',
        '[插图链接]',
        $text
    ) ?? $text;

    return $text;
}

/**
 * @param array{enabled:bool,api_key:string,api_url:string,model:string,timeout:int,fail_open:bool,strictness:string} $cfg
 */
function moderation_deepseek_review(string $userText, array $cfg): ?string
{
    $mode = (string) ($cfg['strictness'] ?? 'normal');
    $base = <<<'SYS'
你是校园论坛内容审核员。仅拦截以下类型文本（其余尽量放行）：
1）黄色/淫秽低俗内容（含露骨性描写、招嫖、色情交易、性侵相关宣扬等）
2）违反国家法律法规的内容：违法犯罪、恐怖主义/暴恐、涉赌、制毒制爆、教唆犯罪等
3）有害信息与谣言：明确的诈骗引流、编造/传播谣言造成现实危害等
4）侵犯他人合法权益：恶意曝光他人隐私/个人信息、侵权、威胁敲诈等
5）暴力与恐吓：血腥暴力鼓动、对他人发出真实威胁
6）尊重他人：禁止人身攻击、歧视、骚扰、恶意辱骂
文中可能出现的「[插图]」「[插图链接]」表示用户插入的图片占位（已由系统脱敏），请勿因图片外链、存储地址或占位符本身判定违规，仅根据剩余文字语义判断。
SYS;
    $policy = '';
    if ($mode === 'lenient') {
        $policy = "判定原则：仅当存在明确、直接、可理解为违规/低俗/仇恨/暴力/诈骗等表达时才拒绝；对语义不清、边界、玩笑、引用或讨论性质的内容默认放行。\n";
    } elseif ($mode === 'strict') {
        $policy = "判定原则：对边界或可能引发争议的表达也可拒绝，优先保障校园环境安全。\n";
    } else {
        $policy = "判定原则：对明显违规拒绝；对边界内容谨慎判断。\n";
    }
    $system = $base . "\n" . $policy . <<<'SYS2'
若可以公开发布，只输出一行 JSON：{"allow":true}
若存在上述问题，只输出：{"allow":false}
不要输出其它文字、解释或 Markdown 代码块。
SYS2;

    $payload = json_encode([
        'model' => $cfg['model'],
        'temperature' => 0,
        'max_tokens' => 80,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => "请审核以下内容：\n\n" . $userText],
        ],
    ], JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return $cfg['fail_open'] ? null : '内容审核异常，请稍后重试。';
    }

    $resp = moderation_http_post_json($cfg['api_url'], $cfg['api_key'], $payload, $cfg['timeout']);
    if ($resp['error'] !== null) {
        error_log('moderation: ' . $resp['error']);
        return $cfg['fail_open'] ? null : '内容审核服务暂时不可用，请稍后再试。';
    }
    $code = $resp['code'];
    $body = $resp['body'];
    if ($code >= 400) {
        error_log('moderation HTTP ' . $code . ' ' . mb_substr($body, 0, 400));
        return $cfg['fail_open'] ? null : '内容审核服务暂时不可用，请稍后再试。';
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return $cfg['fail_open'] ? null : '内容审核异常，请稍后重试。';
    }

    $raw = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
    $raw = preg_replace('/^```(?:json)?\s*/iu', '', $raw);
    $raw = preg_replace('/\s*```$/u', '', $raw);
    $raw = trim($raw);

    $j = json_decode($raw, true);
    if (!is_array($j) || !array_key_exists('allow', $j)) {
        error_log('moderation unparseable model output: ' . mb_substr($raw, 0, 200));
        return $cfg['fail_open'] ? null : '内容审核异常，请稍后重试。';
    }

    $allow = $j['allow'];
    if ($allow === true || $allow === 1 || $allow === '1' || $allow === 'true') {
        return null;
    }
    if ($allow === false || $allow === 0 || $allow === '0' || $allow === 'false') {
        return '内容未通过安全审核：请删除色情/淫秽、违法违规（暴恐/赌博/谣言等）、暴力恐吓、侵犯他人权益或人身攻击歧视骚扰等内容后重试。';
    }

    return $cfg['fail_open'] ? null : '内容审核异常，请稍后重试。';
}

/**
 * @return array{code:int,body:string,error:?string}
 */
function moderation_http_post_json(string $url, string $apiKey, string $jsonBody, int $timeout): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            return ['code' => 0, 'body' => '', 'error' => 'curl_init failed'];
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);
        if ($body === false || $body === '') {
            return ['code' => $code, 'body' => (string) $body, 'error' => $cerr !== '' ? $cerr : 'empty response'];
        }

        return ['code' => $code, 'body' => (string) $body, 'error' => null];
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
            'content' => $jsonBody,
            'timeout' => $timeout,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    $code = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $code = (int) $m[1];
    }
    if ($body === false) {
        return ['code' => $code, 'body' => '', 'error' => 'file_get_contents failed'];
    }

    return ['code' => $code, 'body' => (string) $body, 'error' => null];
}

/** 是否为模型明确判定违规（可进入人工复核队列）；配置/网络类错误为 false */
function moderation_is_ai_content_rejection(?string $message): bool
{
    return $message !== null && str_contains($message, '未通过安全审核');
}

function moderation_flash_message_queued_for_human_review(): string
{
    return '内容已被自动审核拦截，已提交人工复核：两名审核员意见一致即可生效；若一人通过、一人拒绝，将由第三人裁定。处理完成后内容会自动发布（通过时）或维持拦截（拒绝时）。';
}
