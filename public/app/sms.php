<?php

declare(strict_types=1);

/** @return array{url:string,name:string,number:string,enabled:bool} */
function sms_config(): array
{
    $app = $GLOBALS['APP_CONFIG']['app'] ?? [];
    $c = $GLOBALS['APP_CONFIG']['sms'] ?? [];
    $name = trim((string) ($c['sms_name'] ?? ''));
    if ($name === '') {
        $name = (string) ($app['name'] ?? '论坛');
    }

    return [
        'url' => rtrim((string) ($c['spug_sms_url'] ?? ''), " \t\n\r\0\x0B?&"),
        'name' => $name,
        'number' => (string) ($c['sms_number'] ?? '10'),
        'enabled' => (bool) ($c['enabled'] ?? true),
    ];
}

function normalize_phone_cn(string $raw): ?string
{
    $d = preg_replace('/\D/', '', $raw);
    if ($d === null || strlen($d) !== 11 || $d[0] !== '1') {
        return null;
    }
    if (!preg_match('/^1[3-9]\d{9}$/', $d)) {
        return null;
    }

    return $d;
}

function client_ip(): string
{
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($keys as $k) {
        if (empty($_SERVER[$k])) {
            continue;
        }
        $v = (string) $_SERVER[$k];
        if ($k === 'HTTP_X_FORWARDED_FOR') {
            $v = trim(explode(',', $v)[0]);
        }
        if (filter_var($v, FILTER_VALIDATE_IP)) {
            return $v;
        }
    }

    return '0.0.0.0';
}

function mask_phone(string $phone): string
{
    if (strlen($phone) < 7) {
        return '***';
    }

    return substr($phone, 0, 3) . '****' . substr($phone, -4);
}

/** @return string|null 错误信息，null 表示通过 */
function sms_rate_limit_check(PDO $pdo, string $phone, string $ip): ?string
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM sms_send_log WHERE phone = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)'
    );
    $st->execute([$phone]);
    if ((int) $st->fetchColumn() >= 5) {
        return '该手机号 24 小时内发送次数已达上限，请明日再试。';
    }

    $st = $pdo->prepare(
        'SELECT UNIX_TIMESTAMP(created_at) AS ts FROM sms_send_log WHERE phone = ? ORDER BY id DESC LIMIT 1'
    );
    $st->execute([$phone]);
    $row = $st->fetch();
    if ($row && isset($row['ts'])) {
        $last = (int) $row['ts'];
        if ($last > 0) {
            $elapsed = time() - $last;
            if ($elapsed < 60) {
                return '发送过于频繁，请 ' . (60 - $elapsed) . ' 秒后再试。';
            }
        }
    }

    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM sms_send_log WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)'
    );
    $st->execute([$ip]);
    if ((int) $st->fetchColumn() >= 15) {
        return '当前网络环境发送次数过多，请 24 小时后再试或更换网络。';
    }

    return null;
}

function sms_log_insert(PDO $pdo, string $phone, string $ip): void
{
    $st = $pdo->prepare('INSERT INTO sms_send_log (phone, ip) VALUES (?,?)');
    $st->execute([$phone, $ip]);
}

/**
 * @return true|string true 成功，否则错误文案
 */
function sms_spug_send(string $phone, string $code)
{
    $cfg = sms_config();
    if (!$cfg['enabled']) {
        return '短信功能已关闭。';
    }
    if ($cfg['url'] === '') {
        return '短信接口未配置（config 中 spug_sms_url）。';
    }
    if (!preg_match('/^\d{4,6}$/', $code)) {
        return '验证码格式错误。';
    }

    $base = $cfg['url'];
    $query = http_build_query(
        [
            'to' => $phone,
            'name' => $cfg['name'],
            'code' => $code,
            'number' => $cfg['number'],
        ],
        '',
        '&',
        PHP_QUERY_RFC3986
    );
    $url = $base . (str_contains($base, '?') ? '&' : '?') . $query;

    $body = '';
    $httpCode = 0;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = (string) curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);
        $http_response_header = [];
        $body = (string) @file_get_contents($url, false, $ctx);
        if (!empty($http_response_header[0]) && preg_match('#HTTP/\S+\s+(\d+)#', $http_response_header[0], $m)) {
            $httpCode = (int) $m[1];
        }
    }

    $data = json_decode($body, true);
    if (is_array($data) && array_key_exists('code', $data)) {
        $c = (int) $data['code'];
        if ($c !== 200 && $c !== 0) {
            return (string) ($data['msg'] ?? '短信接口返回错误');
        }
    } elseif ($httpCode >= 400) {
        return '短信网关请求失败（HTTP ' . $httpCode . '）。';
    }

    return true;
}

function reg_sms_session(): ?array
{
    $s = $_SESSION['_reg_sms'] ?? null;
    if (!is_array($s) || empty($s['phone']) || empty($s['code_hash']) || empty($s['expires_at'])) {
        return null;
    }

    return $s;
}

function reg_sms_clear(): void
{
    unset($_SESSION['_reg_sms'], $_SESSION['_sms_attempt_gate']);
}

function pwd_reset_sms_session(): ?array
{
    $s = $_SESSION['_pwd_reset_sms'] ?? null;
    if (!is_array($s) || empty($s['phone']) || empty($s['code_hash']) || empty($s['expires_at']) || empty($s['user_id'])) {
        return null;
    }

    return $s;
}

function pwd_reset_sms_clear(): void
{
    unset($_SESSION['_pwd_reset_sms'], $_SESSION['_pwd_reset_send_gate']);
}
