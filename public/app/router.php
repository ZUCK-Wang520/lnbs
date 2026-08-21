<?php

declare(strict_types=1);

require_once __DIR__ . '/render.php';
require_once __DIR__ . '/sms.php';
require_once __DIR__ . '/site_shutdown.php';
require_once __DIR__ . '/topic_poll.php';
if (!function_exists('anon_quota_redeem_ok') && is_readable(__DIR__ . '/anon_quota.php')) {
    require_once __DIR__ . '/anon_quota.php';
}
if (!function_exists('anon_ask_tables_ok') && is_readable(__DIR__ . '/anon_ask.php')) {
    require_once __DIR__ . '/anon_ask.php';
}

function route_dispatch(): void
{
    $method = request_method();
    $path = request_path();
    if ($path !== '/' && str_ends_with($path, '/')) {
        $path = rtrim($path, '/') ?: '/';
    }

    // --- Shutdown / maintenance mode（后台可配置；管理员可正常访问全站）---
    $sd = site_shutdown_effective();
    if (!empty($sd['enabled'])) {
        $u = function_exists('auth_user') ? auth_user() : null;
        $adminBypass = site_shutdown_user_bypasses($u);
        if (!$adminBypass) {
            if (!site_shutdown_allows_guest_path($path, $method, $u)) {
                http_response_code(503);
                render_page('站点停运', 'shutdown.php', [
                    'shutdownMessage' => (string) ($sd['message'] ?? ''),
                    'shutdownEta' => (string) ($sd['eta'] ?? ''),
                    'layout_minimal' => true,
                    'layout_minimal_mode' => 'shutdown',
                ]);
                return;
            }
        }
    }

    // Compatibility: a bare index.php?q=xxx (home) should always go to frontend /search.
    // Must NOT hijack other pages that also use q= (e.g. /admin/users?q=).
    if (
        $method === 'GET'
        && $path === '/'
        && isset($_GET['q']) && is_string($_GET['q']) && trim($_GET['q']) !== ''
        && (!isset($_GET['r']) || trim((string) $_GET['r']) === '')
    ) {
        header('Location: ' . url('/search', ['q' => trim((string) $_GET['q'])]), true, 302);
        exit;
    }

    // --- POST ---
    if ($method === 'POST') {
        if ($path === '/access-challenge/verify') {
            handle_access_challenge_verify_post();
            return;
        }
        if (function_exists('access_challenge_should_block_post') && access_challenge_should_block_post($path)) {
            access_challenge_block_redirect();
        }
        if ($path === '/login') {
            handle_login_post();
            return;
        }
        if ($path === '/login/complete-profile') {
            handle_login_complete_profile_post();
            return;
        }
        if ($path === '/admin/users/set-login-ban') {
            handle_admin_user_set_login_ban();
            return;
        }
        if ($path === '/admin/users/save-login-profile') {
            handle_admin_user_save_login_profile();
            return;
        }
        if ($path === '/admin/ip-ban/set') {
            handle_admin_ip_ban_set();
            return;
        }
        if ($path === '/admin/ip-ban/clear') {
            handle_admin_ip_ban_clear();
            return;
        }
        if ($path === '/admin/announcement/save') {
            handle_admin_announcement_save();
            return;
        }
        if ($path === '/admin/logo/save') {
            handle_admin_logo_save();
            return;
        }
        if ($path === '/admin/logo/clear') {
            handle_admin_logo_clear();
            return;
        }
        if ($path === '/admin/shutdown/save') {
            handle_admin_shutdown_save();
            return;
        }
        if ($path === '/admin/sports-meet/save') {
            handle_admin_sports_meet_save();
            return;
        }
        if ($path === '/admin/sports-event/save') {
            handle_admin_sports_event_save();
            return;
        }
        if ($path === '/admin/sports-event/delete') {
            handle_admin_sports_event_delete();
            return;
        }
        if ($path === '/admin/sports-entry/save') {
            handle_admin_sports_entry_save();
            return;
        }
        if ($path === '/admin/sports-entry/delete') {
            handle_admin_sports_entry_delete();
            return;
        }
        if ($path === '/admin/sports-entry/import-bulk') {
            handle_admin_sports_entry_import_bulk();
            return;
        }
        if ($path === '/forgot-password/send-sms') {
            handle_forgot_password_send_sms();
            return;
        }
        if ($path === '/forgot-password/reset') {
            handle_forgot_password_reset_post();
            return;
        }
        if ($path === '/register/send-sms') {
            handle_register_send_sms();
            return;
        }
        if ($path === '/register/complete') {
            handle_register_complete_post();
            return;
        }
        if ($path === '/logout') {
            csrf_verify();
            auth_logout();
            flash_set('success', '已退出登录。');
            redirect('/');
            return;
        }
        if ($path === '/topic/quick') {
            handle_topic_quick_post();
            return;
        }
        if (preg_match('#^/board/([a-z0-9-]+)/new$#', $path, $m)) {
            handle_topic_create_post($m[1]);
            return;
        }
        if (preg_match('#^/topic/(\d+)/delete$#', $path, $m)) {
            handle_own_topic_delete((int) $m[1]);
            return;
        }
        if (preg_match('#^/post/(\d+)/delete$#', $path, $m)) {
            handle_own_post_delete((int) $m[1]);
            return;
        }
        if (preg_match('#^/topic/(\d+)/reply$#', $path, $m)) {
            handle_reply_post((int) $m[1]);
            return;
        }
        if (preg_match('#^/topic/(\d+)/like$#', $path, $m)) {
            handle_topic_like_post((int) $m[1]);
            return;
        }
        if (preg_match('#^/topic/(\d+)/poll/settings$#', $path, $m)) {
            handle_topic_poll_settings_post((int) $m[1]);
            return;
        }
        if (preg_match('#^/topic/(\d+)/poll/vote/cancel$#', $path, $m)) {
            handle_topic_poll_vote_cancel_post((int) $m[1]);
            return;
        }
        if (preg_match('#^/topic/(\d+)/poll/vote$#', $path, $m)) {
            handle_topic_poll_vote_post((int) $m[1]);
            return;
        }
        if (preg_match('#^/topic/(\d+)/poll/option$#', $path, $m)) {
            handle_topic_poll_option_post((int) $m[1]);
            return;
        }
        if (preg_match('#^/topic/(\d+)/poll/option/delete$#', $path, $m)) {
            handle_topic_poll_option_delete_post((int) $m[1]);
            return;
        }
        if ($path === '/admin/boards/save') {
            handle_admin_board_save();
            return;
        }
        if ($path === '/admin/boards/delete') {
            handle_admin_board_delete();
            return;
        }
        if ($path === '/admin/topics/delete') {
            handle_admin_topic_delete();
            return;
        }
        if ($path === '/admin/posts/delete') {
            handle_admin_post_delete();
            return;
        }
        if ($path === '/admin/users/toggle-ban') {
            handle_admin_user_ban();
            return;
        }
        if ($path === '/admin/users/toggle-moderator-l2') {
            handle_admin_user_toggle_moderator_l2();
            return;
        }
        if ($path === '/admin/users/save-moderator-l2-perms') {
            handle_admin_user_save_moderator_l2_perms();
            return;
        }
        if ($path === '/admin/users/toggle-sponsor') {
            handle_admin_user_toggle_sponsor();
            return;
        }
        if ($path === '/admin/users/toggle-realname-allowed') {
            handle_admin_user_toggle_realname_allowed();
            return;
        }
        if ($path === '/admin/moderation/vote') {
            handle_admin_moderation_vote_post();
            return;
        }
        if ($path === '/profile/avatar') {
            handle_profile_avatar_post();
            return;
        }
        if ($path === '/profile/nickname') {
            handle_profile_nickname_post();
            return;
        }
        if ($path === '/profile/password') {
            handle_profile_password_post();
            return;
        }
        if ($path === '/profile/likes') {
            handle_profile_likes_post();
            return;
        }
        if ($path === '/profile/birthday') {
            handle_profile_birthday_post();
            return;
        }
        if ($path === '/profile/checkin') {
            handle_profile_checkin_post();
            return;
        }
        if ($path === '/profile/realname-verify') {
            handle_profile_realname_verify_post();
            return;
        }
        if ($path === '/profile/delete-account') {
            handle_profile_delete_account_post();
            return;
        }
        if ($path === '/profile/anon-redeem') {
            handle_profile_anon_redeem_post();
            return;
        }
        if ($path === '/admin/anon-codes/generate') {
            handle_admin_anon_codes_generate_post();
            return;
        }
        if ($path === '/upload/cos-image') {
            handle_cos_image_upload_post();
            return;
        }
        if ($path === '/upload/cos-video') {
            handle_cos_video_upload_post();
            return;
        }
        if ($path === '/confessions/send') {
            handle_confessions_send_post();
            return;
        }
        if ($path === '/confessions/ignore') {
            handle_confessions_ignore_post();
            return;
        }
        if ($path === '/ask/create') {
            handle_anon_ask_create_post();
            return;
        }
        if ($path === '/ask/box/toggle') {
            handle_anon_ask_toggle_post();
            return;
        }
        if ($path === '/ask/box/delete') {
            handle_anon_ask_delete_box_post();
            return;
        }
        if ($path === '/ask/submit') {
            handle_anon_ask_submit_post();
            return;
        }
        if ($path === '/ask/answer') {
            handle_anon_ask_answer_post();
            return;
        }
        if ($path === '/ask/question/hide') {
            handle_anon_ask_hide_question_post();
            return;
        }
        if ($path === '/chat/friend-request') {
            handle_chat_friend_request_post();
            return;
        }
        if ($path === '/chat/friend-respond') {
            handle_chat_friend_respond_post();
            return;
        }
        if ($path === '/chat/send') {
            handle_chat_send_post();
            return;
        }
        if ($path === '/messages/mark-all-read') {
            handle_messages_mark_all_read_post();
            return;
        }
        if ($path === '/couple/invite') {
            handle_couple_invite_post();
            return;
        }
        if ($path === '/couple/respond') {
            handle_couple_respond_post();
            return;
        }
        if ($path === '/couple/cancel-invite') {
            handle_couple_cancel_invite_post();
            return;
        }
        if ($path === '/couple/unbind') {
            handle_couple_unbind_post();
            return;
        }
        if ($path === '/couple/note') {
            handle_couple_note_post();
            return;
        }
        if ($path === '/couple/wall') {
            handle_couple_wall_post();
            return;
        }
        if ($path === '/couple/album/add') {
            handle_couple_album_add_post();
            return;
        }
        if ($path === '/couple/album/delete') {
            handle_couple_album_delete_post();
            return;
        }
        if ($path === '/couple/list/add') {
            handle_couple_list_add_post();
            return;
        }
        if ($path === '/couple/list/toggle') {
            handle_couple_list_toggle_post();
            return;
        }
        if ($path === '/couple/list/delete') {
            handle_couple_list_delete_post();
            return;
        }
        http_response_code(404);
        exit('Not Found');
    }

    // --- GET ---
    if ($path === '/') {
        handle_home();
        return;
    }
    if ($path === '/login') {
        handle_login_get();
        return;
    }
    if ($path === '/login/complete-profile') {
        handle_login_complete_profile_get();
        return;
    }
    if ($path === '/search') {
        handle_search_get();
        return;
    }
    if ($path === '/topic/new') {
        handle_topic_compose_get();
        return;
    }
    if ($path === '/geetest/register') {
        handle_geetest_register_get();
        return;
    }
    if ($path === '/auth/rsa-meta') {
        handle_auth_rsa_meta_get();
        return;
    }
    if ($path === '/forgot-password') {
        handle_forgot_password_get();
        return;
    }
    if ($path === '/forgot-password/reset') {
        handle_forgot_password_reset_get();
        return;
    }
    if ($path === '/register') {
        handle_register_get();
        return;
    }
    if ($path === '/register/welcome') {
        handle_register_welcome_get();
        return;
    }
    if ($path === '/register/verify') {
        handle_register_verify_get();
        return;
    }
    if ($path === '/user-notice') {
        handle_user_notice_get();
        return;
    }
    if ($path === '/privacy-policy') {
        handle_privacy_policy_get();
        return;
    }
    if ($path === '/about') {
        handle_about_us_get();
        return;
    }
    if ($path === '/shutdown') {
        http_response_code(503);
        $sd = site_shutdown_effective();
        render_page('站点停运', 'shutdown.php', [
            'shutdownMessage' => (string) ($sd['message'] ?? ''),
            'shutdownEta' => (string) ($sd['eta'] ?? ''),
            'layout_minimal' => true,
            'layout_minimal_mode' => 'shutdown',
        ]);
        return;
    }
    if ($path === '/profile') {
        handle_profile_get();
        return;
    }
    if ($path === '/profile/delete-account') {
        handle_profile_delete_account_get();
        return;
    }
    if ($path === '/messages') {
        handle_messages_get();
        return;
    }
    if ($path === '/confessions/sent') {
        handle_confessions_sent_get();
        return;
    }
    if ($path === '/confessions/new') {
        handle_confessions_new_get();
        return;
    }
    if ($path === '/confessions') {
        handle_confessions_inbox_get();
        return;
    }
    if (preg_match('#^/confession/(\d+)$#', $path, $m)) {
        handle_confession_show((int) $m[1]);
        return;
    }
    if ($path === '/ask') {
        handle_anon_ask_hub_get();
        return;
    }
    if (preg_match('#^/ask/box/(\d+)/poster$#', $path, $m)) {
        handle_anon_ask_poster_get((int) $m[1]);
        return;
    }
    if (preg_match('#^/ask/box/(\d+)$#', $path, $m)) {
        handle_anon_ask_box_get((int) $m[1]);
        return;
    }
    if (preg_match('#^/a/([A-Za-z0-9]{6,24})$#', $path, $m)) {
        handle_anon_ask_public_get($m[1]);
        return;
    }
    if (preg_match('#^/board/([a-z0-9-]+)$#', $path, $m)) {
        handle_board($m[1]);
        return;
    }
    if (preg_match('#^/board/([a-z0-9-]+)/new$#', $path, $m)) {
        handle_topic_new($m[1]);
        return;
    }
    if (preg_match('#^/user/(\d+)/topics$#', $path, $m)) {
        handle_user_topics_get((int) $m[1]);
        return;
    }
    if ($path === '/chat') {
        handle_chat_get();
        return;
    }
    if (preg_match('#^/chat/with/(\d+)$#', $path, $m)) {
        handle_chat_with_get((int) $m[1]);
        return;
    }
    if ($path === '/couple') {
        handle_couple_hub_get();
        return;
    }
    if ($path === '/couple/little') {
        handle_couple_little_get();
        return;
    }
    if ($path === '/couple/leaving') {
        handle_couple_leaving_get();
        return;
    }
    if ($path === '/couple/about') {
        handle_couple_about_get();
        return;
    }
    if ($path === '/couple/album') {
        handle_couple_album_get();
        return;
    }
    if ($path === '/couple/list') {
        handle_couple_list_get();
        return;
    }
    if (preg_match('#^/topic/(\d+)$#', $path, $m)) {
        handle_topic_show((int) $m[1]);
        return;
    }
    if ($path === '/admin') {
        handle_admin_dashboard();
        return;
    }
    if ($path === '/admin/boards') {
        handle_admin_boards();
        return;
    }
    if ($path === '/admin/users') {
        handle_admin_users();
        return;
    }
    if ($path === '/admin/ip-bans') {
        handle_admin_ip_bans();
        return;
    }
    if ($path === '/admin/users/realname') {
        handle_admin_user_realname_get();
        return;
    }
    if ($path === '/admin/chat') {
        handle_admin_chat_get();
        return;
    }
    if ($path === '/admin/moderation') {
        handle_admin_moderation_get();
        return;
    }
    if ($path === '/admin/audit-log') {
        handle_admin_audit_log_get();
        return;
    }
    if ($path === '/admin/sports-meet') {
        handle_admin_sports_meet_get();
        return;
    }
    if ($path === '/admin/anon-codes') {
        handle_admin_anon_codes_get();
        return;
    }

    http_response_code(404);
    render_page('未找到', 'errors/404.php');
}

// ---- Handlers ----

function handle_admin_audit_log_get(): void
{
    require_admin();
    if (!admin_audit_log_table_ok()) {
        flash_set('error', '数据库未升级：请执行 public/database/migration_admin_audit_log.sql。');
        redirect('/admin');
    }
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $actorFilter = max(0, (int) ($_GET['actor'] ?? 0));
    $actionQ = trim((string) ($_GET['q'] ?? ''));
    $perPage = 50;
    $data = admin_audit_log_fetch_page($page, $perPage, $actorFilter, $actionQ);
    extract($data, EXTR_OVERWRITE);
    render_page('操作审计', 'admin/audit_log.php', compact('rows', 'total', 'page', 'pages', 'perPage', 'actorFilter', 'actionQ', 'actorOptions'), true);
}

function handle_home(): void
{
    $q = trim((string) ($_GET['q'] ?? ''));
    if ($q !== '') {
        header('Location: ' . url('/search', ['q' => $q]), true, 302);
        exit;
    }
    $st = db()->query(
        'SELECT b.*, (SELECT COUNT(*) FROM topics t WHERE t.board_id = b.id) AS topic_count
         FROM boards b ORDER BY b.sort_order ASC, b.id ASC'
    );
    $boards = $st->fetchAll();
    $stats = db()->query(
        'SELECT (SELECT COUNT(*) FROM topics) AS topic_total, (SELECT COUNT(*) FROM posts) AS post_total'
    )->fetch();
    $ad = sql_topic_author_display();
    $recent = db()->query(
        "SELECT t.id, t.title, t.created_at, t.updated_at, t.is_anonymous, b.name AS board_name, b.slug AS board_slug, {$ad} AS author_nickname,
                ru.nickname AS author_real_nickname,
                author_u.avatar AS author_avatar,
                CASE WHEN t.is_anonymous = 0 THEN t.user_id END AS author_public_id
         FROM topics t
         JOIN boards b ON b.id = t.board_id
         JOIN users u ON u.id = t.user_id
         LEFT JOIN users ru ON ru.id = t.real_user_id
         LEFT JOIN users author_u ON author_u.id = CASE WHEN t.is_anonymous = 0 THEN t.user_id END
         ORDER BY t.created_at DESC
         LIMIT 10"
    )->fetchAll();

    $homeHotBoardEnabled = false;
    $homeHotByViews = [];
    $homeHotByLikes = [];
    $homeHotBoardUpdatedLabel = '';
    $homeHotBoardWeekLabel = '';
    $homeHotBoardViewsCumulative = false;
    $homeHotBoardRefreshSeconds = 0;
    if (home_hot_weekly_board_ready()) {
        $homeHotBoardRefreshSeconds = home_hot_board_snapshot_ttl_seconds();
        $homeHotBoardEnabled = true;
        $snap = home_hot_board_read_weekly_snapshot();
        if ($snap !== null) {
            $homeHotByViews = $snap['by_views'];
            $homeHotByLikes = $snap['by_likes'];
            $homeHotBoardWeekLabel = $snap['week_label'];
            $homeHotBoardUpdatedLabel = date('Y-m-d H:i', $snap['generated_at']);
            $homeHotBoardViewsCumulative = !empty($snap['views_cumulative']);
        } else {
            $bounds = home_hot_board_week_bounds();
            $homeHotBoardWeekLabel = $bounds['label'];
            $ws = $bounds['start'];
            $we = $bounds['end'];
            $wk = $bounds['key'];
            try {
                $stv = db()->prepare(
                    'SELECT t.id, t.title, b.name AS board_name, b.slug AS board_slug, COUNT(e.id) AS week_views
                     FROM topic_view_events e
                     INNER JOIN topics t ON t.id = e.topic_id
                     INNER JOIN boards b ON b.id = t.board_id
                     WHERE e.created_at >= ? AND e.created_at < ?
                     GROUP BY t.id, t.title, b.name, b.slug
                     ORDER BY COUNT(e.id) DESC, t.id DESC
                     LIMIT 10'
                );
                $stv->execute([$ws, $we]);
                $homeHotByViews = $stv->fetchAll();
                $viewsCumulative = false;
                if ($homeHotByViews === []) {
                    $stv2 = db()->query(
                        'SELECT t.id, t.title, b.name AS board_name, b.slug AS board_slug, COALESCE(t.view_count, 0) AS week_views
                         FROM topics t
                         INNER JOIN boards b ON b.id = t.board_id
                         ORDER BY COALESCE(t.view_count, 0) DESC, t.id DESC
                         LIMIT 10'
                    );
                    $homeHotByViews = $stv2 ? $stv2->fetchAll() : [];
                    $viewsCumulative = $homeHotByViews !== [];
                    $homeHotBoardViewsCumulative = $viewsCumulative;
                }

                $stl = db()->prepare(
                    'SELECT t.id, t.title, b.name AS board_name, b.slug AS board_slug, COUNT(*) AS week_likes
                     FROM topic_likes tl
                     INNER JOIN topics t ON t.id = tl.topic_id
                     INNER JOIN boards b ON b.id = t.board_id
                     WHERE tl.created_at >= ? AND tl.created_at < ?
                     GROUP BY t.id, t.title, b.name, b.slug
                     HAVING COUNT(*) > 0
                     ORDER BY COUNT(*) DESC, t.id DESC
                     LIMIT 10'
                );
                $stl->execute([$ws, $we]);
                $homeHotByLikes = $stl->fetchAll();

                home_hot_board_write_weekly_snapshot($wk, $homeHotByViews, $homeHotByLikes, $viewsCumulative);
                $homeHotBoardUpdatedLabel = date('Y-m-d H:i');
            } catch (Throwable $e) {
                $homeHotByViews = [];
                $homeHotByLikes = [];
            }
        }
    }

    $birthdayTodayUsers = [];
    if (user_birthday_column_ok()) {
        try {
            $delClause = user_deletion_columns_ok() ? ' AND (deleted_at IS NULL)' : '';
            $st = db()->query(
                "SELECT id, nickname, avatar FROM users
                 WHERE birthday IS NOT NULL
                 AND MONTH(birthday) = MONTH(CURDATE())
                 AND DAY(birthday) = DAY(CURDATE())
                 {$delClause}
                 ORDER BY id ASC
                 LIMIT 50"
            );
            $birthdayTodayUsers = $st->fetchAll();
        } catch (Throwable $e) {
            $birthdayTodayUsers = [];
        }
    }

    $gitUpdates = git_updates_for_home(3);
    $gitCommitBaseUrl = git_updates_commit_base_url();
    $gitUpdatesCacheTtl = git_updates_config()['cache_ttl_seconds'];
    render_page(
        '首页',
        'home.php',
        compact(
            'boards',
            'stats',
            'recent',
            'birthdayTodayUsers',
            'gitUpdates',
            'gitCommitBaseUrl',
            'gitUpdatesCacheTtl',
            'homeHotBoardEnabled',
            'homeHotByViews',
            'homeHotByLikes',
            'homeHotBoardUpdatedLabel',
            'homeHotBoardWeekLabel',
            'homeHotBoardViewsCumulative',
            'homeHotBoardRefreshSeconds'
        )
    );
}

function handle_login_get(): void
{
    if (auth_user()) {
        redirect('/');
    }
    render_page('登录', 'login.php');
}

