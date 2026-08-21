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

function auth_login(int $userId, bool $remember = false): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;

    $params = session_get_cookie_params();
    $path = $params['path'] !== '' ? $params['path'] : '/';
    $domain = $params['domain'] ?? '';
    $secure = (bool) ($params['secure'] ?? false);
    $samesite = $params['samesite'] ?? 'Lax';
    if (!in_array($samesite, ['Lax', 'Strict', 'None'], true)) {
        $samesite = 'Lax';
    }

    $days = (int) (($GLOBALS['APP_CONFIG']['auth']['remember_days'] ?? 30));
    if ($days < 1) {
        $days = 30;
    }
    if ($days > 365) {
        $days = 365;
    }
    $rememberTtl = $days * 86400;
    $expires = $remember ? time() + $rememberTtl : 0;

    setcookie(session_name(), session_id(), [
        'expires' => $expires,
        'path' => $path,
        'domain' => $domain !== '' ? $domain : '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => $samesite,
    ]);
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

/** 主题/回复行的真实发帖用户 id（匿名看 real_user_id，否则 user_id） */
function forum_row_real_author_id(array $row): int
{
    if ((int) ($row['is_anonymous'] ?? 0) === 1) {
        return (int) ($row['real_user_id'] ?? 0);
    }

    return (int) ($row['user_id'] ?? 0);
}
