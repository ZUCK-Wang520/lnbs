<?php

declare(strict_types=1);

/**
 * 实名信息加密存储（AES-256-GCM）。
 * 密钥仅放在 config.local.php：realname.storage_key（建议 32+ 字符随机串）。
 */

function realname_storage_key(): string
{
    $cfg = $GLOBALS['APP_CONFIG']['realname'] ?? [];
    $k = (string) ($cfg['storage_key'] ?? '');

    return trim($k);
}

function realname_storage_ready(): bool
{
    return extension_loaded('openssl') && realname_storage_key() !== '';
}

function realname_storage_encrypt(string $plaintext): ?string
{
    $key = realname_storage_key();
    if ($key === '' || $plaintext === '') {
        return null;
    }
    $k = hash('sha256', $key, true); // 32 bytes
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $k, OPENSSL_RAW_DATA, $iv, $tag);
    if ($cipher === false || $tag === '') {
        return null;
    }

    return rtrim(strtr(base64_encode($iv . $tag . $cipher), '+/', '-_'), '=');
}

function realname_storage_decrypt(?string $token): ?string
{
    if ($token === null) {
        return null;
    }
    $t = trim($token);
    if ($t === '') {
        return null;
    }
    $key = realname_storage_key();
    if ($key === '') {
        return null;
    }
    $raw = base64_decode(strtr($t, '-_', '+/'), true);
    if ($raw === false || strlen($raw) < (12 + 16 + 1)) {
        return null;
    }
    $iv = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $cipher = substr($raw, 28);
    $k = hash('sha256', $key, true);
    $plain = openssl_decrypt($cipher, 'aes-256-gcm', $k, OPENSSL_RAW_DATA, $iv, $tag);

    return $plain === false ? null : $plain;
}