function handle_login_post(): void
{
    csrf_verify();
    user_login_ban_purge_expired();
    $ip = client_ip();
    if (ip_is_banned_for_login($ip)) {
        flash_set('error', '当前网络环境暂时无法登录，请稍后再试。');
        redirect('/login');
    }
    if (geetest_enabled()) {
        $gc = (string) ($_POST['geetest_challenge'] ?? '');
        $gv = (string) ($_POST['geetest_validate'] ?? '');
        $gs = (string) ($_POST['geetest_seccode'] ?? '');
        $ge = geetest_validate_or_error($gc, $gv, $gs);
        if ($ge !== null) {
            flash_set('error', $ge);
            redirect('/login');
        }
    }
    $account = trim((string) ($_POST['account'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordRsa = (string) ($_POST['password_rsa'] ?? '');
    if (auth_login_rsa_required()) {
        // When required, ONLY accept RSA-encrypted password.
        if ($passwordRsa === '') {
            flash_set('error', '请使用安全加密登录（请刷新页面后重试）。');
            redirect('/login');
        }
        $dec = auth_login_rsa_decrypt_password($passwordRsa);
        if ($dec === null || ($dec['password'] ?? '') === '') {
            flash_set('error', '登录加密校验失败，请刷新页面后重试。');
            redirect('/login');
        }
        $password = (string) $dec['password'];
    } else {
        if ($password === '' && $passwordRsa !== '') {
            $dec = auth_login_rsa_decrypt_password($passwordRsa);
            if ($dec !== null) {
                $password = $dec['password'];
            }
        }
    }

    $row = null;
    $delSel = user_deletion_columns_ok() ? ', deleted_at' : '';
    $phone = normalize_phone_cn($account);
    if ($phone !== null) {
        try {
            $st = db()->prepare("SELECT id, password_hash, login_banned_until{$delSel} FROM users WHERE phone = ? LIMIT 1");
            $st->execute([$phone]);
            $row = $st->fetch() ?: null;
        } catch (Throwable $e) {
            $st = db()->prepare("SELECT id, password_hash{$delSel} FROM users WHERE phone = ? LIMIT 1");
            $st->execute([$phone]);
            $row = $st->fetch() ?: null;
        }
    }
    if ($row === null && filter_var($account, FILTER_VALIDATE_EMAIL)) {
        try {
            $st = db()->prepare("SELECT id, password_hash, login_banned_until{$delSel} FROM users WHERE email = ? LIMIT 1");
            $st->execute([$account]);
            $row = $st->fetch() ?: null;
        } catch (Throwable $e) {
            $st = db()->prepare("SELECT id, password_hash{$delSel} FROM users WHERE email = ? LIMIT 1");
            $st->execute([$account]);
            $row = $st->fetch() ?: null;
        }
    }
    if ($row !== null && !empty($row['login_banned_until'])) {
        $until = strtotime((string) $row['login_banned_until']);
        if ($until !== false && $until > time()) {
            flash_set('error', '该账号已被封禁登录，暂时无法登录。');
            redirect('/login');
        }
    }
    if ($row === null || !password_verify($password, $row['password_hash'])) {
        access_challenge_on_login_failure();
        flash_set('error', '账号或密码错误。');
        redirect('/login');
    }
    // 已注销账号禁止登录
    if ($row !== null && user_deletion_columns_ok() && !empty($row['deleted_at'])) {
        flash_set('error', '该账号已注销，无法登录。');
        redirect('/login');
    }

    $uid = (int) $row['id'];
    $profileIncomplete = user_login_profile_columns_ok() && user_login_profile_incomplete_for_user_id($uid);

    $remember = isset($_POST['remember']) && (string) $_POST['remember'] === '1';
    auth_login($uid, $remember);
    access_challenge_on_login_success();
    // best-effort: record login ip / time
    try {
        db()->prepare('UPDATE users SET last_login_ip = ?, last_login_at = NOW() WHERE id = ?')->execute([$ip, $uid]);
    } catch (Throwable $e) {
        // ignore when columns not migrated
    }
    user_login_redirect_if_nickname_duplicate($uid);
    if ($profileIncomplete) {
        flash_set('success', '欢迎回来。年级、班级与真实姓名为自愿登记，若暂不填写可稍后在个人资料页补全。');
    } else {
        flash_set('success', '欢迎回来。');
    }
    $loginReturn = isset($_SESSION['_login_return']) ? internal_redirect_target((string) $_SESSION['_login_return']) : null;
    unset($_SESSION['_login_return']);
    if (!$profileIncomplete && $loginReturn !== null) {
        redirect($loginReturn);
    }
    redirect('/');
}

function handle_login_complete_profile_get(): void
{
    $u = auth_user();
    if (!$u) {
        redirect('/login');
    }
    if (!user_login_profile_columns_ok()) {
        redirect('/');
    }
    if (!user_login_profile_incomplete_for_user_id((int) $u['id'])) {
        redirect('/');
    }
    render_page('登记在读信息', 'login_complete_profile.php');
}

function handle_login_complete_profile_post(): void
{
    csrf_verify();
    $u = auth_user();
    if (!$u) {
        redirect('/login');
    }
    if (!user_login_profile_columns_ok()) {
        redirect('/');
    }
    $uid = (int) $u['id'];
    if (!user_login_profile_incomplete_for_user_id($uid)) {
        redirect('/');
    }

    $profileGrade = trim((string) ($_POST['profile_grade'] ?? ''));
    $profileClass = trim((string) ($_POST['profile_class'] ?? ''));
    $profileRealName = trim((string) ($_POST['profile_real_name'] ?? ''));
    if ($profileGrade === '' || $profileClass === '' || $profileRealName === '') {
        flash_set('error', '请填写年级、班级与真实姓名。');
        redirect('/login/complete-profile');
    }
    if (mb_strlen($profileGrade) > 32 || mb_strlen($profileClass) > 64 || mb_strlen($profileRealName) > 32) {
        flash_set('error', '年级不超过 32 字，班级不超过 64 字，真实姓名不超过 32 字。');
        redirect('/login/complete-profile');
    }
    try {
        db()->prepare('UPDATE users SET profile_grade = ?, profile_class = ?, profile_real_name = ? WHERE id = ?')
            ->execute([$profileGrade, $profileClass, $profileRealName, $uid]);
    } catch (Throwable $e) {
        flash_set('error', '保存失败，请稍后再试。');
        redirect('/login/complete-profile');
    }

    user_login_redirect_if_nickname_duplicate($uid);
    flash_set('success', '已保存。');
    redirect('/');
}

function handle_admin_user_set_login_ban(): void
{
    require_admin_permission('users');
    csrf_verify();
    $returnQ = trim((string) ($_POST['_return_q'] ?? ''));
    $returnPage = max(1, (int) ($_POST['_return_page'] ?? 1));
    $anchor = trim((string) ($_POST['_return_anchor'] ?? ''));
    $usersUrl = admin_users_return_url($returnQ, $returnPage, $anchor);
    $id = (int) ($_POST['id'] ?? 0);
    $banUntilRaw = trim((string) ($_POST['ban_until'] ?? ''));
    if ($id <= 0) {
        redirect($usersUrl, true);
    }
    $st = db()->prepare('SELECT id, role FROM users WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $u = $st->fetch();
    if (!$u) {
        flash_set('error', '用户不存在。');
        redirect($usersUrl, true);
    }
    if ((string) ($u['role'] ?? '') === 'admin') {
        flash_set('error', '不能封禁管理员账号。');
        redirect($usersUrl, true);
    }
    try {
        if ($banUntilRaw === '') {
            db()->prepare('UPDATE users SET login_banned_until = NULL WHERE id = ?')->execute([$id]);
            admin_audit_log(auth_user(), 'user.login_ban', '解除用户登录封禁', ['target_user_id' => $id]);
            flash_set('success', '已解除该用户的登录封禁。');
        } else {
            $dt = parse_datetime_local_input($banUntilRaw);
            if ($dt === null) {
                flash_set('error', '到期时间格式无效。');
                redirect($usersUrl, true);
            }
            if ($dt->getTimestamp() <= time()) {
                db()->prepare('UPDATE users SET login_banned_until = NULL WHERE id = ?')->execute([$id]);
                admin_audit_log(auth_user(), 'user.login_ban', '登录封禁到期按解除处理', ['target_user_id' => $id]);
                flash_set('success', '到期时间早于当前时间，已按解除处理。');
                redirect($usersUrl, true);
            }
            $untilStr = $dt->format('Y-m-d H:i:s');
            db()->prepare('UPDATE users SET login_banned_until = ? WHERE id = ?')
                ->execute([$untilStr, $id]);
            admin_audit_log(auth_user(), 'user.login_ban', '设置用户登录封禁到期', ['target_user_id' => $id, 'until' => $untilStr]);
            flash_set('success', '已设置该用户登录封禁到期时间。');
        }
    } catch (Throwable $e) {
        flash_set('error', '数据库未升级：请先执行 public/database/migration_login_ip_and_bans.sql。');
    }
    redirect($usersUrl, true);
}

function handle_admin_user_save_login_profile(): void
{
    require_admin_permission('users');
    csrf_verify();
    $returnQ = trim((string) ($_POST['_return_q'] ?? ''));
    $returnPage = max(1, (int) ($_POST['_return_page'] ?? 1));
    $anchor = trim((string) ($_POST['_return_anchor'] ?? ''));
    $usersUrl = admin_users_return_url($returnQ, $returnPage, $anchor);
    if (!user_login_profile_columns_ok()) {
        flash_set('error', '请先执行数据库脚本 public/database/migration_user_login_profile.sql。');
        redirect($usersUrl, true);
    }
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        redirect($usersUrl, true);
    }
    $grade = trim((string) ($_POST['profile_grade'] ?? ''));
    $class = trim((string) ($_POST['profile_class'] ?? ''));
    $realName = trim((string) ($_POST['profile_real_name'] ?? ''));
    if (function_exists('sports_class_normalize')) {
        $class = sports_class_normalize($class);
    }
    if (mb_strlen($grade) > 32 || mb_strlen($class) > 64 || mb_strlen($realName) > 32) {
        flash_set('error', '年级、班级或真实姓名长度超限。');
        redirect($usersUrl, true);
    }
    $st = db()->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    if (!$st->fetch()) {
        flash_set('error', '用户不存在。');
        redirect($usersUrl, true);
    }
    try {
        db()->prepare('UPDATE users SET profile_grade = ?, profile_class = ?, profile_real_name = ? WHERE id = ?')
            ->execute([$grade !== '' ? $grade : null, $class !== '' ? $class : null, $realName !== '' ? $realName : null, $id]);
    } catch (Throwable $e) {
        flash_set('error', '保存失败。');
        redirect($usersUrl, true);
    }
    user_login_profile_invalidate_incomplete_cache($id);
    admin_audit_log(auth_user(), 'user.login_profile', '更新用户在读信息', [
        'target_user_id' => $id,
    ]);
    flash_set('success', '在读信息已保存。');
    redirect($usersUrl, true);
}

function handle_admin_ip_ban_set(): void
{
    require_admin_permission('users');
    csrf_verify();
    $returnQ = trim((string) ($_POST['_return_q'] ?? ''));
    $returnPage = max(1, (int) ($_POST['_return_page'] ?? 1));
    $anchor = trim((string) ($_POST['_return_anchor'] ?? ''));
    $returnView = trim((string) ($_POST['_return_view'] ?? ''));
    $returnIpBanType = trim((string) ($_POST['_return_ip_ban_type'] ?? ''));
    $usersUrl = admin_users_return_url(
        $returnQ,
        $returnPage,
        $anchor,
        $returnView === 'ip_bans' ? 'ip_bans' : '',
        $returnIpBanType
    );
    $ip = trim((string) ($_POST['ip'] ?? ''));
    $banUntilRaw = trim((string) ($_POST['ban_until'] ?? ''));
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        flash_set('error', 'IP 无效。');
        redirect($usersUrl, true);
    }
    if (!ip_bans_table_ok()) {
        flash_set('error', '数据库未升级：请先执行 public/database/migration_login_ip_and_bans.sql。');
        redirect($usersUrl, true);
    }
    $reason = trim((string) ($_POST['reason'] ?? ''));
    $admin = auth_user();
    $adminId = $admin ? (int) $admin['id'] : null;
    if ($banUntilRaw === '') {
        $until = ip_ban_indefinite_until();
    } else {
        $dt = parse_datetime_local_input($banUntilRaw);
        if ($dt === null) {
            flash_set('error', '到期时间格式无效。');
            redirect($usersUrl, true);
        }
        if ($dt->getTimestamp() <= time()) {
            flash_set('error', '到期时间必须晚于当前时间。');
            redirect($usersUrl, true);
        }
        $until = $dt->format('Y-m-d H:i:s');
    }
    try {
        db()->prepare('INSERT INTO ip_bans (ip, banned_until, reason, created_by) VALUES (?,?,?,?)')
            ->execute([$ip, $until, $reason !== '' ? $reason : null, $adminId]);
    } catch (Throwable $e) {
        flash_set('error', '写入 IP 封禁失败：请确认已执行 public/database/migration_login_ip_and_bans.sql 且表结构正常。');
        redirect($usersUrl, true);
    }
    admin_audit_log(auth_user(), 'ip_ban.set', '封禁 IP 登录', [
        'ip' => $ip,
        'until' => $until,
        'reason' => $reason !== '' ? $reason : null,
    ]);
    flash_set('success', $banUntilRaw === '' ? '已封禁该 IP 登录（不限期，可点解除）。' : '已封禁该 IP 的登录。');
    redirect($usersUrl, true);
}

function handle_admin_ip_ban_clear(): void
{
    require_admin_permission('users');
    csrf_verify();
    $returnQ = trim((string) ($_POST['_return_q'] ?? ''));
    $returnPage = max(1, (int) ($_POST['_return_page'] ?? 1));
    $anchor = trim((string) ($_POST['_return_anchor'] ?? ''));
    $returnView = trim((string) ($_POST['_return_view'] ?? ''));
    $returnIpBanType = trim((string) ($_POST['_return_ip_ban_type'] ?? ''));
    $usersUrl = admin_users_return_url(
        $returnQ,
        $returnPage,
        $anchor,
        $returnView === 'ip_bans' ? 'ip_bans' : '',
        $returnIpBanType
    );
    $ip = trim((string) ($_POST['ip'] ?? ''));
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        flash_set('error', 'IP 无效。');
        redirect($usersUrl, true);
    }
    if (!ip_bans_table_ok()) {
        flash_set('error', '数据库未升级：请先执行 public/database/migration_login_ip_and_bans.sql。');
        redirect($usersUrl, true);
    }
    db()->prepare('DELETE FROM ip_bans WHERE ip = ?')->execute([$ip]);
    admin_audit_log(auth_user(), 'ip_ban.clear', '解除 IP 登录封禁', ['ip' => $ip]);
    flash_set('success', '已解除该 IP 的登录封禁。');
    redirect($usersUrl, true);
}

function handle_geetest_register_get(): void
{
    if (!geetest_enabled()) {
        http_response_code(404);
        exit('Not Found');
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(geetest_register_payload(), JSON_UNESCAPED_UNICODE);
}

function handle_auth_rsa_meta_get(): void
{
    if (!auth_login_rsa_enabled()) {
        http_response_code(404);
        exit('Not Found');
    }
    $pub = auth_login_rsa_public_key_pem();
    $nonce = auth_login_rsa_issue_nonce();
    if ($pub === null || $nonce === null) {
        http_response_code(500);
        exit('RSA not configured.');
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'alg' => 'RSA-OAEP',
        'hash' => 'SHA-1',
        'publicKeyPem' => $pub,
        'nonce' => $nonce,
        'require' => auth_login_rsa_required(),
    ], JSON_UNESCAPED_UNICODE);
}

function handle_register_get(): void
{
    if (auth_user()) {
        redirect('/');
    }
    $pending = reg_sms_session();
    $masked = $pending ? mask_phone((string) $pending['phone']) : '';
    render_page('注册', 'register.php', compact('pending', 'masked'));
}

function handle_user_notice_get(): void
{
    render_page('用户须知', 'user_notice.php');
}

function handle_privacy_policy_get(): void
{
    render_page('隐私政策', 'privacy_policy.php');
}

function handle_about_us_get(): void
{
    render_page('关于我们', 'about_us.php');
}

function handle_register_send_sms(): void
{
    csrf_verify();
    if (auth_user()) {
        redirect('/');
    }
    if (geetest_enabled()) {
        $gc = (string) ($_POST['geetest_challenge'] ?? '');
        $gv = (string) ($_POST['geetest_validate'] ?? '');
        $gs = (string) ($_POST['geetest_seccode'] ?? '');
        $ge = geetest_validate_or_error($gc, $gv, $gs);
        if ($ge !== null) {
            flash_set('error', $ge);
            redirect('/register');
        }
    }
    if (!isset($_POST['agree_user_notice']) || (string) ($_POST['agree_user_notice'] ?? '') !== '1'
        || !isset($_POST['agree_privacy_policy']) || (string) ($_POST['agree_privacy_policy'] ?? '') !== '1') {
        flash_set('error', '请阅读并勾选同意《用户须知》与《隐私政策》后再获取验证码。');
        redirect('/register');
    }

    $now = time();
    if (!empty($_SESSION['_sms_attempt_gate']) && (int) $_SESSION['_sms_attempt_gate'] > $now) {
        $wait = (int) $_SESSION['_sms_attempt_gate'] - $now;
        flash_set('error', '操作过于频繁，请 ' . $wait . ' 秒后再试。');
        redirect('/register');
    }

    $rawPhone = (string) ($_POST['phone'] ?? '');
    $phone = normalize_phone_cn($rawPhone);
    if ($phone === null) {
        flash_set('error', '请输入有效的中国大陆 11 位手机号。');
        redirect('/register');
    }

    $ip = client_ip();
    try {
        $st = db()->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
        $st->execute([$phone]);
        if ($st->fetch()) {
            flash_set('error', '该手机号已注册，请直接登录。');
            redirect('/login');
        }

        $rateErr = sms_rate_limit_check(db(), $phone, $ip);
    } catch (PDOException $e) {
        flash_set('error', '数据库未升级：请执行 public/database/migration_register_sms.sql（users.phone 与 sms_send_log）。');
        redirect('/register');
    }
    if ($rateErr !== null) {
        flash_set('error', $rateErr);
        redirect('/register');
    }

    $cfg = sms_config();
    if ($cfg['enabled'] && $cfg['url'] === '') {
        flash_set('error', '短信服务未配置：请在 config.local.php 中填写 sms.spug_sms_url。');
        redirect('/register');
    }

    $code = sprintf('%06d', random_int(0, 999999));
    $_SESSION['_sms_attempt_gate'] = $now + 45;

    $send = sms_spug_send($phone, $code);
    if ($send !== true) {
        flash_set('error', is_string($send) ? $send : '短信发送失败，请稍后重试。');
        redirect('/register');
    }

    try {
        sms_log_insert(db(), $phone, $ip);
    } catch (PDOException $e) {
        flash_set('error', '数据库未升级：请执行 public/database/migration_register_sms.sql。');
        redirect('/register');
    }
    $_SESSION['_reg_sms'] = [
        'phone' => $phone,
        'code_hash' => password_hash($code, PASSWORD_DEFAULT),
        'expires_at' => $now + 600,
        'attempts' => 0,
    ];

    redirect('/register/welcome');
}

function handle_register_welcome_get(): void
{
    if (auth_user()) {
        redirect('/');
    }
    $s = reg_sms_session();
    if ($s === null || ($s['expires_at'] ?? 0) < time()) {
        reg_sms_clear();
        flash_set('error', '请先获取短信验证码，或验证码已过期。');
        redirect('/register');
    }
    render_page('欢迎', 'register_welcome.php', ['layout_minimal' => true]);
}

function handle_register_verify_get(): void
{
    if (auth_user()) {
        redirect('/');
    }
    $s = reg_sms_session();
    if ($s === null || ($s['expires_at'] ?? 0) < time()) {
        reg_sms_clear();
        flash_set('error', '验证码已失效，请从第一步重新获取。');
        redirect('/register');
    }
    $masked = mask_phone((string) $s['phone']);
    render_page('完成注册', 'register_verify.php', compact('masked'));
}

function handle_register_complete_post(): void
{
    csrf_verify();
    if (auth_user()) {
        redirect('/');
    }
    if (!isset($_POST['agree_user_notice']) || (string) ($_POST['agree_user_notice'] ?? '') !== '1'
        || !isset($_POST['agree_privacy_policy']) || (string) ($_POST['agree_privacy_policy'] ?? '') !== '1') {
        flash_set('error', '请阅读并勾选同意《用户须知》与《隐私政策》后再完成注册。');
        redirect('/register/verify');
    }

    $s = reg_sms_session();
    if ($s === null || ($s['expires_at'] ?? 0) < time()) {
        reg_sms_clear();
        flash_set('error', '验证码已失效，请重新注册。');
        redirect('/register');
    }

    $smsCode = preg_replace('/\s+/', '', (string) ($_POST['sms_code'] ?? ''));
    if ($smsCode === '' || strlen($smsCode) < 4 || strlen($smsCode) > 6) {
        flash_set('error', '请输入短信中的 4–6 位验证码。');
        redirect('/register/verify');
    }

    if (!password_verify($smsCode, (string) $s['code_hash'])) {
        $attempts = (int) ($s['attempts'] ?? 0) + 1;
        $_SESSION['_reg_sms']['attempts'] = $attempts;
        if ($attempts >= 8) {
            reg_sms_clear();
            flash_set('error', '验证失败次数过多，请重新获取短信验证码。');
            redirect('/register');
        }
        flash_set('error', '验证码不正确，还可尝试 ' . (8 - $attempts) . ' 次。');
        redirect('/register/verify');
    }

    $nickname = trim((string) ($_POST['nickname'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password_confirm'] ?? '');
    $phone = (string) $s['phone'];
    $email = user_placeholder_email_from_phone($phone);

    $errors = [];
    if ($nickname === '' || mb_strlen($nickname) > 64) {
        $errors[] = '昵称需在 1–64 字以内。';
    }
    if (strlen($password) < 6) {
        $errors[] = '密码至少 6 位。';
    }
    if ($password !== $password2) {
        $errors[] = '两次密码不一致。';
    }
    if ($errors) {
        flash_set('error', implode(' ', $errors));
        redirect('/register/verify');
    }

    // nickname must be unique site-wide
    $st = db()->prepare('SELECT id FROM users WHERE nickname = ? LIMIT 1');
    $st->execute([$nickname]);
    if ($st->fetch()) {
        flash_set('error', '该昵称已被使用，请换一个。');
        redirect('/register/verify');
    }

    $st = db()->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
    $st->execute([$phone]);
    if ($st->fetch()) {
        reg_sms_clear();
        flash_set('error', '该手机号已被注册。');
        redirect('/login');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        db()->prepare(
            'INSERT INTO users (email, phone, password_hash, nickname, role, banned) VALUES (?,?,?,?, \'user\', 0)'
        )->execute([$email, $phone, $hash, $nickname]);
    } catch (PDOException $e) {
        if ((int) $e->errorInfo[1] === 1062) {
            flash_set('error', '该手机号已被使用。');
        } else {
            flash_set('error', '注册失败，请确认已执行数据库迁移（含 users.phone 与 sms_send_log）。');
        }
        redirect('/register/verify');
    }

    $id = (int) db()->lastInsertId();
    reg_sms_clear();
    unset($_SESSION['_sms_attempt_gate']);
    auth_login($id);
    flash_set('success', '注册成功，欢迎加入鲁巴校园论坛。');
    redirect('/');
}

function handle_forgot_password_get(): void
{
    if (auth_user()) {
        redirect('/profile');
    }
    render_page('忘记密码', 'forgot_password.php');
}

function handle_forgot_password_send_sms(): void
{
    csrf_verify();
    if (auth_user()) {
        redirect('/profile');
    }

    $now = time();
    if (!empty($_SESSION['_pwd_reset_send_gate']) && (int) $_SESSION['_pwd_reset_send_gate'] > $now) {
        $wait = (int) $_SESSION['_pwd_reset_send_gate'] - $now;
        flash_set('error', '操作过于频繁，请 ' . $wait . ' 秒后再试。');
        redirect('/forgot-password');
    }

    $rawPhone = (string) ($_POST['phone'] ?? '');
    $phone = normalize_phone_cn($rawPhone);
    if ($phone === null) {
        flash_set('error', '请输入有效的中国大陆 11 位手机号。');
        redirect('/forgot-password');
    }

    $ip = client_ip();
    $userRow = null;
    try {
        $st = db()->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
        $st->execute([$phone]);
        $userRow = $st->fetch();
    } catch (PDOException $e) {
        flash_set('error', '数据库未升级或未支持手机号登录，请联系管理员。');
        redirect('/forgot-password');
    }

    $_SESSION['_pwd_reset_send_gate'] = $now + 45;

    if (!$userRow) {
        flash_set('success', '若该手机号已注册，您将收到短信验证码（请查收或稍等）。');
        redirect('/forgot-password');
    }

    $rateErr = sms_rate_limit_check(db(), $phone, $ip);
    if ($rateErr !== null) {
        unset($_SESSION['_pwd_reset_send_gate']);
        flash_set('error', $rateErr);
        redirect('/forgot-password');
    }

    $cfg = sms_config();
    if ($cfg['enabled'] && $cfg['url'] === '') {
        unset($_SESSION['_pwd_reset_send_gate']);
        flash_set('error', '短信服务未配置：请在 config 中填写 sms.spug_sms_url（完整地址，如 https://push.spug.cc/sms/你的密钥）。');
        redirect('/forgot-password');
    }

    $code = sprintf('%06d', random_int(0, 999999));
    $send = sms_spug_send($phone, $code);
    if ($send !== true) {
        unset($_SESSION['_pwd_reset_send_gate']);
        flash_set('error', is_string($send) ? $send : '短信发送失败，请稍后重试。');
        redirect('/forgot-password');
    }

    try {
        sms_log_insert(db(), $phone, $ip);
    } catch (PDOException $e) {
        unset($_SESSION['_pwd_reset_send_gate']);
        flash_set('error', '数据库未升级：请执行 migration_register_sms.sql（sms_send_log）。');
        redirect('/forgot-password');
    }

    $_SESSION['_pwd_reset_sms'] = [
        'user_id' => (int) $userRow['id'],
        'phone' => $phone,
        'code_hash' => password_hash($code, PASSWORD_DEFAULT),
        'expires_at' => $now + 600,
        'attempts' => 0,
    ];

    flash_set('success', '验证码已发送，请查收短信。');
    redirect('/forgot-password/reset');
}

function handle_forgot_password_reset_get(): void
{
    if (auth_user()) {
        redirect('/profile');
    }
    $s = pwd_reset_sms_session();
    if ($s === null || ($s['expires_at'] ?? 0) < time()) {
        pwd_reset_sms_clear();
        flash_set('error', '请先获取短信验证码，或验证码已过期。');
        redirect('/forgot-password');
    }
    $masked = mask_phone((string) $s['phone']);
    render_page('重置密码', 'forgot_password_reset.php', compact('masked'));
}

function handle_forgot_password_reset_post(): void
{
    csrf_verify();
    if (auth_user()) {
        redirect('/profile');
    }

    $s = pwd_reset_sms_session();
    if ($s === null || ($s['expires_at'] ?? 0) < time()) {
        pwd_reset_sms_clear();
        flash_set('error', '验证码已失效，请重新获取。');
        redirect('/forgot-password');
    }

    $smsCode = preg_replace('/\s+/', '', (string) ($_POST['sms_code'] ?? ''));
    if ($smsCode === '' || strlen($smsCode) < 4 || strlen($smsCode) > 6) {
        flash_set('error', '请输入短信中的 4–6 位验证码。');
        redirect('/forgot-password/reset');
    }

    if (!password_verify($smsCode, (string) $s['code_hash'])) {
        $attempts = (int) ($s['attempts'] ?? 0) + 1;
        $_SESSION['_pwd_reset_sms']['attempts'] = $attempts;
        if ($attempts >= 8) {
            pwd_reset_sms_clear();
            flash_set('error', '验证失败次数过多，请重新获取短信验证码。');
            redirect('/forgot-password');
        }
        flash_set('error', '验证码不正确，还可尝试 ' . (8 - $attempts) . ' 次。');
        redirect('/forgot-password/reset');
    }

    $password = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password_confirm'] ?? '');
    if (strlen($password) < 6) {
        flash_set('error', '新密码至少 6 位。');
        redirect('/forgot-password/reset');
    }
    if ($password !== $password2) {
        flash_set('error', '两次密码不一致。');
        redirect('/forgot-password/reset');
    }

    $uid = (int) $s['user_id'];
    $phone = (string) $s['phone'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ? AND phone = ?');
    $st->execute([$hash, $uid, $phone]);
    if ($st->rowCount() < 1) {
        pwd_reset_sms_clear();
        flash_set('error', '重置失败，账号可能已变更，请重试。');
        redirect('/forgot-password');
    }

    pwd_reset_sms_clear();
    flash_set('success', '密码已重置，请使用新密码登录。');
    redirect('/login');
}

function handle_profile_get(): void
{
    $u = require_login();
    $uid = (int) $u['id'];
    $likesSel = user_profile_likes_column_ok() ? ', profile_likes' : '';
    $lvlSel = user_level_columns_ok() ? ', experience, level, last_checkin_date, last_daily_post_xp_date' : '';
    $sponsorSel = user_sponsor_column_ok() ? ', is_sponsor' : '';
    $mod2Sel = user_moderator_l2_column_ok() ? ', moderator_l2' : '';
    $rnSel = user_realname_columns_ok() ? ', realname_allowed, realname_verified, realname_verified_at' : '';
    $bdSel = user_birthday_column_ok() ? ', birthday' : '';
    try {
        $st = db()->prepare(
            "SELECT id, email, phone, nickname, avatar{$likesSel}{$lvlSel}, role, banned{$mod2Sel}{$sponsorSel}{$rnSel}{$bdSel}, created_at FROM users WHERE id = ? LIMIT 1"
        );
        $st->execute([$uid]);
        $profile = $st->fetch();
    } catch (PDOException $e) {
        $st = db()->prepare(
            "SELECT id, email, nickname{$likesSel}{$sponsorSel}, role, banned{$mod2Sel}, created_at FROM users WHERE id = ? LIMIT 1"
        );
        $st->execute([$uid]);
        $profile = $st->fetch();
        if ($profile) {
            $profile['phone'] = null;
            $profile['avatar'] = null;
            if (!user_profile_likes_column_ok()) {
                $profile['profile_likes'] = null;
            }
            if (!user_sponsor_column_ok()) {
                $profile['is_sponsor'] = 0;
            }
            if (!user_moderator_l2_column_ok()) {
                $profile['moderator_l2'] = 0;
            }
            if (!user_realname_columns_ok()) {
                $profile['realname_allowed'] = 0;
                $profile['realname_verified'] = 0;
                $profile['realname_verified_at'] = null;
            }
            if (!user_birthday_column_ok()) {
                $profile['birthday'] = null;
            }
        }
    }
    if (!$profile) {
        flash_set('error', '用户不存在。');
        redirect('/');
    }
    if (!user_realname_columns_ok()) {
        $profile['realname_allowed'] = 0;
        $profile['realname_verified'] = 0;
        $profile['realname_verified_at'] = null;
    }
    if (!user_birthday_column_ok()) {
        $profile['birthday'] = null;
    }
    $profile['experience'] = (int) ($profile['experience'] ?? 0);
    $profile['level'] = (int) ($profile['level'] ?? 1);
    $profile['last_checkin_date'] = $profile['last_checkin_date'] ?? null;
    $profile['last_daily_post_xp_date'] = $profile['last_daily_post_xp_date'] ?? null;
    if (!user_sponsor_column_ok()) {
        $profile['is_sponsor'] = 0;
    }
    if (!user_moderator_l2_column_ok()) {
        $profile['moderator_l2'] = 0;
    }
    $levelBar = user_level_columns_ok() ? user_level_bar($profile['experience']) : null;

    try {
        $st = db()->prepare('SELECT COUNT(*) FROM topics WHERE user_id = ? OR real_user_id = ?');
        $st->execute([$uid, $uid]);
        $topicCount = (int) $st->fetchColumn();
        $st = db()->prepare('SELECT COUNT(*) FROM posts WHERE user_id = ? OR real_user_id = ?');
        $st->execute([$uid, $uid]);
        $postCount = (int) $st->fetchColumn();
        $st = db()->prepare(
            'SELECT t.id, t.title, t.updated_at, b.name AS board_name, b.slug AS board_slug
             FROM topics t
             JOIN boards b ON b.id = t.board_id
             WHERE t.user_id = ? OR t.real_user_id = ?
             ORDER BY t.updated_at DESC
             LIMIT 12'
        );
        $st->execute([$uid, $uid]);
        $recentTopics = $st->fetchAll();
    } catch (PDOException $e) {
        $st = db()->prepare('SELECT COUNT(*) FROM topics WHERE user_id = ?');
        $st->execute([$uid]);
        $topicCount = (int) $st->fetchColumn();
        $st = db()->prepare('SELECT COUNT(*) FROM posts WHERE user_id = ?');
        $st->execute([$uid]);
        $postCount = (int) $st->fetchColumn();
        $st = db()->prepare(
            'SELECT t.id, t.title, t.updated_at, b.name AS board_name, b.slug AS board_slug
             FROM topics t
             JOIN boards b ON b.id = t.board_id
             WHERE t.user_id = ?
             ORDER BY t.updated_at DESC
             LIMIT 12'
        );
        $st->execute([$uid]);
        $recentTopics = $st->fetchAll();
    }

    $sportsMatches = sports_matches_for_user_profile($uid);
    $anonQuota = anon_quota_display_status($uid);
    render_page('个人中心', 'profile.php', compact('profile', 'topicCount', 'postCount', 'recentTopics', 'levelBar', 'sportsMatches', 'anonQuota'));
}

function handle_profile_anon_redeem_post(): void
{
    csrf_verify();
    $user = require_login();
    $code = (string) ($_POST['anon_code'] ?? '');
    $res = anon_quota_redeem_code((int) $user['id'], $code);
    if (empty($res['ok'])) {
        flash_set('error', (string) ($res['error'] ?? '兑换失败。'));
    } else {
        flash_set('success', (string) ($res['message'] ?? '兑换成功。'));
    }
    redirect('/profile#anon-quota');
}

function handle_profile_delete_account_get(): void
{
    $u = require_login();
    render_page('注销账号', 'profile_delete.php', compact('u'));
}

function handle_profile_checkin_post(): void
{
    csrf_verify();
    $u = require_login();
    $r = user_perform_checkin((int) $u['id']);
    if (empty($r['ok'])) {
        flash_set('error', (string) ($r['error'] ?? '签到失败。'));
        redirect('/profile');
    }
    flash_set('success', '签到成功，+' . (int) ($r['xp'] ?? 0) . ' 经验值！');
    redirect('/profile');
}

function handle_messages_get(): void
{
    $u = require_login();
    $uid = (int) $u['id'];
    $items = topic_reply_notifications_list_for_user($uid);
    $tableOk = topic_reply_notifications_table_ok();
    $coupleInvites = (couple_tables_ok()) ? couple_incoming_pending_invites($uid) : [];
    $coupleInviteCount = count($coupleInvites);
    $chatUnreadOk = chat_read_at_column_ok();
    $chatUnreadThreads = $chatUnreadOk ? chat_unread_threads_for_user($uid) : [];
    $chatUnreadCount = $chatUnreadOk ? chat_unread_message_count($uid) : 0;
    render_page('消息', 'messages.php', compact(
        'items',
        'tableOk',
        'coupleInvites',
        'coupleInviteCount',
        'chatUnreadOk',
        'chatUnreadThreads',
        'chatUnreadCount'
    ));
}

function handle_messages_mark_all_read_post(): void
{
    csrf_verify();
    $u = require_login();
    $uid = (int) $u['id'];
    topic_reply_notifications_mark_all_read($uid);
    chat_mark_all_messages_read($uid);
    flash_set('success', '已全部标为已读。');
    redirect('/messages');
}

function handle_profile_avatar_post(): void
{
    csrf_verify();
    $u = require_login();
    $r = avatar_process_upload((int) $u['id']);
    if (!$r['ok']) {
        flash_set('error', $r['error'] ?? '上传失败。');
        redirect('/profile');
    }
    try {
        db()->prepare('UPDATE users SET avatar = ? WHERE id = ?')->execute([(string) $r['path'], (int) $u['id']]);
    } catch (PDOException $e) {
        flash_set('error', '数据库需升级：请执行 public/database/migration_user_avatar.sql。');
        redirect('/profile');
    }
    flash_set('success', '头像已更新。');
    redirect('/profile');
}

function handle_cos_image_upload_post(): void
{
    header('Content-Type: application/json; charset=utf-8');
    if (!csrf_check_post()) {
        http_response_code(419);
        echo json_encode(['ok' => false, 'error' => '会话已失效，请刷新页面后重试。'], JSON_UNESCAPED_UNICODE);

        return;
    }
    $u = auth_user();
    if (!$u) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => '请先登录。'], JSON_UNESCAPED_UNICODE);

        return;
    }
    if ((int) $u['banned'] === 1) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => '您已被禁言，无法上传。'], JSON_UNESCAPED_UNICODE);

        return;
    }
    if (empty($_FILES['image']) || !is_array($_FILES['image'])) {
        echo json_encode(['ok' => false, 'error' => '请选择图片文件。'], JSON_UNESCAPED_UNICODE);

        return;
    }
    $f = $_FILES['image'];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        echo json_encode(['ok' => false, 'error' => '请选择图片文件。'], JSON_UNESCAPED_UNICODE);

        return;
    }
    if (($f['error'] ?? 0) !== UPLOAD_ERR_OK) {
        echo json_encode(['ok' => false, 'error' => '上传失败，请重试。'], JSON_UNESCAPED_UNICODE);

        return;
    }
    $tmp = (string) ($f['tmp_name'] ?? '');
    $size = (int) ($f['size'] ?? 0);
    if ($tmp === '' || !is_file($tmp)) {
        echo json_encode(['ok' => false, 'error' => '临时文件无效。'], JSON_UNESCAPED_UNICODE);

        return;
    }
    $r = cos_upload_forum_image((int) $u['id'], $tmp, $size);
    if (empty($r['ok'])) {
        echo json_encode(['ok' => false, 'error' => (string) ($r['error'] ?? '上传失败。')], JSON_UNESCAPED_UNICODE);

        return;
    }
    $url = (string) ($r['url'] ?? '');
    $md = '![](' . $url . ')';
    echo json_encode(['ok' => true, 'url' => $url, 'markdown' => $md], JSON_UNESCAPED_UNICODE);
}

