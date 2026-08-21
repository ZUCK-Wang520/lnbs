<?php

declare(strict_types=1);

/** 防火墙自动封禁写入 reason 时的前缀；全站拦截仅针对带此前缀的记录 */
function ip_firewall_reason_prefix(): string
{
    return '[防火墙]';
}

/** 写入 ip_bans.banned_until 表示「不限期」，直至管理员点「解除 IP 封禁」 */
function ip_ban_indefinite_until(): string
{
    return '2099-12-31 23:59:59';
}

function ip_ban_mysql_until_is_indefinite(string $mysqlUntil): bool
{
    return trim($mysqlUntil) === ip_ban_indefinite_until();
}

/**
 * 用户管理列表：批量查询当前页里各 last_login_ip 是否仍在登录封禁期内。
 *
 * @param list<array<string, mixed>> $users
 * @return array<string, string> ip => MAX(banned_until)，仅含当前仍生效的条目
 */
function ip_bans_active_map_for_user_rows(array $users): array
{
    if (!ip_bans_table_ok() || $users === []) {
        return [];
    }
    $ips = [];
    foreach ($users as $u) {
        if (!is_array($u)) {
            continue;
        }
        $lip = trim((string) ($u['last_login_ip'] ?? ''));
        if ($lip !== '' && filter_var($lip, FILTER_VALIDATE_IP)) {
            $ips[$lip] = true;
        }
    }
    $keys = array_keys($ips);
    if ($keys === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $st = db()->prepare(
        "SELECT ip, MAX(banned_until) AS u FROM ip_bans WHERE ip IN ({$placeholders}) GROUP BY ip"
    );
    $st->execute($keys);
    $out = [];
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        if (!$row) {
            break;
        }
        $ipKey = trim((string) ($row['ip'] ?? ''));
        $ts = strtotime((string) ($row['u'] ?? ''));
        if ($ipKey !== '' && $ts !== false && $ts > time()) {
            $out[$ipKey] = (string) $row['u'];
        }
    }

    return $out;
}

/**
 * 后台：分页列出当前仍生效的 IP 封禁（按 IP 聚合，取最新一条记录）。
 *
 * @param ''|'firewall'|'login' $typeFilter firewall=仅防火墙；login=非防火墙（含手动登录封禁）
 * @return array{rows:list<array<string,mixed>>,total:int,pages:int,page:int}
 */
function ip_bans_list_active(int $page, int $perPage, string $typeFilter = ''): array
{
    $page = max(1, $page);
    $perPage = max(5, min(100, $perPage));
    $empty = ['rows' => [], 'total' => 0, 'pages' => 1, 'page' => 1];
    if (!ip_bans_table_ok()) {
        return $empty;
    }
    if (!in_array($typeFilter, ['', 'firewall', 'login'], true)) {
        $typeFilter = '';
    }
    $prefix = ip_firewall_reason_prefix();
    $reasonSql = '';
    $reasonBind = [];
    if ($typeFilter === 'firewall') {
        $reasonSql = ' AND b.reason LIKE ?';
        $reasonBind[] = $prefix . '%';
    } elseif ($typeFilter === 'login') {
        $reasonSql = ' AND (b.reason IS NULL OR b.reason NOT LIKE ?)';
        $reasonBind[] = $prefix . '%';
    }
    $latestSub = 'SELECT ip, MAX(id) AS max_id FROM ip_bans WHERE banned_until > NOW() GROUP BY ip';
    try {
        $countSt = db()->prepare(
            "SELECT COUNT(*) FROM ip_bans b
             INNER JOIN ({$latestSub}) t ON b.id = t.max_id
             WHERE 1=1{$reasonSql}"
        );
        $countSt->execute($reasonBind);
        $total = (int) $countSt->fetchColumn();
    } catch (Throwable $e) {
        return $empty;
    }
    $pages = max(1, (int) ceil($total / $perPage));
    if ($page > $pages) {
        $page = $pages;
    }
    $offset = (int) (($page - 1) * $perPage);
    $lim = (int) $perPage;
    try {
        $st = db()->prepare(
            "SELECT b.ip, b.banned_until, b.reason, b.created_at
             FROM ip_bans b
             INNER JOIN ({$latestSub}) t ON b.id = t.max_id
             WHERE 1=1{$reasonSql}
             ORDER BY b.created_at DESC
             LIMIT {$lim} OFFSET {$offset}"
        );
        $st->execute($reasonBind);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return ['rows' => [], 'total' => $total, 'pages' => $pages, 'page' => $page];
    }
    foreach ($rows as &$row) {
        $ip = trim((string) ($row['ip'] ?? ''));
        $row['is_firewall'] = str_starts_with(trim((string) ($row['reason'] ?? '')), $prefix);
        $row['linked_users'] = [];
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
            try {
                $ust = db()->prepare(
                    'SELECT id, nickname FROM users WHERE last_login_ip = ? ORDER BY id DESC LIMIT 5'
                );
                $ust->execute([$ip]);
                $row['linked_users'] = $ust->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) {
                $row['linked_users'] = [];
            }
        }
    }
    unset($row);

    return ['rows' => $rows, 'total' => $total, 'pages' => $pages, 'page' => $page];
}

