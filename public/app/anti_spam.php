<?php

declare(strict_types=1);

/**
 * @return array{enabled:bool,reply_per_minute:int,reply_per_10min:int,duplicate_window_sec:int}
 */
function anti_spam_config(): array
{
    $cfg = $GLOBALS['APP_CONFIG']['anti_spam'] ?? [];
    if (!is_array($cfg)) {
        $cfg = [];
    }
    $enabled = array_key_exists('enabled', $cfg) ? (bool) $cfg['enabled'] : true;
    $rpm = (int) ($cfg['reply_per_minute'] ?? 8);
    $r10 = (int) ($cfg['reply_per_10min'] ?? 40);
    $dup = (int) ($cfg['duplicate_window_sec'] ?? 25);
    if ($rpm < 2) $rpm = 2;
    if ($r10 < $rpm) $r10 = max($rpm, 10);
    if ($dup < 5) $dup = 5;
    if ($dup > 180) $dup = 180;

    return [
        'enabled' => $enabled,
        'reply_per_minute' => $rpm,
        'reply_per_10min' => $r10,
        'duplicate_window_sec' => $dup,
    ];
}

/**
 * @return ?string 返回错误信息；通过返回 null
 */
function anti_spam_reply_check(int $userId, string $body): ?string
{
    $cfg = anti_spam_config();
    if (!$cfg['enabled']) {
        return null;
    }
    $now = time();

    // Session-level gate: prevent extremely fast double submit
    $gateKey = '_reply_gate_' . $userId;
    $nextAt = (int) ($_SESSION[$gateKey] ?? 0);
    if ($nextAt > $now) {
        $wait = $nextAt - $now;
        return '操作过于频繁，请 ' . $wait . ' 秒后再试。';
    }
    $_SESSION[$gateKey] = $now + 2;

    // Duplicate content within a short window (same user)
    $st = db()->prepare('SELECT body, UNIX_TIMESTAMP(created_at) AS ts FROM posts WHERE user_id = ? ORDER BY id DESC LIMIT 1');
    $st->execute([$userId]);
    $last = $st->fetch();
    if ($last && isset($last['ts'])) {
        $ts = (int) $last['ts'];
        if ($ts > 0 && ($now - $ts) <= $cfg['duplicate_window_sec']) {
            if (trim((string) ($last['body'] ?? '')) === trim($body)) {
                return '请勿重复提交相同回复。';
            }
        }
    }

    // Rate limit: per minute / per 10 minutes (same user)
    $st1 = db()->prepare('SELECT COUNT(*) FROM posts WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)');
    $st1->execute([$userId]);
    $c1 = (int) $st1->fetchColumn();
    if ($c1 >= $cfg['reply_per_minute']) {
        return '回复过于频繁，请稍后再试。';
    }
    $st2 = db()->prepare('SELECT COUNT(*) FROM posts WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)');
    $st2->execute([$userId]);
    $c2 = (int) $st2->fetchColumn();
    if ($c2 >= $cfg['reply_per_10min']) {
        return '回复过于频繁，请稍后再试。';
    }

    return null;
}

