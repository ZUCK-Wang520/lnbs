<?php

declare(strict_types=1);

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** 短信注册：满足 users.email NOT NULL，与 11 位手机号一一对应，不作为对外登录账号 */
function user_placeholder_email_from_phone(string $phone11): string
{
    return $phone11 . '@lnbs.internal';
}

function user_email_is_phone_placeholder(string $email): bool
{
    return (bool) preg_match('/^1[3-9]\d{9}@lnbs\.internal$/', $email);
}

/** 宝塔 Nginx 等未配置伪静态时开启：链接变为 /index.php?r=/路径，避免 /login 直接 404 */
function router_query_mode(): bool
{
    return !empty($GLOBALS['APP_CONFIG']['app']['router_query']);
}

function redirect(string $path): never
{
    header('Location: ' . url($path), true, 302);
    exit;
}

/**
 * 将表单回跳地址规范为站内路径（如 /user/1/topics）。
 * 修复 query 路由下误把 /index.php?r=/path 再交给 redirect() 导致的 r 双重嵌套。
 *
 * @return ?string 合法路径，否则 null（勿跳转）
 */
function internal_redirect_target(string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === '' || str_starts_with($raw, '//')) {
        return null;
    }
    if (preg_match('#^https?://#i', $raw)) {
        $path = parse_url($raw, PHP_URL_PATH) ?: '';
        $query = parse_url($raw, PHP_URL_QUERY);
        if (str_contains($path, 'index.php') && is_string($query)) {
            parse_str($query, $q);
            $raw = isset($q['r']) ? trim((string) $q['r']) : '';
        } else {
            return null;
        }
    }
    for ($i = 0; $i < 5; $i++) {
        $qpos = strpos($raw, '?');
        if ($qpos === false) {
            break;
        }
        $before = substr($raw, 0, $qpos);
        if (!str_contains($before, 'index.php')) {
            return null;
        }
        parse_str(substr($raw, $qpos + 1), $parts);
        if (empty($parts['r']) || !is_string($parts['r'])) {
            return null;
        }
        $raw = trim($parts['r']);
    }
    if ($raw === '' || $raw[0] !== '/') {
        return null;
    }
    if (str_contains($raw, '..')) {
        return null;
    }
    $raw = '/' . ltrim($raw, '/');
    $q2 = strpos($raw, '?');
    if ($q2 !== false) {
        $raw = substr($raw, 0, $q2);
    }
    $h2 = strpos($raw, '#');
    if ($h2 !== false) {
        $raw = substr($raw, 0, $h2);
    }
    if ($raw === '') {
        return '/';
    }

    return $raw;
}

function url(string $path, array $query = []): string
{
    $path = '/' . ltrim($path, '/');
    if ($path === '//') {
        $path = '/';
    }
    $fragment = '';
    $hashPos = strpos($path, '#');
    if ($hashPos !== false) {
        $fragment = substr($path, $hashPos);
        $path = substr($path, 0, $hashPos);
        if ($path === '' || $path === '/') {
            $path = '/';
        }
    }
    if (router_query_mode()) {
        $bu = rtrim((string) ($GLOBALS['APP_CONFIG']['app']['base_url'] ?? ''), '/');
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $out = $bu . $script . '?r=' . rawurlencode($path);
        foreach ($query as $k => $v) {
            $out .= '&' . rawurlencode((string) $k) . '=' . rawurlencode((string) $v);
        }
        return $out . $fragment;
    }
    $base = $GLOBALS['APP_CONFIG']['app']['base_url'] ?? '';
    $full = $path;
    if ($base !== '' && $base !== '/') {
        $full = rtrim($base, '/') . $path;
    }
    if ($query !== []) {
        $full .= (str_contains($full, '?') ? '&' : '?') . http_build_query($query);
    }
    return $full . $fragment;
}

function asset(string $path): string
{
    $rel = 'assets/' . ltrim($path, '/');
    $app = $GLOBALS['APP_CONFIG']['app'] ?? [];
    if (!empty($app['asset_base'])) {
        return rtrim((string) $app['asset_base'], '/') . '/' . $rel;
    }
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = str_replace('\\', '/', dirname($script));
    if ($dir !== '/' && $dir !== '.' && $dir !== '') {
        return rtrim($dir, '/') . '/' . $rel;
    }
    $bu = rtrim((string) ($app['base_url'] ?? ''), '/');
    if ($bu !== '') {
        return $bu . '/' . $rel;
    }
    return '/' . $rel;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['_flash'])) {
        return null;
    }
    $f = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
    return $f;
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

/** Query 路由下 index.php?phone= 表示私信搜索；无 r= 时仍应进入 /chat（兼容错误链接） */
function request_get_phone_for_chat_nonempty(): bool
{
    return isset($_GET['phone'])
        && is_string($_GET['phone'])
        && trim($_GET['phone']) !== '';
}