function handle_cos_video_upload_post(): void
{
    header('Content-Type: application/json; charset=utf-8');
    if (!csrf_check_post()) {
        http_response_code(419);
        echo json_encode(['ok' => false, 'error' => '会话已失效，请刷新页面后重试。'], JSON_UNESCAPED_UNICODE);

        return;
    }
    $u = auth_user();
    if (!$u) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => '请先登录。'], JSON_UNESCAPED_UNICODE);

        return;
    }
    if ((int) $u['banned'] === 1) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => '您已被禁言，无法上传。'], JSON_UNESCAPED_UNICODE);

        return;
    }
    if (empty($_FILES['video']) || !is_array($_FILES['video'])) {
        echo json_encode(['ok' => false, 'error' => '请选择视频文件。'], JSON_UNESCAPED_UNICODE);

        return;
    }
    $f = $_FILES['video'];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        echo json_encode(['ok' => false, 'error' => '请选择视频文件。'], JSON_UNESCAPED_UNICODE);

        return;
    }
    if (($f['error'] ?? 0) !== UPLOAD_ERR_OK) {
        echo json_encode(['ok' => false, 'error' => '上传失败，请重试。'], JSON_UNESCAPED_UNICODE);

        return;
    }
    $tmp = (string) ($f['tmp_name'] ?? '');
    $size = (int) ($f['size'] ?? 0);
    $origName = (string) ($f['name'] ?? '');
    if ($tmp === '' || !is_file($tmp)) {
        echo json_encode(['ok' => false, 'error' => '临时文件无效。'], JSON_UNESCAPED_UNICODE);

        return;
    }
    $r = cos_upload_forum_video((int) $u['id'], $tmp, $size, $origName);
    if (empty($r['ok'])) {
        echo json_encode(['ok' => false, 'error' => (string) ($r['error'] ?? '上传失败。')], JSON_UNESCAPED_UNICODE);

        return;
    }
    $url = (string) ($r['url'] ?? '');
    $md = '![](' . $url . ')';
    echo json_encode(['ok' => true, 'url' => $url, 'markdown' => $md], JSON_UNESCAPED_UNICODE);
}

function handle_user_topics_get(int $userId): void
{
    if ($userId <= 0) {
        http_response_code(404);
        render_page('未找到', 'errors/404.php');
        return;
    }
    try {
        $anonId = anonymous_user_id();
    } catch (Throwable $e) {
        $anonId = 0;
    }
    if ($anonId > 0 && $userId === $anonId) {
        http_response_code(404);
        render_page('未找到', 'errors/404.php');
        return;
    }
    $likesSel = user_profile_likes_column_ok() ? ', profile_likes' : '';
    $sponsorSel = user_sponsor_column_ok() ? ', is_sponsor' : '';
    $mod2Sel = user_moderator_l2_column_ok() ? ', moderator_l2' : '';
    $rnSel = user_realname_columns_ok() ? ', realname_verified' : '';
    try {
        $st = db()->prepare("SELECT id, nickname, avatar{$likesSel}{$sponsorSel}{$mod2Sel}{$rnSel} FROM users WHERE id = ? LIMIT 1");
        $st->execute([$userId]);
        $pubUser = $st->fetch();
    } catch (PDOException $e) {
        $st = db()->prepare(
            $sponsorSel !== '' ? "SELECT id, nickname, avatar{$sponsorSel}{$mod2Sel} FROM users WHERE id = ? LIMIT 1" : "SELECT id, nickname, avatar{$mod2Sel} FROM users WHERE id = ? LIMIT 1"
        );
        $st->execute([$userId]);
        $pubUser = $st->fetch();
        if ($pubUser) {
            $pubUser['profile_likes'] = null;
            if ($sponsorSel === '') {
                $pubUser['is_sponsor'] = 0;
            }
            if ($mod2Sel === '') {
                $pubUser['moderator_l2'] = 0;
            }
            $pubUser['realname_verified'] = 0;
        }
    }
    if ($pubUser && !user_profile_likes_column_ok()) {
        $pubUser['profile_likes'] = null;
    }
    if ($pubUser && !user_sponsor_column_ok()) {
        $pubUser['is_sponsor'] = 0;
    }
    if ($pubUser && !user_moderator_l2_column_ok()) {
        $pubUser['moderator_l2'] = 0;
    }
    if ($pubUser && !user_realname_columns_ok()) {
        $pubUser['realname_verified'] = 0;
    }
    if (!$pubUser) {
        http_response_code(404);
        render_page('未找到', 'errors/404.php');
        return;
    }
    try {
        $st = db()->prepare(
            'SELECT t.id, t.title, t.updated_at, t.created_at, b.name AS board_name, b.slug AS board_slug
             FROM topics t
             JOIN boards b ON b.id = t.board_id
             WHERE t.user_id = ? AND t.is_anonymous = 0
             ORDER BY t.updated_at DESC
             LIMIT 200'
        );
        $st->execute([$userId]);
        $topics = $st->fetchAll();
    } catch (PDOException $e) {
        $topics = [];
    }
    $viewer = auth_user();
    $chatPeerState = null;
    $couplePeerState = null;
    if ($viewer && (int) $viewer['id'] !== $userId) {
        $chatPeerState = chat_peer_state((int) $viewer['id'], $userId);
        $couplePeerState = couple_peer_state((int) $viewer['id'], $userId);
    }
    $pageTitle = (string) $pubUser['nickname'] . ' 的主题';
    render_page($pageTitle, 'user_topics.php', compact('pubUser', 'topics', 'viewer', 'chatPeerState', 'couplePeerState'));
}

function handle_profile_nickname_post(): void
{
    csrf_verify();
    $u = require_login();
    $nick = trim((string) ($_POST['nickname'] ?? ''));
    if ($nick === '' || mb_strlen($nick) > 64) {
        flash_set('error', '昵称需在 1–64 字以内。');
        redirect('/profile');
    }
    $st = db()->prepare('SELECT id FROM users WHERE nickname = ? AND id <> ? LIMIT 1');
    $st->execute([$nick, (int) $u['id']]);
    if ($st->fetch()) {
        flash_set('error', '该昵称已被使用，请换一个。');
        redirect('/profile');
    }
    db()->prepare('UPDATE users SET nickname = ? WHERE id = ?')->execute([$nick, (int) $u['id']]);
    flash_set('success', '昵称已更新。');
    redirect('/profile');
}

function handle_profile_password_post(): void
{
    csrf_verify();
    $u = require_login();
    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $new2 = (string) ($_POST['new_password_confirm'] ?? '');
    if (strlen($new) < 6) {
        flash_set('error', '新密码至少 6 位。');
        redirect('/profile');
    }
    if ($new !== $new2) {
        flash_set('error', '两次输入的新密码不一致。');
        redirect('/profile');
    }
    $st = db()->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
    $st->execute([(int) $u['id']]);
    $row = $st->fetch();
    if (!$row || !password_verify($current, (string) $row['password_hash'])) {
        flash_set('error', '当前密码不正确。');
        redirect('/profile');
    }
    $hash = password_hash($new, PASSWORD_DEFAULT);
    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, (int) $u['id']]);
    flash_set('success', '密码已修改，请牢记新密码。');
    redirect('/profile');
}

