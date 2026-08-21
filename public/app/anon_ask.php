<?php

declare(strict_types=1);

/**
 * 匿名提问箱模块。
 *
 * 核心隐私原则：箱主（提问箱创建者）永远看不到提问者身份。
 * asker_user_id 只用于风控（限流、封禁）与后台审计，任何面向箱主的查询都不得 JOIN/返回提问者昵称。
 */

const ANON_ASK_MAX_BOXES_PER_USER = 12;
const ANON_ASK_TITLE_MAX_LEN = 60;
const ANON_ASK_INTRO_MAX_LEN = 300;
const ANON_ASK_QUESTION_MAX_LEN = 800;
const ANON_ASK_ANSWER_MAX_LEN = 2000;

/** 数据表是否已就绪（需执行 migration_anon_ask.sql） */
function anon_ask_tables_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT id FROM anon_ask_boxes LIMIT 1');
        db()->query('SELECT id FROM anon_ask_questions LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/** 生成唯一的分享 token（去除易混淆字符 0/O/1/l/I） */
function anon_ask_generate_token(): string
{
    $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $len = strlen($alphabet);
    for ($attempt = 0; $attempt < 12; $attempt++) {
        $token = '';
        for ($i = 0; $i < 10; $i++) {
            $token .= $alphabet[random_int(0, $len - 1)];
        }
        try {
            $st = db()->prepare('SELECT 1 FROM anon_ask_boxes WHERE token = ? LIMIT 1');
            $st->execute([$token]);
            if (!$st->fetchColumn()) {
                return $token;
            }
        } catch (Throwable $e) {
            return $token;
        }
    }

    return bin2hex(random_bytes(6));
}

/** 通过分享 token 找到提问箱（含箱主昵称，供公开页展示「向 XXX 提问」） */
function anon_ask_find_box_by_token(string $token): ?array
{
    if ($token === '' || !anon_ask_tables_ok()) {
        return null;
    }
    $st = db()->prepare(
        'SELECT b.*, u.nickname AS owner_nickname
         FROM anon_ask_boxes b
         JOIN users u ON u.id = b.user_id
         WHERE b.token = ? LIMIT 1'
    );
    $st->execute([$token]);
    $row = $st->fetch();

    return $row ?: null;
}

/** 找到某箱主拥有的提问箱（越权校验） */
function anon_ask_find_box_owned(int $boxId, int $ownerId): ?array
{
    if ($boxId <= 0 || $ownerId <= 0 || !anon_ask_tables_ok()) {
        return null;
    }
    $st = db()->prepare('SELECT * FROM anon_ask_boxes WHERE id = ? AND user_id = ? LIMIT 1');
    $st->execute([$boxId, $ownerId]);
    $row = $st->fetch();

    return $row ?: null;
}

/** 某箱主全部提问箱的未读提问总数（导航红点） */
function anon_ask_owner_unread_total(int $ownerId): int
{
    if ($ownerId <= 0 || !anon_ask_tables_ok()) {
        return 0;
    }
    try {
        $st = db()->prepare(
            'SELECT COUNT(*)
             FROM anon_ask_questions q
             JOIN anon_ask_boxes b ON b.id = q.box_id
             WHERE b.user_id = ? AND q.is_read = 0'
        );
        $st->execute([$ownerId]);

        return (int) $st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/** 提交提问的限流；返回错误文案，null 表示通过 */
function anon_ask_submit_rate_error(int $askerId): ?string
{
    try {
        $st = db()->prepare(
            'SELECT COUNT(*) FROM anon_ask_questions WHERE asker_user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)'
        );
        $st->execute([$askerId]);
        if ((int) $st->fetchColumn() >= 60) {
            return '您今日的提问次数已达上限，请明天再来。';
        }
        $st = db()->prepare(
            'SELECT COUNT(*) FROM anon_ask_questions WHERE asker_user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)'
        );
        $st->execute([$askerId]);
        if ((int) $st->fetchColumn() >= 5) {
            return '提问太频繁了，请稍后再试。';
        }
    } catch (Throwable $e) {
        return null;
    }

    return null;
}

/** 将纯文本安全渲染为 HTML（转义 + 换行） */
function anon_ask_text_html(string $text): string
{
    return nl2br(h($text), false);
}
