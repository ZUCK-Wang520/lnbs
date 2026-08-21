<?php

declare(strict_types=1);

/** 楼主发帖时初始选项上限 */
const TOPIC_POLL_AUTHOR_MAX_OPTIONS = 10;

/** 每人最多可投票数（上限） */
const TOPIC_POLL_MAX_VOTES_PER_USER = 10;

function topic_polls_table_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT id FROM topic_polls LIMIT 1');
        db()->query('SELECT id FROM topic_poll_options LIMIT 1');
        db()->query('SELECT id FROM topic_poll_votes LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

function topic_poll_allow_user_options_column_ok(): bool
{
    static $cachedTrue = false;
    if ($cachedTrue) {
        return true;
    }
    if (!topic_polls_table_ok()) {
        return false;
    }
    try {
        db()->query('SELECT allow_user_options FROM topic_polls LIMIT 1');
        $cachedTrue = true;

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function topic_poll_votes_per_user_column_ok(): bool
{
    static $cachedTrue = false;
    if ($cachedTrue) {
        return true;
    }
    if (!topic_polls_table_ok()) {
        return false;
    }
    try {
        db()->query('SELECT votes_per_user FROM topic_polls LIMIT 1');
        $cachedTrue = true;

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function topic_poll_votes_per_user_cap(int $optionCount = 0): int
{
    return TOPIC_POLL_MAX_VOTES_PER_USER;
}

function topic_poll_normalize_votes_per_user(int $votes, int $optionCount): int
{
    return max(1, min(topic_poll_votes_per_user_cap($optionCount), $votes));
}

function topic_poll_votes_per_user_from_post(array $post, int $optionCount = TOPIC_POLL_MAX_VOTES_PER_USER): int
{
    return topic_poll_normalize_votes_per_user((int) ($post['poll_votes_per_user'] ?? 1), $optionCount);
}

/**
 * 从表单解析投票明细（含同一选项多票）。
 *
 * @return list<int> option_id 列表，重复表示多票
 */
function topic_poll_ballots_from_post(array $post): array
{
    $counts = $post['poll_vote_count'] ?? null;
    if (is_array($counts)) {
        $out = [];
        foreach ($counts as $optId => $cnt) {
            $oid = (int) $optId;
            if ($oid <= 0) {
                continue;
            }
            $n = max(0, (int) $cnt);
            for ($i = 0; $i < $n; $i++) {
                $out[] = $oid;
            }
        }

        return $out;
    }
    $raw = $post['option_id'] ?? [];
    if (!is_array($raw)) {
        if ($raw === '' || $raw === null) {
            return [];
        }
        $raw = [$raw];
    }
    $out = [];
    foreach ($raw as $id) {
        $n = (int) $id;
        if ($n > 0) {
            $out[] = $n;
        }
    }

    return $out;
}

function topic_poll_enabled_in_post(array $post): bool
{
    return isset($post['poll_enabled']) && (string) $post['poll_enabled'] === '1';
}

function topic_poll_allow_user_options_from_post(array $post): bool
{
    return topic_poll_enabled_in_post($post)
        && isset($post['poll_allow_user_options'])
        && (string) $post['poll_allow_user_options'] === '1';
}

/**
 * @return ?list<string> null = 未启用投票
 */
function topic_poll_options_from_post(array $post): ?array
{
    if (!topic_poll_enabled_in_post($post)) {
        return null;
    }
    $raw = $post['poll_options'] ?? [];
    if (!is_array($raw)) {
        return [];
    }
    $out = [];
    foreach ($raw as $line) {
        $t = trim((string) $line);
        if ($t !== '') {
            $out[] = $t;
        }
    }

    return $out;
}

/**
 * @param list<string> $options
 */
function topic_poll_validate_options(array $options): ?string
{
    if (count($options) < 2) {
        return '投票至少需要 2 个非空选项。';
    }
    if (count($options) > TOPIC_POLL_AUTHOR_MAX_OPTIONS) {
        return '楼主初始选项最多 ' . TOPIC_POLL_AUTHOR_MAX_OPTIONS . ' 个。';
    }
    $seen = [];
    foreach ($options as $opt) {
        if (mb_strlen($opt) > 80) {
            return '每个投票选项不超过 80 字。';
        }
        $key = mb_strtolower($opt, 'UTF-8');
        if (isset($seen[$key])) {
            return '投票选项不能重复。';
        }
        $seen[$key] = true;
    }

    return null;
}

/**
 * @param list<string> $options
 */
function topic_poll_create_for_topic(
    int $topicId,
    array $options,
    bool $allowUserOptions = false,
    int $votesPerUser = 1
): void {
    if (!topic_polls_table_ok() || $topicId <= 0 || $options === []) {
        return;
    }
    $votesPerUser = topic_poll_normalize_votes_per_user($votesPerUser, count($options));
    if (topic_poll_votes_per_user_column_ok() && topic_poll_allow_user_options_column_ok()) {
        db()->prepare('INSERT INTO topic_polls (topic_id, allow_user_options, votes_per_user) VALUES (?,?,?)')
            ->execute([$topicId, $allowUserOptions ? 1 : 0, $votesPerUser]);
    } elseif (topic_poll_votes_per_user_column_ok()) {
        db()->prepare('INSERT INTO topic_polls (topic_id, votes_per_user) VALUES (?,?)')
            ->execute([$topicId, $votesPerUser]);
    } elseif (topic_poll_allow_user_options_column_ok()) {
        db()->prepare('INSERT INTO topic_polls (topic_id, allow_user_options) VALUES (?,?)')
            ->execute([$topicId, $allowUserOptions ? 1 : 0]);
    } else {
        db()->prepare('INSERT INTO topic_polls (topic_id) VALUES (?)')->execute([$topicId]);
    }
    $pollId = (int) db()->lastInsertId();
    $hasAddedBy = topic_poll_option_added_by_column_ok();
    if ($hasAddedBy) {
        $st = db()->prepare(
            'INSERT INTO topic_poll_options (poll_id, label, sort_order, added_by_user_id) VALUES (?,?,?,NULL)'
        );
        foreach ($options as $i => $label) {
            $st->execute([$pollId, $label, $i]);
        }
    } else {
        $st = db()->prepare('INSERT INTO topic_poll_options (poll_id, label, sort_order) VALUES (?,?,?)');
        foreach ($options as $i => $label) {
            $st->execute([$pollId, $label, $i]);
        }
    }
}

function topic_poll_option_added_by_column_ok(): bool
{
    static $cachedTrue = false;
    if ($cachedTrue) {
        return true;
    }
    if (!topic_polls_table_ok()) {
        return false;
    }
    try {
        db()->query('SELECT added_by_user_id FROM topic_poll_options LIMIT 1');
        $cachedTrue = true;

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function topic_poll_option_label_taken(int $pollId, string $label): bool
{
    $key = mb_strtolower(trim($label), 'UTF-8');
    if ($key === '') {
        return true;
    }
    $st = db()->prepare('SELECT label FROM topic_poll_options WHERE poll_id = ?');
    $st->execute([$pollId]);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $existing) {
        if (mb_strtolower(trim((string) $existing), 'UTF-8') === $key) {
            return true;
        }
    }

    return false;
}

function topic_poll_count_options(int $pollId): int
{
    $st = db()->prepare('SELECT COUNT(*) FROM topic_poll_options WHERE poll_id = ?');
    $st->execute([$pollId]);

    return (int) $st->fetchColumn();
}

/** 发帖前校验投票表单；未启用投票时返回 null。 */
function topic_poll_validate_from_post(array $post): ?string
{
    $options = topic_poll_options_from_post($post);
    if ($options === null) {
        return null;
    }
    if (!topic_polls_table_ok()) {
        return '投票功能未就绪，请先执行 public/database/migration_topic_polls.sql。';
    }

    return topic_poll_validate_options($options);
}

/**
 * 发帖成功后附加投票；返回错误文案，null 表示成功或未启用。
 */
function topic_poll_try_attach_after_create(int $topicId, array $post): ?string
{
    $options = topic_poll_options_from_post($post);
    if ($options === null) {
        return null;
    }
    if (!topic_polls_table_ok()) {
        return '主题已发布，但投票功能需执行 public/database/migration_topic_polls.sql。';
    }
    $verr = topic_poll_validate_options($options);
    if ($verr !== null) {
        return '主题已发布，但投票未保存：' . $verr;
    }
    try {
        $allowOthers = topic_poll_allow_user_options_from_post($post);
        $votesPerUser = topic_poll_votes_per_user_from_post($post, count($options));
        topic_poll_create_for_topic($topicId, $options, $allowOthers, $votesPerUser);
    } catch (Throwable $e) {
        return '主题已发布，但投票保存失败，请稍后重试。';
    }

    return null;
}

/**
 * @return ?array{
 *   poll_id:int,
 *   topic_id:int,
 *   allow_user_options:bool,
 *   topic_owner_id:int,
 *   options:list<array{id:int,label:string,vote_count:int,added_by_user_id:?int}>,
 *   total_votes:int,
 *   votes_per_user:int,
 *   my_option_ids:list<int>,
 *   my_option_counts:array<int,int>,
 *   my_vote_count:int,
 *   participant_count:int,
 *   my_option_id:?int
 * }
 */
function topic_poll_for_topic(int $topicId, ?int $userId = null): ?array
{
    if (!topic_polls_table_ok() || $topicId <= 0) {
        return null;
    }
    $pollCols = ['p.id', 'p.topic_id', 't.user_id', 't.real_user_id', 't.is_anonymous'];
    if (topic_poll_allow_user_options_column_ok()) {
        $pollCols[] = 'p.allow_user_options';
    }
    if (topic_poll_votes_per_user_column_ok()) {
        $pollCols[] = 'p.votes_per_user';
    }
    $pollSel = implode(', ', $pollCols);
    $st = db()->prepare(
        "SELECT {$pollSel} FROM topic_polls p JOIN topics t ON t.id = p.topic_id WHERE p.topic_id = ? LIMIT 1"
    );
    $st->execute([$topicId]);
    $poll = $st->fetch(PDO::FETCH_ASSOC);
    if (!$poll) {
        return null;
    }
    $pollId = (int) $poll['id'];
    $topicOwnerId = forum_row_real_author_id($poll);
    $allowUserOptions = topic_poll_allow_user_options_column_ok()
        && (int) ($poll['allow_user_options'] ?? 0) === 1;
    $optSel = topic_poll_option_added_by_column_ok()
        ? 'o.id, o.label, o.sort_order, o.added_by_user_id, COUNT(v.id) AS vote_count'
        : 'o.id, o.label, o.sort_order, COUNT(v.id) AS vote_count';
    $groupBy = topic_poll_option_added_by_column_ok()
        ? 'o.id, o.label, o.sort_order, o.added_by_user_id'
        : 'o.id, o.label, o.sort_order';
    $ost = db()->prepare(
        "SELECT {$optSel}
         FROM topic_poll_options o
         LEFT JOIN topic_poll_votes v ON v.option_id = o.id
         WHERE o.poll_id = ?
         GROUP BY {$groupBy}
         ORDER BY vote_count DESC, o.sort_order ASC, o.id ASC"
    );
    $ost->execute([$pollId]);
    $options = [];
    $total = 0;
    foreach ($ost->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $cnt = (int) ($row['vote_count'] ?? 0);
        $total += $cnt;
        $addedBy = isset($row['added_by_user_id']) && $row['added_by_user_id'] !== null
            ? (int) $row['added_by_user_id']
            : null;
        $options[] = [
            'id' => (int) $row['id'],
            'label' => (string) $row['label'],
            'vote_count' => $cnt,
            'added_by_user_id' => $addedBy,
        ];
    }
    $optCount = count($options);
    $votesPerUser = topic_poll_votes_per_user_column_ok()
        ? topic_poll_normalize_votes_per_user((int) ($poll['votes_per_user'] ?? 1), $optCount)
        : 1;
    $pst = db()->prepare('SELECT COUNT(DISTINCT user_id) FROM topic_poll_votes WHERE poll_id = ?');
    $pst->execute([$pollId]);
    $participantCount = (int) $pst->fetchColumn();
    $myOptionIds = [];
    if ($userId !== null && $userId > 0) {
        $vst = db()->prepare(
            'SELECT option_id FROM topic_poll_votes WHERE poll_id = ? AND user_id = ? ORDER BY id ASC'
        );
        $vst->execute([$pollId, $userId]);
        foreach ($vst->fetchAll(PDO::FETCH_ASSOC) as $vrow) {
            $myOptionIds[] = (int) $vrow['option_id'];
        }
    }
    $myVoteCount = count($myOptionIds);
    $myOptionCounts = [];
    foreach ($myOptionIds as $oid) {
        $myOptionCounts[$oid] = ($myOptionCounts[$oid] ?? 0) + 1;
    }
    $myOptionId = $myVoteCount > 0 ? $myOptionIds[0] : null;

    return [
        'poll_id' => $pollId,
        'topic_id' => (int) $poll['topic_id'],
        'allow_user_options' => $allowUserOptions,
        'topic_owner_id' => $topicOwnerId,
        'options' => $options,
        'total_votes' => $total,
        'votes_per_user' => $votesPerUser,
        'my_option_ids' => $myOptionIds,
        'my_option_counts' => $myOptionCounts,
        'my_vote_count' => $myVoteCount,
        'participant_count' => $participantCount,
        'my_option_id' => $myOptionId,
    ];
}

function topic_poll_user_can_delete_option(int $actorId, int $topicOwnerId, ?int $addedByUserId): bool
{
    if ($actorId <= 0) {
        return false;
    }
    if ($actorId === $topicOwnerId) {
        return true;
    }

    return $addedByUserId !== null && $addedByUserId === $actorId;
}

function topic_poll_delete_option(int $topicId, int $userId, int $optionId): ?string
{
    if (!topic_polls_table_ok()) {
        return '数据库未升级：请执行 public/database/migration_topic_polls.sql。';
    }
    if ($userId <= 0) {
        return '请先登录。';
    }
    if ($optionId <= 0) {
        return '无效的选项。';
    }
    $hasAddedBy = topic_poll_option_added_by_column_ok();
    $optSel = $hasAddedBy
        ? 'o.id, o.poll_id, o.added_by_user_id, p.topic_id, t.user_id, t.real_user_id, t.is_anonymous, t.locked'
        : 'o.id, o.poll_id, p.topic_id, t.user_id, t.real_user_id, t.is_anonymous, t.locked';
    $st = db()->prepare(
        "SELECT {$optSel}
         FROM topic_poll_options o
         JOIN topic_polls p ON p.id = o.poll_id
         JOIN topics t ON t.id = p.topic_id
         WHERE p.topic_id = ? AND o.id = ? LIMIT 1"
    );
    $st->execute([$topicId, $optionId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return '选项不存在。';
    }
    if ((int) ($row['locked'] ?? 0) === 1) {
        return '主题已锁定，无法删除投票选项。';
    }
    $ownerId = forum_row_real_author_id($row);
    $addedBy = $hasAddedBy && isset($row['added_by_user_id']) && $row['added_by_user_id'] !== null
        ? (int) $row['added_by_user_id']
        : null;
    if (!topic_poll_user_can_delete_option($userId, $ownerId, $addedBy)) {
        return '您无权删除该选项。';
    }
    $pollId = (int) $row['poll_id'];
    if (topic_poll_count_options($pollId) <= 2) {
        return '至少需保留 2 个投票选项。';
    }
    try {
        db()->prepare('DELETE FROM topic_poll_options WHERE id = ? AND poll_id = ?')->execute([$optionId, $pollId]);
    } catch (Throwable $e) {
        return '删除失败，请稍后重试。';
    }

    return null;
}

function topic_poll_add_user_option(int $topicId, int $userId, string $label): ?string
{
    if (!topic_polls_table_ok()) {
        return '数据库未升级：请执行 public/database/migration_topic_polls.sql。';
    }
    if (!topic_poll_allow_user_options_column_ok()) {
        return '该功能需执行 public/database/migration_topic_poll_user_options.sql。';
    }
    if ($userId <= 0) {
        return '请先登录后再添加选项。';
    }
    $label = trim($label);
    if ($label === '') {
        return '请填写选项内容。';
    }
    if (mb_strlen($label) > 80) {
        return '选项不超过 80 字。';
    }
    $st = db()->prepare(
        'SELECT p.id AS poll_id, p.allow_user_options, t.locked
         FROM topic_polls p
         JOIN topics t ON t.id = p.topic_id
         WHERE p.topic_id = ? LIMIT 1'
    );
    $st->execute([$topicId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return '该主题没有投票。';
    }
    if ((int) ($row['locked'] ?? 0) === 1) {
        return '主题已锁定，无法添加投票选项。';
    }
    if ((int) ($row['allow_user_options'] ?? 0) !== 1) {
        return '楼主未开放由他人添加选项。';
    }
    $pollId = (int) $row['poll_id'];
    if (topic_poll_option_label_taken($pollId, $label)) {
        return '该选项已存在，请换一个说法。';
    }
    $merr = moderation_check_user_content('投票选项:' . $label);
    if ($merr !== null) {
        return $merr;
    }
    try {
        $sort = topic_poll_count_options($pollId);
        if (topic_poll_option_added_by_column_ok()) {
            db()->prepare(
                'INSERT INTO topic_poll_options (poll_id, label, sort_order, added_by_user_id) VALUES (?,?,?,?)'
            )->execute([$pollId, $label, $sort, $userId]);
        } else {
            db()->prepare(
                'INSERT INTO topic_poll_options (poll_id, label, sort_order) VALUES (?,?,?)'
            )->execute([$pollId, $label, $sort]);
        }
    } catch (Throwable $e) {
        return '添加选项失败，请稍后重试。';
    }

    return null;
}

function topic_poll_cancel_vote(int $topicId, int $userId): ?string
{
    if (!topic_polls_table_ok()) {
        return '数据库未升级：请执行 public/database/migration_topic_polls.sql。';
    }
    if ($userId <= 0) {
        return '请先登录。';
    }
    $poll = topic_poll_for_topic($topicId, $userId);
    if ($poll === null) {
        return '该主题没有投票。';
    }
    if ($poll['my_vote_count'] === 0) {
        return '您尚未投票。';
    }
    $st = db()->prepare(
        'SELECT t.locked FROM topic_polls p JOIN topics t ON t.id = p.topic_id WHERE p.topic_id = ? LIMIT 1'
    );
    $st->execute([$topicId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row && (int) ($row['locked'] ?? 0) === 1) {
        return '主题已锁定，无法取消投票。';
    }
    try {
        db()->prepare('DELETE FROM topic_poll_votes WHERE poll_id = ? AND user_id = ?')
            ->execute([(int) $poll['poll_id'], $userId]);
    } catch (Throwable $e) {
        return '取消投票失败，请稍后重试。';
    }

    return null;
}

/**
 * @param list<int> $optionIds
 */
function topic_poll_cast_vote(int $topicId, int $userId, array $optionIds): ?string
{
    if (!topic_polls_table_ok()) {
        return '数据库未升级：请执行 public/database/migration_topic_polls.sql。';
    }
    if ($userId <= 0) {
        return '请先登录后再投票。';
    }
    $poll = topic_poll_for_topic($topicId, $userId);
    if ($poll === null) {
        return '该主题没有投票。';
    }
    if ($poll['my_vote_count'] > 0) {
        return '您已投过票，请先取消后再重新投票。';
    }
    $limit = (int) $poll['votes_per_user'];
    if ($optionIds === []) {
        return $limit > 1 ? '请至少投 1 票。' : '请选择一个选项。';
    }
    if (count($optionIds) > $limit) {
        return '每人最多投 ' . $limit . ' 票。';
    }
    $validIds = [];
    foreach ($poll['options'] as $opt) {
        $validIds[(int) $opt['id']] = true;
    }
    foreach ($optionIds as $optionId) {
        if (!isset($validIds[$optionId])) {
            return '无效的投票选项。';
        }
    }
    $lockSt = db()->prepare(
        'SELECT t.locked FROM topic_polls p JOIN topics t ON t.id = p.topic_id WHERE p.topic_id = ? LIMIT 1'
    );
    $lockSt->execute([$topicId]);
    $lockRow = $lockSt->fetch(PDO::FETCH_ASSOC);
    if ($lockRow && (int) ($lockRow['locked'] ?? 0) === 1) {
        return '主题已锁定，无法投票。';
    }
    $pollId = (int) $poll['poll_id'];
    try {
        $st = db()->prepare('INSERT INTO topic_poll_votes (poll_id, option_id, user_id) VALUES (?,?,?)');
        foreach ($optionIds as $optionId) {
            $st->execute([$pollId, $optionId, $userId]);
        }
    } catch (PDOException $e) {
        return '投票失败，请稍后重试。';
    } catch (Throwable $e) {
        return '投票失败，请稍后重试。';
    }

    return null;
}

function topic_poll_update_votes_per_user(int $topicId, int $userId, int $votesPerUser): ?string
{
    if (!topic_polls_table_ok()) {
        return '数据库未升级：请执行 public/database/migration_topic_polls.sql。';
    }
    if (!topic_poll_votes_per_user_column_ok()) {
        return '该功能需执行 public/database/migration_topic_poll_votes_per_user.sql。';
    }
    if ($userId <= 0) {
        return '请先登录。';
    }
    $poll = topic_poll_for_topic($topicId, null);
    if ($poll === null) {
        return '该主题没有投票。';
    }
    if ($userId !== (int) $poll['topic_owner_id']) {
        return '仅发帖人可修改投票设置。';
    }
    $st = db()->prepare(
        'SELECT t.locked FROM topic_polls p JOIN topics t ON t.id = p.topic_id WHERE p.topic_id = ? LIMIT 1'
    );
    $st->execute([$topicId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row && (int) ($row['locked'] ?? 0) === 1) {
        return '主题已锁定，无法修改投票设置。';
    }
    $normalized = topic_poll_normalize_votes_per_user($votesPerUser, count($poll['options']));
    try {
        db()->prepare('UPDATE topic_polls SET votes_per_user = ? WHERE id = ?')
            ->execute([$normalized, (int) $poll['poll_id']]);
    } catch (Throwable $e) {
        return '保存失败，请稍后重试。';
    }

    return null;
}
