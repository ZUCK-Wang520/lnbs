<?php

declare(strict_types=1);

/** 每日签到经验 */
const USER_LEVEL_XP_CHECKIN = 14;

/** 每日首次发帖/回复经验（当天仅奖一次） */
const USER_LEVEL_XP_DAILY_POST = 20;

function user_level_columns_ok(): bool
{
    static $cachedTrue = false;
    if ($cachedTrue) {
        return true;
    }
    try {
        db()->query('SELECT experience, level, last_checkin_date FROM users LIMIT 1');
        $cachedTrue = true;

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/** 升到 level 所需的累计经验下限（level 1 为 0） */
function user_xp_threshold_for_level(int $level): int
{
    if ($level <= 1) {
        return 0;
    }
    $t = 0;
    for ($L = 1; $L < $level; $L++) {
        $t += 36 + $L * 16;
    }

    return $t;
}

function user_level_for_xp(int $xp): int
{
    $xp = max(0, $xp);
    for ($lv = 120; $lv >= 1; $lv--) {
        if ($xp >= user_xp_threshold_for_level($lv)) {
            return $lv;
        }
    }

    return 1;
}

/**
 * @return array{level:int,xp:int,xp_floor:int,xp_next:int,pct:float}
 */
function user_level_bar(int $xp): array
{
    $level = user_level_for_xp($xp);
    $floor = user_xp_threshold_for_level($level);
    $next = user_xp_threshold_for_level($level + 1);
    $span = max(1, $next - $floor);
    $into = max(0, $xp - $floor);
    $pct = min(100.0, 100.0 * $into / $span);

    return [
        'level' => $level,
        'xp' => $xp,
        'xp_floor' => $floor,
        'xp_next' => $next,
        'pct' => $pct,
    ];
}

/**
 * @return array{from:int,to:int,xp:int}|null
 */
function user_apply_xp(int $userId, int $delta): ?array
{
    if (!user_level_columns_ok() || $userId <= 0 || $delta === 0) {
        return null;
    }
    try {
        $st = db()->prepare('SELECT experience, level FROM users WHERE id = ? LIMIT 1');
        $st->execute([$userId]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }
        $oldXp = (int) $row['experience'];
        $oldLevel = (int) $row['level'];
        $newXp = max(0, $oldXp + $delta);
        $newLevel = user_level_for_xp($newXp);
        db()->prepare('UPDATE users SET experience = ?, level = ? WHERE id = ?')->execute([$newXp, $newLevel, $userId]);
        if (!empty($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $userId) {
            $_SESSION['user_level_display'] = $newLevel;
        }
        if ($newLevel > $oldLevel) {
            $_SESSION['_level_up'] = ['from' => $oldLevel, 'to' => $newLevel];

            return ['from' => $oldLevel, 'to' => $newLevel, 'xp' => $newXp];
        }
    } catch (Throwable $e) {
    }

    return null;
}

/**
 * @return array{ok:bool,error?:string,xp?:int}
 */
function user_perform_checkin(int $userId): array
{
    if (!user_level_columns_ok()) {
        return ['ok' => false, 'error' => '等级功能未启用，请执行 migration_user_level.sql。'];
    }
    $today = date('Y-m-d');
    try {
        $st = db()->prepare('SELECT last_checkin_date FROM users WHERE id = ? LIMIT 1');
        $st->execute([$userId]);
        $row = $st->fetch();
        if (!$row) {
            return ['ok' => false, 'error' => '用户不存在。'];
        }
        if ((string) ($row['last_checkin_date'] ?? '') === $today) {
            return ['ok' => false, 'error' => '今日已签到，明天再来吧。'];
        }
        db()->prepare('UPDATE users SET last_checkin_date = ? WHERE id = ?')->execute([$today, $userId]);
        user_apply_xp($userId, USER_LEVEL_XP_CHECKIN);
        $_SESSION['_checkin_celebration'] = ['xp' => USER_LEVEL_XP_CHECKIN];

        return ['ok' => true, 'xp' => USER_LEVEL_XP_CHECKIN];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => '签到失败，请稍后重试。'];
    }
}

function user_try_award_daily_post_xp(int $userId): void
{
    if (!user_level_columns_ok() || $userId <= 0) {
        return;
    }
    $today = date('Y-m-d');
    try {
        $st = db()->prepare(
            'UPDATE users SET last_daily_post_xp_date = ? WHERE id = ? AND (last_daily_post_xp_date IS NULL OR last_daily_post_xp_date < ?)'
        );
        $st->execute([$today, $userId, $today]);
        if ($st->rowCount() < 1) {
            return;
        }
        user_apply_xp($userId, USER_LEVEL_XP_DAILY_POST);
    } catch (Throwable $e) {
    }
}

/** @return ?array{xp:int} */
function user_checkin_celebration_consume(): ?array
{
    if (empty($_SESSION['_checkin_celebration']) || !is_array($_SESSION['_checkin_celebration'])) {
        return null;
    }
    $x = $_SESSION['_checkin_celebration'];
    unset($_SESSION['_checkin_celebration']);
    $xp = (int) ($x['xp'] ?? 0);

    return $xp > 0 ? ['xp' => $xp] : null;
}

/** @return ?array{from:int,to:int} */
function user_level_up_consume_overlay(): ?array
{
    if (empty($_SESSION['_level_up']) || !is_array($_SESSION['_level_up'])) {
        return null;
    }
    $x = $_SESSION['_level_up'];
    unset($_SESSION['_level_up']);
    $from = (int) ($x['from'] ?? 0);
    $to = (int) ($x['to'] ?? 0);
    if ($from < 1 || $to < $from) {
        return null;
    }

    return ['from' => $from, 'to' => $to];
}

function user_level_refresh_session_cache(int $userId): void
{
    if (!user_level_columns_ok() || $userId <= 0) {
        unset($_SESSION['user_level_display']);

        return;
    }
    try {
        $st = db()->prepare('SELECT level FROM users WHERE id = ? LIMIT 1');
        $st->execute([$userId]);
        $lv = $st->fetchColumn();
        $_SESSION['user_level_display'] = (int) $lv;
    } catch (Throwable $e) {
        unset($_SESSION['user_level_display']);
    }
}
