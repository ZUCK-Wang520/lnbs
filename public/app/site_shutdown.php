<?php

declare(strict_types=1);

function site_shutdown_table_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT id, enabled, message, eta FROM site_shutdown LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/**
 * @return array{enabled:int, message:string, eta:string}
 */
function site_shutdown_get(): array
{
    $def = ['enabled' => 0, 'message' => '', 'eta' => ''];
    if (!site_shutdown_table_ok()) {
        return $def;
    }
    $st = db()->query('SELECT enabled, message, eta FROM site_shutdown WHERE id = 1 LIMIT 1');
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return $def;
    }

    return [
        'enabled' => (int) ($row['enabled'] ?? 0),
        'message' => (string) ($row['message'] ?? ''),
        'eta' => (string) ($row['eta'] ?? ''),
    ];
}

/**
 * 维护模式配置：已迁移时用数据库；否则回退 config.local.php → app.shutdown。
 *
 * @return array{enabled:bool, message:string, eta:string}
 */
function site_shutdown_effective(): array
{
    $defaultMessage = "本站正在进行系统维护与功能升级。\n期间普通用户无法访问，管理员可正常登录使用。";

    if (site_shutdown_table_ok()) {
        $row = site_shutdown_get();
        $msg = trim($row['message']);
        if ($msg === '' && !empty($row['enabled'])) {
            $msg = $defaultMessage;
        }

        return [
            'enabled' => !empty($row['enabled']),
            'message' => $msg !== '' ? $msg : '站点维护中，请稍后再试。',
            'eta' => trim($row['eta']),
        ];
    }

    $c = $GLOBALS['APP_CONFIG']['app']['shutdown'] ?? null;
    if (!is_array($c)) {
        return ['enabled' => false, 'message' => '站点维护中，请稍后再试。', 'eta' => ''];
    }

    $msg = trim((string) ($c['message'] ?? ''));
    if ($msg === '') {
        $msg = '站点维护中，请稍后再试。';
    }

    return [
        'enabled' => !empty($c['enabled']),
        'message' => $msg,
        'eta' => trim((string) ($c['eta'] ?? '')),
    ];
}

function site_shutdown_user_bypasses(?array $u): bool
{
    return $u !== null
        && function_exists('user_can_access_admin_backend')
        && user_can_access_admin_backend($u);
}

/** 维护期间未登录用户仍可访问的路径（登录页依赖的接口须放行） */
function site_shutdown_allows_guest_path(string $path, string $method, ?array $u): bool
{
    if ($path === '/login' || $path === '/geetest/register' || $path === '/auth/rsa-meta') {
        return true;
    }
    if ($path === '/logout' && $method === 'POST') {
        return true;
    }
    if ($path === '/access-challenge/verify' && $method === 'POST') {
        return true;
    }
    if ($u === null && str_starts_with($path, '/admin')) {
        return true;
    }

    return false;
}

function site_shutdown_save(int $enabled, string $message, string $eta): void
{
    if (!site_shutdown_table_ok()) {
        return;
    }
    $st = db()->prepare(
        'INSERT INTO site_shutdown (id, enabled, message, eta, updated_at) VALUES (1, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE enabled = VALUES(enabled), message = VALUES(message), eta = VALUES(eta), updated_at = NOW()'
    );
    $st->execute([$enabled ? 1 : 0, $message, $eta]);
}
