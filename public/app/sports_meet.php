<?php

declare(strict_types=1);

/** 班级存库、与前台资料比对时，自动去掉末尾的「班」（可写 17 或 17班，存为 17） */
function sports_class_normalize(string $s): string
{
    $s = trim($s);
    if ($s === '') {
        return '';
    }

    return (string) preg_replace('/班+$/u', '', $s);
}

function sports_meet_tables_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT id FROM sports_meets LIMIT 1');
        db()->query('SELECT id FROM sports_events LIMIT 1');
        db()->query('SELECT id FROM sports_entries LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/** @return list<array<string,mixed>> */
function sports_meet_list(): array
{
    if (!sports_meet_tables_ok()) {
        return [];
    }
    $st = db()->query('SELECT id, title, starts_at, ends_at, is_active, created_at FROM sports_meets ORDER BY starts_at DESC, id DESC');

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/** @return list<array<string,mixed>> */
function sports_event_list_by_meet(int $meetId): array
{
    if (!sports_meet_tables_ok() || $meetId <= 0) {
        return [];
    }
    $st = db()->prepare(
        'SELECT id, meet_id, event_name, starts_at, ends_at, sort_order
         FROM sports_events
         WHERE meet_id = ?
         ORDER BY sort_order ASC, starts_at ASC, id ASC'
    );
    $st->execute([$meetId]);

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/** @return list<array<string,mixed>> */
function sports_entry_list_by_meet(int $meetId): array
{
    if (!sports_meet_tables_ok() || $meetId <= 0) {
        return [];
    }
    $st = db()->prepare(
        'SELECT se.id, se.meet_id, se.event_id, se.grade_name, se.class_name, se.student_name, se.result_text, se.achievement_text, se.updated_at,
                ev.event_name, ev.starts_at AS event_starts_at, ev.ends_at AS event_ends_at
         FROM sports_entries se
         JOIN sports_events ev ON ev.id = se.event_id
         WHERE se.meet_id = ?
         ORDER BY ev.starts_at ASC, ev.id ASC, se.id ASC'
    );
    $st->execute([$meetId]);

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/** @return list<array<string,mixed>> */
function sports_matches_for_user_profile(int $userId): array
{
    if (!sports_meet_tables_ok() || $userId <= 0 || !user_login_profile_columns_ok()) {
        return [];
    }
    $st = db()->prepare('SELECT profile_grade, profile_class, profile_real_name FROM users WHERE id = ? LIMIT 1');
    $st->execute([$userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) {
        return [];
    }
    $grade = trim((string) ($row['profile_grade'] ?? ''));
    $className = sports_class_normalize((string) ($row['profile_class'] ?? ''));
    $studentName = trim((string) ($row['profile_real_name'] ?? ''));
    if ($grade === '' || $className === '' || $studentName === '') {
        return [];
    }
    $listSt = db()->prepare(
        'SELECT se.id, se.class_name, se.result_text, se.achievement_text,
                ev.event_name, ev.starts_at AS event_starts_at, ev.ends_at AS event_ends_at,
                sm.title AS meet_title, sm.starts_at AS meet_starts_at, sm.ends_at AS meet_ends_at
         FROM sports_entries se
         JOIN sports_events ev ON ev.id = se.event_id
         JOIN sports_meets sm ON sm.id = se.meet_id
         WHERE se.grade_name = ? AND se.student_name = ?
         ORDER BY ev.starts_at ASC, se.id ASC'
    );
    $listSt->execute([$grade, $studentName]);
    $candidates = $listSt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $out = [];
    foreach ($candidates as $c) {
        if (sports_class_normalize((string) ($c['class_name'] ?? '')) === $className) {
            unset($c['class_name']);
            $out[] = $c;
        }
    }

    return $out;
}