function handle_profile_birthday_post(): void
{
    csrf_verify();
    $u = require_login();
    if (!user_birthday_column_ok()) {
        flash_set('error', '数据库需升级：请执行 public/database/migration_user_birthday.sql。');
        redirect('/profile');
    }
    if (isset($_POST['birthday_clear']) && (string) $_POST['birthday_clear'] === '1') {
        db()->prepare('UPDATE users SET birthday = NULL WHERE id = ?')->execute([(int) $u['id']]);
        flash_set('success', '已清空生日。');
        redirect('/profile');
    }
    $raw = trim((string) ($_POST['birthday'] ?? ''));
    if ($raw === '') {
        flash_set('error', '请选择日期，或点击「清空生日」。');
        redirect('/profile');
    }
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $raw);
    if ($d === false || $d->format('Y-m-d') !== $raw) {
        flash_set('error', '请选择有效的生日日期。');
        redirect('/profile');
    }
    $y = (int) $d->format('Y');
    $m = (int) $d->format('n');
    $day = (int) $d->format('j');
    if (!checkdate($m, $day, $y)) {
        flash_set('error', '请选择有效的生日日期。');
        redirect('/profile');
    }
    $today = new DateTimeImmutable('today');
    if ($d > $today) {
        flash_set('error', '生日不能晚于今天。');
        redirect('/profile');
    }
    if ($y < (int) $today->format('Y') - 120) {
        flash_set('error', '生日年份不合法。');
        redirect('/profile');
    }
    db()->prepare('UPDATE users SET birthday = ? WHERE id = ?')->execute([$raw, (int) $u['id']]);
    flash_set('success', '生日已保存。生日当天全站首页会展示祝福。');
    redirect('/profile');
}

function handle_profile_likes_post(): void
{
    csrf_verify();
    $u = require_login();
    if (!user_profile_likes_column_ok()) {
        flash_set('error', '数据库需升级：请执行 public/database/migration_user_likes.sql（users.profile_likes）。');
        redirect('/profile');
    }
    $text = trim((string) ($_POST['profile_likes'] ?? ''));
    if (mb_strlen($text) > 2000) {
        flash_set('error', '「喜欢」内容请控制在 2000 字以内。');
        redirect('/profile');
    }
    if ($text !== '') {
        $nick = trim((string) ($u['nickname'] ?? ''));
        $merr = moderation_check_user_content(
            '个人喜欢:' . $text,
            $nick !== '' ? ('用户昵称:' . $nick) : ''
        );
        if ($merr !== null) {
            if (moderation_is_ai_content_rejection($merr) && moderation_appeal_try_queue((int) $u['id'], 'profile_likes', [
                'text' => $text,
            ], $merr)) {
                flash_set('success', moderation_flash_message_queued_for_human_review());
                redirect('/profile');
            }
            flash_set('error', $merr);
            redirect('/profile');
        }
    }
    $store = $text === '' ? null : $text;
    try {
        db()->prepare('UPDATE users SET profile_likes = ? WHERE id = ?')->execute([$store, (int) $u['id']]);
    } catch (PDOException $e) {
        flash_set('error', '保存失败，请确认已执行 public/database/migration_user_likes.sql。');
        redirect('/profile');
    }
    flash_set('success', '「我的喜欢」已保存，访客可在你的公开主页查看。');
    redirect('/profile');
}

function handle_profile_realname_verify_post(): void
{
    csrf_verify();
    $u = require_login();
    if (!user_realname_columns_ok()) {
        flash_set('error', '数据库需升级：请执行 public/database/migration_user_realname.sql。');
        redirect('/profile');
    }
    if (!realname_shuma_ready()) {
        flash_set('error', '实名认证服务未启用：请配置 config.local.php 的 realname.enabled 与 realname.appcode。');
        redirect('/profile');
    }

    $uid = (int) $u['id'];
    $st = db()->prepare('SELECT realname_allowed, realname_verified FROM users WHERE id = ? LIMIT 1');
    $st->execute([$uid]);
    $row = $st->fetch();
    if (!$row) {
        flash_set('error', '用户不存在。');
        redirect('/profile');
    }
    if ((int) ($row['realname_allowed'] ?? 0) !== 1) {
        flash_set('error', '你暂无实名认证资格，请联系管理员在后台开启。');
        redirect('/profile');
    }
    if ((int) ($row['realname_verified'] ?? 0) === 1) {
        flash_set('success', '你已完成实名认证，无需重复提交。');
        redirect('/profile');
    }

    $name = trim((string) ($_POST['realname_name'] ?? ''));
    $idcard = strtoupper(trim((string) ($_POST['realname_idcard'] ?? '')));
    if ($name === '' || mb_strlen($name) > 32) {
        flash_set('error', '请输入真实姓名（1–32 字）。');
        redirect('/profile');
    }
    if (!realname_plausible_id_card($idcard)) {
        flash_set('error', '身份证号格式不正确。');
        redirect('/profile');
    }
    $r = realname_shuma_verify_faceid($name, $idcard);
    if (empty($r['ok'])) {
        flash_set('error', (string) ($r['error'] ?? '实名认证失败。'));
        redirect('/profile');
    }

    // 通过后：标记已实名，并加密存储姓名/身份证号（仅供管理员审计查看）
    $nameEnc = user_realname_identity_columns_ok() && realname_storage_ready() ? realname_storage_encrypt($name) : null;
    $idEnc = user_realname_identity_columns_ok() && realname_storage_ready() ? realname_storage_encrypt($idcard) : null;
    try {
        if (user_realname_identity_columns_ok()) {
            db()->prepare('UPDATE users SET realname_verified = 1, realname_verified_at = NOW(), realname_name_enc = ?, realname_idcard_enc = ? WHERE id = ?')
                ->execute([$nameEnc, $idEnc, $uid]);
        } else {
            db()->prepare('UPDATE users SET realname_verified = 1, realname_verified_at = NOW() WHERE id = ?')->execute([$uid]);
        }
    } catch (Throwable $e) {
        db()->prepare('UPDATE users SET realname_verified = 1, realname_verified_at = NOW() WHERE id = ?')->execute([$uid]);
    }
    flash_set('success', '实名认证通过。');
    redirect('/profile');
}

function handle_profile_delete_account_post(): void
{
    csrf_verify();
    $u = require_login();
    $uid = (int) $u['id'];
    $pw = (string) ($_POST['current_password'] ?? '');
    $reason = trim((string) ($_POST['reason'] ?? ''));
    if (mb_strlen($reason) > 255) {
        $reason = mb_substr($reason, 0, 255, 'UTF-8');
    }

    // verify password
    $st = db()->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
    $st->execute([$uid]);
    $row = $st->fetch();
    if (!$row || !password_verify($pw, (string) $row['password_hash'])) {
        flash_set('error', '当前密码不正确。');
        redirect('/profile/delete-account');
    }
    if (!user_deletion_columns_ok()) {
        flash_set('error', '数据库需升级：请执行 public/database/migration_user_account_deletion.sql。');
        redirect('/profile/delete-account');
    }

    $ip = client_ip();
    $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $now = date('Y-m-d H:i:s');
    $newEmail = 'deleted_' . $uid . '_' . time() . '@example.invalid';
    $newNick = '已注销用户#' . $uid;
    $randPw = bin2hex(random_bytes(24));
    $hash = password_hash($randPw, PASSWORD_DEFAULT);

    try {
        db()->prepare(
            'UPDATE users
             SET deleted_at = ?, deleted_reason = ?, deleted_ip = ?, deleted_user_agent = ?,
                 email = ?, phone = NULL, nickname = ?, avatar = NULL, password_hash = ?,
                 login_banned_until = ?,
                 updated_at = updated_at
             WHERE id = ?'
        )->execute([$now, ($reason !== '' ? $reason : null), $ip, ($ua !== '' ? $ua : null), $newEmail, $newNick, $hash, '2999-12-31 23:59:59', $uid]);
    } catch (Throwable $e) {
        // fallback for schemas lacking some columns (best-effort)
        db()->prepare(
            'UPDATE users SET deleted_at = ?, deleted_reason = ?, deleted_ip = ?, deleted_user_agent = ?, email = ?, phone = NULL, nickname = ?, password_hash = ? WHERE id = ?'
        )->execute([$now, ($reason !== '' ? $reason : null), $ip, ($ua !== '' ? $ua : null), $newEmail, $newNick, $hash, $uid]);
    }

    auth_logout();
    flash_set('success', '账号已注销。');
    redirect('/');
}

function handle_admin_user_realname_get(): void
{
    require_admin_permission('users');
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(404);
        render_page('未找到', 'errors/404.php', [], true);
        return;
    }
    if (!user_realname_columns_ok()) {
        flash_set('error', '请先执行数据库脚本 public/database/migration_user_realname.sql。');
        redirect('/admin/users');
    }
    if (!user_realname_identity_columns_ok()) {
        flash_set('error', '请先执行数据库脚本 public/database/migration_user_realname_identity.sql。');
        redirect('/admin/users');
    }
    $st = db()->prepare('SELECT id, nickname, realname_verified, realname_verified_at, realname_name_enc, realname_idcard_enc, deleted_at FROM users WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $user = $st->fetch();
    if (!$user) {
        http_response_code(404);
        render_page('未找到', 'errors/404.php', [], true);
        return;
    }
    $name = realname_storage_decrypt($user['realname_name_enc'] ?? null);
    $idcard = realname_storage_decrypt($user['realname_idcard_enc'] ?? null);
    render_page('实名信息', 'admin/user_realname.php', compact('user', 'name', 'idcard'), true);
}

function handle_confessions_new_get(): void
{
    $u = require_login();
    if ((int) $u['banned'] === 1) {
        flash_set('error', '您已被禁言，暂时无法发送表白。');
        redirect('/');
    }
    render_page('写表白', 'confessions_new.php');
}

function handle_confessions_inbox_get(): void
{
    $u = require_login();
    $filter = (string) ($_GET['filter'] ?? 'inbox');
    if (!in_array($filter, ['inbox', 'ignored'], true)) {
        $filter = 'inbox';
    }
    try {
        if ($filter === 'ignored') {
            $st = db()->prepare(
                "SELECT c.*, fu.nickname AS from_nickname
                 FROM confessions c
                 JOIN users fu ON fu.id = c.from_user_id
                 WHERE c.to_user_id = ? AND c.status = 'ignored'
                 ORDER BY c.created_at DESC LIMIT 200"
            );
        } else {
            $st = db()->prepare(
                "SELECT c.*, fu.nickname AS from_nickname
                 FROM confessions c
                 JOIN users fu ON fu.id = c.from_user_id
                 WHERE c.to_user_id = ? AND c.status IN ('unread','read')
                 ORDER BY (c.status = 'unread') DESC, c.created_at DESC LIMIT 200"
            );
        }
        $st->execute([(int) $u['id']]);
        $items = $st->fetchAll();
    } catch (PDOException $e) {
        flash_set('error', '表白功能需先执行数据库脚本：public/database/migration_confessions.sql');
        redirect('/');
    }
    render_page('表白收件箱', 'confessions_inbox.php', compact('items', 'filter'));
}

function handle_confessions_sent_get(): void
{
    $u = require_login();
    try {
        $st = db()->prepare(
            "SELECT c.*, tu.nickname AS to_nickname, tu.phone AS to_phone
             FROM confessions c
             JOIN users tu ON tu.id = c.to_user_id
             WHERE c.from_user_id = ?
             ORDER BY c.created_at DESC LIMIT 200"
        );
        $st->execute([(int) $u['id']]);
        $items = $st->fetchAll();
    } catch (PDOException $e) {
        flash_set('error', '表白功能需先执行数据库脚本：public/database/migration_confessions.sql');
        redirect('/');
    }
    render_page('我发出的表白', 'confessions_sent.php', compact('items'));
}

function handle_confession_show(int $id): void
{
    $u = require_login();
    try {
        $st = db()->prepare(
            "SELECT c.*, fu.nickname AS from_nickname
             FROM confessions c
             JOIN users fu ON fu.id = c.from_user_id
             WHERE c.id = ? AND c.to_user_id = ? LIMIT 1"
        );
        $st->execute([$id, (int) $u['id']]);
        $c = $st->fetch();
    } catch (PDOException $e) {
        http_response_code(500);
        exit('数据库未升级表白表。');
    }
    if (!$c) {
        http_response_code(404);
        render_page('未找到', 'errors/404.php');
        return;
    }
    if ($c['status'] === 'unread') {
        db()->prepare("UPDATE confessions SET status = 'read' WHERE id = ? AND to_user_id = ?")
            ->execute([$id, (int) $u['id']]);
        $c['status'] = 'read';
    }
    render_page('表白', 'confession_view.php', compact('c'));
}

function handle_confessions_send_post(): void
{
    csrf_verify();
    $u = require_login();
    if ((int) $u['banned'] === 1) {
        flash_set('error', '您已被禁言，暂时无法发送表白。');
        redirect('/');
    }
    $targetRaw = (string) ($_POST['target'] ?? '');
    $body = trim((string) ($_POST['body'] ?? ''));
    $wantAnon = isset($_POST['anonymous']) && (string) $_POST['anonymous'] === '1';

    ['user' => $target, 'error' => $resolveErr] = confession_resolve_target($targetRaw);
    if ($resolveErr !== null) {
        flash_set('error', $resolveErr);
        redirect('/confessions/new');
    }
    if (confession_target_invalid($target)) {
        flash_set('error', '不能向该用户发送表白。');
        redirect('/confessions/new');
    }
    $tid = (int) $target['id'];
    if ($tid === (int) $u['id']) {
        flash_set('error', '不能向自己发送表白。');
        redirect('/confessions/new');
    }
    if ($body === '' || mb_strlen($body) > 2000) {
        flash_set('error', '表白内容需在 1–2000 字以内。');
        redirect('/confessions/new');
    }
    $rateErr = confession_rate_check((int) $u['id'], $tid);
    if ($rateErr !== null) {
        flash_set('error', $rateErr);
        redirect('/confessions/new');
    }
    try {
        db()->prepare(
            'INSERT INTO confessions (from_user_id, to_user_id, body, is_anonymous) VALUES (?,?,?,?)'
        )->execute([(int) $u['id'], $tid, $body, $wantAnon ? 1 : 0]);
    } catch (PDOException $e) {
        flash_set('error', '发送失败：请确认已执行 public/database/migration_confessions.sql。');
        redirect('/confessions/new');
    }
    flash_set('success', '表白已发送，对方可在「表白收件箱」中查看。');
    redirect('/confessions/sent');
}

function handle_confessions_ignore_post(): void
{
    csrf_verify();
    $u = require_login();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        redirect('/confessions');
    }
    try {
        db()->prepare(
            "UPDATE confessions SET status = 'ignored' WHERE id = ? AND to_user_id = ? AND status IN ('unread','read')"
        )->execute([$id, (int) $u['id']]);
    } catch (PDOException $e) {
        flash_set('error', '操作失败。');
        redirect('/confessions');
    }
    flash_set('success', '已忽略该条表白。');
    redirect('/confessions');
}

/** 匿名提问箱：我的提问箱主页（列表 + 新建） */
function handle_anon_ask_hub_get(): void
{
    $u = require_login();
    if (!anon_ask_tables_ok()) {
        flash_set('error', '匿名提问功能需先执行数据库脚本：public/database/migration_anon_ask.sql');
        redirect('/');
    }
    $st = db()->prepare(
        "SELECT b.*,
                (SELECT COUNT(*) FROM anon_ask_questions q WHERE q.box_id = b.id) AS q_total,
                (SELECT COUNT(*) FROM anon_ask_questions q WHERE q.box_id = b.id AND q.is_read = 0) AS q_unread,
                (SELECT COUNT(*) FROM anon_ask_questions q WHERE q.box_id = b.id AND q.status = 'pending') AS q_pending
         FROM anon_ask_boxes b
         WHERE b.user_id = ?
         ORDER BY b.created_at DESC"
    );
    $st->execute([(int) $u['id']]);
    $boxes = $st->fetchAll();

    render_page('匿名提问箱', 'ask_hub.php', compact('boxes'));
}

/** 匿名提问箱：箱主管理页（收到的提问 + 回复），提问者身份永不展示 */
function handle_anon_ask_box_get(int $boxId): void
{
    $u = require_login();
    if (!anon_ask_tables_ok()) {
        flash_set('error', '匿名提问功能需先执行数据库脚本：public/database/migration_anon_ask.sql');
        redirect('/');
    }
    $box = anon_ask_find_box_owned($boxId, (int) $u['id']);
    if (!$box) {
        http_response_code(404);
        render_page('未找到', 'errors/404.php');
        return;
    }
    $filter = (string) ($_GET['filter'] ?? 'all');
    if (!in_array($filter, ['all', 'pending', 'answered'], true)) {
        $filter = 'all';
    }
    $where = 'box_id = ? AND status <> \'hidden\'';
    if ($filter === 'pending') {
        $where = 'box_id = ? AND status = \'pending\'';
    } elseif ($filter === 'answered') {
        $where = 'box_id = ? AND status = \'answered\'';
    }
    // 注意：绝不查询 asker_user_id 对应的昵称，保护提问者匿名
    $st = db()->prepare(
        "SELECT id, box_id, content, answer, answered_at, is_public, status, is_read, created_at
         FROM anon_ask_questions
         WHERE {$where}
         ORDER BY (status = 'pending') DESC, created_at DESC
         LIMIT 300"
    );
    $st->execute([$boxId]);
    $questions = $st->fetchAll();

    // 打开管理页即视为已读
    try {
        db()->prepare('UPDATE anon_ask_questions SET is_read = 1 WHERE box_id = ? AND is_read = 0')
            ->execute([$boxId]);
    } catch (Throwable $e) {
        // ignore
    }

    $shareUrl = anon_ask_public_share_url((string) $box['token']);
    render_page($box['title'] . ' · 提问箱', 'ask_box.php', compact('box', 'questions', 'filter', 'shareUrl'));
}

/** 分享海报页（含二维码，可下载） */
function handle_anon_ask_poster_get(int $boxId): void
{
    $u = require_login();
    if (!anon_ask_tables_ok()) {
        flash_set('error', '匿名提问功能需先执行数据库脚本：public/database/migration_anon_ask.sql');
        redirect('/');
    }
    $box = anon_ask_find_box_owned($boxId, (int) $u['id']);
    if (!$box) {
        http_response_code(404);
        render_page('未找到', 'errors/404.php');
        return;
    }
    $shareUrl = anon_ask_public_share_url((string) $box['token']);
    $ownerName = (string) $u['nickname'];
    $logoUrl = function_exists('site_logo_url') ? site_logo_url() : null;
    render_page($box['title'] . ' · 分享海报', 'ask_poster.php', compact('box', 'shareUrl', 'ownerName', 'logoUrl'));
}

/** 公开提问页：任何人可看，提交需登录，提交后箱主看不到提问者 */
function handle_anon_ask_public_get(string $token): void
{
    if (!anon_ask_tables_ok()) {
        http_response_code(404);
        render_page('未找到', 'errors/404.php');
        return;
    }
    $box = anon_ask_find_box_by_token($token);
    if (!$box) {
        http_response_code(404);
        render_page('提问箱不存在', 'errors/404.php');
        return;
    }
    $current = auth_user();
    $isOwner = $current && (int) $current['id'] === (int) $box['user_id'];
    if (!$current) {
        // 记住来路：登录成功后自动回到这个提问页
        $_SESSION['_login_return'] = '/a/' . $token;
    }

    // 公开墙：箱主已选择公开的问答
    $st = db()->prepare(
        "SELECT content, answer, answered_at
         FROM anon_ask_questions
         WHERE box_id = ? AND status = 'answered' AND is_public = 1 AND answer IS NOT NULL
         ORDER BY answered_at DESC
         LIMIT 100"
    );
    $st->execute([(int) $box['id']]);
    $publicQa = $st->fetchAll();

    render_page('向 ' . $box['owner_nickname'] . ' 匿名提问', 'ask_public.php', compact('box', 'current', 'isOwner', 'publicQa'));
}

/** 站内分享链接（公开提问页） */
function anon_ask_public_share_url(string $token): string
{
    $app = $GLOBALS['APP_CONFIG']['app'] ?? [];
    $base = trim((string) ($app['public_base_url'] ?? $app['base_url'] ?? ''));
    $path = url('/a/' . $token);
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    if ($base !== '' && preg_match('#^https?://#i', $base)) {
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        $scheme = 'https';
    }
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '') {
        return $path;
    }

    return $scheme . '://' . $host . '/' . ltrim($path, '/');
}

function handle_anon_ask_create_post(): void
{
    csrf_verify();
    $u = require_login();
    if (!anon_ask_tables_ok()) {
        flash_set('error', '匿名提问功能需先执行数据库脚本：public/database/migration_anon_ask.sql');
        redirect('/ask');
    }
    if ((int) $u['banned'] === 1) {
        flash_set('error', '您已被禁言，暂时无法创建提问箱。');
        redirect('/ask');
    }
    $title = trim((string) ($_POST['title'] ?? ''));
    $intro = trim((string) ($_POST['intro'] ?? ''));
    if ($title === '' || mb_strlen($title) > ANON_ASK_TITLE_MAX_LEN) {
        flash_set('error', '提问箱标题需在 1–' . ANON_ASK_TITLE_MAX_LEN . ' 字以内。');
        redirect('/ask');
    }
    if (mb_strlen($intro) > ANON_ASK_INTRO_MAX_LEN) {
        flash_set('error', '一句话介绍不能超过 ' . ANON_ASK_INTRO_MAX_LEN . ' 字。');
        redirect('/ask');
    }
    $st = db()->prepare('SELECT COUNT(*) FROM anon_ask_boxes WHERE user_id = ?');
    $st->execute([(int) $u['id']]);
    if ((int) $st->fetchColumn() >= ANON_ASK_MAX_BOXES_PER_USER) {
        flash_set('error', '最多只能创建 ' . ANON_ASK_MAX_BOXES_PER_USER . ' 个提问箱，请先删除不用的。');
        redirect('/ask');
    }
    if (($merr = moderation_check_user_content($title, $intro)) !== null) {
        flash_set('error', $merr);
        redirect('/ask');
    }
    $token = anon_ask_generate_token();
    try {
        db()->prepare(
            'INSERT INTO anon_ask_boxes (user_id, token, title, intro) VALUES (?,?,?,?)'
        )->execute([(int) $u['id'], $token, $title, $intro !== '' ? $intro : null]);
    } catch (PDOException $e) {
        flash_set('error', '创建失败，请稍后再试。');
        redirect('/ask');
    }
    $boxId = (int) db()->lastInsertId();
    flash_set('success', '提问箱已创建，分享二维码海报，邀请大家匿名提问吧！');
    redirect('/ask/box/' . $boxId);
}

function handle_anon_ask_toggle_post(): void
{
    csrf_verify();
    $u = require_login();
    $boxId = (int) ($_POST['box_id'] ?? 0);
    $box = anon_ask_find_box_owned($boxId, (int) $u['id']);
    if (!$box) {
        flash_set('error', '提问箱不存在。');
        redirect('/ask');
    }
    $next = (int) $box['is_active'] === 1 ? 0 : 1;
    db()->prepare('UPDATE anon_ask_boxes SET is_active = ? WHERE id = ? AND user_id = ?')
        ->execute([$next, $boxId, (int) $u['id']]);
    flash_set('success', $next === 1 ? '已开启：现在可以接收新提问。' : '已暂停：新提问将被拒绝。');
    redirect('/ask/box/' . $boxId);
}

function handle_anon_ask_delete_box_post(): void
{
    csrf_verify();
    $u = require_login();
    $boxId = (int) ($_POST['box_id'] ?? 0);
    $box = anon_ask_find_box_owned($boxId, (int) $u['id']);
    if (!$box) {
        flash_set('error', '提问箱不存在。');
        redirect('/ask');
    }
    db()->prepare('DELETE FROM anon_ask_boxes WHERE id = ? AND user_id = ?')
        ->execute([$boxId, (int) $u['id']]);
    flash_set('success', '提问箱及其全部提问已删除。');
    redirect('/ask');
}

