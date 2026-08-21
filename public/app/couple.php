<?php

declare(strict_types=1);

require_once __DIR__ . '/anonymous.php';

function couple_tables_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT 1 FROM couples LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/** @return array{0:int,1:int} lower,higher */
function couple_ordered_pair(int $a, int $b): array
{
    return $a < $b ? [$a, $b] : [$b, $a];
}

/** @return array{id:int,user_lower_id:int,user_higher_id:int,love_note:string,bound_at:string}|null */
function couple_row_for_user(int $userId): ?array
{
    if (!couple_tables_ok() || $userId <= 0) {
        return null;
    }
    try {
        $st = db()->prepare(
            'SELECT id, user_lower_id, user_higher_id, love_note, bound_at FROM couples
             WHERE user_lower_id = ? OR user_higher_id = ? LIMIT 1'
        );
        $st->execute([$userId, $userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function couple_partner_id_for_user(int $userId): ?int
{
    $row = couple_row_for_user($userId);
    if (!$row) {
        return null;
    }
    $lo = (int) $row['user_lower_id'];
    $hi = (int) $row['user_higher_id'];

    return $userId === $lo ? $hi : $lo;
}

/** @return array<string,mixed>|null user row */
function couple_partner_public_row(int $userId): ?array
{
    $pid = couple_partner_id_for_user($userId);
    if ($pid === null || $pid <= 0) {
        return null;
    }
    try {
        $st = db()->prepare('SELECT id, nickname, avatar FROM users WHERE id = ? LIMIT 1');
        $st->execute([$pid]);

        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function couple_cancel_pending_for_users(PDO $db, int $a, int $b): void
{
    $st = $db->prepare(
        "UPDATE couple_invitations SET status = 'cancelled', responded_at = CURRENT_TIMESTAMP
         WHERE status = 'pending' AND (from_user_id IN (?,?) OR to_user_id IN (?,?))"
    );
    $st->execute([$a, $b, $a, $b]);
}

/** @return list<array<string,mixed>> */
function couple_incoming_pending_invites(int $userId): array
{
    if (!couple_tables_ok() || $userId <= 0) {
        return [];
    }
    try {
        $st = db()->prepare(
            "SELECT i.id, i.from_user_id, i.message, i.created_at, u.nickname AS from_nickname, u.avatar AS from_avatar
             FROM couple_invitations i
             JOIN users u ON u.id = i.from_user_id
             WHERE i.to_user_id = ? AND i.status = 'pending'
             ORDER BY i.id DESC"
        );
        $st->execute([$userId]);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

function couple_incoming_pending_count(int $userId): int
{
    if (!couple_tables_ok() || $userId <= 0) {
        return 0;
    }
    try {
        $st = db()->prepare(
            "SELECT COUNT(*) FROM couple_invitations WHERE to_user_id = ? AND status = 'pending'"
        );
        $st->execute([$userId]);

        return (int) $st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/** @return list<array<string,mixed>> */
function couple_outgoing_pending_invites(int $userId): array
{
    if (!couple_tables_ok() || $userId <= 0) {
        return [];
    }
    try {
        $st = db()->prepare(
            "SELECT i.id, i.to_user_id, i.message, i.created_at, u.nickname AS to_nickname, u.avatar AS to_avatar
             FROM couple_invitations i
             JOIN users u ON u.id = i.to_user_id
             WHERE i.from_user_id = ? AND i.status = 'pending'
             ORDER BY i.id DESC"
        );
        $st->execute([$userId]);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

/** @return list<array<string,mixed>> */
function couple_wall_posts(int $coupleId, int $limit = 80): array
{
    if (!couple_tables_ok() || $coupleId <= 0) {
        return [];
    }
    $limit = max(1, min(200, $limit));
    try {
        $st = db()->prepare(
            "SELECT w.id, w.body, w.created_at, w.author_user_id, u.nickname AS author_nickname
             FROM couple_wall_posts w
             JOIN users u ON u.id = w.author_user_id
             WHERE w.couple_id = ?
             ORDER BY w.id DESC
             LIMIT {$limit}"
        );
        $st->execute([$coupleId]);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * @return array{
 *   tables_ok:bool,
 *   we_are_couple:bool,
 *   out_pending:bool,
 *   in_pending:bool,
 *   can_invite:bool,
 *   i_coupled_elsewhere:bool,
 *   they_coupled_elsewhere:bool
 * }
 */
function couple_peer_state(int $me, int $them): array
{
    $def = [
        'tables_ok' => false,
        'we_are_couple' => false,
        'out_pending' => false,
        'in_pending' => false,
        'can_invite' => false,
        'i_coupled_elsewhere' => false,
        'they_coupled_elsewhere' => false,
    ];
    if (!couple_tables_ok() || $me <= 0 || $them <= 0 || $me === $them) {
        return $def;
    }
    try {
        if (chat_invalid_peer_user_id($them)) {
            return $def;
        }
    } catch (Throwable $e) {
        return $def;
    }

    $def['tables_ok'] = true;
    $myP = couple_partner_id_for_user($me);
    $theirP = couple_partner_id_for_user($them);
    if ($myP !== null && $myP === $them) {
        $def['we_are_couple'] = true;

        return $def;
    }
    if ($myP !== null) {
        $def['i_coupled_elsewhere'] = true;
    }
    if ($theirP !== null) {
        $def['they_coupled_elsewhere'] = true;
    }

    try {
        $st = db()->prepare(
            "SELECT 1 FROM couple_invitations WHERE from_user_id = ? AND to_user_id = ? AND status = 'pending' LIMIT 1"
        );
        $st->execute([$me, $them]);
        $def['out_pending'] = (bool) $st->fetchColumn();
        $st = db()->prepare(
            "SELECT 1 FROM couple_invitations WHERE from_user_id = ? AND to_user_id = ? AND status = 'pending' LIMIT 1"
        );
        $st->execute([$them, $me]);
        $def['in_pending'] = (bool) $st->fetchColumn();
    } catch (Throwable $e) {
        return $def;
    }

    $def['can_invite'] = !$def['i_coupled_elsewhere']
        && !$def['they_coupled_elsewhere']
        && !$def['out_pending']
        && !$def['in_pending']
        && !$def['we_are_couple'];

    return $def;
}

/** @return string|null error message */
function couple_send_invite(int $fromId, int $toId, string $message): ?string
{
    if (!couple_tables_ok()) {
        return '情侣功能未启用：请执行数据库脚本 migration_couple.sql。';
    }
    if ($fromId <= 0 || $toId <= 0 || $fromId === $toId) {
        return '无法发送邀请。';
    }
    try {
        if (chat_invalid_peer_user_id($toId)) {
            return '无法向该用户发送邀请。';
        }
    } catch (Throwable $e) {
        return '无法向该用户发送邀请。';
    }

    $msg = trim($message);
    if (mb_strlen($msg, 'UTF-8') > 300) {
        return '附言请控制在 300 字以内。';
    }

    if (couple_partner_id_for_user($fromId) !== null) {
        return '你已与他人绑定情侣，请先解除绑定。';
    }
    if (couple_partner_id_for_user($toId) !== null) {
        return '对方已与他人绑定情侣。';
    }

    try {
        $db = db();
        $db->beginTransaction();
        $st = $db->prepare(
            'SELECT id, status FROM couple_invitations WHERE from_user_id = ? AND to_user_id = ? LIMIT 1 FOR UPDATE'
        );
        $st->execute([$fromId, $toId]);
        $ex = $st->fetch(PDO::FETCH_ASSOC);
        if ($ex && (string) $ex['status'] === 'pending') {
            $db->rollBack();

            return '你已向对方发送过邀请，请等待回应。';
        }
        if ($ex) {
            $st = $db->prepare(
                "UPDATE couple_invitations SET status = 'pending', message = ?, created_at = CURRENT_TIMESTAMP, responded_at = NULL
                 WHERE id = ? AND status IN ('declined','cancelled')"
            );
            $st->execute([$msg, (int) $ex['id']]);
            if ($st->rowCount() === 0) {
                $db->rollBack();

                return '无法重复发送邀请。';
            }
        } else {
            $st = $db->prepare(
                'INSERT INTO couple_invitations (from_user_id, to_user_id, message, status) VALUES (?,?,?,?)'
            );
            $st->execute([$fromId, $toId, $msg, 'pending']);
        }
        $db->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        return '发送失败，请稍后再试。';
    }

    return null;
}

/** @return string|null error */
function couple_respond_invite(int $inviteId, int $responderId, bool $accept): ?string
{
    if (!couple_tables_ok()) {
        return '情侣功能未启用：请执行数据库脚本 migration_couple.sql。';
    }
    if ($inviteId <= 0 || $responderId <= 0) {
        return '参数无效。';
    }

    try {
        $db = db();
        $db->beginTransaction();
        $st = $db->prepare(
            'SELECT id, from_user_id, to_user_id, status FROM couple_invitations WHERE id = ? LIMIT 1 FOR UPDATE'
        );
        $st->execute([$inviteId]);
        $inv = $st->fetch(PDO::FETCH_ASSOC);
        if (!$inv || (string) $inv['status'] !== 'pending') {
            $db->rollBack();

            return '该邀请已失效。';
        }
        if ((int) $inv['to_user_id'] !== $responderId) {
            $db->rollBack();

            return '无权处理该邀请。';
        }
        $fromId = (int) $inv['from_user_id'];
        $toId = (int) $inv['to_user_id'];

        if ($accept) {
            if (couple_partner_id_for_user($fromId) !== null || couple_partner_id_for_user($toId) !== null) {
                $db->rollBack();

                return '绑定失败：一方已与其他用户绑定。';
            }
            [$lo, $hi] = couple_ordered_pair($fromId, $toId);
            $st = $db->prepare(
                'INSERT INTO couples (user_lower_id, user_higher_id, love_note) VALUES (?,?,?)'
            );
            $st->execute([$lo, $hi, '']);
            $st = $db->prepare(
                "UPDATE couple_invitations SET status = 'accepted', responded_at = CURRENT_TIMESTAMP WHERE id = ?"
            );
            $st->execute([$inviteId]);
            couple_cancel_pending_for_users($db, $fromId, $toId);
        } else {
            $st = $db->prepare(
                "UPDATE couple_invitations SET status = 'declined', responded_at = CURRENT_TIMESTAMP WHERE id = ?"
            );
            $st->execute([$inviteId]);
        }
        $db->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        return '操作失败，请稍后再试。';
    }

    return null;
}

/** @return string|null error */
function couple_cancel_my_invite(int $fromId, int $inviteId): ?string
{
    if (!couple_tables_ok()) {
        return '情侣功能未启用。';
    }
    try {
        $st = db()->prepare(
            "UPDATE couple_invitations SET status = 'cancelled', responded_at = CURRENT_TIMESTAMP
             WHERE id = ? AND from_user_id = ? AND status = 'pending'"
        );
        $st->execute([$inviteId, $fromId]);
        if ($st->rowCount() === 0) {
            return '无法撤销该邀请。';
        }
    } catch (Throwable $e) {
        return '操作失败。';
    }

    return null;
}

/** @return string|null error */
function couple_unbind(int $userId): ?string
{
    if (!couple_tables_ok()) {
        return '情侣功能未启用。';
    }
    $row = couple_row_for_user($userId);
    if (!$row) {
        return '当前未绑定情侣。';
    }
    try {
        $st = db()->prepare('DELETE FROM couples WHERE id = ?');
        $st->execute([(int) $row['id']]);
    } catch (Throwable $e) {
        return '解除失败。';
    }

    return null;
}

/** @return string|null error */
function couple_update_love_note(int $userId, string $note): ?string
{
    if (!couple_tables_ok()) {
        return '情侣功能未启用。';
    }
    $row = couple_row_for_user($userId);
    if (!$row) {
        return '当前未绑定情侣。';
    }
    $note = trim($note);
    if (mb_strlen($note, 'UTF-8') > 500) {
        return '情话请控制在 500 字以内。';
    }
    try {
        $st = db()->prepare('UPDATE couples SET love_note = ? WHERE id = ?');
        $st->execute([$note, (int) $row['id']]);
    } catch (Throwable $e) {
        return '保存失败。';
    }

    return null;
}

/** @return string|null error */
function couple_wall_add(int $userId, string $body): ?string
{
    if (!couple_tables_ok()) {
        return '情侣功能未启用。';
    }
    $row = couple_row_for_user($userId);
    if (!$row) {
        return '当前未绑定情侣。';
    }
    $body = trim($body);
    if ($body === '') {
        return '留言不能为空。';
    }
    if (mb_strlen($body, 'UTF-8') > 500) {
        return '留言请控制在 500 字以内。';
    }
    try {
        $st = db()->prepare(
            'INSERT INTO couple_wall_posts (couple_id, author_user_id, body) VALUES (?,?,?)'
        );
        $st->execute([(int) $row['id'], $userId, $body]);
    } catch (Throwable $e) {
        return '发表失败。';
    }

    return null;
}

/** @return int days inclusive of bound day */
function couple_days_together(?string $boundAtSql): int
{
    if ($boundAtSql === null || $boundAtSql === '') {
        return 0;
    }
    try {
        $t = strtotime((string) $boundAtSql);
        if ($t === false) {
            return 0;
        }
        $d0 = new DateTime('@' . $t);
        $d0->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $today = new DateTime('today', new DateTimeZone(date_default_timezone_get()));

        return (int) $d0->diff($today)->days + 1;
    } catch (Throwable $e) {
        return 0;
    }
}

function couple_extras_tables_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT 1 FROM couple_gallery_items LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/** @return list<array<string,mixed>> */
function couple_gallery_list(int $coupleId, int $limit = 120): array
{
    if (!couple_extras_tables_ok() || $coupleId <= 0) {
        return [];
    }
    $limit = max(1, min(200, $limit));
    try {
        $st = db()->prepare(
            "SELECT id, image_url, caption, created_at FROM couple_gallery_items
             WHERE couple_id = ? ORDER BY id DESC LIMIT {$limit}"
        );
        $st->execute([$coupleId]);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * 从 POST 解析恋爱相册图片 URL。
 * 与发帖/回复一致优先读 cos_image_urls（JSON），再读旧字段 couple_cos_image_urls，最后读 image_url；
 * 兼容 r= 路由、值为数组、json_decode 失败时用正则从原串抽 https（避免 (string) 数组得到 "Array"）。
 */
function couple_album_resolve_image_url_from_request(): string
{
    $asUrl = static function (string $s): string {
        $s = trim($s);
        if ($s === '' || strcasecmp($s, 'Array') === 0) {
            return '';
        }

        return preg_match('#^https?://#i', $s) ? $s : '';
    };

    $fromCosJson = static function ($raw) use ($asUrl): string {
        if (is_array($raw)) {
            foreach ($raw as $one) {
                if (is_string($one)) {
                    $u = $asUrl($one);
                    if ($u !== '') {
                        return $u;
                    }
                }
            }

            return '';
        }
        if (!is_string($raw) || $raw === '') {
            return '';
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $one) {
                if (is_string($one)) {
                    $u = $asUrl($one);
                    if ($u !== '') {
                        return $u;
                    }
                }
            }
        }
        if (preg_match('#https?://[^\s"\'<>]+#i', $raw, $m)) {
            return rtrim($m[0], '."\',\\');
        }

        return '';
    };

    foreach (['cos_image_urls', 'couple_cos_image_urls'] as $key) {
        $got = $fromCosJson($_POST[$key] ?? null);
        if ($got !== '') {
            return $got;
        }
    }

    $img = $_POST['image_url'] ?? null;
    if (is_array($img)) {
        foreach ($img as $v) {
            if (is_string($v)) {
                $u = $asUrl($v);
                if ($u !== '') {
                    return $u;
                }
            }
        }
    } elseif (is_string($img)) {
        $u = $asUrl($img);
        if ($u !== '') {
            return $u;
        }
    }

    return '';
}

/** @return string|null error */
function couple_gallery_add(int $userId, string $url, string $caption): ?string
{
    if (!couple_extras_tables_ok()) {
        return '相册功能未启用：请执行 migration_couple_extras.sql。';
    }
    $row = couple_row_for_user($userId);
    if (!$row) {
        return '当前未绑定情侣。';
    }
    $url = trim($url);
    if ($url === '') {
        return '请先通过 COS 上传图片，或填写有效的图片 https 地址。';
    }
    if (strlen($url) > 2048) {
        return '图片地址过长（最多 2048 字符），请联系管理员检查 COS 域名配置。';
    }
    if (!preg_match('#^https?://#i', $url)) {
        return '图片地址需以 http:// 或 https:// 开头。';
    }
    $allowedPrefixes = forum_cos_allowed_url_prefixes();
    if ($allowedPrefixes !== [] && !forum_cos_is_trusted_public_url($url, $allowedPrefixes)) {
        return '图片须来自本站已配置的 COS/CDN 地址，请先使用下方上传到腾讯云 COS。';
    }
    $cap = trim($caption);
    if (mb_strlen($cap, 'UTF-8') > 200) {
        return '说明请控制在 200 字以内。';
    }
    try {
        $st = db()->prepare(
            'INSERT INTO couple_gallery_items (couple_id, image_url, caption) VALUES (?,?,?)'
        );
        $st->execute([(int) $row['id'], $url, $cap]);
    } catch (Throwable $e) {
        return '添加失败。';
    }

    return null;
}

/** @return string|null error */
function couple_gallery_delete(int $userId, int $itemId): ?string
{
    if (!couple_extras_tables_ok()) {
        return '相册功能未启用。';
    }
    $row = couple_row_for_user($userId);
    if (!$row) {
        return '当前未绑定情侣。';
    }
    try {
        $st = db()->prepare(
            'DELETE FROM couple_gallery_items WHERE id = ? AND couple_id = ?'
        );
        $st->execute([$itemId, (int) $row['id']]);
        if ($st->rowCount() === 0) {
            return '无法删除该项。';
        }
    } catch (Throwable $e) {
        return '删除失败。';
    }

    return null;
}

/** @return list<array<string,mixed>> */
function couple_promise_list(int $coupleId): array
{
    if (!couple_extras_tables_ok() || $coupleId <= 0) {
        return [];
    }
    try {
        $st = db()->prepare(
            'SELECT id, body, is_done, sort_order, created_at FROM couple_promise_items
             WHERE couple_id = ? ORDER BY is_done ASC, sort_order ASC, id ASC'
        );
        $st->execute([$coupleId]);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

/** @return string|null error */
function couple_promise_add(int $userId, string $body): ?string
{
    if (!couple_extras_tables_ok()) {
        return '约定列表未启用：请执行 migration_couple_extras.sql。';
    }
    $row = couple_row_for_user($userId);
    if (!$row) {
        return '当前未绑定情侣。';
    }
    $body = trim($body);
    if ($body === '') {
        return '内容不能为空。';
    }
    if (mb_strlen($body, 'UTF-8') > 300) {
        return '单条约定请控制在 300 字以内。';
    }
    try {
        $st = db()->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 AS n FROM couple_promise_items WHERE couple_id = ?'
        );
        $st->execute([(int) $row['id']]);
        $n = (int) $st->fetchColumn();
        $ins = db()->prepare(
            'INSERT INTO couple_promise_items (couple_id, body, sort_order) VALUES (?,?,?)'
        );
        $ins->execute([(int) $row['id'], $body, $n]);
    } catch (Throwable $e) {
        return '添加失败。';
    }

    return null;
}

/** @return string|null error */
function couple_promise_toggle(int $userId, int $itemId): ?string
{
    if (!couple_extras_tables_ok()) {
        return '约定列表未启用。';
    }
    $row = couple_row_for_user($userId);
    if (!$row) {
        return '当前未绑定情侣。';
    }
    try {
        $st = db()->prepare(
            'UPDATE couple_promise_items SET is_done = 1 - is_done WHERE id = ? AND couple_id = ?'
        );
        $st->execute([$itemId, (int) $row['id']]);
        if ($st->rowCount() === 0) {
            return '无法更新。';
        }
    } catch (Throwable $e) {
        return '操作失败。';
    }

    return null;
}

/** @return string|null error */
function couple_promise_delete(int $userId, int $itemId): ?string
{
    if (!couple_extras_tables_ok()) {
        return '约定列表未启用。';
    }
    $row = couple_row_for_user($userId);
    if (!$row) {
        return '当前未绑定情侣。';
    }
    try {
        $st = db()->prepare(
            'DELETE FROM couple_promise_items WHERE id = ? AND couple_id = ?'
        );
        $st->execute([$itemId, (int) $row['id']]);
        if ($st->rowCount() === 0) {
            return '无法删除。';
        }
    } catch (Throwable $e) {
        return '删除失败。';
    }

    return null;
}

/** Unix ms for JS countdown from bound_at */
function couple_bound_at_epoch_ms(?string $boundAtSql): int
{
    if ($boundAtSql === null || $boundAtSql === '') {
        return 0;
    }
    $t = strtotime((string) $boundAtSql);
    if ($t === false) {
        return 0;
    }

    return $t * 1000;
}
