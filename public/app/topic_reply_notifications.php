<?php

declare(strict_types=1);

function topic_reply_notifications_table_ok(): bool
{
    // 仅缓存「表存在」：首次检测失败时不写 static，避免执行迁移后 PHP-FPM 未重载时永远误判为不存在。
    static $cachedTrue = false;
    if ($cachedTrue) {
        return true;
    }
    try {
        db()->query('SELECT id FROM topic_reply_notifications LIMIT 1');
        $cachedTrue = true;

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function topic_reply_notifications_unread_count(int $userId): int
{
    if (!topic_reply_notifications_table_ok() || $userId <= 0) {
        return 0;
    }
    try {
        $st = db()->prepare(
            'SELECT COUNT(*) FROM topic_reply_notifications WHERE recipient_user_id = ? AND read_at IS NULL'
        );
        $st->execute([$userId]);

        return (int) $st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * 他人回复主题后为楼主写入一条通知（本人回复自己的主题不通知）。
 */
function topic_reply_notification_try_insert(int $recipientUserId, int $topicId, int $postId): void
{
    if (!topic_reply_notifications_table_ok() || $recipientUserId <= 0 || $topicId <= 0 || $postId <= 0) {
        return;
    }
    try {
        db()->prepare(
            'INSERT IGNORE INTO topic_reply_notifications (recipient_user_id, topic_id, post_id) VALUES (?,?,?)'
        )->execute([$recipientUserId, $topicId, $postId]);
    } catch (Throwable $e) {
        // 未迁移或约束失败时静默跳过
    }
}

/** 用户打开主题页时，将该主题下发给 TA 的未读通知全部标为已读 */
function topic_reply_notifications_mark_topic_read(int $userId, int $topicId): void
{
    if (!topic_reply_notifications_table_ok() || $userId <= 0 || $topicId <= 0) {
        return;
    }
    try {
        db()->prepare(
            'UPDATE topic_reply_notifications SET read_at = CURRENT_TIMESTAMP
             WHERE recipient_user_id = ? AND topic_id = ? AND read_at IS NULL'
        )->execute([$userId, $topicId]);
    } catch (Throwable $e) {
    }
}

function topic_reply_notifications_mark_all_read(int $userId): void
{
    if (!topic_reply_notifications_table_ok() || $userId <= 0) {
        return;
    }
    try {
        db()->prepare(
            'UPDATE topic_reply_notifications SET read_at = CURRENT_TIMESTAMP
             WHERE recipient_user_id = ? AND read_at IS NULL'
        )->execute([$userId]);
    } catch (Throwable $e) {
    }
}

/**
 * @return list<array{id:string,topic_id:string,post_id:string,topic_title:string,created_at:string,read_at:?string}>
 */
function topic_reply_notifications_list_for_user(int $userId, int $limit = 80): array
{
    if (!topic_reply_notifications_table_ok() || $userId <= 0) {
        return [];
    }
    $limit = max(1, min(200, $limit));
    try {
        $st = db()->prepare(
            "SELECT n.id, n.topic_id, n.post_id, n.created_at, n.read_at, t.title AS topic_title
             FROM topic_reply_notifications n
             JOIN topics t ON t.id = n.topic_id
             WHERE n.recipient_user_id = ?
             ORDER BY n.read_at IS NULL DESC, n.created_at DESC, n.id DESC
             LIMIT {$limit}"
        );
        $st->execute([$userId]);

        return $st->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}