function handle_anon_ask_submit_post(): void
{
    csrf_verify();
    $u = require_login();
    if (!anon_ask_tables_ok()) {
        flash_set('error', '匿名提问功能暂不可用。');
        redirect('/');
    }
    $token = trim((string) ($_POST['token'] ?? ''));
    $box = anon_ask_find_box_by_token($token);
    if (!$box) {
        flash_set('error', '提问箱不存在。');
        redirect('/');
    }
    $backUrl = url('/a/' . $token);
    if ((int) $u['banned'] === 1) {
        flash_set('error', '您已被禁言，暂时无法提问。');
        redirect($backUrl, true);
    }
    if ((int) $box['is_active'] !== 1) {
        flash_set('error', '该提问箱已暂停接收新提问。');
        redirect($backUrl, true);
    }
    $content = trim((string) ($_POST['content'] ?? ''));
    if ($content === '' || mb_strlen($content) > ANON_ASK_QUESTION_MAX_LEN) {
        flash_set('error', '提问内容需在 1–' . ANON_ASK_QUESTION_MAX_LEN . ' 字以内。');
        redirect($backUrl, true);
    }
    $rateErr = anon_ask_submit_rate_error((int) $u['id']);
    if ($rateErr !== null) {
        flash_set('error', $rateErr);
        redirect($backUrl, true);
    }
    if (($merr = moderation_check_user_content($content)) !== null) {
        flash_set('error', $merr);
        redirect($backUrl, true);
    }
    try {
        db()->prepare(
            'INSERT INTO anon_ask_questions (box_id, asker_user_id, content, asker_ip) VALUES (?,?,?,?)'
        )->execute([(int) $box['id'], (int) $u['id'], $content, client_ip()]);
    } catch (PDOException $e) {
        flash_set('error', '提交失败，请稍后再试。');
        redirect($backUrl, true);
    }
    flash_set('success', '提问已匿名送达，对方看不到你是谁。回复后可在这里查看公开问答。');
    redirect($backUrl, true);
}

function handle_anon_ask_answer_post(): void
{
    csrf_verify();
    $u = require_login();
    $qid = (int) ($_POST['question_id'] ?? 0);
    if ($qid <= 0) {
        redirect('/ask');
    }
    // 校验该提问属于当前用户的提问箱
    $st = db()->prepare(
        'SELECT q.id, q.box_id
         FROM anon_ask_questions q
         JOIN anon_ask_boxes b ON b.id = q.box_id
         WHERE q.id = ? AND b.user_id = ? LIMIT 1'
    );
    $st->execute([$qid, (int) $u['id']]);
    $row = $st->fetch();
    if (!$row) {
        flash_set('error', '提问不存在。');
        redirect('/ask');
    }
    $boxId = (int) $row['box_id'];
    $answer = trim((string) ($_POST['answer'] ?? ''));
    $isPublic = isset($_POST['is_public']) && (string) $_POST['is_public'] === '1' ? 1 : 0;
    if ($answer === '' || mb_strlen($answer) > ANON_ASK_ANSWER_MAX_LEN) {
        flash_set('error', '回复内容需在 1–' . ANON_ASK_ANSWER_MAX_LEN . ' 字以内。');
        redirect('/ask/box/' . $boxId);
    }
    if (($merr = moderation_check_user_content($answer)) !== null) {
        flash_set('error', $merr);
        redirect('/ask/box/' . $boxId);
    }
    db()->prepare(
        "UPDATE anon_ask_questions
         SET answer = ?, is_public = ?, status = 'answered', answered_at = NOW(), is_read = 1
         WHERE id = ?"
    )->execute([$answer, $isPublic, $qid]);
    flash_set('success', $isPublic ? '已回复，并公开到问答墙。' : '已回复（仅自己可见，未公开）。');
    redirect('/ask/box/' . $boxId);
}

function handle_anon_ask_hide_question_post(): void
{
    csrf_verify();
    $u = require_login();
    $qid = (int) ($_POST['question_id'] ?? 0);
    if ($qid <= 0) {
        redirect('/ask');
    }
    $st = db()->prepare(
        'SELECT q.id, q.box_id
         FROM anon_ask_questions q
         JOIN anon_ask_boxes b ON b.id = q.box_id
         WHERE q.id = ? AND b.user_id = ? LIMIT 1'
    );
    $st->execute([$qid, (int) $u['id']]);
    $row = $st->fetch();
    if (!$row) {
        flash_set('error', '提问不存在。');
        redirect('/ask');
    }
    $boxId = (int) $row['box_id'];
    db()->prepare("UPDATE anon_ask_questions SET status = 'hidden', is_public = 0, is_read = 1 WHERE id = ?")
        ->execute([$qid]);
    flash_set('success', '已删除该提问。');
    redirect('/ask/box/' . $boxId);
}

function board_by_slug(string $slug): ?array
{
    $st = db()->prepare('SELECT * FROM boards WHERE slug = ? LIMIT 1');
    $st->execute([$slug]);
    $b = $st->fetch();
    return $b ?: null;
}

function handle_board(string $slug): void
{
    $board = board_by_slug($slug);
    if (!$board) {
        http_response_code(404);
        render_page('未找到', 'errors/404.php');
        return;
    }
    $ad = sql_topic_author_display();
    $st = db()->prepare(
        "SELECT t.*, {$ad} AS author_nickname, ru.nickname AS author_real_nickname,
                (SELECT COUNT(*) FROM posts p WHERE p.topic_id = t.id) AS reply_count,
                author_u.avatar AS author_avatar,
                CASE WHEN t.is_anonymous = 0 THEN t.user_id END AS author_public_id
         FROM topics t
         JOIN users u ON u.id = t.user_id
         LEFT JOIN users ru ON ru.id = t.real_user_id
         LEFT JOIN users author_u ON author_u.id = CASE WHEN t.is_anonymous = 0 THEN t.user_id END
         WHERE t.board_id = ?
         ORDER BY t.pinned DESC, t.updated_at DESC
         LIMIT 200"
    );
    $st->execute([(int) $board['id']]);
    $topics = $st->fetchAll();
    render_page($board['name'], 'board.php', compact('board', 'topics'));
}

function handle_topic_new(string $slug): void
{
    $user = require_login();
    if ((int) $user['banned'] === 1) {
        flash_set('error', '您已被禁言，无法发帖。');
        redirect('/board/' . $slug);
    }
    $board = board_by_slug($slug);
    if (!$board) {
        http_response_code(404);
        render_page('未找到', 'errors/404.php');
        return;
    }
    $anonQuota = anon_quota_display_status((int) $user['id']);
    render_page('发布主题', 'topic_new.php', compact('board', 'anonQuota'));
}

function handle_topic_compose_get(): void
{
    $user = require_login();
    if ((int) ($user['banned'] ?? 0) === 1) {
        flash_set('error', '您已被禁言，无法发帖。');
        redirect('/');
    }
    $boards = db()->query('SELECT id, name, slug FROM boards ORDER BY sort_order ASC, id ASC')->fetchAll();
    $anonQuota = anon_quota_display_status((int) $user['id']);
    render_page('发帖', 'topic_compose.php', compact('boards', 'anonQuota'));
}

function handle_topic_create_post(string $slug): void
{
    csrf_verify();
    $user = require_login();
    if ((int) $user['banned'] === 1) {
        flash_set('error', '您已被禁言，无法发帖。');
        redirect('/board/' . $slug);
    }
    if (geetest_enabled()) {
        $gc = (string) ($_POST['geetest_challenge'] ?? '');
        $gv = (string) ($_POST['geetest_validate'] ?? '');
        $gs = (string) ($_POST['geetest_seccode'] ?? '');
        $ge = geetest_validate_or_error($gc, $gv, $gs);
        if ($ge !== null) {
            flash_set('error', $ge);
            redirect('/board/' . $slug . '/new');
        }
    }
    $board = board_by_slug($slug);
    if (!$board) {
        http_response_code(404);
        exit('Not Found');
    }
    $fail = '/board/' . $slug . '/new';
    $title = trim((string) ($_POST['title'] ?? ''));
    $bodyText = trim((string) ($_POST['body'] ?? ''));
    $body = forum_merge_topic_body_from_post(
        $bodyText,
        (string) ($_POST['cos_image_urls'] ?? '[]'),
        (string) ($_POST['cos_video_urls'] ?? '[]')
    );
    if ($title === '' || mb_strlen($title) > 200) {
        flash_set('error', '标题需在 1–200 字。');
        redirect($fail);
    }
    if (trim($body) === '') {
        flash_set('error', '请填写正文或上传图片 / 视频。');
        redirect($fail);
    }
    $pollErr = topic_poll_validate_from_post($_POST);
    if ($pollErr !== null) {
        flash_set('error', $pollErr);
        redirect($fail);
    }
    $pollOpts = topic_poll_options_from_post($_POST);
    [$uid, $isAnon, $anonNick, $realUid] = resolve_topic_author($user, $_POST);
    $anonUseBonus = false;
    if ((int) $isAnon === 1) {
        $quotaCheck = anon_quota_assert_can_use((int) $user['id'], 'topic');
        if (empty($quotaCheck['ok'])) {
            flash_set('error', (string) ($quotaCheck['error'] ?? '匿名发帖次数不足。'));
            redirect($fail);
        }
        $anonUseBonus = !empty($quotaCheck['use_bonus']);
    }
    $nickLine = ((int) $isAnon === 1 && trim((string) $anonNick) !== '') ? ('匿名显示昵称:' . trim((string) $anonNick)) : '';
    $modChunks = ['标题:' . $title, '正文:' . $body, $nickLine];
    if ($pollOpts !== null) {
        $modChunks[] = '投票选项:' . implode('; ', $pollOpts);
    }
    $merr = moderation_check_user_content(...$modChunks);
    if ($merr !== null) {
        if (moderation_is_ai_content_rejection($merr) && moderation_appeal_try_queue((int) $user['id'], 'topic_new', [
            'board_slug' => $slug,
            'title' => $title,
            'body' => $body,
            'anonymous' => isset($_POST['anonymous']) && (string) $_POST['anonymous'] === '1',
            'display_nickname' => trim((string) ($_POST['display_nickname'] ?? '')),
        ], $merr)) {
            flash_set('success', moderation_flash_message_queued_for_human_review());
            redirect($fail);
        }
        flash_set('error', $merr);
        redirect($fail);
    }
    try {
        $st = db()->prepare(
            'INSERT INTO topics (board_id, user_id, real_user_id, title, body, is_anonymous, anon_nickname) VALUES (?,?,?,?,?,?,?)'
        );
        $st->execute([(int) $board['id'], $uid, $realUid, $title, $body, $isAnon, $anonNick]);
    } catch (PDOException $e) {
        flash_set('error', '数据库需升级：请执行 public/database/migration_anonymous_real_user.sql。');
        redirect($fail);
    }
    $tid = (int) db()->lastInsertId();
    if ((int) $isAnon === 1) {
        anon_quota_commit_use((int) $user['id'], 'topic', $anonUseBonus);
    }
    $pollAttachErr = topic_poll_try_attach_after_create($tid, $_POST);
    user_try_award_daily_post_xp((int) $user['id']);
    if ($pollAttachErr !== null) {
        flash_set('error', $pollAttachErr);
    } else {
        flash_set('success', '发帖成功。');
    }
    redirect('/topic/' . $tid);
}

function handle_topic_quick_post(): void
{
    csrf_verify();
    $user = require_login();
    if ((int) $user['banned'] === 1) {
        flash_set('error', '您已被禁言。');
        redirect('/topic/new');
    }
    if (geetest_enabled()) {
        $gc = (string) ($_POST['geetest_challenge'] ?? '');
        $gv = (string) ($_POST['geetest_validate'] ?? '');
        $gs = (string) ($_POST['geetest_seccode'] ?? '');
        $ge = geetest_validate_or_error($gc, $gv, $gs);
        if ($ge !== null) {
            flash_set('error', $ge);
            redirect('/topic/new');
        }
    }
    $slug = trim((string) ($_POST['board_slug'] ?? ''));
    $board = board_by_slug($slug);
    if (!$board) {
        flash_set('error', '请选择有效版块。');
        redirect('/topic/new');
    }
    $fail = '/topic/new';
    $title = trim((string) ($_POST['title'] ?? ''));
    $bodyText = trim((string) ($_POST['body'] ?? ''));
    $body = forum_merge_topic_body_from_post(
        $bodyText,
        (string) ($_POST['cos_image_urls'] ?? '[]'),
        (string) ($_POST['cos_video_urls'] ?? '[]')
    );
    if ($title === '' || mb_strlen($title) > 200) {
        flash_set('error', '标题需在 1–200 字。');
        redirect($fail);
    }
    if (trim($body) === '') {
        flash_set('error', '请填写正文或上传图片 / 视频。');
        redirect($fail);
    }
    $pollErr = topic_poll_validate_from_post($_POST);
    if ($pollErr !== null) {
        flash_set('error', $pollErr);
        redirect($fail);
    }
    $pollOpts = topic_poll_options_from_post($_POST);
    [$uid, $isAnon, $anonNick, $realUid] = resolve_topic_author($user, $_POST);
    $anonUseBonus = false;
    if ((int) $isAnon === 1) {
        $quotaCheck = anon_quota_assert_can_use((int) $user['id'], 'topic');
        if (empty($quotaCheck['ok'])) {
            flash_set('error', (string) ($quotaCheck['error'] ?? '匿名发帖次数不足。'));
            redirect($fail);
        }
        $anonUseBonus = !empty($quotaCheck['use_bonus']);
    }
    $nickLine = ((int) $isAnon === 1 && trim((string) $anonNick) !== '') ? ('匿名显示昵称:' . trim((string) $anonNick)) : '';
    $modChunks = ['标题:' . $title, '正文:' . $body, $nickLine];
    if ($pollOpts !== null) {
        $modChunks[] = '投票选项:' . implode('; ', $pollOpts);
    }
    $merr = moderation_check_user_content(...$modChunks);
    if ($merr !== null) {
        if (moderation_is_ai_content_rejection($merr) && moderation_appeal_try_queue((int) $user['id'], 'topic_quick', [
            'board_slug' => (string) $board['slug'],
            'title' => $title,
            'body' => $body,
            'anonymous' => isset($_POST['anonymous']) && (string) $_POST['anonymous'] === '1',
            'display_nickname' => trim((string) ($_POST['display_nickname'] ?? '')),
        ], $merr)) {
            flash_set('success', moderation_flash_message_queued_for_human_review());
            redirect($fail);
        }
        flash_set('error', $merr);
        redirect($fail);
    }
    try {
        $st = db()->prepare(
            'INSERT INTO topics (board_id, user_id, real_user_id, title, body, is_anonymous, anon_nickname) VALUES (?,?,?,?,?,?,?)'
        );
        $st->execute([(int) $board['id'], $uid, $realUid, $title, $body, $isAnon, $anonNick]);
    } catch (PDOException $e) {
        flash_set('error', '数据库需升级：请执行 public/database/migration_anonymous_real_user.sql。');
        redirect($fail);
    }
    $tid = (int) db()->lastInsertId();
    if ((int) $isAnon === 1) {
        anon_quota_commit_use((int) $user['id'], 'topic', $anonUseBonus);
    }
    $pollAttachErr = topic_poll_try_attach_after_create($tid, $_POST);
    user_try_award_daily_post_xp((int) $user['id']);
    if ($pollAttachErr !== null) {
        flash_set('error', $pollAttachErr);
    } else {
        flash_set('success', '发帖成功。');
    }
    redirect('/topic/' . $tid);
}

function handle_topic_show(int $id): void
{
    $ad = sql_topic_author_display();
    $st = db()->prepare(
        "SELECT t.*, b.name AS board_name, b.slug AS board_slug, {$ad} AS author_nickname,
                ru.nickname AS author_real_nickname,
                author_u.avatar AS author_avatar,
                CASE WHEN t.is_anonymous = 0 THEN t.user_id END AS author_public_id,
                COALESCE(t.view_count, 0) AS view_count,
                (SELECT COUNT(*) FROM topic_likes tl WHERE tl.topic_id = t.id) AS like_count
         FROM topics t
         JOIN boards b ON b.id = t.board_id
         JOIN users u ON u.id = t.user_id
         LEFT JOIN users ru ON ru.id = t.real_user_id
         LEFT JOIN users author_u ON author_u.id = CASE WHEN t.is_anonymous = 0 THEN t.user_id END
         WHERE t.id = ? LIMIT 1"
    );
    $st->execute([$id]);
    $topic = $st->fetch();
    if (!$topic) {
        http_response_code(404);
        render_page('未找到', 'errors/404.php');
        return;
    }
    // view count: count once per session per topic in 30 minutes
    $now = time();
    if (!isset($_SESSION['_topic_views']) || !is_array($_SESSION['_topic_views'])) {
        $_SESSION['_topic_views'] = [];
    }
    $seenAt = (int) ($_SESSION['_topic_views'][(string) $id] ?? 0);
    if ($seenAt <= 0 || ($now - $seenAt) > 1800) {
        $_SESSION['_topic_views'][(string) $id] = $now;
        try {
            // Keep topics.updated_at unchanged (it has ON UPDATE CURRENT_TIMESTAMP).
            db()->prepare('UPDATE topics SET view_count = COALESCE(view_count, 0) + 1, updated_at = updated_at WHERE id = ?')->execute([$id]);
            $topic['view_count'] = (int) ($topic['view_count'] ?? 0) + 1;
            if (topic_view_events_table_ok()) {
                try {
                    db()->prepare('INSERT INTO topic_view_events (topic_id) VALUES (?)')->execute([$id]);
                } catch (Throwable $e) {
                    // ignore
                }
            }
        } catch (Throwable $e) {
            // ignore if column not migrated yet
        }
    }
    $pad = sql_post_author_display();
    if (forum_posts_parent_ok()) {
        $pst = db()->prepare(
            "SELECT p.*, {$pad} AS author_nickname, ru.nickname AS author_real_nickname,
                    pu.avatar AS author_avatar,
                    CASE WHEN p.is_anonymous = 0 THEN p.user_id END AS author_public_id,
                    p.parent_post_id,
                    CASE WHEN par.id IS NULL THEN NULL
                         WHEN par.is_anonymous = 1 THEN COALESCE(NULLIF(TRIM(par.anon_nickname), ''), '匿名')
                         ELSE paru.nickname END AS parent_author_nickname,
                    parru.nickname AS parent_author_real_nickname
             FROM posts p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN users ru ON ru.id = p.real_user_id
             LEFT JOIN users pu ON pu.id = CASE WHEN p.is_anonymous = 0 THEN p.user_id END
             LEFT JOIN posts par ON par.id = p.parent_post_id
             LEFT JOIN users paru ON paru.id = par.user_id
             LEFT JOIN users parru ON parru.id = par.real_user_id
             WHERE p.topic_id = ?
             ORDER BY p.created_at ASC, p.id ASC"
        );
    } else {
        $pst = db()->prepare(
            "SELECT p.*, {$pad} AS author_nickname, ru.nickname AS author_real_nickname,
                    pu.avatar AS author_avatar,
                    CASE WHEN p.is_anonymous = 0 THEN p.user_id END AS author_public_id
             FROM posts p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN users ru ON ru.id = p.real_user_id
             LEFT JOIN users pu ON pu.id = CASE WHEN p.is_anonymous = 0 THEN p.user_id END
             WHERE p.topic_id = ?
             ORDER BY p.created_at ASC, p.id ASC"
        );
    }
    $pst->execute([$id]);
    $posts = $pst->fetchAll();
    $current = auth_user();
    $likedByMe = false;
    if ($current) {
        try {
            $st = db()->prepare('SELECT id FROM topic_likes WHERE topic_id = ? AND user_id = ? LIMIT 1');
            $st->execute([$id, (int) $current['id']]);
            $likedByMe = (bool) $st->fetch();
        } catch (Throwable $e) {
            $likedByMe = false;
        }
    }
    if ($current) {
        topic_reply_notifications_mark_topic_read((int) $current['id'], $id);
    }
    $topicPoll = null;
    if (topic_polls_table_ok()) {
        $pollUid = $current ? (int) $current['id'] : null;
        $topicPoll = topic_poll_for_topic($id, $pollUid);
    }
    $anonQuota = $current ? anon_quota_display_status((int) $current['id']) : null;
    render_page($topic['title'], 'topic.php', compact('topic', 'posts', 'current', 'likedByMe', 'topicPoll', 'anonQuota'));
}

function handle_topic_poll_vote_post(int $topicId): void
{
    csrf_verify();
    $u = require_login();
    $optionIds = topic_poll_ballots_from_post($_POST);
    $err = topic_poll_cast_vote($topicId, (int) $u['id'], $optionIds);
    if ($err !== null) {
        flash_set('error', $err);
    } else {
        flash_set('success', '投票成功。');
    }
    redirect('/topic/' . $topicId);
}

function handle_topic_poll_settings_post(int $topicId): void
{
    csrf_verify();
    $u = require_login();
    $votesPerUser = (int) ($_POST['poll_votes_per_user'] ?? 1);
    $err = topic_poll_update_votes_per_user($topicId, (int) $u['id'], $votesPerUser);
    if ($err !== null) {
        flash_set('error', $err);
    } else {
        flash_set('success', '已更新每人可投票数。');
    }
    redirect('/topic/' . $topicId);
}

function handle_topic_poll_vote_cancel_post(int $topicId): void
{
    csrf_verify();
    $u = require_login();
    $err = topic_poll_cancel_vote($topicId, (int) $u['id']);
    if ($err !== null) {
        flash_set('error', $err);
    } else {
        flash_set('success', '已取消投票，可重新选择。');
    }
    redirect('/topic/' . $topicId);
}

function handle_topic_poll_option_post(int $topicId): void
{
    csrf_verify();
    $u = require_login();
    if ((int) $u['banned'] === 1) {
        flash_set('error', '您已被禁言，无法添加投票选项。');
        redirect('/topic/' . $topicId);
    }
    $label = trim((string) ($_POST['poll_option_label'] ?? ''));
    $err = topic_poll_add_user_option($topicId, (int) $u['id'], $label);
    if ($err !== null) {
        flash_set('error', $err);
    } else {
        flash_set('success', '已添加投票选项。');
    }
    redirect('/topic/' . $topicId);
}

function handle_topic_poll_option_delete_post(int $topicId): void
{
    csrf_verify();
    $u = require_login();
    if ((int) $u['banned'] === 1) {
        flash_set('error', '您已被禁言，无法删除投票选项。');
        redirect('/topic/' . $topicId);
    }
    $optionId = (int) ($_POST['option_id'] ?? 0);
    $err = topic_poll_delete_option($topicId, (int) $u['id'], $optionId);
    if ($err !== null) {
        flash_set('error', $err);
    } else {
        flash_set('success', '已删除该投票选项。');
    }
    redirect('/topic/' . $topicId);
}

