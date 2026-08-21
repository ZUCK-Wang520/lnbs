<?php
declare(strict_types=1);

function geetest_cfg(): array
{
    $cfg = $GLOBALS['APP_CONFIG']['geetest'] ?? [];
    return is_array($cfg) ? $cfg : [];
}

function geetest_enabled(): bool
{
    $cfg = geetest_cfg();
    return !empty($cfg['enabled']) && !empty((string) ($cfg['id'] ?? '')) && !empty((string) ($cfg['key'] ?? ''));
}

function geetest_digestmod(): string
{
    $cfg = geetest_cfg();
    $m = (string) ($cfg['digestmod'] ?? 'md5');
    return in_array($m, ['md5', 'sha256', 'hmac-sha256'], true) ? $m : 'md5';
}

function geetest_client_type(): string
{
    // web/h5/native/unknown
    $ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($ua !== '' && (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone') || str_contains($ua, 'ipad'))) {
        return 'h5';
    }
    return 'web';
}

function geetest_register_build_challenge(string $originChallenge, string $digestmod, string $key): string
{
    if ($digestmod === 'sha256') {
        return hash('sha256', $originChallenge . $key);
    }
    if ($digestmod === 'hmac-sha256') {
        return hash_hmac('sha256', $originChallenge, $key);
    }
    return md5($originChallenge . $key);
}

function geetest_http_get_json(string $url, array $query): ?array
{
    $qs = http_build_query($query);
    $full = $url . (str_contains($url, '?') ? '&' : '?') . $qs;
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'header' => "User-Agent: lnbs-geetest\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $body = @file_get_contents($full, false, $ctx);
    if (!is_string($body) || $body === '') {
        return null;
    }
    $j = json_decode($body, true);
    return is_array($j) ? $j : null;
}

function geetest_http_post_json(string $url, array $form): ?array
{
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'timeout' => 5,
            'header' => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: lnbs-geetest\r\n",
            'content' => http_build_query($form),
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    if (!is_string($body) || $body === '') {
        return null;
    }
    $j = json_decode($body, true);
    return is_array($j) ? $j : null;
}

function geetest_register_payload(): array
{
    $cfg = geetest_cfg();
    $gt = (string) ($cfg['id'] ?? '');
    $key = (string) ($cfg['key'] ?? '');
    $digestmod = geetest_digestmod();

    $ip = client_ip();
    $param = [
        'user_id' => '0',
        'client_type' => geetest_client_type(),
        'ip_address' => $ip,
        'digestmod' => $digestmod,
        'gt' => $gt,
        'json_format' => '1',
        'sdk' => 'php-lnbs:1.0.0',
    ];
    $resp = geetest_http_get_json('https://api.geetest.com/register.php', $param);
    $origin = is_array($resp) ? (string) ($resp['challenge'] ?? '') : '';

    $ok = $origin !== '' && $origin !== '0';
    if (!$ok) {
        $challenge = bin2hex(random_bytes(16));
        $_SESSION['gt_server_status'] = 0;
        $_SESSION['gt_challenge'] = $challenge;
        return [
            'success' => 0,
            'new_captcha' => true,
            'challenge' => $challenge,
            'gt' => $gt,
        ];
    }
    $challenge = geetest_register_build_challenge($origin, $digestmod, $key);
    $_SESSION['gt_server_status'] = 1;
    $_SESSION['gt_challenge'] = $challenge;
    return [
        'success' => 1,
        'new_captcha' => true,
        'challenge' => $challenge,
        'gt' => $gt,
    ];
}

/**
 * @return ?string null=通过；否则返回用户可见错误提示
 */
function geetest_validate_or_error(string $challenge, string $validate, string $seccode): ?string
{
    $challenge = trim($challenge);
    $validate = trim($validate);
    $seccode = trim($seccode);
    if ($challenge === '' || $validate === '' || $seccode === '') {
        return '请先完成滑块验证码验证。';
    }

    $status = (int) ($_SESSION['gt_server_status'] ?? 0);
    if ($status !== 1) {
        // bypass: only basic param check
        return null;
    }

    $cfg = geetest_cfg();
    $gt = (string) ($cfg['id'] ?? '');
    $ip = client_ip();
    $param = [
        'seccode' => $seccode,
        'json_format' => '1',
        'challenge' => $challenge,
        'sdk' => 'php-lnbs:1.0.0',
        'captchaid' => $gt,
        'client_type' => geetest_client_type(),
        'ip_address' => $ip,
        'user_id' => '0',
    ];
    $resp = geetest_http_post_json('https://api.geetest.com/validate.php', $param);
    if (!is_array($resp)) {
        return '验证码验证失败，请稍后重试。';
    }
    $respSeccode = (string) ($resp['seccode'] ?? '');
    if ($respSeccode === '' || $respSeccode === 'false') {
        return '验证码已失效或验证未通过，请重新完成滑块验证。';
    }
    return null;
}

function geetest_validate_or_fail(string $challenge, string $validate, string $seccode): bool
{
    return geetest_validate_or_error($challenge, $validate, $seccode) === null;
}

