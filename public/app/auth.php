<?php

declare(strict_types=1);

function auth_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    try {
        $delSel = function_exists('user_deletion_columns_ok') && user_deletion_columns_ok() ? ', deleted_at' : '';
        $permSel = function_exists('user_moderator_l2_perms_column_ok') && user_moderator_l2_perms_column_ok() ? ', moderator_l2_perms' : '';
        $st = db()->prepare(
            "SELECT id, email, nickname, role, banned, moderator_l2{$permSel}{$delSel} FROM users WHERE id = ? LIMIT 1"
        );
        $st->execute([(int) $_SESSION['user_id']]);
        $u = $st->fetch();
    } catch (Throwable $e) {
        $st = db()->prepare(
            'SELECT id, email, nickname, role, banned FROM users WHERE id = ? LIMIT 1'
        );
        $st->execute([(int) $_SESSION['user_id']]);
        $u = $st->fetch();
        if ($u) {
            $u['moderator_l2'] = 0;
            $u['moderator_l2_perms'] = null;
        }
    }

    if ($u && function_exists('user_deletion_columns_ok') && user_deletion_columns_ok() && !empty($u['deleted_at'])) {
        // 账号已注销：强制清会话
        auth_logout();
        return null;
    }

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

    user_level_refresh_session_cache($userId);
}

/**
 * 将已过期的登录封禁时间清空（login_banned_until），避免后台长期显示旧到期、与「实际已可登录」不一致。
 */
function user_login_ban_purge_expired(): void
{
    try {
        db()->exec('UPDATE users SET login_banned_until = NULL WHERE login_banned_until IS NOT NULL AND login_banned_until <= NOW()');
    } catch (Throwable $e) {
        // 未执行 migration_login_ip_and_bans 等时忽略
    }
}