function handle_topic_like_post(int $topicId): void
{
    csrf_verify();
    $u = require_login();
    $fail = '/topic/' . $topicId;
    $st = db()->prepare('SELECT id FROM topics WHERE id = ? LIMIT 1');
    $st->execute([$topicId]);
    if (!$st->fetch()) {
        http_response_code(404);
        exit('Not Found');
    }
    try {
        $st = db()->prepare('SELECT id FROM topic_likes WHERE topic_id = ? AND user_id = ? LIMIT 1');
        $st->execute([$topicId, (int) $u['id']]);
        $row = $st->fetch();
        if ($row) {
            db()->prepare('DELETE FROM topic_likes WHERE topic_id = ? AND user_id = ?')->execute([$topicId, (int) $u['id']]);
            flash_set('success', '已取消点赞。');
        } else {
            db()->prepare('INSERT INTO topic_likes (topic_id, user_id) VALUES (?,?)')->execute([$topicId, (int) $u['id']]);
            flash_set('success', '已点赞。');
        }
    } catch (Throwable $e) {
        flash_set('error', '数据库未升级：请执行 public/database/migration_topic_likes_and_views.sql。');
    }
    redirect($fail);
}

function handle_search_get(): void
{
    $q = trim((string) ($_GET['q'] ?? ''));
    $results = [];
    if ($q !== '') {
        $esc = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
        $pat = '%' . $esc . '%';
        $ad = sql_topic_author_display();
        $st = db()->prepare(
            "SELECT t.id, t.title, t.updated_at, t.created_at, t.is_anonymous,
                    b.name AS board_name, b.slug AS board_slug,
                    {$ad} AS author_nickname,
                    COALESCE(t.view_count, 0) AS view_count,
                    (SELECT COUNT(*) FROM posts p WHERE p.topic_id = t.id) AS reply_count,
                    (SELECT COUNT(*) FROM topic_likes tl WHERE tl.topic_id = t.id) AS like_count
             FROM topics t
             JOIN boards b ON b.id = t.board_id
             JOIN users u ON u.id = t.user_id
             WHERE (t.title LIKE ? OR t.body LIKE ?)
             ORDER BY t.updated_at DESC
             LIMIT 200"
        );
        $st->execute([$pat, $pat]);
        $results = $st->fetchAll();
    }
    render_page('搜索', 'search.php', compact('q', 'results'));
}

function handle_reply_post(int $topicId): void
{
    csrf_verify();
    $fail = '/topic/' . $topicId;
    $user = require_login();
    if ((int) $user['banned'] === 1) {
        flash_set('error', '您已被禁言，无法回复。');
        redirect($fail);
    }
    $bodyText0 = trim((string) ($_POST['body'] ?? ''));
    $body0 = forum_merge_topic_body_from_post(
        $bodyText0,
        (string) ($_POST['cos_image_urls'] ?? '[]'),
        (string) ($_POST['cos_video_urls'] ?? '[]')
    );
    $spamErr = anti_spam_reply_check((int) $user['id'], $body0);
    if ($spamErr !== null) {
        access_challenge_mark_on_anti_spam_block();
        flash_set('error', $spamErr);
        redirect($fail);
    }
    $st = db()->prepare(
        'SELECT id, locked, user_id, real_user_id, is_anonymous FROM topics WHERE id = ? LIMIT 1'
    );
    $st->execute([$topicId]);
    $topic = $st->fetch();
    if (!$topic) {
        http_response_code(404);
        exit('Not Found');
    }
    if ((int) $topic['locked'] === 1) {
        flash_set('error', '主题已锁定。');
        redirect($fail);
    }
    $body = $body0;
    if (trim($body) === '') {
        flash_set('error', '回复不能为空；也可仅上传图片或视频。');
        redirect($fail);
    }
    $parentPostId = (int) ($_POST['parent_post_id'] ?? 0);
    if (!forum_posts_parent_ok()) {
        $parentPostId = 0;
    }
    if ($parentPostId > 0) {
        $st = db()->prepare('SELECT id, topic_id FROM posts WHERE id = ? LIMIT 1');
        $st->execute([$parentPostId]);
        $par = $st->fetch();
        if (!$par || (int) $par['topic_id'] !== $topicId) {
            flash_set('error', '所回复的评论不存在或不属于本主题。');
            redirect($fail);
        }
    } else {
        $parentPostId = 0;
    }

    [$uid, $isAnon, $anonNick, $realUid] = resolve_reply_author($user, $_POST);
    $anonUseBonus = false;
    if ((int) $isAnon === 1) {
        $quotaCheck = anon_quota_assert_can_use((int) $user['id'], 'reply');
        if (empty($quotaCheck['ok'])) {
            flash_set('error', (string) ($quotaCheck['error'] ?? '匿名回复次数不足。'));
            redirect($fail);
        }
        $anonUseBonus = !empty($quotaCheck['use_bonus']);
    }
    $nickLine = ((int) $isAnon === 1 && trim((string) $anonNick) !== '') ? ('匿名显示昵称:' . trim((string) $anonNick)) : '';
    $parentLine = '';
    if ($parentPostId > 0 && forum_posts_parent_ok()) {
        $st = db()->prepare(
            'SELECT p.is_anonymous, p.anon_nickname, u.nickname AS nick FROM posts p JOIN users u ON u.id = p.user_id WHERE p.id = ? LIMIT 1'
        );
        $st->execute([$parentPostId]);
        $prow = $st->fetch();
        if ($prow) {
            $pname = (int) $prow['is_anonymous'] === 1
                ? (trim((string) $prow['anon_nickname']) !== '' ? trim((string) $prow['anon_nickname']) : '匿名')
                : (string) $prow['nick'];
            $parentLine = '回复对象:@' . $pname;
        }
    }
    $merr = moderation_check_user_content(
        '回复:' . $body,
        $nickLine,
        $parentLine !== '' ? ('上下文:' . $parentLine) : ''
    );
    if ($merr !== null) {
        if (moderation_is_ai_content_rejection($merr) && moderation_appeal_try_queue((int) $user['id'], 'post_reply', [
            'topic_id' => $topicId,
            'parent_post_id' => $parentPostId,
            'body' => $body,
            'anonymous' => isset($_POST['anonymous']) && (string) $_POST['anonymous'] === '1',
            'display_nickname' => trim((string) ($_POST['display_nickname'] ?? '')),
        ], $merr)) {
            flash_set('success', moderation_flash_message_queued_for_human_review());
            redirect($fail);
        }
        flash_set('error', $merr);
        redirect($fail);
    }
    try {
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
    } catch (PDOException $e) {
        flash_set('error', '数据库需升级：请执行 public/database/migration_anonymous_real_user.sql 与 migration_posts_parent.sql。');
        redirect($fail);
    }
    $newPostId = (int) db()->lastInsertId();
    $ownerRealId = forum_row_real_author_id($topic);
    $replierRealId = $realUid !== null ? (int) $realUid : (int) $uid;
    if ($ownerRealId > 0 && $replierRealId > 0 && $ownerRealId !== $replierRealId) {
        topic_reply_notification_try_insert($ownerRealId, $topicId, $newPostId);
    }
    if ((int) $isAnon === 1) {
        anon_quota_commit_use((int) $user['id'], 'reply', $anonUseBonus);
    }
    user_try_award_daily_post_xp((int) $user['id']);
    flash_set('success', '回复已发布。');
    redirect($fail . '#post-' . $newPostId);
}

function handle_own_topic_delete(int $topicId): void
{
    csrf_verify();
    $u = require_login();
    if ($topicId <= 0) {
        redirect('/');
    }
    $st = db()->prepare(
        'SELECT t.user_id, t.real_user_id, t.is_anonymous, b.slug AS board_slug
         FROM topics t
         JOIN boards b ON b.id = t.board_id
         WHERE t.id = ? LIMIT 1'
    );
    $st->execute([$topicId]);
    $t = $st->fetch();
    if (!$t) {
        flash_set('error', '主题不存在。');
        redirect('/');
    }
    if (forum_row_real_author_id($t) !== (int) $u['id']) {
        flash_set('error', '只能删除自己发布的主题。');
        redirect('/topic/' . $topicId);
    }
    db()->prepare('DELETE FROM topics WHERE id = ?')->execute([$topicId]);
    flash_set('success', '主题已删除。');
    redirect('/board/' . $t['board_slug']);
}

function handle_own_post_delete(int $postId): void
{
    csrf_verify();
    $u = require_login();
    $topicId = (int) ($_POST['topic_id'] ?? 0);
    if ($postId <= 0 || $topicId <= 0) {
        flash_set('error', '参数无效。');
        redirect('/');
    }
    $st = db()->prepare(
        'SELECT user_id, real_user_id, is_anonymous, topic_id FROM posts WHERE id = ? LIMIT 1'
    );
    $st->execute([$postId]);
    $p = $st->fetch();
    if (!$p || (int) $p['topic_id'] !== $topicId) {
        flash_set('error', '回复不存在。');
        redirect('/topic/' . $topicId);
    }
    if (forum_row_real_author_id($p) !== (int) $u['id']) {
        flash_set('error', '只能删除自己的回复。');
        redirect('/topic/' . $topicId);
    }
    db()->prepare('DELETE FROM posts WHERE id = ?')->execute([$postId]);
    flash_set('success', '回复已删除。');
    redirect('/topic/' . $topicId);
}

function handle_chat_get(): void
{
    $u = require_login();
    if ((int) $u['banned'] === 1) {
        flash_set('error', '您已被禁言，暂时无法使用私信。');
        redirect('/');
    }
    if (!chat_tables_ok()) {
        flash_set('error', '私信功能需升级数据库：请执行 public/database/migration_chat.sql。');
        render_page('私信', 'chat/index.php', [
            'tablesOk' => false,
            'searchUser' => null,
            'searchPhoneRaw' => '',
            'incoming' => [],
            'outgoing' => [],
            'friends' => [],
        ]);
        return;
    }
    $me = (int) $u['id'];
    $searchUser = null;
    $searchPhoneRaw = trim((string) ($_GET['phone'] ?? ''));
    if ($searchPhoneRaw !== '') {
        $norm = normalize_phone_cn($searchPhoneRaw);
        if ($norm !== null) {
            try {
                $anonId = anonymous_user_id();
            } catch (Throwable $e) {
                $anonId = 0;
            }
            $st = db()->prepare('SELECT id, nickname, avatar, phone FROM users WHERE phone = ? LIMIT 1');
            $st->execute([$norm]);
            $row = $st->fetch();
            if ($row && (int) $row['id'] !== $me && ($anonId <= 0 || (int) $row['id'] !== $anonId)) {
                $searchUser = $row;
            }
        }
    }
    $st = db()->prepare(
        "SELECT r.from_user_id, r.created_at, u.nickname, u.avatar
         FROM chat_friend_requests r
         JOIN users u ON u.id = r.from_user_id
         WHERE r.to_user_id = ? AND r.status = 'pending'
         ORDER BY r.id DESC"
    );
    $st->execute([$me]);
    $incoming = $st->fetchAll();
    $st = db()->prepare(
        "SELECT r.to_user_id, r.created_at, u.nickname, u.avatar
         FROM chat_friend_requests r
         JOIN users u ON u.id = r.to_user_id
         WHERE r.from_user_id = ? AND r.status = 'pending'
         ORDER BY r.id DESC"
    );
    $st->execute([$me]);
    $outgoing = $st->fetchAll();
    $st = db()->prepare(
        'SELECT u.id, u.nickname, u.avatar FROM chat_friendships f
         JOIN users u ON u.id = f.friend_user_id
         WHERE f.user_id = ?
         ORDER BY u.nickname ASC'
    );
    $st->execute([$me]);
    $friendRows = $st->fetchAll();
    $unreadByPeer = chat_unread_counts_by_peer($me);
    $friends = [];
    foreach ($friendRows as $fr) {
        $fid = (int) $fr['id'];
        $st2 = db()->prepare(
            'SELECT body, created_at, from_user_id FROM chat_messages
             WHERE (from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?)
             ORDER BY id DESC LIMIT 1'
        );
        $st2->execute([$me, $fid, $fid, $me]);
        $last = $st2->fetch() ?: null;
        $friends[] = array_merge($fr, [
            'last_message' => $last,
            'unread_count' => $unreadByPeer[$fid] ?? 0,
        ]);
    }
    usort($friends, static function (array $a, array $b): int {
        $ta = $a['last_message']['created_at'] ?? '';
        $tb = $b['last_message']['created_at'] ?? '';
        if ($ta === $tb) {
            return strcmp((string) $a['nickname'], (string) $b['nickname']);
        }

        return strcmp((string) $tb, (string) $ta);
    });
    render_page('私信', 'chat/index.php', [
        'tablesOk' => true,
        'searchUser' => $searchUser,
        'searchPhoneRaw' => $searchPhoneRaw,
        'incoming' => $incoming,
        'outgoing' => $outgoing,
        'friends' => $friends,
    ]);
}

function handle_chat_with_get(int $peerId): void
{
    $u = require_login();
    if ((int) $u['banned'] === 1) {
        flash_set('error', '您已被禁言，暂时无法使用私信。');
        redirect('/');
    }
    $me = (int) $u['id'];
    if (!chat_tables_ok()) {
        flash_set('error', '私信功能需升级数据库：请执行 public/database/migration_chat.sql。');
        redirect('/chat');
    }
    if ($peerId <= 0 || $peerId === $me || chat_invalid_peer_user_id($peerId)) {
        flash_set('error', '无法与该用户私信。');
        redirect('/chat');
    }
    if (!chat_are_friends($me, $peerId)) {
        flash_set('error', '仅好友可发私信，请先发送好友申请并等待对方同意。');
        redirect('/chat');
    }
    $st = db()->prepare('SELECT id, nickname, avatar FROM users WHERE id = ? LIMIT 1');
    $st->execute([$peerId]);
    $peer = $st->fetch();
    if (!$peer) {
        flash_set('error', '用户不存在。');
        redirect('/chat');
    }
    $st = db()->prepare(
        'SELECT id, from_user_id, to_user_id, body, created_at FROM chat_messages
         WHERE (from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?)
         ORDER BY id ASC LIMIT 500'
    );
    $st->execute([$me, $peerId, $peerId, $me]);
    $messages = $st->fetchAll();
    chat_mark_thread_read($me, $peerId);
    render_page('与 ' . (string) $peer['nickname'] . ' 的对话', 'chat/thread.php', compact('peer', 'messages', 'me'));
}

function handle_chat_friend_request_post(): void
{
    csrf_verify();
    $u = require_login();
    if ((int) $u['banned'] === 1) {
        flash_set('error', '您已被禁言，暂时无法添加好友。');
        redirect('/chat');
    }
    if (!chat_tables_ok()) {
        flash_set('error', '数据库未升级，无法发送好友申请。');
        redirect('/chat');
    }
    $me = (int) $u['id'];
    $toId = (int) ($_POST['to_user_id'] ?? 0);
    if ($toId <= 0 || $toId === $me || chat_invalid_peer_user_id($toId)) {
        flash_set('error', '无效的用户。');
        redirect('/chat');
    }
    $st = db()->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
    $st->execute([$toId]);
    if (!$st->fetch()) {
        flash_set('error', '用户不存在。');
        redirect('/chat');
    }
    $state = chat_peer_state($me, $toId);
    if (!empty($state['is_friend'])) {
        flash_set('success', '你们已是好友，可直接私信。');
        redirect('/chat/with/' . $toId);
    }
    if (empty($state['can_request'])) {
        if (!empty($state['out_pending'])) {
            flash_set('error', '好友申请已发送，请等待对方处理。');
        } elseif (!empty($state['in_pending'])) {
            flash_set('error', '对方已向你发起申请，请到私信中心同意或拒绝。');
        } else {
            flash_set('error', '当前无法发送好友申请。');
        }
        redirect('/chat');
    }
    try {
        db()->prepare(
            "INSERT INTO chat_friend_requests (from_user_id, to_user_id, status) VALUES (?, ?, 'pending')
             ON DUPLICATE KEY UPDATE
               status = IF(status = 'declined', 'pending', status),
               updated_at = CURRENT_TIMESTAMP"
        )->execute([$me, $toId]);
    } catch (PDOException $e) {
        flash_set('error', '发送失败，请稍后重试。');
        redirect('/chat');
    }
    flash_set('success', '好友申请已发送。');
    $ref = internal_redirect_target((string) ($_POST['_ref'] ?? ''));
    if ($ref !== null) {
        redirect($ref);
    }
    redirect('/chat');
}

function handle_chat_friend_respond_post(): void
{
    csrf_verify();
    $u = require_login();
    if (!chat_tables_ok()) {
        flash_set('error', '数据库未升级。');
        redirect('/chat');
    }
    $me = (int) $u['id'];
    $fromId = (int) ($_POST['from_user_id'] ?? 0);
    $decision = (string) ($_POST['decision'] ?? '');
    if ($fromId <= 0 || $fromId === $me) {
        flash_set('error', '参数无效。');
        redirect('/chat');
    }
    if ($decision !== 'accept' && $decision !== 'decline') {
        flash_set('error', '参数无效。');
        redirect('/chat');
    }
    $st = db()->prepare(
        "SELECT id FROM chat_friend_requests WHERE from_user_id = ? AND to_user_id = ? AND status = 'pending' LIMIT 1"
    );
    $st->execute([$fromId, $me]);
    if (!$st->fetch()) {
        flash_set('error', '该申请已处理或不存在。');
        redirect('/chat');
    }
    $pdo = db();
    if ($decision === 'decline') {
        $pdo->prepare(
            "UPDATE chat_friend_requests SET status = 'declined' WHERE from_user_id = ? AND to_user_id = ? AND status = 'pending'"
        )->execute([$fromId, $me]);
        flash_set('success', '已拒绝该好友申请。');
        redirect('/chat');
    }
    try {
        $pdo->beginTransaction();
        $pdo->prepare(
            "UPDATE chat_friend_requests SET status = 'accepted' WHERE from_user_id = ? AND to_user_id = ? AND status = 'pending'"
        )->execute([$fromId, $me]);
        $pdo->prepare(
            'INSERT IGNORE INTO chat_friendships (user_id, friend_user_id) VALUES (?, ?), (?, ?)'
        )->execute([$me, $fromId, $fromId, $me]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash_set('error', '操作失败，请重试。');
        redirect('/chat');
    }
    flash_set('success', '已添加好友，可以开始聊天了。');
    redirect('/chat/with/' . $fromId);
}

function handle_chat_send_post(): void
{
    csrf_verify();
    $u = require_login();
    if ((int) $u['banned'] === 1) {
        flash_set('error', '您已被禁言，无法发送私信。');
        redirect('/chat');
    }
    if (!chat_tables_ok()) {
        flash_set('error', '数据库未升级。');
        redirect('/chat');
    }
    $me = (int) $u['id'];
    $toId = (int) ($_POST['to_user_id'] ?? 0);
    $body = trim((string) ($_POST['body'] ?? ''));
    if ($toId <= 0 || $toId === $me || chat_invalid_peer_user_id($toId)) {
        flash_set('error', '无法发送。');
        redirect('/chat');
    }
    if ($body === '' || mb_strlen($body) > 2000) {
        flash_set('error', '内容需在 1–2000 字以内。');
        redirect('/chat/with/' . $toId);
    }
    if (!chat_are_friends($me, $toId)) {
        flash_set('error', '仅好友可发私信。');
        redirect('/chat');
    }
    try {
        db()->prepare(
            'INSERT INTO chat_messages (from_user_id, to_user_id, body) VALUES (?,?,?)'
        )->execute([$me, $toId, $body]);
    } catch (PDOException $e) {
        flash_set('error', '发送失败。');
        redirect('/chat/with/' . $toId);
    }
    redirect('/chat/with/' . $toId);
}

function handle_admin_chat_get(): void
{
    require_admin_permission('chat');
    if (!chat_tables_ok()) {
        flash_set('error', '数据库未包含私信表，请先执行 migration_chat.sql。');
        redirect('/admin');
    }
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $per = 80;
    $off = ($page - 1) * $per;
    $st = db()->query('SELECT COUNT(*) FROM chat_messages');
    $total = (int) $st->fetchColumn();
    $st = db()->prepare(
        'SELECT m.id, m.from_user_id, m.to_user_id, m.body, m.created_at,
                fu.nickname AS from_nickname, tu.nickname AS to_nickname
         FROM chat_messages m
         JOIN users fu ON fu.id = m.from_user_id
         JOIN users tu ON tu.id = m.to_user_id
         ORDER BY m.id DESC
         LIMIT ' . (int) $per . ' OFFSET ' . (int) $off
    );
    $st->execute();
    $rows = $st->fetchAll();
    $pages = max(1, (int) ceil($total / $per));
    render_page('私信审计', 'admin/chat.php', compact('rows', 'page', 'pages', 'total'), true);
}

function handle_admin_moderation_get(): void
{
    require_content_reviewer();
    $appeals = moderation_appeals_list_with_meta(120);
    $tableOk = moderation_appeals_table_ok();
    $viewerId = (int) (auth_user()['id'] ?? 0);
    render_page('人工复核', 'admin/moderation.php', compact('appeals', 'tableOk', 'viewerId'), true);
}

function handle_admin_moderation_vote_post(): void
{
    csrf_verify();
    $u = require_content_reviewer();
    $id = (int) ($_POST['appeal_id'] ?? 0);
    $dec = trim((string) ($_POST['decision'] ?? ''));
    if ($id <= 0 || ($dec !== 'approve' && $dec !== 'reject')) {
        flash_set('error', '参数无效。');
        redirect('/admin/moderation');
    }
    $r = moderation_appeal_cast_vote($id, (int) $u['id'], $dec);
    admin_audit_log($u, 'moderation.vote', (string) ($r['message'] ?? ''), [
        'appeal_id' => $id,
        'decision' => $dec,
        'ok' => !empty($r['ok']),
    ]);
    flash_set($r['ok'] ? 'success' : 'error', $r['message']);
    redirect('/admin/moderation');
}

function handle_admin_user_toggle_moderator_l2(): void
{
    require_admin();
    csrf_verify();
    $returnQ = trim((string) ($_POST['_return_q'] ?? ''));
    $returnPage = max(1, (int) ($_POST['_return_page'] ?? 1));
    $anchor = trim((string) ($_POST['_return_anchor'] ?? ''));
    $usersUrl = admin_users_return_url($returnQ, $returnPage, $anchor);
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        redirect($usersUrl, true);
    }
    $permCol = user_moderator_l2_perms_column_ok();
    $sql = $permCol
        ? 'SELECT id, role, moderator_l2, moderator_l2_perms FROM users WHERE id = ? LIMIT 1'
        : 'SELECT id, role, moderator_l2 FROM users WHERE id = ? LIMIT 1';
    $st = db()->prepare($sql);
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row || (string) $row['role'] === 'admin') {
        flash_set('error', '不能修改管理员账号的二审标记。');
        redirect($usersUrl, true);
    }
    try {
        $cur = (int) ($row['moderator_l2'] ?? 0);
        if ($cur === 1) {
            db()->prepare('UPDATE users SET moderator_l2 = 0 WHERE id = ? AND role <> ?')->execute([$id, 'admin']);
        } elseif ($permCol) {
            $p = trim((string) ($row['moderator_l2_perms'] ?? ''));
            $def = json_encode(['moderation' => true, 'anon_identity' => true], JSON_UNESCAPED_UNICODE);
            if ($p === '') {
                db()->prepare('UPDATE users SET moderator_l2 = 1, moderator_l2_perms = ? WHERE id = ? AND role <> ?')->execute([$def, $id, 'admin']);
            } else {
                db()->prepare('UPDATE users SET moderator_l2 = 1 WHERE id = ? AND role <> ?')->execute([$id, 'admin']);
            }
        } else {
            db()->prepare('UPDATE users SET moderator_l2 = 1 WHERE id = ? AND role <> ?')->execute([$id, 'admin']);
        }
    } catch (Throwable $e) {
        flash_set('error', '请先执行数据库脚本 public/database/migration_moderation_appeals.sql。');
        redirect($usersUrl, true);
    }
    $after = (int) ($row['moderator_l2'] ?? 0) === 1 ? 0 : 1;
    admin_audit_log(auth_user(), 'user.moderator_l2', $after ? '设为二级管理员' : '取消二级管理员', ['target_user_id' => $id, 'moderator_l2' => $after]);
    flash_set('success', '二级管理员状态已更新。');
    redirect($usersUrl, true);
}

