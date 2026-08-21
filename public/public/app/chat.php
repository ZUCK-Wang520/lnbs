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