/** 是否已存在 users.profile_likes（migration_user_likes.sql） */
function user_profile_likes_column_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT profile_likes FROM users LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

function request_path(): string
{
    if (router_query_mode()) {
        if (isset($_GET['r']) && is_string($_GET['r'])) {
            $r = trim($_GET['r']);
            // 误生成 r=/index.php?r=/real/path 时解包为站内路径
            for ($unwrap = 0; $unwrap < 5; $unwrap++) {
                $qpos = strpos($r, '?');
                if ($qpos === false) {
                    break;
                }
                $beforeQ = substr($r, 0, $qpos);
                if (!str_contains($beforeQ, 'index.php')) {
                    break;
                }
                parse_str(substr($r, $qpos + 1), $parts);
                if (empty($parts['r']) || !is_string($parts['r'])) {
                    break;
                }
                $r = trim($parts['r']);
            }
            $q = strpos($r, '?');
            if ($q !== false) {
                $r = substr($r, 0, $q);
            }
            $h = strpos($r, '#');
            if ($h !== false) {
                $r = substr($r, 0, $h);
            }
            $r = trim($r);
            if ($r === '' || $r === '/') {
                if (request_get_phone_for_chat_nonempty()) {
                    return '/chat';
                }
                return '/';
            }
            $p = '/' . ltrim($r, '/');
            if (str_contains($p, '..')) {
                return '/';
            }
            return $p;
        }
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $pathOnly = parse_url($uri, PHP_URL_PATH) ?: '/';
        if ($pathOnly === '/index.php' || str_ends_with($pathOnly, '/index.php')) {
            // index.php?phone=… 未带 r=（错误分享/旧链接）仍进私信搜索，GET 参数保留
            if (request_get_phone_for_chat_nonempty()) {
                return '/chat';
            }
            return '/';
        }
    }

    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    $base = $GLOBALS['APP_CONFIG']['app']['base_url'] ?? '';
    $baseNorm = rtrim($base, '/');
    if ($baseNorm !== '' && ($path === $baseNorm || str_starts_with($path, $baseNorm . '/'))) {
        $path = substr($path, strlen($baseNorm)) ?: '/';
    }
    if ($path === '') {
        $path = '/';
    }
    // 误用 http://域名/public/xxx 时去掉 /public 前缀
    if ($path === '/public' || str_starts_with($path, '/public/')) {
        $path = substr($path, strlen('/public')) ?: '/';
    }
    if ($path === '') {
        $path = '/';
    }
    // 直接访问 /index.php 时视为首页（站点根目录指向 public 时常见）
    if (str_ends_with($path, '/index.php')) {
        $path = substr($path, 0, -strlen('/index.php'));
        $path = $path === '' ? '/' : $path;
    }
    if ($path === '') {
        $path = '/';
    }
    return $path;
}

/** 静态文件 URL（路径相对于 public/，不含域名） */
function public_url(string $pathUnderPublic): string
{
    $rel = ltrim(str_replace('\\', '/', $pathUnderPublic), '/');
    $app = $GLOBALS['APP_CONFIG']['app'] ?? [];
    if (!empty($app['asset_base'])) {
        return rtrim((string) $app['asset_base'], '/') . '/' . $rel;
    }
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = str_replace('\\', '/', dirname($script));
    if ($dir !== '/' && $dir !== '.' && $dir !== '') {
        return rtrim($dir, '/') . '/' . $rel;
    }
    $bu = rtrim((string) ($app['base_url'] ?? ''), '/');
    if ($bu !== '') {
        return $bu . '/' . $rel;
    }

    return '/' . $rel;
}

/**
 * 站点 Logo 的 URL；未找到文件时返回 null（顶栏显示默认图形）。
 * 优先级：config app.logo_file → public 下常见文件名。
 */
function site_logo_url(): ?string
{
    $public = dirname(__DIR__);
    $configured = trim((string) ($GLOBALS['APP_CONFIG']['app']['logo_file'] ?? ''));
    if ($configured !== '') {
        $clean = ltrim(str_replace('\\', '/', $configured), '/');
        if (is_readable($public . '/' . $clean)) {
            return public_url($clean);
        }
    }
    $candidates = [
        'logo.jpg.png',
        'logo.png',
        'logo.jpg',
        'logo.jpeg',
        'logo.webp',
        'logo.svg',
        'assets/images/logo.png',
        'assets/images/logo.jpg',
        'assets/images/logo.jpeg',
        'assets/images/logo.webp',
        'assets/images/logo.svg',
    ];
    foreach ($candidates as $rel) {
        if (is_readable($public . '/' . $rel)) {
            return public_url($rel);
        }
    }

    return null;
}

/** 在线人数（bootstrap 中按会话更新，见 app/online.php） */
function online_count(): int
{
    return (int) ($GLOBALS['ONLINE_COUNT'] ?? 0);
}

