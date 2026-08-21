<?php

declare(strict_types=1);

$ROOT = dirname(__DIR__);

$configFile = $ROOT . '/config/config.local.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('缺少 public/config/config.local.php，请复制 config.example.php 并配置 MySQL。');
}

$APP_CONFIG = require $configFile;
$GLOBALS['APP_CONFIG'] = $APP_CONFIG;

$authCfg = $APP_CONFIG['auth'] ?? [];
$sessionGc = (int) ($authCfg['session_gc_maxlifetime'] ?? 2592000);
if ($sessionGc < 3600) {
    $sessionGc = 3600;
}
ini_set('session.gc_maxlifetime', (string) $sessionGc);

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
require_once __DIR__ . '/avatar.php';
require_once __DIR__ . '/online.php';
$GLOBALS['ONLINE_COUNT'] = online_touch_and_count();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/anonymous.php';
require_once __DIR__ . '/confession.php';
require_once __DIR__ . '/topic_reply_notifications.php';
require_once __DIR__ . '/chat.php';
require_once __DIR__ . '/moderation.php';

define('VIEWS', $ROOT . '/views');
