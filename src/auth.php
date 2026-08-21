<?php

declare(strict_types=1);

function auth_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $st = db()->prepare(
        'SELECT id, email, nickname, role, banned FROM users WHERE id = ? LIMIT 1'
    );
    $st->execute([(int) $_SESSION['user_id']]);
    $u = $st->fetch();
    return $u ?: null;
}

function auth_login(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }
}

function require_login(): array
{
    $u = auth_user();
    if (!$u) {
        flash_set('error', '请先登录。');
        redirect('/login');
    }
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if (($u['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('无权访问。');
    }
    return $u;
}