function ip_bans_table_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT ip, banned_until FROM ip_bans LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }
    return $ok;
}

function ip_is_firewall_blocked(string $ip): bool
{
    if (!ip_bans_table_ok()) {
        return false;
    }
    $ip = trim($ip);
    if ($ip === '' || $ip === '0.0.0.0') {
        return false;
    }
    $prefix = ip_firewall_reason_prefix();
    $st = db()->prepare(
        'SELECT MAX(banned_until) AS u FROM ip_bans WHERE ip = ? AND reason LIKE ?'
    );
    $st->execute([$ip, $prefix . '%']);
    $row = $st->fetch();
    if (!$row || $row['u'] === null || (string) $row['u'] === '') {
        return false;
    }
    $until = strtotime((string) $row['u']);

    return $until !== false && $until > time();
}

/**
 * 防火墙命中后写入 ip_bans（全站封禁，reason 带 [防火墙] 前缀）。
 */
function ip_firewall_auto_ban(string $ip, string $detail, ?int $banHours = null): void
{
    if (!ip_bans_table_ok()) {
        return;
    }
    $ip = trim($ip);
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return;
    }
    if (ip_is_firewall_blocked($ip)) {
        return;
    }
    $detail = trim($detail);
    if ($detail === '') {
        $detail = '恶意访问';
    }
    if (mb_strlen($detail) > 180) {
        $detail = mb_substr($detail, 0, 180);
    }
    $reason = ip_firewall_reason_prefix() . ' ' . $detail;
    if ($banHours === null || $banHours <= 0) {
        $until = ip_ban_indefinite_until();
    } else {
        $until = date('Y-m-d H:i:s', time() + min($banHours, 8760) * 3600);
    }
    try {
        db()->prepare('INSERT INTO ip_bans (ip, banned_until, reason, created_by) VALUES (?,?,?,NULL)')
            ->execute([$ip, $until, $reason]);
    } catch (Throwable $e) {
        error_log('ip_firewall_auto_ban: ' . $e->getMessage());
    }
}

function ip_is_banned_for_login(string $ip): bool
{
    if (!ip_bans_table_ok()) {
        return false;
    }
    $ip = trim($ip);
    if ($ip === '' || $ip === '0.0.0.0') {
        return false;
    }
    $st = db()->prepare('SELECT MAX(banned_until) AS u FROM ip_bans WHERE ip = ?');
    $st->execute([$ip]);
    $row = $st->fetch();
    if (!$row || $row['u'] === null || (string) $row['u'] === '') {
        return false;
    }
    $until = strtotime((string) $row['u']);
    return $until !== false && $until > time();
}

function enforce_login_bans_for_current_session(): void
{
    if (empty($_SESSION['user_id'])) {
        return;
    }
    $path = function_exists('request_path') ? request_path() : '';
    $method = function_exists('request_method') ? request_method() : 'GET';
    if ($path === '/login' || $path === '/auth/rsa-meta' || $path === '/geetest/register') {
        return;
    }
    if ($path === '/logout' && $method === 'POST') {
        return;
    }

    $ip = function_exists('client_ip') ? client_ip() : '';
    $ipBanned = $ip !== '' && ip_is_banned_for_login($ip);

    $userBanned = false;
    try {
        $st = db()->prepare('SELECT login_banned_until FROM users WHERE id = ? LIMIT 1');
        $st->execute([(int) $_SESSION['user_id']]);
        $row = $st->fetch();
        if ($row && !empty($row['login_banned_until'])) {
            $until = strtotime((string) $row['login_banned_until']);
            if ($until !== false && $until <= time()) {
                try {
                    db()->prepare('UPDATE users SET login_banned_until = NULL WHERE id = ?')->execute([(int) $_SESSION['user_id']]);
                } catch (Throwable $e) {
                    // ignore
                }
                $userBanned = false;
            } else {
                $userBanned = $until !== false && $until > time();
            }
        }
    } catch (Throwable $e) {
        $userBanned = false;
    }

    if (!$ipBanned && !$userBanned) {
        return;
    }

    if (function_exists('auth_logout')) {
        auth_logout();
    } else {
        $_SESSION = [];
    }
    if (function_exists('flash_set')) {
        flash_set('error', '登录已失效：该账号或当前网络环境已被封禁。');
    }
    if (function_exists('redirect')) {
        redirect('/login');
    }
    header('Location: /login', true, 302);
    exit;
}

