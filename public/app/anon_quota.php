<?php

declare(strict_types=1);

const ANON_QUOTA_DAILY_TOPIC = 3;
const ANON_QUOTA_DAILY_REPLY = 3;

function anon_quota_tables_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT id FROM anon_redeem_codes LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

function anon_quota_user_columns_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT anon_topic_bonus, anon_reply_bonus FROM users LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/** 每日限额 + 兑换余额（users 表字段） */
function anon_quota_limits_ok(): bool
{
    return anon_quota_user_columns_ok();
}

/** 兑换码功能（含生成与兑换） */
function anon_quota_redeem_ok(): bool
{
    return anon_quota_tables_ok() && anon_quota_user_columns_ok();
}

function anon_quota_enabled(): bool
{
    return anon_quota_redeem_ok();
}

/**
 * 供页面展示：限额就绪即返回用量；未迁移时返回 setup_needed。
 *
 * @return array{
 *   enabled: bool,
 *   limits_ok: bool,
 *   redeem_ok: bool,
 *   setup_needed?: bool,
 *   topic: array{daily_used:int,daily_limit:int,bonus:int,remaining:int},
 *   reply: array{daily_used:int,daily_limit:int,bonus:int,remaining:int}
 * }
 */
function anon_quota_display_status(int $userId): array
{
    if (!anon_quota_limits_ok()) {
        return [
            'enabled' => false,
            'limits_ok' => false,
            'redeem_ok' => false,
            'setup_needed' => true,
            'topic' => [
                'daily_used' => 0,
                'daily_limit' => ANON_QUOTA_DAILY_TOPIC,
                'bonus' => 0,
                'remaining' => ANON_QUOTA_DAILY_TOPIC,
            ],
            'reply' => [
                'daily_used' => 0,
                'daily_limit' => ANON_QUOTA_DAILY_REPLY,
                'bonus' => 0,
                'remaining' => ANON_QUOTA_DAILY_REPLY,
            ],
        ];
    }
    $status = anon_quota_status($userId);
    $status['limits_ok'] = true;
    $status['redeem_ok'] = anon_quota_tables_ok();
    $status['enabled'] = true;

    return $status;
}

function anon_post_wants_anonymous(array $post): bool
{
    return isset($post['anonymous']) && (string) $post['anonymous'] === '1';
}

/** @return array{topic_bonus:int, reply_bonus:int} */
function anon_quota_user_bonuses(int $userId): array
{
    if (!anon_quota_user_columns_ok() || $userId <= 0) {
        return ['topic_bonus' => 0, 'reply_bonus' => 0];
    }
    $st = db()->prepare('SELECT anon_topic_bonus, anon_reply_bonus FROM users WHERE id = ? LIMIT 1');
    $st->execute([$userId]);
    $row = $st->fetch();
    if (!$row) {
        return ['topic_bonus' => 0, 'reply_bonus' => 0];
    }

    return [
        'topic_bonus' => max(0, (int) $row['anon_topic_bonus']),
        'reply_bonus' => max(0, (int) $row['anon_reply_bonus']),
    ];
}

