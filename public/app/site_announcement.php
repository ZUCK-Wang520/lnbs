<?php

declare(strict_types=1);

function site_announcement_table_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT id, enabled, body FROM site_announcement LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/**
 * @return array{enabled:int, body:string}
 */
function site_announcement_get(): array
{
    $def = ['enabled' => 0, 'body' => ''];
    if (!site_announcement_table_ok()) {
        return $def;
    }
    $st = db()->query('SELECT enabled, body FROM site_announcement WHERE id = 1 LIMIT 1');
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return $def;
    }

    return [
        'enabled' => (int) ($row['enabled'] ?? 0),
        'body' => (string) ($row['body'] ?? ''),
    ];
}

/**
 * 前台展示用：已转义并换行；无公告时返回 null。
 */
function site_announcement_for_layout(): ?string
{
    if (!site_announcement_table_ok()) {
        return null;
    }
    $a = site_announcement_get();
    if (empty($a['enabled']) || trim($a['body']) === '') {
        return null;
    }

    return nl2br(h(trim($a['body'])), false);
}

function site_announcement_save(int $enabled, string $body): void
{
    if (!site_announcement_table_ok()) {
        return;
    }
    $st = db()->prepare(
        'INSERT INTO site_announcement (id, enabled, body, updated_at) VALUES (1, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE enabled = VALUES(enabled), body = VALUES(body), updated_at = NOW()'
    );
    $st->execute([$enabled ? 1 : 0, $body]);
}
