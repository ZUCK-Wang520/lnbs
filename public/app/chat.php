<?php

declare(strict_types=1);

require_once __DIR__ . '/anonymous.php';

function chat_tables_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT 1 FROM chat_messages LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

function chat_invalid_peer_user_id(int $uid): bool
{
    if ($uid <= 0) {
        return true;
    }
    try {
        return $uid === anonymous_user_id();
    } catch (Throwable $e) {
        return false;
    }
}

function chat_are_friends(int $a, int $b): bool
{
    if ($a <= 0 || $b <= 0 || $a === $b) {
        return false;
    }
    try {
        $st = db()->prepare(
            'SELECT 1 FROM chat_friendships WHERE user_id = ? AND friend_user_id = ? LIMIT 1'
        );
        $st->execute([$a, $b]);

        return (bool) $st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

/** @return array{is_friend:bool,out_pending:bool,in_pending:bool,can_request:bool} */
function chat_peer_state(int $me, int $them): array
{
    $def = ['is_friend' => false, 'out_pending' => false, 'in_pending' => false, 'can_request' => false];
    if ($me <= 0 || $them <= 0 || $me === $them || chat_invalid_peer_user_id($them)) {
        return $def;
    }
    if (!chat_tables_ok()) {
        return $def;
    }
    try {
        if (chat_are_friends($me, $them)) {
            return ['is_friend' => true, 'out_pending' => false, 'in_pending' => false, 'can_request' => false];
        }
        $st = db()->prepare(
            'SELECT status FROM chat_friend_requests WHERE from_user_id = ? AND to_user_id = ? LIMIT 1'
        );
        $st->execute([$me, $them]);
        $out = $st->fetch();
        $st = db()->prepare(
            'SELECT status FROM chat_friend_requests WHERE from_user_id = ? AND to_user_id = ? LIMIT 1'
        );
        $st->execute([$them, $me]);
        $inn = $st->fetch();
        $op = $out && (string) $out['status'] === 'pending';
        $ip = $inn && (string) $inn['status'] === 'pending';

        return [
            'is_friend' => false,
            'out_pending' => $op,
            'in_pending' => $ip,
            'can_request' => !$op && !$ip && (!$out || (string) $out['status'] === 'declined'),
        ];
    } catch (Throwable $e) {
        return $def;
    }
}

function chat_count_incoming_pending(int $userId): int
{
    if (!chat_tables_ok() || $userId <= 0) {
        return 0;
    }
    try {
        $st = db()->prepare(
            "SELECT COUNT(*) FROM chat_friend_requests WHERE to_user_id = ? AND status = 'pending'"
        );
        $st->execute([$userId]);

        return (int) $st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function chat_read_at_column_ok(): bool
{
    static $cachedTrue = false;
    if ($cachedTrue) {
        return true;
    }
    if (!chat_tables_ok()) {
        return false;
    }
    try {
        db()->query('SELECT read_at FROM chat_messages LIMIT 1');
        $cachedTrue = true;

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function chat_unread_message_count(int $userId): int
{
    if (!chat_read_at_column_ok() || $userId <= 0) {
        return 0;
    }
    try {
        $st = db()->prepare(
            'SELECT COUNT(*) FROM chat_messages WHERE to_user_id = ? AND read_at IS NULL'
        );
        $st->execute([$userId]);

        return (int) $st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * @return array<int,int> peer_user_id => unread count
 */
function chat_unread_counts_by_peer(int $userId): array
{
    if (!chat_read_at_column_ok() || $userId <= 0) {
        return [];
    }
    try {
        $st = db()->prepare(
            'SELECT from_user_id, COUNT(*) AS c FROM chat_messages
             WHERE to_user_id = ? AND read_at IS NULL
             GROUP BY from_user_id'
        );
        $st->execute([$userId]);
        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[(int) $row['from_user_id']] = (int) $row['c'];
        }

        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

/** 打开与某好友的对话时，将该会话中发给当前用户的未读标为已读 */
function chat_mark_thread_read(int $userId, int $peerId): void
{
    if (!chat_read_at_column_ok() || $userId <= 0 || $peerId <= 0) {
        return;
    }
    try {
        db()->prepare(
            'UPDATE chat_messages SET read_at = CURRENT_TIMESTAMP
             WHERE to_user_id = ? AND from_user_id = ? AND read_at IS NULL'
        )->execute([$userId, $peerId]);
    } catch (Throwable $e) {
    }
}

function chat_mark_all_messages_read(int $userId): void
{
    if (!chat_read_at_column_ok() || $userId <= 0) {
        return;
    }
    try {
        db()->prepare(
            'UPDATE chat_messages SET read_at = CURRENT_TIMESTAMP
             WHERE to_user_id = ? AND read_at IS NULL'
        )->execute([$userId]);
    } catch (Throwable $e) {
    }
}

/**
 * 消息中心：按发送方聚合的未读私信（每人一条，取最新）。
 *
 * @return list<array{from_user_id:int,nickname:string,body_preview:string,created_at:string,unread_count:int}>
 */
function chat_unread_threads_for_user(int $userId, int $limit = 50): array
{
    if (!chat_read_at_column_ok() || $userId <= 0) {
        return [];
    }
    $counts = chat_unread_counts_by_peer($userId);
    if ($counts === []) {
        return [];
    }
    $limit = max(1, min(100, $limit));
    try {
        $st = db()->prepare(
            "SELECT m.from_user_id, m.body, m.created_at, u.nickname
             FROM chat_messages m
             JOIN users u ON u.id = m.from_user_id
             WHERE m.to_user_id = ? AND m.read_at IS NULL
             ORDER BY m.created_at DESC, m.id DESC
             LIMIT 500"
        );
        $st->execute([$userId]);
        $seen = [];
        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $fid = (int) $row['from_user_id'];
            if (isset($seen[$fid])) {
                continue;
            }
            $seen[$fid] = true;
            $body = (string) $row['body'];
            $preview = mb_strlen($body) > 60 ? mb_substr($body, 0, 60) . '…' : $body;
            $out[] = [
                'from_user_id' => $fid,
                'nickname' => (string) $row['nickname'],
                'body_preview' => $preview,
                'created_at' => (string) $row['created_at'],
                'unread_count' => $counts[$fid] ?? 1,
            ];
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

/** 导航「私信」角标：待处理好友申请 + 未读消息 */
function chat_nav_badge_total(int $userId): int
{
    if ($userId <= 0 || !chat_tables_ok()) {
        return 0;
    }

    return chat_count_incoming_pending($userId) + chat_unread_message_count($userId);
}
