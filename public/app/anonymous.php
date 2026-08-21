<?php

declare(strict_types=1);

const ANON_USER_EMAIL = '__anon__@internal';

function anonymous_user_id(): int
{
    static $id = null;
    if ($id !== null) {
        return $id;
    }
    $st = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $st->execute([ANON_USER_EMAIL]);
    $row = $st->fetch();
    if (!$row) {
        throw new RuntimeException('缺少匿名占位用户，请执行 php public/database/seed.php');
    }
    $id = (int) $row['id'];
    return $id;
}

/** SQL 片段：主题列表/详情中的作者展示名（对外展示） */
function sql_topic_author_display(): string
{
    return "CASE WHEN t.is_anonymous = 1 THEN COALESCE(NULLIF(TRIM(t.anon_nickname), ''), '匿名') ELSE u.nickname END";
}

/** SQL 片段：回复作者展示名（对外展示） */
function sql_post_author_display(): string
{
    return "CASE WHEN p.is_anonymous = 1 THEN COALESCE(NULLIF(TRIM(p.anon_nickname), ''), '匿名') ELSE u.nickname END";
}

/**
 * 发帖/回复必须登录；匿名仅对已登录用户开放，真实账号写入 real_user_id。
 *
 * @return array{0: int, 1: int, 2: ?string, 3: ?int} user_id, is_anonymous, anon_nickname, real_user_id
 */
function resolve_topic_author(array $user, array $post): array
{
    $wantAnon = isset($post['anonymous']) && (string) $post['anonymous'] === '1';
    $displayNick = trim((string) ($post['display_nickname'] ?? ''));
    if ($wantAnon) {
        $show = $displayNick !== '' ? mb_substr($displayNick, 0, 16) : '匿名';

        return [anonymous_user_id(), 1, $show, (int) $user['id']];
    }

    return [(int) $user['id'], 0, null, null];
}

/**
 * @return array{0: int, 1: int, 2: ?string, 3: ?int}
 */
function resolve_reply_author(array $user, array $post): array
{
    $wantAnon = isset($post['anonymous']) && (string) $post['anonymous'] === '1';
    $displayNick = trim((string) ($post['display_nickname'] ?? ''));
    if ($wantAnon) {
        $show = $displayNick !== '' ? mb_substr($displayNick, 0, 16) : '匿名';

        return [anonymous_user_id(), 1, $show, (int) $user['id']];
    }

    return [(int) $user['id'], 0, null, null];
}

/** 是否已执行 migration_posts_parent.sql（posts.parent_post_id） */
function forum_posts_parent_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT parent_post_id FROM posts LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}