function handle_admin_user_save_moderator_l2_perms(): void
{
    require_admin();
    csrf_verify();
    $returnQ = trim((string) ($_POST['_return_q'] ?? ''));
    $returnPage = max(1, (int) ($_POST['_return_page'] ?? 1));
    $anchor = trim((string) ($_POST['_return_anchor'] ?? ''));
    $usersUrl = admin_users_return_url($returnQ, $returnPage, $anchor);
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        redirect($usersUrl, true);
    }
    if (!user_moderator_l2_perms_column_ok()) {
        flash_set('error', '请先执行数据库脚本 public/database/migration_moderator_l2_permissions.sql。');
        redirect($usersUrl, true);
    }
    $st = db()->prepare('SELECT id, role, moderator_l2 FROM users WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row || (string) $row['role'] === 'admin' || (int) ($row['moderator_l2'] ?? 0) !== 1) {
        flash_set('error', '只能为已设为二级管理员的用户配置权限。');
        redirect($usersUrl, true);
    }
    $data = [];
    foreach (admin_l2_permission_keys() as $k) {
        $data[$k] = !empty($_POST['perm_' . $k]);
    }
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    db()->prepare('UPDATE users SET moderator_l2_perms = ? WHERE id = ?')->execute([$json, $id]);
    admin_audit_log(auth_user(), 'user.moderator_l2_perms', '保存二级管理员权限', ['target_user_id' => $id, 'perms' => $data]);
    flash_set('success', '二级管理员权限已保存。');
    redirect($usersUrl, true);
}

function handle_admin_user_toggle_sponsor(): void
{
    require_admin_permission('users');
    csrf_verify();
    $returnQ = trim((string) ($_POST['_return_q'] ?? ''));
    $returnPage = max(1, (int) ($_POST['_return_page'] ?? 1));
    $anchor = trim((string) ($_POST['_return_anchor'] ?? ''));
    $usersUrl = admin_users_return_url($returnQ, $returnPage, $anchor);
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        redirect($usersUrl, true);
    }
    $st = db()->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    if (!$st->fetch()) {
        flash_set('error', '用户不存在。');
        redirect($usersUrl, true);
    }
    if (!user_sponsor_column_ok()) {
        flash_set('error', '请先执行数据库脚本 public/database/migration_user_sponsor.sql。');
        redirect($usersUrl, true);
    }
    try {
        db()->prepare('UPDATE users SET is_sponsor = 1 - COALESCE(is_sponsor, 0) WHERE id = ?')->execute([$id]);
    } catch (Throwable $e) {
        flash_set('error', '赞助标记更新失败。');
        redirect($usersUrl, true);
    }
    admin_audit_log(auth_user(), 'user.sponsor', '切换赞助展示标记', ['target_user_id' => $id]);
    flash_set('success', '赞助者状态已更新。');
    redirect($usersUrl, true);
}

function handle_admin_user_toggle_realname_allowed(): void
{
    require_admin_permission('users');
    csrf_verify();
    $returnQ = trim((string) ($_POST['_return_q'] ?? ''));
    $returnPage = max(1, (int) ($_POST['_return_page'] ?? 1));
    $anchor = trim((string) ($_POST['_return_anchor'] ?? ''));
    $usersUrl = admin_users_return_url($returnQ, $returnPage, $anchor);
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        redirect($usersUrl, true);
    }
    $st = db()->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    if (!$st->fetch()) {
        flash_set('error', '用户不存在。');
        redirect($usersUrl, true);
    }
    if (!user_realname_columns_ok()) {
        flash_set('error', '请先执行数据库脚本 public/database/migration_user_realname.sql。');
        redirect($usersUrl, true);
    }
    try {
        db()->prepare('UPDATE users SET realname_allowed = 1 - COALESCE(realname_allowed, 0) WHERE id = ?')->execute([$id]);
    } catch (Throwable $e) {
        flash_set('error', '实名认证资格更新失败。');
        redirect($usersUrl, true);
    }
    admin_audit_log(auth_user(), 'user.realname_allowed', '切换实名认证资格', ['target_user_id' => $id]);
    flash_set('success', '实名认证资格已更新。');
    redirect($usersUrl, true);
}

function handle_admin_anon_codes_get(): void
{
    require_admin_permission('users');
    $codes = anon_quota_redeem_ok() ? anon_quota_admin_list_codes(80) : [];
    $anonQuotaSetup = [
        'limits_ok' => anon_quota_limits_ok(),
        'redeem_ok' => anon_quota_redeem_ok(),
    ];
    render_page('匿名兑换码', 'admin/anon_codes.php', compact('codes', 'anonQuotaSetup'), true);
}

function handle_admin_anon_codes_generate_post(): void
{
    require_admin_permission('users');
    csrf_verify();
    if (!anon_quota_redeem_ok()) {
        flash_set('error', '兑换码表未就绪：请在数据库执行 migration_anon_quota.sql 中的 CREATE TABLE 两段（users 字段若已存在可跳过 ALTER）。');
        redirect('/admin/anon-codes');
    }
    $admin = auth_user();
    $res = anon_quota_admin_create_codes((int) $admin['id'], [
        'kind' => (string) ($_POST['kind'] ?? ''),
        'topic_grants' => (int) ($_POST['topic_grants'] ?? 0),
        'reply_grants' => (int) ($_POST['reply_grants'] ?? 0),
        'max_redemptions' => (int) ($_POST['max_redemptions'] ?? 1),
        'batch' => (int) ($_POST['batch'] ?? 1),
        'note' => (string) ($_POST['note'] ?? ''),
        'expires_at' => (string) ($_POST['expires_at'] ?? ''),
    ]);
    if (empty($res['ok'])) {
        flash_set('error', (string) ($res['error'] ?? '生成失败。'));
        redirect('/admin/anon-codes');
    }
    $codes = $res['codes'] ?? [];
    admin_audit_log($admin, 'anon_redeem.generate', '生成匿名兑换码', [
        'count' => count($codes),
        'kind' => (string) ($_POST['kind'] ?? ''),
    ]);
    $msg = '已生成 ' . count($codes) . ' 个兑换码：' . implode('、', $codes);
    if (mb_strlen($msg) > 500) {
        $msg = '已生成 ' . count($codes) . ' 个兑换码（详见列表）。';
    }
    flash_set('success', $msg);
    redirect('/admin/anon-codes');
}

function handle_admin_dashboard(): void
{
    $u = require_login();
    if (!user_can_access_admin_backend($u)) {
        http_response_code(403);
        exit('无权访问。');
    }
    $permKeys = admin_l2_permission_keys();
    if (user_is_super_admin($u)) {
        $adminPerms = array_fill_keys($permKeys, true);
    } else {
        $adminPerms = user_effective_l2_permissions($u);
    }
    if (!user_is_super_admin($u) && empty($adminPerms['dashboard'])) {
        $routes = [
            'moderation' => '/admin/moderation',
            'users' => '/admin/users',
            'boards' => '/admin/boards',
            'chat' => '/admin/chat',
            'sports_meet' => '/admin/sports-meet',
        ];
        foreach ($routes as $key => $dest) {
            if (!empty($adminPerms[$key])) {
                redirect($dest);
            }
        }
        if (!empty($adminPerms['content'])) {
            flash_set('error', '您仅有「删除主题/回复」权限，请从版块进入具体主题操作。');
            redirect('/');
        }
        http_response_code(403);
        exit('无权访问。');
    }
    $moderationPending = moderation_appeals_table_ok() ? moderation_appeals_pending_count() : 0;
    $announcement = site_announcement_table_ok() ? site_announcement_get() : ['enabled' => 0, 'body' => ''];
    $shutdown = site_shutdown_table_ok() ? site_shutdown_get() : ['enabled' => 0, 'message' => '', 'eta' => ''];
    render_page('管理后台', 'admin/dashboard.php', compact('moderationPending', 'adminPerms', 'announcement', 'shutdown'), true);
}

function handle_admin_sports_meet_get(): void
{
    require_admin_permission('sports_meet');
    if (!sports_meet_tables_ok()) {
        flash_set('error', '数据库未升级：请执行 public/database/migration_sports_meet.sql。');
        redirect('/admin');
    }
    $meetId = max(0, (int) ($_GET['meet_id'] ?? 0));
    $meets = sports_meet_list();
    if ($meetId <= 0 && !empty($meets)) {
        $meetId = (int) $meets[0]['id'];
    }
    $events = sports_event_list_by_meet($meetId);
    $entries = sports_entry_list_by_meet($meetId);
    render_page('运动会管理', 'admin/sports_meet.php', compact('meets', 'meetId', 'events', 'entries'), true);
}

