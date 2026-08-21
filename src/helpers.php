<?php

declare(strict_types=1);

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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

function url(string $path): string
{
    $path = '/' . ltrim($path, '/');
    if ($path === '//') {
        $path = '/';
    }
    if (router_query_mode()) {
        $bu = rtrim((string) ($GLOBALS['APP_CONFIG']['app']['base_url'] ?? ''), '/');
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        return $bu . $script . '?r=' . rawurlencode($path);
    }
    $base = $GLOBALS['APP_CONFIG']['app']['base_url'] ?? '';
    if ($base !== '' && $base !== '/') {
        return rtrim($base, '/') . $path;
    }
    return $path;
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

function request_path(): string
{
    if (router_query_mode()) {
        if (isset($_GET['r']) && is_string($_GET['r'])) {
            $r = trim($_GET['r']);
            if ($r === '' || $r === '/') {
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