function auth_logout(): void
{
    unset($_SESSION['user_level_display']);
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

/** @return list<string> */
function admin_l2_permission_keys(): array
{
    return ['dashboard', 'moderation', 'users', 'boards', 'chat', 'sports_meet', 'content', 'anon_identity'];
}

/** @return array<string, string> 权限 key => 中文说明 */
function admin_l2_permission_labels(): array
{
    return [
        'dashboard' => '管理首页',
        'moderation' => '人工复核',
        'users' => '用户管理',
        'boards' => '版块管理',
        'chat' => '私信审计',
        'sports_meet' => '运动会管理',
        'content' => '删除主题/回复',
        'anon_identity' => '查看匿名真实昵称',
    ];
}

function user_is_super_admin(?array $u): bool
{
    return $u && (($u['role'] ?? '') === 'admin');
}

function user_is_secondary_admin(?array $u): bool
{
    return (bool) ($u && ($u['role'] ?? '') === 'user' && !empty((int) ($u['moderator_l2'] ?? 0)));
}

/**
 * 二级管理员有效权限（站长不在此列，请用 user_has_admin_permission）。
 * 未迁移 moderator_l2_perms 的旧库：仅开放人工复核，与历史行为一致。
 *
 * @return array<string, bool>
 */
function user_effective_l2_permissions(?array $u): array
{
    $keys = admin_l2_permission_keys();
    $out = array_fill_keys($keys, false);
    if (!user_is_secondary_admin($u)) {
        return $out;
    }
    if (!function_exists('user_moderator_l2_perms_column_ok') || !user_moderator_l2_perms_column_ok()) {
        $out['moderation'] = true;
        $out['anon_identity'] = true;

        return $out;
    }
    $raw = trim((string) ($u['moderator_l2_perms'] ?? ''));
    if ($raw === '') {
        $out['moderation'] = true;
        $out['anon_identity'] = true;

        return $out;
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $out['moderation'] = true;
        $out['anon_identity'] = true;

        return $out;
    }
    foreach ($keys as $k) {
        $out[$k] = !empty($decoded[$k]);
    }
    // 旧版 JSON 无 anon_identity 时：与此前「用户/复核/删帖」任一即可见真实昵称一致
    if (!array_key_exists('anon_identity', $decoded)) {
        $out['anon_identity'] = $out['users'] || $out['moderation'] || $out['content'];
    }

    return $out;
}

function user_has_admin_permission(?array $u, string $perm): bool
{
    if (!$u) {
        return false;
    }
    if (user_is_super_admin($u)) {
        return true;
    }
    if (!in_array($perm, admin_l2_permission_keys(), true)) {
        return false;
    }
    if (!user_is_secondary_admin($u)) {
        return false;
    }

    return !empty(user_effective_l2_permissions($u)[$perm]);
}

function user_can_access_admin_backend(?array $u): bool
{
    if (!$u) {
        return false;
    }
    if (user_is_super_admin($u)) {
        return true;
    }
    if (!user_is_secondary_admin($u)) {
        return false;
    }
    foreach (user_effective_l2_permissions($u) as $key => $on) {
        if ($key === 'anon_identity') {
            continue;
        }
        if ($on) {
            return true;
        }
    }

    return false;
}

/** 前台匿名贴中展示真实身份（主题/列表等）；与后台模块独立，可单独关闭 */
function user_can_view_anonymous_real_identity(?array $u): bool
{
    if (!$u) {
        return false;
    }
    if (user_is_super_admin($u)) {
        return true;
    }

    return user_has_admin_permission($u, 'anon_identity');
}

function require_admin_permission(string $perm): array
{
    $u = require_login();
    if (!user_has_admin_permission($u, $perm)) {
        http_response_code(403);
        exit('无权访问。');
    }

    return $u;
}

/** 站长或具备「人工复核」权限的二级管理员 */
function user_is_content_reviewer(?array $u): bool
{
    return user_has_admin_permission($u, 'moderation');
}

function require_content_reviewer(): array
{
    $u = require_login();
    if (!user_is_content_reviewer($u)) {
        http_response_code(403);
        exit('无权访问。');
    }

    return $u;
}

function auth_login_rsa_cfg(): array
{
    $auth = $GLOBALS['APP_CONFIG']['auth'] ?? [];
    $cfg = $auth['login_rsa'] ?? [];
    if (!is_array($cfg)) {
        return [];
    }
    return $cfg;
}

function auth_login_rsa_enabled(): bool
{
    $cfg = auth_login_rsa_cfg();
    return !empty($cfg['enabled'])
        && extension_loaded('openssl')
        && !empty((string) ($cfg['private_key_pem'] ?? ''));
}

function auth_login_rsa_required(): bool
{
    $cfg = auth_login_rsa_cfg();
    return auth_login_rsa_enabled() && !empty($cfg['require']);
}

function auth_login_rsa_public_key_pem(): ?string
{
    if (!auth_login_rsa_enabled()) {
        return null;
    }
    $cfg = auth_login_rsa_cfg();
    $pub = trim((string) ($cfg['public_key_pem'] ?? ''));
    if ($pub !== '') {
        return $pub;
    }
    $privPem = (string) ($cfg['private_key_pem'] ?? '');
    $pkey = openssl_pkey_get_private($privPem);
    if ($pkey === false) {
        return null;
    }
    $details = openssl_pkey_get_details($pkey);
    if (!is_array($details) || empty($details['key']) || !is_string($details['key'])) {
        return null;
    }
    return $details['key'];
}

function auth_login_rsa_issue_nonce(): ?string
{
    if (!auth_login_rsa_enabled()) {
        return null;
    }
    $nonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    $now = time();
    $list = $_SESSION['_login_rsa_nonces'] ?? [];
    if (!is_array($list)) {
        $list = [];
    }
    // prune expired and cap size
    $kept = [];
    foreach ($list as $it) {
        if (!is_array($it)) {
            continue;
        }
        $exp = (int) ($it['expires_at'] ?? 0);
        $n = (string) ($it['nonce'] ?? '');
        if ($n === '' || $exp < $now) {
            continue;
        }
        $kept[] = ['nonce' => $n, 'expires_at' => $exp];
    }
    $kept[] = ['nonce' => $nonce, 'expires_at' => $now + 120];
    if (count($kept) > 8) {
        $kept = array_slice($kept, -8);
    }
    $_SESSION['_login_rsa_nonces'] = $kept;
    return $nonce;
}

function auth_login_rsa_consume_nonce(string $nonce): bool
{
    $now = time();
    $list = $_SESSION['_login_rsa_nonces'] ?? [];
    if (!is_array($list) || $nonce === '') {
        return false;
    }
    $new = [];
    $ok = false;
    foreach ($list as $it) {
        if (!is_array($it)) {
            continue;
        }
        $exp = (int) ($it['expires_at'] ?? 0);
        $n = (string) ($it['nonce'] ?? '');
        if ($n === '' || $exp < $now) {
            continue;
        }
        if (!$ok && hash_equals($n, $nonce)) {
            $ok = true;
            continue; // consume it (do not keep)
        }
        $new[] = ['nonce' => $n, 'expires_at' => $exp];
    }
    $_SESSION['_login_rsa_nonces'] = $new;
    return $ok;
}

/**
 * @return array{password:string}|null
 */
function auth_login_rsa_decrypt_password(string $b64Ciphertext): ?array
{
    if (!auth_login_rsa_enabled()) {
        return null;
    }
    $cfg = auth_login_rsa_cfg();
    $privPem = (string) ($cfg['private_key_pem'] ?? '');
    $priv = openssl_pkey_get_private($privPem);
    if ($priv === false) {
        return null;
    }
    $cipher = base64_decode($b64Ciphertext, true);
    if ($cipher === false || $cipher === '') {
        return null;
    }
    $out = '';
    // PHP OpenSSL OAEP is effectively SHA-1 in most builds; keep frontend in sync.
    $ok = openssl_private_decrypt($cipher, $out, $priv, OPENSSL_PKCS1_OAEP_PADDING);
    if (!$ok || $out === '') {
        return null;
    }
    $data = json_decode($out, true);
    if (!is_array($data)) {
        return null;
    }
    $password = (string) ($data['password'] ?? '');
    $nonce = (string) ($data['nonce'] ?? '');
    $ts = (int) ($data['ts'] ?? 0);
    if ($password === '' || $nonce === '' || $ts <= 0) {
        return null;
    }
    if (abs(time() - $ts) > 180) {
        return null;
    }
    if (!auth_login_rsa_consume_nonce($nonce)) {
        return null;
    }
    return ['password' => $password];
}

/** 主题/回复行的真实发帖用户 id（匿名看 real_user_id，否则 user_id） */
function forum_row_real_author_id(array $row): int
{
    if ((int) ($row['is_anonymous'] ?? 0) === 1) {
        return (int) ($row['real_user_id'] ?? 0);
    }

    return (int) ($row['user_id'] ?? 0);
}
