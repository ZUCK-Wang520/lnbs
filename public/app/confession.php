<?php

declare(strict_types=1);

require_once __DIR__ . '/anonymous.php';
require_once __DIR__ . '/sms.php';

/**
 * @return array{user: ?array, error: ?string}
 */
function confession_resolve_target(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return ['user' => null, 'error' => '请填写对方的手机号码。'];
    }
    $phone = normalize_phone_cn($raw);
    if ($phone === null) {
        return ['user' => null, 'error' => '请输入有效的中国大陆 11 位手机号。'];
    }
    $st = db()->prepare('SELECT id, nickname, email, phone FROM users WHERE phone = ? LIMIT 1');
    $st->execute([$phone]);
    $u = $st->fetch();

    return $u ? ['user' => $u, 'error' => null] : ['user' => null, 'error' => '未找到使用该手机号注册的用户。'];
}

function confession_unread_count(int $userId): int
{
    try {
        $st = db()->prepare(
            "SELECT COUNT(*) FROM confessions WHERE to_user_id = ? AND status = 'unread'"
        );
        $st->execute([$userId]);

        return (int) $st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/** @return ?string 错误文案，null 表示通过 */
function confession_rate_check(int $fromId, int $toId): ?string
{
    try {
        $st = db()->prepare(
            'SELECT COUNT(*) FROM confessions WHERE from_user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)'
        );
        $st->execute([$fromId]);
        if ((int) $st->fetchColumn() >= 40) {
            return '您今日发送表白次数已达上限，请明天再试。';
        }
        $st = db()->prepare(
            'SELECT COUNT(*) FROM confessions WHERE from_user_id = ? AND to_user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)'
        );
        $st->execute([$fromId, $toId]);
        if ((int) $st->fetchColumn() >= 8) {
            return '今日向该用户发送过多，请明天再试。';
        }
    } catch (Throwable $e) {
        return null;
    }

    return null;
}

function confession_target_invalid(?array $target): bool
{
    if (!$target) {
        return true;
    }
    if (($target['email'] ?? '') === ANON_USER_EMAIL) {
        return true;
    }
    if (empty($target['phone'])) {
        return true;
    }

    return false;
}
