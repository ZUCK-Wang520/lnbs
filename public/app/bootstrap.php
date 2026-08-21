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

$vendorAutoload = $ROOT . '/vendor/autoload.php';
if (is_readable($vendorAutoload)) {
    require_once $vendorAutoload;
}

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

$forceHttps = (bool) (($APP_CONFIG['app']['force_https'] ?? false));
if ($forceHttps && !$httpsOn) {
    $host = (string) ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? ''));
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    if ($host !== '') {
        header('Location: https://' . $host . $uri, true, 302);
        exit;
    }
}
if ($forceHttps && $httpsOn) {
    // HSTS: prevent browsers from downgrading to http after first https visit.
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => $httpsOn,
    ]);
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/avatar.php';
require_once __DIR__ . '/site_logo.php';
require_once __DIR__ . '/online.php';
$GLOBALS['ONLINE_COUNT'] = online_touch_and_count();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/admin_audit_log.php';
require_once __DIR__ . '/git_updates.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/geetest.php';
require_once __DIR__ . '/access_challenge.php';
require_once __DIR__ . '/anti_spam.php';
require_once __DIR__ . '/sms.php';
require_once __DIR__ . '/ip_ban.php';
require_once __DIR__ . '/user_level.php';
require_once __DIR__ . '/anonymous.php';
require_once __DIR__ . '/anon_quota.php';
require_once __DIR__ . '/confession.php';
require_once __DIR__ . '/anon_ask.php';
require_once __DIR__ . '/topic_reply_notifications.php';
require_once __DIR__ . '/topic_poll.php';
require_once __DIR__ . '/chat.php';
require_once __DIR__ . '/couple.php';
require_once __DIR__ . '/moderation.php';
require_once __DIR__ . '/moderation_appeals.php';
require_once __DIR__ . '/cos_storage.php';
require_once __DIR__ . '/realname_shuma.php';
require_once __DIR__ . '/realname_storage.php';
require_once __DIR__ . '/forum_body.php';
require_once __DIR__ . '/site_announcement.php';
require_once __DIR__ . '/site_shutdown.php';
require_once __DIR__ . '/sports_meet.php';
require_once __DIR__ . '/render.php';
require_once __DIR__ . '/firewall.php';

// If user is already logged in and gets banned, kick immediately.
enforce_login_bans_for_current_session();

define('VIEWS', $ROOT . '/views');

firewall_enforce();
