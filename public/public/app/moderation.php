<?php

declare(strict_types=1);

/**
 * 发帖 / 回复前：调用 DeepSeek Chat Completions（OpenAI 兼容）做内容安全判断。
 *
 * @return array{enabled:bool,api_key:string,api_url:string,model:string,timeout:int,fail_open:bool}
 */
function moderation_config(): array
{
    $m = $GLOBALS['APP_CONFIG']['moderation'] ?? [];

    return [
        'enabled' => !empty($m['enabled']),
        'api_key' => trim((string) ($m['api_key'] ?? '')),
        'api_url' => trim((string) ($m['api_url'] ?? 'https://api.deepseek.com/v1/chat/completions')),
        'model' => trim((string) ($m['model'] ?? 'deepseek-chat')),
        'timeout' => max(5, min(90, (int) ($m['timeout'] ?? 35))),
        'fail_open' => array_key_exists('fail_open', $m) ? (bool) $m['fail_open'] : true,
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
    if (mb_strlen($text) > 12000) {
        $text = mb_substr($text, 0, 12000);
    }

    return moderation_deepseek_review($text, $cfg);
}

/**
 * @param array{enabled:bool,api_key:string,api_url:string,model:string,timeout:int,fail_open:bool} $cfg
 */
function moderation_deepseek_review(string $userText, array $cfg): ?string
{
    $system = <<<'SYS'
你是校园论坛内容审核员。判断用户提交的文本是否含有：违法违规、色情低俗、暴力恐吓、仇恨歧视、谣言诈骗、政治敏感、严重人身攻击等不适宜校园场景公开发布的内容。
若可以公开发布，只输出一行 JSON：{"allow":true}
若存在上述问题，只输出：{"allow":false}
不要输出其它文字、解释或 Markdown 代码块。
SYS;

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
        return '内容未通过安全审核，请修改违规、低俗、暴力、歧视或不适宜校园讨论的表述后重试。';
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
