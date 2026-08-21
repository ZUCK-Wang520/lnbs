<?php

declare(strict_types=1);

function admin_audit_log_table_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT id FROM admin_audit_log LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/**
 * 记录后台敏感操作（失败静默，不阻断业务）。
 *
 * @param array<string, mixed>|null $actor auth_user() 或含 id、nickname 的数组
 * @param array<string, mixed> $meta 可序列化为 JSON 的附加字段
 */
function admin_audit_log(?array $actor, string $action, ?string $summary = null, array $meta = []): void
{
    if (!$actor || empty($actor['id']) || !admin_audit_log_table_ok()) {
        return;
    }
    $action = trim($action);
    if ($action === '') {
        return;
    }
    if (strlen($action) > 80) {
        $action = substr($action, 0, 80);
    }
    if ($summary !== null && $summary !== '') {
        if (mb_strlen($summary) > 500) {
            $summary = mb_substr($summary, 0, 500);
        }
    } else {
        $summary = null;
    }
    $nick = trim((string) ($actor['nickname'] ?? ''));
    if (mb_strlen($nick) > 64) {
        $nick = mb_substr($nick, 0, 64);
    }
    $path = function_exists('request_path') ? (string) request_path() : '';
    if (strlen($path) > 500) {
        $path = substr($path, 0, 500);
    }
    $ip = function_exists('client_ip') ? trim((string) client_ip()) : '';
    if (strlen($ip) > 45) {
        $ip = substr($ip, 0, 45);
    }
    $metaJson = null;
    if ($meta !== []) {
        $jflags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_SUBSTITUTE')) {
            $jflags |= JSON_INVALID_SUBSTITUTE;
        }
        $enc = json_encode($meta, $jflags);
        if ($enc !== false) {
            if (strlen($enc) > 65535) {
                $enc = substr($enc, 0, 65535);
            }
            $metaJson = $enc;
        }
    }
    try {
        db()->prepare(
            'INSERT INTO admin_audit_log (actor_user_id, actor_nickname, action, summary, meta_json, request_path, ip) VALUES (?,?,?,?,?,?,?)'
        )->execute([
            (int) $actor['id'],
            $nick !== '' ? $nick : null,
            $action,
            $summary,
            $metaJson,
            $path !== '' ? $path : null,
            $ip !== '' ? $ip : null,
        ]);
    } catch (Throwable $e) {
        // ignore
    }
}

/**
 * @return array{rows: list<array<string, mixed>>, total: int, page: int, pages: int, perPage: int, actorOptions: list<array{id: int, nickname: ?string}>}
 */
function admin_audit_log_fetch_page(int $page, int $perPage, int $actorFilter, string $actionSubstr): array
{
    if ($perPage < 10) {
        $perPage = 10;
    }
    if ($perPage > 100) {
        $perPage = 100;
    }
    $where = [];
    $params = [];
    if ($actorFilter > 0) {
        $where[] = 'actor_user_id = ?';
        $params[] = $actorFilter;
    }
    if ($actionSubstr !== '') {
        $where[] = 'action LIKE ?';
        $params[] = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $actionSubstr) . '%';
    }
    $sqlWhere = $where === [] ? '' : ('WHERE ' . implode(' AND ', $where));

    $st = db()->prepare("SELECT COUNT(*) FROM admin_audit_log {$sqlWhere}");
    $st->execute($params);
    $total = (int) $st->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    if ($page > $pages) {
        $page = $pages;
    }
    if ($page < 1) {
        $page = 1;
    }
    $offset = (int) (($page - 1) * $perPage);
    $lim = (int) $perPage;
    $off = (int) $offset;

    $st = db()->prepare(
        "SELECT id, actor_user_id, actor_nickname, action, summary, meta_json, request_path, ip, created_at
         FROM admin_audit_log {$sqlWhere}
         ORDER BY id DESC
         LIMIT {$lim} OFFSET {$off}"
    );
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $actorOptions = [];
    try {
        $st2 = db()->query(
            'SELECT actor_user_id AS id, MAX(actor_nickname) AS nickname FROM admin_audit_log GROUP BY actor_user_id ORDER BY MAX(id) DESC LIMIT 100'
        );
        while ($r = $st2->fetch(PDO::FETCH_ASSOC)) {
            if ($r) {
                $actorOptions[] = [
                    'id' => (int) $r['id'],
                    'nickname' => isset($r['nickname']) ? (string) $r['nickname'] : null,
                ];
            }
        }
    } catch (Throwable $e) {
        $actorOptions = [];
    }

    return [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
        'perPage' => $perPage,
        'actorOptions' => $actorOptions,
    ];
}
