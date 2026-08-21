<?php

declare(strict_types=1);

require_once __DIR__ . '/anonymous.php';

function moderation_appeals_table_ok(): bool
{
    static $cachedTrue = false;
    if ($cachedTrue) {
        return true;
    }
    try {
        db()->query('SELECT id FROM moderation_appeals LIMIT 1');
        $cachedTrue = true;

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function moderation_appeal_try_queue(int $authorId, string $action, array $payload, ?string $aiHint): bool
{
    if (!moderation_appeals_table_ok() || $authorId <= 0) {
        return false;
    }
    $allowed = ['topic_new', 'topic_quick', 'post_reply', 'profile_likes', 'chat_send'];
    if (!in_array($action, $allowed, true)) {
        return false;
    }
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($json === false || strlen($json) > 65000) {
        return false;
    }
    $hint = $aiHint !== null ? mb_substr($aiHint, 0, 500) : null;
    try {
        db()->prepare(
            'INSERT INTO moderation_appeals (author_user_id, action, payload_json, ai_hint) VALUES (?,?,?,?)'
        )->execute([$authorId, $action, $json, $hint]);

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function moderation_board_by_slug(string $slug): ?array
{
    $st = db()->prepare('SELECT id, name, slug FROM boards WHERE slug = ? LIMIT 1');
    $st->execute([$slug]);
    $b = $st->fetch();

    return $b ?: null;
}

/**
 * @return array{0:int,1:int,2:?string,3:?int}
 */
function moderation_appeal_resolve_topic_author(int $realUserId, array $payload): array
{
    $wantAnon = !empty($payload['anonymous']);
    $displayNick = trim((string) ($payload['display_nickname'] ?? ''));
    if ($wantAnon) {
        $show = $displayNick !== '' ? mb_substr($displayNick, 0, 16) : '匿名';

        return [anonymous_user_id(), 1, $show, $realUserId];
    }

    return [$realUserId, 0, null, null];
}

/**
 * @return array{ok:bool,error:?string}
 */
function moderation_appeal_execute(int $appealId, int $authorUserId, string $action, array $payload): array
{
    $st = db()->prepare('SELECT id, nickname, banned FROM users WHERE id = ? LIMIT 1');
    $st->execute([$authorUserId]);
    $author = $st->fetch();
    if (!$author) {
        return ['ok' => false, 'error' => '用户不存在'];
    }
    if ((int) $author['banned'] === 1) {
        return ['ok' => false, 'error' => '用户已被禁言'];
    }

    try {
        if ($action === 'topic_new' || $action === 'topic_quick') {
            $slug = trim((string) ($payload['board_slug'] ?? ''));
            $title = trim((string) ($payload['title'] ?? ''));
            $body = trim((string) ($payload['body'] ?? ''));
            if ($slug === '' || $title === '' || mb_strlen($title) > 200 || $body === '') {
                return ['ok' => false, 'error' => '发帖参数无效'];
            }
            $board = moderation_board_by_slug($slug);
            if (!$board) {
                return ['ok' => false, 'error' => '版块不存在'];
            }
            [$uid, $isAnon, $anonNick, $realUid] = moderation_appeal_resolve_topic_author($authorUserId, $payload);
            $anonUseBonus = false;
            if ((int) $isAnon === 1) {
                $quotaCheck = anon_quota_assert_can_use($authorUserId, 'topic');
                if (empty($quotaCheck['ok'])) {
                    return ['ok' => false, 'error' => (string) ($quotaCheck['error'] ?? '匿名发帖次数不足')];
                }
                $anonUseBonus = !empty($quotaCheck['use_bonus']);
            }
            $ins = db()->prepare(
                'INSERT INTO topics (board_id, user_id, real_user_id, title, body, is_anonymous, anon_nickname) VALUES (?,?,?,?,?,?,?)'
            );
            $ins->execute([(int) $board['id'], $uid, $realUid, $title, $body, $isAnon, $anonNick]);
            if ((int) $isAnon === 1) {
                anon_quota_commit_use($authorUserId, 'topic', $anonUseBonus);
            }

            return ['ok' => true, 'error' => null];
        }

        if ($action === 'post_reply') {
            $topicId = (int) ($payload['topic_id'] ?? 0);
            $body = trim((string) ($payload['body'] ?? ''));
            if ($topicId <= 0 || $body === '') {
                return ['ok' => false, 'error' => '回复参数无效'];
            }
            $tst = db()->prepare('SELECT id, locked, user_id, real_user_id, is_anonymous FROM topics WHERE id = ? LIMIT 1');
            $tst->execute([$topicId]);
            $topic = $tst->fetch();
            if (!$topic || (int) $topic['locked'] === 1) {
                return ['ok' => false, 'error' => '主题不存在或已锁定'];
            }
            $parentPostId = (int) ($payload['parent_post_id'] ?? 0);
            if (!forum_posts_parent_ok()) {
                $parentPostId = 0;
            }
            if ($parentPostId > 0) {
                $pst = db()->prepare('SELECT id, topic_id FROM posts WHERE id = ? LIMIT 1');
                $pst->execute([$parentPostId]);
                $par = $pst->fetch();
                if (!$par || (int) $par['topic_id'] !== $topicId) {
                    return ['ok' => false, 'error' => '父评论无效'];
                }
            } else {
                $parentPostId = 0;
            }
            [$uid, $isAnon, $anonNick, $realUid] = moderation_appeal_resolve_topic_author($authorUserId, $payload);
            $anonUseBonus = false;
            if ((int) $isAnon === 1) {
                $quotaCheck = anon_quota_assert_can_use($authorUserId, 'reply');
                if (empty($quotaCheck['ok'])) {
                    return ['ok' => false, 'error' => (string) ($quotaCheck['error'] ?? '匿名回复次数不足')];
                }
                $anonUseBonus = !empty($quotaCheck['use_bonus']);
            }
            if (forum_posts_parent_ok()) {
                $pidIns = $parentPostId > 0 ? $parentPostId : null;
                db()->prepare(
                    'INSERT INTO posts (topic_id, parent_post_id, user_id, real_user_id, body, is_anonymous, anon_nickname) VALUES (?,?,?,?,?,?,?)'
                )->execute([$topicId, $pidIns, $uid, $realUid, $body, $isAnon, $anonNick]);
            } else {
                db()->prepare(
                    'INSERT INTO posts (topic_id, user_id, real_user_id, body, is_anonymous, anon_nickname) VALUES (?,?,?,?,?,?)'
                )->execute([$topicId, $uid, $realUid, $body, $isAnon, $anonNick]);
            }
            if ((int) $isAnon === 1) {
                anon_quota_commit_use($authorUserId, 'reply', $anonUseBonus);
            }
            $newPostId = (int) db()->lastInsertId();
            $ownerRealId = forum_row_real_author_id($topic);
            $replierRealId = $realUid !== null ? (int) $realUid : (int) $uid;
            if ($ownerRealId > 0 && $replierRealId > 0 && $ownerRealId !== $replierRealId) {
                topic_reply_notification_try_insert($ownerRealId, $topicId, $newPostId);
            }

            return ['ok' => true, 'error' => null];
        }

        if ($action === 'profile_likes') {
            if (!user_profile_likes_column_ok()) {
                return ['ok' => false, 'error' => '缺少 profile_likes 字段'];
            }
            $text = trim((string) ($payload['text'] ?? ''));
            if (mb_strlen($text) > 2000) {
                return ['ok' => false, 'error' => '喜欢内容过长'];
            }
            $store = $text === '' ? null : $text;
            db()->prepare('UPDATE users SET profile_likes = ? WHERE id = ?')->execute([$store, $authorUserId]);

            return ['ok' => true, 'error' => null];
        }

        if ($action === 'chat_send') {
            if (!chat_tables_ok()) {
                return ['ok' => false, 'error' => '私信表不可用'];
            }
            $toId = (int) ($payload['to_user_id'] ?? 0);
            $body = trim((string) ($payload['body'] ?? ''));
            if ($toId <= 0 || $toId === $authorUserId || chat_invalid_peer_user_id($toId)) {
                return ['ok' => false, 'error' => '私信对象无效'];
            }
            if ($body === '' || mb_strlen($body) > 2000) {
                return ['ok' => false, 'error' => '私信内容无效'];
            }
            if (!chat_are_friends($authorUserId, $toId)) {
                return ['ok' => false, 'error' => '双方已非好友'];
            }
            db()->prepare(
                'INSERT INTO chat_messages (from_user_id, to_user_id, body) VALUES (?,?,?)'
            )->execute([$authorUserId, $toId, $body]);

            return ['ok' => true, 'error' => null];
        }
    } catch (Throwable $e) {
        error_log('moderation_appeal_execute: ' . $e->getMessage());

        return ['ok' => false, 'error' => '执行失败'];
    }

    return ['ok' => false, 'error' => '未知动作'];
}

/**
 * @return list<array<string,mixed>>
 */
function moderation_appeals_list_with_meta(int $limit = 100): array
{
    if (!moderation_appeals_table_ok()) {
        return [];
    }
    $limit = max(1, min(300, $limit));
    $st = db()->prepare(
        "SELECT a.id, a.author_user_id, a.action, a.payload_json, a.ai_hint, a.status, a.created_at, a.resolved_at,
                u.nickname AS author_nickname
         FROM moderation_appeals a
         JOIN users u ON u.id = a.author_user_id
         ORDER BY a.status = 'pending' DESC, a.id DESC
         LIMIT {$limit}"
    );
    $st->execute();
    $rows = $st->fetchAll() ?: [];
    foreach ($rows as &$r) {
        $aid = (int) $r['id'];
        $vst = db()->prepare(
            'SELECT v.voter_user_id, v.decision, v.created_at, u.nickname AS voter_nick
             FROM moderation_appeal_votes v
             JOIN users u ON u.id = v.voter_user_id
             WHERE v.appeal_id = ?
             ORDER BY v.id ASC'
        );
        $vst->execute([$aid]);
        $r['votes'] = $vst->fetchAll() ?: [];
    }
    unset($r);

    return $rows;
}

function moderation_appeals_pending_count(): int
{
    if (!moderation_appeals_table_ok()) {
        return 0;
    }
    try {
        return (int) db()->query("SELECT COUNT(*) FROM moderation_appeals WHERE status = 'pending'")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * @return array{ok:bool,message:string}
 */
function moderation_appeal_cast_vote(int $appealId, int $voterId, string $decision): array
{
    if (!moderation_appeals_table_ok()) {
        return ['ok' => false, 'message' => '数据库未升级。'];
    }
    if ($decision !== 'approve' && $decision !== 'reject') {
        return ['ok' => false, 'message' => '参数无效。'];
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        $st = $pdo->prepare('SELECT * FROM moderation_appeals WHERE id = ? FOR UPDATE');
        $st->execute([$appealId]);
        $appeal = $st->fetch();
        if (!$appeal || (string) $appeal['status'] !== 'pending') {
            $pdo->rollBack();

            return ['ok' => false, 'message' => '该条已处理或不存在。'];
        }
        if ((int) $appeal['author_user_id'] === $voterId) {
            $pdo->rollBack();

            return ['ok' => false, 'message' => '不能审核自己提交的内容。'];
        }

        try {
            $pdo->prepare(
                'INSERT INTO moderation_appeal_votes (appeal_id, voter_user_id, decision) VALUES (?,?,?)'
            )->execute([$appealId, $voterId, $decision]);
        } catch (PDOException $e) {
            $pdo->rollBack();

            return ['ok' => false, 'message' => '您已表决过该条。'];
        }

        $vst = $pdo->prepare(
            'SELECT decision FROM moderation_appeal_votes WHERE appeal_id = ? ORDER BY id ASC'
        );
        $vst->execute([$appealId]);
        $decisions = $vst->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $final = null;
        $n = count($decisions);
        if ($n >= 2) {
            $d0 = (string) $decisions[0];
            $d1 = (string) $decisions[1];
            if ($d0 === $d1) {
                $final = $d0 === 'approve';
            } elseif ($n >= 3) {
                $d2 = (string) $decisions[2];
                $final = $d2 === 'approve';
            }
        }

        if ($final === null) {
            $pdo->commit();

            return ['ok' => true, 'message' => '已记录表决，等待其他审核员。'];
        }

        if ($final === true) {
            $payload = json_decode((string) $appeal['payload_json'], true);
            if (!is_array($payload)) {
                $pdo->prepare(
                    "UPDATE moderation_appeals SET status = 'rejected', resolved_at = CURRENT_TIMESTAMP WHERE id = ?"
                )->execute([$appealId]);
                $pdo->commit();

                return ['ok' => true, 'message' => '数据损坏，已标记为拒绝。'];
            }
            $exec = moderation_appeal_execute(
                $appealId,
                (int) $appeal['author_user_id'],
                (string) $appeal['action'],
                $payload
            );
            if (!$exec['ok']) {
                $pdo->prepare(
                    "UPDATE moderation_appeals SET status = 'rejected', resolved_at = CURRENT_TIMESTAMP WHERE id = ?"
                )->execute([$appealId]);
                $pdo->commit();

                return ['ok' => true, 'message' => '已通过表决但执行失败：' . (string) $exec['error']];
            }
            $pdo->prepare(
                "UPDATE moderation_appeals SET status = 'approved', resolved_at = CURRENT_TIMESTAMP WHERE id = ?"
            )->execute([$appealId]);
            $pdo->commit();

            return ['ok' => true, 'message' => '已通过并已发布内容。'];
        }

        $pdo->prepare(
            "UPDATE moderation_appeals SET status = 'rejected', resolved_at = CURRENT_TIMESTAMP WHERE id = ?"
        )->execute([$appealId]);
        $pdo->commit();

        return ['ok' => true, 'message' => '已否决该条内容。'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('moderation_appeal_cast_vote: ' . $e->getMessage());

        return ['ok' => false, 'message' => '操作失败，请重试。'];
    }
}