function anon_quota_daily_topic_count(int $userId, ?string $day = null): int
{
    if ($userId <= 0) {
        return 0;
    }
    $day = $day ?? date('Y-m-d');
    try {
        $st = db()->prepare(
            'SELECT COUNT(*) FROM topics WHERE is_anonymous = 1 AND real_user_id = ? AND DATE(created_at) = ?'
        );
        $st->execute([$userId, $day]);

        return (int) $st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function anon_quota_daily_reply_count(int $userId, ?string $day = null): int
{
    if ($userId <= 0) {
        return 0;
    }
    $day = $day ?? date('Y-m-d');
    try {
        $st = db()->prepare(
            'SELECT COUNT(*) FROM posts WHERE is_anonymous = 1 AND real_user_id = ? AND DATE(created_at) = ?'
        );
        $st->execute([$userId, $day]);

        return (int) $st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * @return array{
 *   enabled: bool,
 *   topic: array{daily_used:int,daily_limit:int,bonus:int,remaining:int},
 *   reply: array{daily_used:int,daily_limit:int,bonus:int,remaining:int}
 * }
 */
function anon_quota_status(int $userId): array
{
    $topicLimit = ANON_QUOTA_DAILY_TOPIC;
    $replyLimit = ANON_QUOTA_DAILY_REPLY;
    $bonuses = anon_quota_user_bonuses($userId);
    $topicUsed = anon_quota_daily_topic_count($userId);
    $replyUsed = anon_quota_daily_reply_count($userId);
    $topicFreeLeft = max(0, $topicLimit - $topicUsed);
    $replyFreeLeft = max(0, $replyLimit - $replyUsed);

    return [
        'enabled' => anon_quota_limits_ok(),
        'topic' => [
            'daily_used' => $topicUsed,
            'daily_limit' => $topicLimit,
            'bonus' => $bonuses['topic_bonus'],
            'remaining' => $topicFreeLeft + $bonuses['topic_bonus'],
        ],
        'reply' => [
            'daily_used' => $replyUsed,
            'daily_limit' => $replyLimit,
            'bonus' => $bonuses['reply_bonus'],
            'remaining' => $replyFreeLeft + $bonuses['reply_bonus'],
        ],
    ];
}

/**
 * @param 'topic'|'reply' $type
 * @return array{ok:bool, error?:string, use_bonus?:bool}
 */
function anon_quota_assert_can_use(int $userId, string $type): array
{
    if (!anon_quota_limits_ok()) {
        return ['ok' => true, 'use_bonus' => false];
    }
    if ($userId <= 0) {
        return ['ok' => false, 'error' => '请先登录。'];
    }
    $status = anon_quota_status($userId);
    $slot = $type === 'reply' ? $status['reply'] : $status['topic'];
    if ($slot['remaining'] <= 0) {
        $label = $type === 'reply' ? '匿名回复' : '匿名发帖';
        $limit = $type === 'reply' ? ANON_QUOTA_DAILY_REPLY : ANON_QUOTA_DAILY_TOPIC;

        return [
            'ok' => false,
            'error' => $label . '今日次数已用完（每日免费 ' . $limit . ' 次）。可在个人中心使用兑换码增加次数。',
        ];
    }
    $dailyUsed = $slot['daily_used'];
    $dailyLimit = $slot['daily_limit'];
    $useBonus = $dailyUsed >= $dailyLimit;

    return ['ok' => true, 'use_bonus' => $useBonus];
}

/**
 * @param 'topic'|'reply' $type
 */
function anon_quota_commit_use(int $userId, string $type, bool $useBonus): void
{
    if (!$useBonus || !anon_quota_user_columns_ok() || $userId <= 0) {
        return;
    }
    $col = $type === 'reply' ? 'anon_reply_bonus' : 'anon_topic_bonus';
    db()->prepare("UPDATE users SET {$col} = {$col} - 1 WHERE id = ? AND {$col} > 0")->execute([$userId]);
}

function anon_quota_normalize_code(string $raw): string
{
    return strtoupper(preg_replace('/\s+/', '', trim($raw)) ?? '');
}

function anon_quota_generate_code_string(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $len = strlen($alphabet);
    $parts = [];
    for ($p = 0; $p < 2; $p++) {
        $chunk = '';
        for ($i = 0; $i < 4; $i++) {
            $chunk .= $alphabet[random_int(0, $len - 1)];
        }
        $parts[] = $chunk;
    }

    return 'ANON-' . $parts[0] . '-' . $parts[1];
}

/**
 * @param array{kind:string,topic_grants:int,reply_grants:int,max_redemptions:int,note?:string,expires_at?:?string,batch?:int} $opts
 * @return array{ok:bool, error?:string, codes?:list<string>}
 */
function anon_quota_admin_create_codes(int $adminUserId, array $opts): array
{
    if (!anon_quota_redeem_ok()) {
        return ['ok' => false, 'error' => '兑换码表未就绪，请执行 migration_anon_quota.sql 中的 CREATE TABLE。'];
    }
    $kind = (string) ($opts['kind'] ?? '');
    if (!in_array($kind, ['topic', 'reply', 'both'], true)) {
        return ['ok' => false, 'error' => '请选择有效的兑换类型。'];
    }
    $topicGrants = max(0, (int) ($opts['topic_grants'] ?? 0));
    $replyGrants = max(0, (int) ($opts['reply_grants'] ?? 0));
    if ($kind === 'topic') {
        if ($topicGrants <= 0) {
            return ['ok' => false, 'error' => '请填写匿名发帖次数。'];
        }
        $replyGrants = 0;
    } elseif ($kind === 'reply') {
        if ($replyGrants <= 0) {
            return ['ok' => false, 'error' => '请填写匿名回复次数。'];
        }
        $topicGrants = 0;
    } else {
        if ($topicGrants <= 0 && $replyGrants <= 0) {
            return ['ok' => false, 'error' => '组合兑换码至少填写一种次数。'];
        }
    }
    $maxRedemptions = max(1, min(100000, (int) ($opts['max_redemptions'] ?? 1)));
    $batch = max(1, min(50, (int) ($opts['batch'] ?? 1)));
    $note = trim((string) ($opts['note'] ?? ''));
    if (mb_strlen($note) > 255) {
        $note = mb_substr($note, 0, 255);
    }
    $expiresAt = null;
    $expiresRaw = trim((string) ($opts['expires_at'] ?? ''));
    if ($expiresRaw !== '') {
        $ts = strtotime($expiresRaw);
        if ($ts === false) {
            return ['ok' => false, 'error' => '过期时间格式无效。'];
        }
        $expiresAt = date('Y-m-d H:i:s', $ts);
    }

    $codes = [];
    $ins = db()->prepare(
        'INSERT INTO anon_redeem_codes (code, kind, topic_grants, reply_grants, max_redemptions, created_by_user_id, note, expires_at)
         VALUES (?,?,?,?,?,?,?,?)'
    );
    for ($n = 0; $n < $batch; $n++) {
        $code = '';
        for ($try = 0; $try < 20; $try++) {
            $candidate = anon_quota_generate_code_string();
            $chk = db()->prepare('SELECT id FROM anon_redeem_codes WHERE code = ? LIMIT 1');
            $chk->execute([$candidate]);
            if (!$chk->fetch()) {
                $code = $candidate;
                break;
            }
        }
        if ($code === '') {
            return ['ok' => false, 'error' => '生成兑换码失败，请重试。'];
        }
        $ins->execute([$code, $kind, $topicGrants, $replyGrants, $maxRedemptions, $adminUserId, $note !== '' ? $note : null, $expiresAt]);
        $codes[] = $code;
    }

    return ['ok' => true, 'codes' => $codes];
}

/**
 * @return array{ok:bool, error?:string, topic_added?:int, reply_added?:int}
 */
function anon_quota_redeem_code(int $userId, string $rawCode): array
{
    if (!anon_quota_redeem_ok()) {
        return ['ok' => false, 'error' => '兑换功能未就绪，请联系管理员创建 anon_redeem_codes 数据表。'];
    }
    if ($userId <= 0) {
        return ['ok' => false, 'error' => '请先登录。'];
    }
    $code = anon_quota_normalize_code($rawCode);
    if ($code === '' || strlen($code) < 6) {
        return ['ok' => false, 'error' => '请输入有效的兑换码。'];
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare(
            'SELECT id, kind, topic_grants, reply_grants, max_redemptions, redemption_count, expires_at
             FROM anon_redeem_codes WHERE code = ? LIMIT 1 FOR UPDATE'
        );
        $st->execute([$code]);
        $row = $st->fetch();
        if (!$row) {
            $pdo->rollBack();

            return ['ok' => false, 'error' => '兑换码不存在或已失效。'];
        }
        if (!empty($row['expires_at']) && strtotime((string) $row['expires_at']) < time()) {
            $pdo->rollBack();

            return ['ok' => false, 'error' => '兑换码已过期。'];
        }
        if ((int) $row['redemption_count'] >= (int) $row['max_redemptions']) {
            $pdo->rollBack();

            return ['ok' => false, 'error' => '该兑换码已达使用上限。'];
        }
        $codeId = (int) $row['id'];
        $used = $pdo->prepare('SELECT id FROM anon_redeem_uses WHERE code_id = ? AND user_id = ? LIMIT 1');
        $used->execute([$codeId, $userId]);
        if ($used->fetch()) {
            $pdo->rollBack();

            return ['ok' => false, 'error' => '您已使用过该兑换码。'];
        }

        $topicAdd = max(0, (int) $row['topic_grants']);
        $replyAdd = max(0, (int) $row['reply_grants']);
        $pdo->prepare(
            'UPDATE users SET anon_topic_bonus = anon_topic_bonus + ?, anon_reply_bonus = anon_reply_bonus + ? WHERE id = ?'
        )->execute([$topicAdd, $replyAdd, $userId]);
        $pdo->prepare('UPDATE anon_redeem_codes SET redemption_count = redemption_count + 1 WHERE id = ?')->execute([$codeId]);
        $pdo->prepare('INSERT INTO anon_redeem_uses (code_id, user_id) VALUES (?,?)')->execute([$codeId, $userId]);
        $pdo->commit();

        $parts = [];
        if ($topicAdd > 0) {
            $parts[] = '匿名发帖 +' . $topicAdd;
        }
        if ($replyAdd > 0) {
            $parts[] = '匿名回复 +' . $replyAdd;
        }

        return [
            'ok' => true,
            'topic_added' => $topicAdd,
            'reply_added' => $replyAdd,
            'message' => '兑换成功：' . implode('，', $parts) . '。',
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['ok' => false, 'error' => '兑换失败，请稍后重试。'];
    }
}

/**
 * @return list<array<string, mixed>>
 */
function anon_quota_admin_list_codes(int $limit = 80): array
{
    if (!anon_quota_tables_ok()) {
        return [];
    }
    $limit = max(1, min(200, $limit));
    $st = db()->prepare(
        'SELECT c.*, u.nickname AS creator_nickname
         FROM anon_redeem_codes c
         JOIN users u ON u.id = c.created_by_user_id
         ORDER BY c.id DESC
         LIMIT ' . (int) $limit
    );
    $st->execute();

    return $st->fetchAll() ?: [];
}
