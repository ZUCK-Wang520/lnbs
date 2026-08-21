<?php

declare(strict_types=1);

/**
 * 阿里云市场：身份证二要素核验（APPCODE）。
 * @see https://zidv2.market.alicloudapi.com/idcheck/Post
 */

function realname_shuma_config(): array
{
    $cfg = $GLOBALS['APP_CONFIG']['realname'] ?? [];

    return [
        'enabled' => (bool) ($cfg['enabled'] ?? false),
        'appcode' => trim((string) ($cfg['appcode'] ?? '')),
        'api_url' => trim((string) ($cfg['api_url'] ?? 'https://zidv2.market.alicloudapi.com/idcheck/Post')),
        'timeout' => max(15, min(120, (int) ($cfg['timeout'] ?? 60))),
    ];
}

function realname_shuma_ready(): bool
{
    $c = realname_shuma_config();

    return $c['enabled'] && $c['appcode'] !== '';
}

/**
 * @return array{ok:bool,error?:string}
 */
function realname_shuma_verify_faceid(string $name, string $idcard, ?string $unusedImageBase64 = null, ?string $unusedImageUrl = null): array
{
    if (!realname_shuma_ready()) {
        return ['ok' => false, 'error' => '实名认证服务未启用或未配置 AppCode。'];
    }

    $c = realname_shuma_config();
    $payload = [
        // 阿里云市场二要素参数名
        'cardNo' => $idcard,
        'realName' => $name,
    ];
    $body = http_build_query($payload, '', '&');

    $ch = curl_init($c['api_url']);
    if ($ch === false) {
        return ['ok' => false, 'error' => '无法发起实名认证请求。'];
    }
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_TIMEOUT => $c['timeout'],
        CURLOPT_HTTPHEADER => [
            'Authorization: APPCODE ' . $c['appcode'],
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        ],
    ]);
    if (str_starts_with($c['api_url'], 'https://')) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    }

    $raw = curl_exec($ch);
    $cerr = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $raw === '') {
        return ['ok' => false, 'error' => $cerr !== '' ? '网络错误：' . $cerr : '实名接口无响应。'];
    }
    $json = json_decode((string) $raw, true);
    // HTTP 非 200 时也尽量把接口 msg 透出，方便定位 400 原因
    if ($code !== 200) {
        if (is_array($json) && isset($json['msg']) && is_string($json['msg']) && trim($json['msg']) !== '') {
            return ['ok' => false, 'error' => trim($json['msg']) . '（HTTP ' . $code . '）'];
        }

        return ['ok' => false, 'error' => '实名接口异常（HTTP ' . $code . '）。'];
    }
    if (!is_array($json)) {
        return ['ok' => false, 'error' => '实名接口返回格式无效。'];
    }

    // 阿里云市场：error_code==0 表示请求成功，result.isok 表示是否核验通过
    $errorCode = (int) ($json['error_code'] ?? -1);
    $reason = (string) ($json['reason'] ?? '');
    if ($errorCode !== 0) {
        return ['ok' => false, 'error' => $reason !== '' ? $reason : ('实名认证失败（error_code=' . $errorCode . '）。')];
    }
    $result = $json['result'] ?? null;
    if (!is_array($result)) {
        return ['ok' => false, 'error' => '实名接口返回数据异常。'];
    }
    $isok = $result['isok'] ?? null;
    if ($isok === true || $isok === 1 || $isok === 'true' || $isok === '1') {
        return ['ok' => true];
    }

    // 注意：reason 可能固定为 Success（仅表示通讯成功），业务是否匹配以 isok 为准
    $msg = '姓名与身份证号不匹配。';
    if ($reason !== '' && strcasecmp(trim($reason), 'success') !== 0) {
        $msg = $reason;
    }

    return ['ok' => false, 'error' => $msg];
}

/** 简单校验中国大陆 18 位身份证号格式（含末位 X）。 */
function realname_plausible_id_card(string $id): bool
{
    $id = strtoupper(trim($id));
    if (!preg_match('/^[1-9]\d{5}(18|19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dX]$/', $id)) {
        return false;
    }
    $weights = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
    $codes = '10X98765432';
    $sum = 0;
    for ($i = 0; $i < 17; $i++) {
        $sum += (int) $id[$i] * $weights[$i];
    }
    $check = $codes[$sum % 11];

    return $id[17] === $check;
}
