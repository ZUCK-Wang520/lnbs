<?php

declare(strict_types=1);

$ROOT = dirname(__DIR__);

$configFile = $ROOT . '/config/config.local.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('缺少 config/config.local.php，请复制 config.example.php 并配置 MySQL。');
}

$APP_CONFIG = require $configFile;
$GLOBALS['APP_CONFIG'] = $APP_CONFIG;

$httpsOn =
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => $httpsOn,
    ]);
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

define('VIEWS', $ROOT . '/views');