function handle_admin_sports_meet_save(): void
{
    require_admin_permission('sports_meet');
    csrf_verify();
    if (!sports_meet_tables_ok()) {
        flash_set('error', '数据库未升级：请执行 public/database/migration_sports_meet.sql。');
        redirect('/admin');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $title = trim((string) ($_POST['title'] ?? ''));
    $startsAt = trim((string) ($_POST['starts_at'] ?? ''));
    $endsAt = trim((string) ($_POST['ends_at'] ?? ''));
    $isActive = isset($_POST['is_active']) && (string) $_POST['is_active'] === '1' ? 1 : 0;
    if ($title === '' || $startsAt === '' || $endsAt === '') {
        flash_set('error', '请完整填写运动会名称、开始时间和结束时间。');
        redirect('/admin/sports-meet');
    }
    if (mb_strlen($title) > 120) {
        flash_set('error', '运动会名称过长（最多 120 字）。');
        redirect('/admin/sports-meet');
    }
    if (strtotime($startsAt) === false || strtotime($endsAt) === false || strtotime($startsAt) > strtotime($endsAt)) {
        flash_set('error', '运动会时间范围无效，请检查开始/结束时间。');
        redirect('/admin/sports-meet');
    }
    if ($id > 0) {
        $st = db()->prepare('UPDATE sports_meets SET title = ?, starts_at = ?, ends_at = ?, is_active = ? WHERE id = ?');
        $st->execute([$title, $startsAt, $endsAt, $isActive, $id]);
        $meetId = $id;
    } else {
        $st = db()->prepare('INSERT INTO sports_meets (title, starts_at, ends_at, is_active) VALUES (?, ?, ?, ?)');
        $st->execute([$title, $startsAt, $endsAt, $isActive]);
        $meetId = (int) db()->lastInsertId();
    }
    admin_audit_log(auth_user(), 'sports_meet.save', '保存运动会基本信息', ['meet_id' => $meetId, 'title' => $title, 'active' => $isActive]);
    flash_set('success', '运动会信息已保存。');
    redirect('/admin/sports-meet?meet_id=' . $meetId);
}

function handle_admin_sports_event_save(): void
{
    require_admin_permission('sports_meet');
    csrf_verify();
    if (!sports_meet_tables_ok()) {
        flash_set('error', '数据库未升级：请执行 public/database/migration_sports_meet.sql。');
        redirect('/admin');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $meetId = (int) ($_POST['meet_id'] ?? 0);
    $eventName = trim((string) ($_POST['event_name'] ?? ''));
    $startsAt = trim((string) ($_POST['starts_at'] ?? ''));
    $endsAt = trim((string) ($_POST['ends_at'] ?? ''));
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    if ($meetId <= 0 || $eventName === '' || $startsAt === '' || $endsAt === '') {
        flash_set('error', '请完整填写项目所属运动会、项目名称与时间。');
        redirect('/admin/sports-meet' . ($meetId > 0 ? '?meet_id=' . $meetId : ''));
    }
    if (mb_strlen($eventName) > 120) {
        flash_set('error', '项目名称过长（最多 120 字）。');
        redirect('/admin/sports-meet?meet_id=' . $meetId);
    }
    if (strtotime($startsAt) === false || strtotime($endsAt) === false || strtotime($startsAt) > strtotime($endsAt)) {
        flash_set('error', '项目时间范围无效，请检查开始/结束时间。');
        redirect('/admin/sports-meet?meet_id=' . $meetId);
    }
    if ($id > 0) {
        $st = db()->prepare('UPDATE sports_events SET event_name = ?, starts_at = ?, ends_at = ?, sort_order = ? WHERE id = ? AND meet_id = ?');
        $st->execute([$eventName, $startsAt, $endsAt, $sortOrder, $id, $meetId]);
    } else {
        $st = db()->prepare('INSERT INTO sports_events (meet_id, event_name, starts_at, ends_at, sort_order) VALUES (?, ?, ?, ?, ?)');
        $st->execute([$meetId, $eventName, $startsAt, $endsAt, $sortOrder]);
        $id = (int) db()->lastInsertId();
    }
    admin_audit_log(auth_user(), 'sports_event.save', '保存运动会项目', ['meet_id' => $meetId, 'event_id' => $id, 'event_name' => $eventName]);
    flash_set('success', '项目已保存。');
    redirect('/admin/sports-meet?meet_id=' . $meetId);
}

function handle_admin_sports_event_delete(): void
{
    require_admin_permission('sports_meet');
    csrf_verify();
    if (!sports_meet_tables_ok()) {
        flash_set('error', '数据库未升级：请执行 public/database/migration_sports_meet.sql。');
        redirect('/admin');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $meetId = (int) ($_POST['meet_id'] ?? 0);
    if ($id <= 0 || $meetId <= 0) {
        redirect('/admin/sports-meet');
    }
    db()->prepare('DELETE FROM sports_events WHERE id = ? AND meet_id = ?')->execute([$id, $meetId]);
    admin_audit_log(auth_user(), 'sports_event.delete', '删除运动会项目', ['meet_id' => $meetId, 'event_id' => $id]);
    flash_set('success', '项目已删除。');
    redirect('/admin/sports-meet?meet_id=' . $meetId);
}

function handle_admin_sports_entry_save(): void
{
    require_admin_permission('sports_meet');
    csrf_verify();
    if (!sports_meet_tables_ok()) {
        flash_set('error', '数据库未升级：请执行 public/database/migration_sports_meet.sql。');
        redirect('/admin');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $meetId = (int) ($_POST['meet_id'] ?? 0);
    $eventId = (int) ($_POST['event_id'] ?? 0);
    $grade = trim((string) ($_POST['grade_name'] ?? ''));
    $className = sports_class_normalize((string) ($_POST['class_name'] ?? ''));
    $studentName = trim((string) ($_POST['student_name'] ?? ''));
    $resultText = trim((string) ($_POST['result_text'] ?? ''));
    $achievement = trim((string) ($_POST['achievement_text'] ?? ''));
    if ($meetId <= 0 || $eventId <= 0 || $grade === '' || $className === '' || $studentName === '') {
        flash_set('error', '请完整填写参赛记录（年级、班级、姓名、项目）。');
        redirect('/admin/sports-meet' . ($meetId > 0 ? '?meet_id=' . $meetId : ''));
    }
    $chk = db()->prepare('SELECT id FROM sports_events WHERE id = ? AND meet_id = ? LIMIT 1');
    $chk->execute([$eventId, $meetId]);
    if (!$chk->fetch()) {
        flash_set('error', '所选项目不存在或不属于当前运动会。');
        redirect('/admin/sports-meet?meet_id=' . $meetId);
    }
    if (mb_strlen($grade) > 32 || mb_strlen($className) > 64 || mb_strlen($studentName) > 32) {
        flash_set('error', '年级、班级或姓名长度超限。');
        redirect('/admin/sports-meet?meet_id=' . $meetId);
    }
    if (mb_strlen($resultText) > 120 || mb_strlen($achievement) > 300) {
        flash_set('error', '成绩或成就信息过长。');
        redirect('/admin/sports-meet?meet_id=' . $meetId);
    }
    if ($id > 0) {
        $st = db()->prepare(
            'UPDATE sports_entries
             SET event_id = ?, grade_name = ?, class_name = ?, student_name = ?, result_text = ?, achievement_text = ?
             WHERE id = ? AND meet_id = ?'
        );
        $st->execute([$eventId, $grade, $className, $studentName, $resultText !== '' ? $resultText : null, $achievement !== '' ? $achievement : null, $id, $meetId]);
    } else {
        $st = db()->prepare(
            'INSERT INTO sports_entries (meet_id, event_id, grade_name, class_name, student_name, result_text, achievement_text)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([$meetId, $eventId, $grade, $className, $studentName, $resultText !== '' ? $resultText : null, $achievement !== '' ? $achievement : null]);
    }
    admin_audit_log(auth_user(), 'sports_entry.save', '保存参赛记录', ['meet_id' => $meetId, 'event_id' => $eventId, 'student_name' => $studentName]);
    flash_set('success', '参赛记录已保存。');
    redirect('/admin/sports-meet?meet_id=' . $meetId);
}

function handle_admin_sports_entry_delete(): void
{
    require_admin_permission('sports_meet');
    csrf_verify();
    if (!sports_meet_tables_ok()) {
        flash_set('error', '数据库未升级：请执行 public/database/migration_sports_meet.sql。');
        redirect('/admin');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $meetId = (int) ($_POST['meet_id'] ?? 0);
    if ($id <= 0 || $meetId <= 0) {
        redirect('/admin/sports-meet');
    }
    db()->prepare('DELETE FROM sports_entries WHERE id = ? AND meet_id = ?')->execute([$id, $meetId]);
    admin_audit_log(auth_user(), 'sports_entry.delete', '删除参赛记录', ['meet_id' => $meetId, 'entry_id' => $id]);
    flash_set('success', '参赛记录已删除。');
    redirect('/admin/sports-meet?meet_id=' . $meetId);
}

function handle_admin_sports_entry_import_bulk(): void
{
    require_admin_permission('sports_meet');
    csrf_verify();
    if (!sports_meet_tables_ok()) {
        flash_set('error', '数据库未升级：请执行 public/database/migration_sports_meet.sql。');
        redirect('/admin');
    }
    $meetId = (int) ($_POST['meet_id'] ?? 0);
    $eventId = (int) ($_POST['event_id'] ?? 0);
    $grade = trim((string) ($_POST['grade_name'] ?? ''));
    $bulk = (string) ($_POST['bulk_text'] ?? '');

    if ($meetId <= 0 || $eventId <= 0 || $grade === '') {
        flash_set('error', '请填写本批统一的年级、并选择要导入到哪个项目。');
        redirect('/admin/sports-meet' . ($meetId > 0 ? '?meet_id=' . $meetId : ''));
    }
    if (mb_strlen($grade) > 32) {
        flash_set('error', '年级长度过长。');
        redirect('/admin/sports-meet?meet_id=' . $meetId);
    }
    if (trim($bulk) === '') {
        flash_set('error', '请粘贴要导入的名单（每行：姓名 班级，如 游晨轩 17；多写的「班」字会自动去掉）。');
        redirect('/admin/sports-meet?meet_id=' . $meetId);
    }
    $chk = db()->prepare('SELECT id FROM sports_events WHERE id = ? AND meet_id = ? LIMIT 1');
    $chk->execute([$eventId, $meetId]);
    if (!$chk->fetch()) {
        flash_set('error', '所选项目不存在或不属于当前运动会。');
        redirect('/admin/sports-meet?meet_id=' . $meetId);
    }

    $lines = preg_split("/\r\n|\n|\r/", $bulk);
    $inserted = 0;
    $skippedDup = 0;
    $invalid = [];
    $invalidCount = 0;
    $dupSt = db()->prepare(
        'SELECT id FROM sports_entries WHERE meet_id = ? AND event_id = ? AND grade_name = ? AND class_name = ? AND student_name = ? LIMIT 1'
    );
    $ins = db()->prepare(
        'INSERT INTO sports_entries (meet_id, event_id, grade_name, class_name, student_name, result_text, achievement_text)
         VALUES (?, ?, ?, ?, ?, NULL, NULL)'
    );

    $pdo = db();
    try {
        $pdo->beginTransaction();
        foreach ($lines as $rawLine) {
            $line = trim((string) $rawLine);
            if ($line === '') {
                continue;
            }
            if (str_starts_with($line, '#') || str_starts_with($line, '//')) {
                continue;
            }
            if (!preg_match('/^(.+?)\s+(\S+)\s*$/u', $line, $m)) {
                $invalidCount++;
                if (count($invalid) < 5) {
                    $invalid[] = $line;
                }
                continue;
            }
            $studentName = trim($m[1]);
            $className = sports_class_normalize($m[2]);
            if ($studentName === '' || $className === '' || mb_strlen($studentName) > 32 || mb_strlen($className) > 64) {
                $invalidCount++;
                if (count($invalid) < 5) {
                    $invalid[] = $line;
                }
                continue;
            }
            $dupSt->execute([$meetId, $eventId, $grade, $className, $studentName]);
            if ($dupSt->fetch()) {
                $skippedDup++;
                continue;
            }
            $ins->execute([$meetId, $eventId, $grade, $className, $studentName]);
            $inserted++;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash_set('error', '导入失败，请重试。');
        redirect('/admin/sports-meet?meet_id=' . $meetId);
    }

    $msg = '导入完成：新增 ' . $inserted . ' 条';
    if ($skippedDup > 0) {
        $msg .= '，已跳过与现有记录重复 ' . $skippedDup . ' 条';
    }
    if ($invalidCount > 0) {
        $msg .= '，无法识别 ' . $invalidCount . ' 行';
        if (!empty($invalid)) {
            $msg .= '（例：' . implode('、', $invalid) . '）';
        }
    }
    $msg .= '。年级已统一为：' . $grade;
    admin_audit_log(auth_user(), 'sports_entry.import', '批量导入参赛记录', [
        'meet_id' => $meetId,
        'event_id' => $eventId,
        'grade' => $grade,
        'inserted' => $inserted,
        'invalid' => $invalidCount,
    ]);
    flash_set('success', $msg);
    redirect('/admin/sports-meet?meet_id=' . $meetId);
}

function handle_admin_shutdown_save(): void
{
    require_admin();
    csrf_verify();
    if (!site_shutdown_table_ok()) {
        flash_set('error', '数据库未升级：请执行 public/database/migration_site_shutdown.sql。');
        redirect('/admin');
    }
    $enabled = isset($_POST['enabled']) && (string) $_POST['enabled'] === '1' ? 1 : 0;
    $message = trim((string) ($_POST['message'] ?? ''));
    $eta = trim((string) ($_POST['eta'] ?? ''));
    if ($enabled === 1 && $message === '') {
        flash_set('error', '开启维护模式时请填写维护说明；若暂不维护请取消勾选。');
        redirect('/admin');
    }
    if (mb_strlen($message) > 4000) {
        flash_set('error', '维护说明过长（最多 4000 字）。');
        redirect('/admin');
    }
    if (mb_strlen($eta) > 255) {
        flash_set('error', '预计恢复时间过长（最多 255 字）。');
        redirect('/admin');
    }
    site_shutdown_save($enabled, $message, $eta);
    admin_audit_log(auth_user(), 'shutdown.save', $enabled ? '开启全站维护模式' : '关闭全站维护模式', [
        'enabled' => $enabled,
        'message_len' => mb_strlen($message),
        'eta' => $eta,
    ]);
    flash_set('success', $enabled ? '全站维护模式已开启，普通用户将看到维护页；管理员可正常访问。' : '全站维护模式已关闭。');
    redirect('/admin');
}

function handle_admin_announcement_save(): void
{
    require_admin();
    csrf_verify();
    if (!site_announcement_table_ok()) {
        flash_set('error', '数据库未升级：请执行 public/database/migration_site_announcement.sql。');
        redirect('/admin');
    }
    $enabled = isset($_POST['enabled']) && (string) $_POST['enabled'] === '1' ? 1 : 0;
    $body = trim((string) ($_POST['body'] ?? ''));
    if ($enabled === 1 && $body === '') {
        flash_set('error', '开启公告显示时请先填写公告内容；若暂不发布请取消勾选「全站显示」。');
        redirect('/admin');
    }
    if (mb_strlen($body) > 4000) {
        flash_set('error', '公告内容过长（最多 4000 字）。');
        redirect('/admin');
    }
    site_announcement_save($enabled, $body);
    admin_audit_log(auth_user(), 'announcement.save', $enabled ? '更新站点公告（已开启）' : '更新站点公告（已关闭）', [
        'enabled' => $enabled,
        'body_len' => mb_strlen($body),
    ]);
    flash_set('success', '站点公告已保存。');
    redirect('/admin');
}

function handle_admin_logo_save(): void
{
    require_admin();
    csrf_verify();
    if (!function_exists('user_is_super_admin') || !user_is_super_admin(auth_user())) {
        flash_set('error', '仅站长可更换站点 Logo。');
        redirect('/admin');
    }
    $result = site_logo_process_upload();
    if (empty($result['ok'])) {
        flash_set('error', (string) ($result['error'] ?? '上传失败。'));
        redirect('/admin');
    }
    admin_audit_log(auth_user(), 'logo.upload', '上传站点 Logo', [
        'path' => (string) ($result['path'] ?? ''),
    ]);
    flash_set('success', '站点 Logo 已更新，刷新前台即可看到。');
    redirect('/admin');
}

function handle_admin_logo_clear(): void
{
    require_admin();
    csrf_verify();
    if (!function_exists('user_is_super_admin') || !user_is_super_admin(auth_user())) {
        flash_set('error', '仅站长可清除站点 Logo。');
        redirect('/admin');
    }
    site_logo_clear_upload();
    admin_audit_log(auth_user(), 'logo.clear', '清除站点 Logo', []);
    flash_set('success', '已清除上传的站点 Logo（若配置了 logo_file 静态路径仍会显示）。');
    redirect('/admin');
}

function handle_admin_boards(): void
{
    require_admin_permission('boards');
    $st = db()->query('SELECT * FROM boards ORDER BY sort_order ASC, id ASC');
    $boards = $st->fetchAll();
    render_page('版块管理', 'admin/boards.php', compact('boards'), true);
}

function handle_admin_ip_bans(): void
{
    require_admin_permission('users');
    user_login_ban_purge_expired();
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = 25;
    $ipBanTypeFilter = trim((string) ($_GET['ip_ban_type'] ?? ''));
    if (!in_array($ipBanTypeFilter, ['', 'firewall', 'login'], true)) {
        $ipBanTypeFilter = '';
    }
    $ipBanList = ip_bans_list_active($page, $perPage, $ipBanTypeFilter);
    $ipBanRows = $ipBanList['rows'];
    $total = $ipBanList['total'];
    $pages = $ipBanList['pages'];
    $page = $ipBanList['page'];
    $searchQ = '';
    $profileFilter = '';
    $users = [];
    $ipBanActive = [];
    $usersView = 'ip_bans';
    render_page('已封禁 IP', 'admin/users.php', compact(
        'users',
        'searchQ',
        'page',
        'pages',
        'total',
        'perPage',
        'ipBanActive',
        'profileFilter',
        'usersView',
        'ipBanTypeFilter',
        'ipBanRows'
    ), true);
}

function handle_admin_users(): void
{
    require_admin_permission('users');
    user_login_ban_purge_expired();
    $searchQ = trim((string) ($_GET['q'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = 25;
    // 兼容旧链接 ?view=ip_bans（query 路由下 view 参数偶发丢失）
    if (trim((string) ($_GET['view'] ?? '')) === 'ip_bans') {
        $params = [];
        if ($page > 1) {
            $params['page'] = $page;
        }
        $legacyType = trim((string) ($_GET['ip_ban_type'] ?? ''));
        if (in_array($legacyType, ['firewall', 'login'], true)) {
            $params['ip_ban_type'] = $legacyType;
        }
        redirect(url('/admin/ip-bans', $params), true);
    }
    $likePat = null;
    if ($searchQ !== '') {
        $esc = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchQ);
        $likePat = '%' . $esc . '%';
    }
    $profileFilter = trim((string) ($_GET['profile'] ?? ''));
    if ($profileFilter !== 'complete' && $profileFilter !== 'incomplete') {
        $profileFilter = '';
    }
    $usersView = 'users';
    $ipBanTypeFilter = '';
    $sponsorSel = user_sponsor_column_ok() ? ', is_sponsor' : '';
    $realnameSel = user_realname_columns_ok() ? ', realname_allowed, realname_verified, realname_verified_at' : '';
    $l2PermSel = user_moderator_l2_perms_column_ok() ? ', moderator_l2_perms' : '';
    $loginProfileSel = user_login_profile_columns_ok() ? ', profile_grade, profile_class, profile_real_name' : '';
    $lim = (int) $perPage;

    $whereParts = [];
    $whereBind = [];
    if ($likePat !== null) {
        $whereParts[] = "(COALESCE(phone, '') LIKE ? OR nickname LIKE ?)";
        $whereBind[] = $likePat;
        $whereBind[] = $likePat;
    }
    if (user_login_profile_columns_ok() && $profileFilter === 'complete') {
        $whereParts[] = "TRIM(COALESCE(profile_grade, '')) <> ''"
            . " AND TRIM(COALESCE(profile_class, '')) <> ''"
            . " AND TRIM(COALESCE(profile_real_name, '')) <> ''";
    } elseif (user_login_profile_columns_ok() && $profileFilter === 'incomplete') {
        $whereParts[] = "(TRIM(COALESCE(profile_grade, '')) = '' OR TRIM(COALESCE(profile_class, '')) = '' OR TRIM(COALESCE(profile_real_name, '')) = '')";
    }
    $whereSql = $whereParts === [] ? '' : 'WHERE ' . implode(' AND ', $whereParts);
    try {
        if ($whereBind === []) {
            $total = (int) db()->query("SELECT COUNT(*) FROM users $whereSql")->fetchColumn();
        } else {
            $cst = db()->prepare("SELECT COUNT(*) FROM users $whereSql");
            $cst->execute($whereBind);
            $total = (int) $cst->fetchColumn();
        }
        $pages = max(1, (int) ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = (int) (($page - 1) * $perPage);
        $selectList = "id, email, phone, nickname, role, banned, moderator_l2{$l2PermSel}{$sponsorSel}{$realnameSel}{$loginProfileSel}"
            . (user_deletion_columns_ok() ? ', deleted_at' : '')
            . ', created_at, last_login_ip, last_login_at, login_banned_until';
        $listSql = "SELECT {$selectList} FROM users $whereSql ORDER BY id DESC LIMIT {$lim} OFFSET {$offset}";
        if ($whereBind === []) {
            $st = db()->query($listSql);
            $users = $st->fetchAll();
        } else {
            $st = db()->prepare($listSql);
            $st->execute($whereBind);
            $users = $st->fetchAll();
        }
    } catch (Throwable $e) {
        if ($whereBind === []) {
            $total = (int) db()->query("SELECT COUNT(*) FROM users $whereSql")->fetchColumn();
        } else {
            $cst = db()->prepare("SELECT COUNT(*) FROM users $whereSql");
            $cst->execute($whereBind);
            $total = (int) $cst->fetchColumn();
        }
        $pages = max(1, (int) ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = (int) (($page - 1) * $perPage);
        if ($whereBind === []) {
            $st = db()->query(
                "SELECT id, email, phone, nickname, role, banned{$sponsorSel}, created_at FROM users $whereSql ORDER BY id DESC LIMIT {$lim} OFFSET {$offset}"
            );
        } else {
            $st = db()->prepare(
                "SELECT id, email, phone, nickname, role, banned{$sponsorSel}, created_at FROM users $whereSql ORDER BY id DESC LIMIT {$lim} OFFSET {$offset}"
            );
            $st->execute($whereBind);
        }
        $users = $st->fetchAll();
        foreach ($users as &$uu) {
            $uu['moderator_l2'] = 0;
            $uu['moderator_l2_perms'] = null;
            if (!user_sponsor_column_ok()) {
                $uu['is_sponsor'] = 0;
            }
            if (!user_realname_columns_ok()) {
                $uu['realname_allowed'] = 0;
                $uu['realname_verified'] = 0;
                $uu['realname_verified_at'] = null;
            }
            if (!user_deletion_columns_ok()) {
                $uu['deleted_at'] = null;
            }
            if (!user_login_profile_columns_ok()) {
                $uu['profile_grade'] = null;
                $uu['profile_class'] = null;
                $uu['profile_real_name'] = null;
            }
            $uu['last_login_ip'] = null;
            $uu['last_login_at'] = null;
            $uu['login_banned_until'] = null;
        }
        unset($uu);
    }
    $ipBanActive = ip_bans_active_map_for_user_rows($users);
    $ipBanRows = [];
    render_page('用户管理', 'admin/users.php', compact(
        'users',
        'searchQ',
        'page',
        'pages',
        'total',
        'perPage',
        'ipBanActive',
        'profileFilter',
        'usersView',
        'ipBanTypeFilter',
        'ipBanRows'
    ), true);
}

function handle_admin_board_save(): void
{
    require_admin_permission('boards');
    csrf_verify();
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $name = trim((string) ($_POST['name'] ?? ''));
    $slug = trim((string) ($_POST['slug'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $sort_order = (int) ($_POST['sort_order'] ?? 0);
    if ($name === '' || $slug === '' || !preg_match('/^[a-z0-9-]+$/', $slug)) {
        flash_set('error', '名称与 slug（小写字母、数字、连字符）必填。');
        redirect('/admin/boards');
    }
    if ($id > 0) {
        try {
            db()->prepare(
                'UPDATE boards SET name=?, slug=?, description=?, sort_order=? WHERE id=?'
            )->execute([$name, $slug, $description, $sort_order, $id]);
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                flash_set('error', 'slug 已存在。');
            } else {
                flash_set('error', '保存失败。');
            }
            redirect('/admin/boards');
        }
        admin_audit_log(auth_user(), 'board.save', '更新版块', ['board_id' => $id, 'slug' => $slug, 'name' => $name]);
        flash_set('success', '版块已更新。');
    } else {
        try {
            db()->prepare(
                'INSERT INTO boards (name, slug, description, sort_order) VALUES (?,?,?,?)'
            )->execute([$name, $slug, $description, $sort_order]);
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                flash_set('error', 'slug 已存在。');
            } else {
                flash_set('error', '创建失败。');
            }
            redirect('/admin/boards');
        }
        $newId = (int) db()->lastInsertId();
        admin_audit_log(auth_user(), 'board.save', '创建版块', ['board_id' => $newId, 'slug' => $slug, 'name' => $name]);
        flash_set('success', '版块已创建。');
    }
    redirect('/admin/boards');
}

function handle_admin_board_delete(): void
{
    require_admin_permission('boards');
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        db()->prepare('DELETE FROM boards WHERE id = ?')->execute([$id]);
        admin_audit_log(auth_user(), 'board.delete', '删除版块', ['board_id' => $id]);
        flash_set('success', '版块已删除（下属主题一并删除）。');
    }
    redirect('/admin/boards');
}

function handle_admin_topic_delete(): void
{
    require_admin_permission('content');
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        db()->prepare('DELETE FROM topics WHERE id = ?')->execute([$id]);
        admin_audit_log(auth_user(), 'topic.delete', '删除主题', ['topic_id' => $id]);
        flash_set('success', '主题已删除。');
    }
    redirect('/admin');
}

function handle_admin_post_delete(): void
{
    require_admin_permission('content');
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    $topicId = (int) ($_POST['topic_id'] ?? 0);
    if ($id > 0) {
        db()->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
        admin_audit_log(auth_user(), 'post.delete', '删除回复', ['post_id' => $id, 'topic_id' => $topicId]);
        flash_set('success', '回复已删除。');
    }
    redirect($topicId > 0 ? '/topic/' . $topicId : '/admin');
}

function handle_admin_user_ban(): void
{
    require_admin_permission('users');
    csrf_verify();
    $returnQ = trim((string) ($_POST['_return_q'] ?? ''));
    $returnPage = max(1, (int) ($_POST['_return_page'] ?? 1));
    $anchor = trim((string) ($_POST['_return_anchor'] ?? ''));
    $usersUrl = admin_users_return_url($returnQ, $returnPage, $anchor);
    $id = (int) ($_POST['id'] ?? 0);
    $to = (int) ($_POST['banned'] ?? 0);
    if ($id > 0) {
        $st = db()->prepare('SELECT role FROM users WHERE id = ?');
        $st->execute([$id]);
        $u = $st->fetch();
        if ($u && $u['role'] !== 'admin') {
            db()->prepare('UPDATE users SET banned = ? WHERE id = ?')->execute([$to ? 1 : 0, $id]);
            admin_audit_log(auth_user(), 'user.ban', $to ? '禁言用户' : '解除禁言', ['target_user_id' => $id, 'banned' => $to ? 1 : 0]);
            flash_set('success', $to ? '已禁言用户。' : '已解除禁言。');
        } else {
            flash_set('error', '不能对管理员执行此操作。');
        }
    }
    redirect($usersUrl, true);
}

function handle_couple_invite_post(): void
{
    $u = require_login();
    csrf_verify();
    $toId = (int) ($_POST['to_user_id'] ?? 0);
    $msg = (string) ($_POST['message'] ?? '');
    $ref = trim((string) ($_POST['_ref'] ?? ''));
    $err = couple_send_invite((int) $u['id'], $toId, $msg);
    if ($err !== null) {
        flash_set('error', $err);
    } else {
        flash_set('success', '已发送情侣绑定邀请，等待对方同意。');
    }
    redirect($ref !== '' ? $ref : '/couple');
}

function handle_couple_respond_post(): void
{
    $u = require_login();
    csrf_verify();
    $id = (int) ($_POST['invite_id'] ?? 0);
    $accept = !empty($_POST['accept']);
    $err = couple_respond_invite($id, (int) $u['id'], $accept);
    if ($err !== null) {
        flash_set('error', $err);
    } else {
        flash_set('success', $accept ? '已接受绑定，祝你们甜蜜～' : '已拒绝该邀请。');
    }
    redirect('/couple');
}

function handle_couple_cancel_invite_post(): void
{
    $u = require_login();
    csrf_verify();
    $id = (int) ($_POST['invite_id'] ?? 0);
    $err = couple_cancel_my_invite((int) $u['id'], $id);
    if ($err !== null) {
        flash_set('error', $err);
    } else {
        flash_set('success', '已撤销邀请。');
    }
    redirect('/couple');
}

function handle_couple_unbind_post(): void
{
    $u = require_login();
    csrf_verify();
    $err = couple_unbind((int) $u['id']);
    if ($err !== null) {
        flash_set('error', $err);
    } else {
        flash_set('success', '已解除情侣绑定。');
    }
    redirect('/couple');
}

function handle_couple_note_post(): void
{
    $u = require_login();
    csrf_verify();
    $note = (string) ($_POST['love_note'] ?? '');
    $err = couple_update_love_note((int) $u['id'], $note);
    if ($err !== null) {
        flash_set('error', $err);
    } else {
        flash_set('success', '已保存。');
    }
    redirect('/couple/little');
}

function handle_couple_wall_post(): void
{
    $u = require_login();
    csrf_verify();
    $body = (string) ($_POST['body'] ?? '');
    $err = couple_wall_add((int) $u['id'], $body);
    if ($err !== null) {
        flash_set('error', $err);
    } else {
        flash_set('success', '留言已发布。');
    }
    redirect('/couple/leaving');
}

function handle_couple_album_add_post(): void
{
    $u = require_login();
    csrf_verify();
    $url = couple_album_resolve_image_url_from_request();
    $err = couple_gallery_add((int) $u['id'], $url, (string) ($_POST['caption'] ?? ''));
    if ($err !== null) {
        flash_set('error', $err);
    } else {
        flash_set('success', '已添加照片。');
    }
    redirect('/couple/album');
}

function handle_couple_album_delete_post(): void
{
    $u = require_login();
    csrf_verify();
    $err = couple_gallery_delete((int) $u['id'], (int) ($_POST['id'] ?? 0));
    if ($err !== null) {
        flash_set('error', $err);
    } else {
        flash_set('success', '已删除。');
    }
    redirect('/couple/album');
}

function handle_couple_list_add_post(): void
{
    $u = require_login();
    csrf_verify();
    $err = couple_promise_add((int) $u['id'], (string) ($_POST['body'] ?? ''));
    if ($err !== null) {
        flash_set('error', $err);
    } else {
        flash_set('success', '已添加约定。');
    }
    redirect('/couple/list');
}

function handle_couple_list_toggle_post(): void
{
    $u = require_login();
    csrf_verify();
    $err = couple_promise_toggle((int) $u['id'], (int) ($_POST['id'] ?? 0));
    if ($err !== null) {
        flash_set('error', $err);
    } else {
        flash_set('success', '已更新。');
    }
    redirect('/couple/list');
}

function handle_couple_list_delete_post(): void
{
    $u = require_login();
    csrf_verify();
    $err = couple_promise_delete((int) $u['id'], (int) ($_POST['id'] ?? 0));
    if ($err !== null) {
        flash_set('error', $err);
    } else {
        flash_set('success', '已删除。');
    }
    redirect('/couple/list');
}

function handle_couple_hub_get(): void
{
    $u = require_login();
    $coupleTablesOk = couple_tables_ok();
    $paired = false;
    $coupleRow = null;
    $partner = null;
    $boundAtMs = 0;
    $incomingInvites = [];
    $outgoingInvites = [];
    if ($coupleTablesOk) {
        $coupleRow = couple_row_for_user((int) $u['id']);
        $paired = $coupleRow !== null;
        if ($paired) {
            $partner = couple_partner_public_row((int) $u['id']);
            $boundAtMs = couple_bound_at_epoch_ms((string) ($coupleRow['bound_at'] ?? ''));
        } else {
            $incomingInvites = couple_incoming_pending_invites((int) $u['id']);
            $outgoingInvites = couple_outgoing_pending_invites((int) $u['id']);
        }
    }
    render_page('情侣空间', 'couple/hub.php', compact(
        'u',
        'coupleTablesOk',
        'paired',
        'coupleRow',
        'partner',
        'boundAtMs',
        'incomingInvites',
        'outgoingInvites'
    ));
}

function handle_couple_little_get(): void
{
    $u = require_login();
    if (!couple_tables_ok()) {
        flash_set('error', '情侣功能未启用：请执行 migration_couple.sql。');
        redirect('/couple');
    }
    $coupleRow = couple_row_for_user((int) $u['id']);
    if (!$coupleRow) {
        flash_set('error', '请先完成情侣绑定。');
        redirect('/couple');
    }
    $partner = couple_partner_public_row((int) $u['id']);
    render_page('点点滴滴', 'couple/little.php', compact('u', 'coupleRow', 'partner'));
}

function handle_couple_leaving_get(): void
{
    $u = require_login();
    if (!couple_tables_ok()) {
        flash_set('error', '情侣功能未启用：请执行 migration_couple.sql。');
        redirect('/couple');
    }
    $coupleRow = couple_row_for_user((int) $u['id']);
    if (!$coupleRow) {
        flash_set('error', '请先完成情侣绑定。');
        redirect('/couple');
    }
    $partner = couple_partner_public_row((int) $u['id']);
    $wallPosts = couple_wall_posts((int) $coupleRow['id']);
    render_page('留言祝福', 'couple/leaving.php', compact('u', 'coupleRow', 'partner', 'wallPosts'));
}

function handle_couple_about_get(): void
{
    $u = require_login();
    if (!couple_tables_ok()) {
        flash_set('error', '情侣功能未启用：请执行 migration_couple.sql。');
        redirect('/couple');
    }
    $coupleRow = couple_row_for_user((int) $u['id']);
    if (!$coupleRow) {
        flash_set('error', '请先完成情侣绑定。');
        redirect('/couple');
    }
    $partner = couple_partner_public_row((int) $u['id']);
    $st = db()->prepare('SELECT id, nickname, avatar FROM users WHERE id = ? LIMIT 1');
    $st->execute([(int) $u['id']]);
    $mePublic = $st->fetch(PDO::FETCH_ASSOC) ?: ['id' => (int) $u['id'], 'nickname' => $u['nickname'], 'avatar' => null];
    $meAv = user_avatar_public_url($mePublic['avatar'] ?? null);
    $partnerAv = $partner ? user_avatar_public_url($partner['avatar'] ?? null) : null;
    $days = couple_days_together((string) ($coupleRow['bound_at'] ?? ''));
    render_page('关于我们', 'couple/about.php', compact('u', 'coupleRow', 'partner', 'mePublic', 'meAv', 'partnerAv', 'days'));
}

function handle_couple_album_get(): void
{
    $u = require_login();
    if (!couple_tables_ok()) {
        flash_set('error', '情侣功能未启用：请执行 migration_couple.sql。');
        redirect('/couple');
    }
    $coupleRow = couple_row_for_user((int) $u['id']);
    if (!$coupleRow) {
        flash_set('error', '请先完成情侣绑定。');
        redirect('/couple');
    }
    $partner = couple_partner_public_row((int) $u['id']);
    $extrasOk = couple_extras_tables_ok();
    $gallery = $extrasOk ? couple_gallery_list((int) $coupleRow['id']) : [];
    render_page('恋爱相册', 'couple/album.php', compact('u', 'coupleRow', 'partner', 'extrasOk', 'gallery'));
}

function handle_couple_list_get(): void
{
    $u = require_login();
    if (!couple_tables_ok()) {
        flash_set('error', '情侣功能未启用：请执行 migration_couple.sql。');
        redirect('/couple');
    }
    $coupleRow = couple_row_for_user((int) $u['id']);
    if (!$coupleRow) {
        flash_set('error', '请先完成情侣绑定。');
        redirect('/couple');
    }
    $partner = couple_partner_public_row((int) $u['id']);
    $extrasOk = couple_extras_tables_ok();
    $promises = $extrasOk ? couple_promise_list((int) $coupleRow['id']) : [];
    render_page('恋爱列表', 'couple/list.php', compact('u', 'coupleRow', 'partner', 'extrasOk', 'promises'));
}
