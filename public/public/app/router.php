<?php

declare(strict_types=1);

require_once __DIR__ . '/render.php';
require_once __DIR__ . '/sms.php';

function route_dispatch(): void
{
    $method = request_method();
    $path = request_path();
    if ($path !== '/' && str_ends_with($path, '/')) {
        $path = rtrim($path, '/') ?: '/';
    }

    // --- POST ---
    if ($method === 'POST') {
        if ($path === '/login') {
            handle_login_post();
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
        if ($path === '/confessions/send') {
            handle_confessions_send_post();
            return;
        }
        if ($path === '/confessions/ignore') {
            handle_confessions_ignore_post();
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
    if ($path === '/profile') {
        handle_profile_get();
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
    if ($path === '/admin/chat') {
        handle_admin_chat_get();
        return;
    }

    http_response_code(404);
    render_page('未找到', 'errors/404.php');
}

// ---- Handlers ----

function handle_home(): void
{
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
        "SELECT t.id, t.title, t.updated_at, t.is_anonymous, b.name AS board_name, b.slug AS board_slug, {$ad} AS author_nickname,
                ru.nickname AS author_real_nickname,
                author_u.avatar AS author_avatar,
                CASE WHEN t.is_anonymous = 0 THEN t.user_id END AS author_public_id
         FROM topics t
         JOIN boards b ON b.id = t.board_id
         JOIN users u ON u.id = t.user_id
         LEFT JOIN users ru ON ru.id = t.real_user_id
         LEFT JOIN users author_u ON author_u.id = CASE WHEN t.is_anonymous = 0 THEN t.user_id END
         ORDER BY t.updated_at DESC
         LIMIT 10"
    )->fetchAll();
    render_page('首页', 'home.php', compact('boards', 'stats', 'recent'));
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
    $account = trim((string) ($_POST['account'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $row = null;
    $phone = normalize_phone_cn($account);
    if ($phone !== null) {
        $st = db()->prepare('SELECT id, password_hash FROM users WHERE phone = ? LIMIT 1');
        $st->execute([$phone]);
        $row = $st->fetch() ?: null;
    }
    if ($row === null && filter_var($account, FILTER_VALIDATE_EMAIL)) {
        $st = db()->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
        $st->execute([$account]);
        $row = $st->fetch() ?: null;
    }
    if ($row === null || !password_verify($password, $row['password_hash'])) {
        flash_set('error', '账号或密码错误。');
        redirect('/login');
    }
    $remember = isset($_POST['remember']) && (string) $_POST['remember'] === '1';
    auth_login((int) $row['id'], $remember);
    flash_set('success', '欢迎回来。');
    redirect('/');
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

function handle_register_send_sms(): void
{
    csrf_verify();
    if (auth_user()) {
        redirect('/');
    }
    if (!isset($_POST['agree_user_notice']) || (string) ($_POST['agree_user_notice'] ?? '') !== '1') {
        flash_set('error', '请阅读并勾选同意《用户须知》后再获取验证码。');
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
    if (!isset($_POST['agree_user_notice']) || (string) ($_POST['agree_user_notice'] ?? '') !== '1') {
        flash_set('error', '请阅读并勾选同意《用户须知》后再完成注册。');
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
    try {
        $st = db()->prepare(
            "SELECT id, email, phone, nickname, avatar{$likesSel}, role, banned, created_at FROM users WHERE id = ? LIMIT 1"
        );
        $st->execute([$uid]);
        $profile = $st->fetch();
    } catch (PDOException $e) {
        $st = db()->prepare(
            "SELECT id, email, nickname{$likesSel}, role, banned, created_at FROM users WHERE id = ? LIMIT 1"
        );
        $st->execute([$uid]);
        $profile = $st->fetch();
        if ($profile) {
            $profile['phone'] = null;
            $profile['avatar'] = null;
            if (!user_profile_likes_column_ok()) {
                $profile['profile_likes'] = null;
            }
        }
    }
    if (!$profile) {
        flash_set('error', '用户不存在。');
        redirect('/');
    }

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

    render_page('个人中心', 'profile.php', compact('profile', 'topicCount', 'postCount', 'recentTopics'));
}

function handle_messages_get(): void
{
    $u = require_login();
    $uid = (int) $u['id'];
    $items = topic_reply_notifications_list_for_user($uid);
    $tableOk = topic_reply_notifications_table_ok();
    render_page('消息', 'messages.php', compact('items', 'tableOk'));
}

function handle_messages_mark_all_read_post(): void
{
    csrf_verify();
    $u = require_login();
    topic_reply_notifications_mark_all_read((int) $u['id']);
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
    try {
        $st = db()->prepare("SELECT id, nickname, avatar{$likesSel} FROM users WHERE id = ? LIMIT 1");
        $st->execute([$userId]);
        $pubUser = $st->fetch();
    } catch (PDOException $e) {
        $st = db()->prepare('SELECT id, nickname, avatar FROM users WHERE id = ? LIMIT 1');
        $st->execute([$userId]);
        $pubUser = $st->fetch();
        if ($pubUser) {
            $pubUser['profile_likes'] = null;
        }
    }
    if ($pubUser && !user_profile_likes_column_ok()) {
        $pubUser['profile_likes'] = null;
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
    if ($viewer && (int) $viewer['id'] !== $userId) {
        $chatPeerState = chat_peer_state((int) $viewer['id'], $userId);
    }
    $pageTitle = (string) $pubUser['nickname'] . ' 的主题';
    render_page($pageTitle, 'user_topics.php', compact('pubUser', 'topics', 'viewer', 'chatPeerState'));
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
    render_page('发布主题', 'topic_new.php', compact('board'));
}

function handle_topic_create_post(string $slug): void
{
    csrf_verify();
    $user = require_login();
    if ((int) $user['banned'] === 1) {
        flash_set('error', '您已被禁言，无法发帖。');
        redirect('/board/' . $slug);
    }
    $board = board_by_slug($slug);
    if (!$board) {
        http_response_code(404);
        exit('Not Found');
    }
    $fail = '/board/' . $slug . '/new';
    $title = trim((string) ($_POST['title'] ?? ''));
    $body = trim((string) ($_POST['body'] ?? ''));
    if ($title === '' || mb_strlen($title) > 200) {
        flash_set('error', '标题需在 1–200 字。');
        redirect($fail);
    }
    if ($body === '') {
        flash_set('error', '正文不能为空。');
        redirect($fail);
    }
    [$uid, $isAnon, $anonNick, $realUid] = resolve_topic_author($user, $_POST);
    $nickLine = ((int) $isAnon === 1 && trim((string) $anonNick) !== '') ? ('匿名显示昵称:' . trim((string) $anonNick)) : '';
    $merr = moderation_check_user_content('标题:' . $title, '正文:' . $body, $nickLine);
    if ($merr !== null) {
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
    flash_set('success', '发帖成功。');
    redirect('/topic/' . $tid);
}

function handle_topic_quick_post(): void
{
    csrf_verify();
    $user = require_login();
    if ((int) $user['banned'] === 1) {
        flash_set('error', '您已被禁言。');
        redirect('/');
    }
    $slug = trim((string) ($_POST['board_slug'] ?? ''));
    $board = board_by_slug($slug);
    if (!$board) {
        flash_set('error', '请选择有效版块。');
        redirect('/');
    }
    $fail = '/';
    $title = trim((string) ($_POST['title'] ?? ''));
    $body = trim((string) ($_POST['body'] ?? ''));
    if ($title === '' || mb_strlen($title) > 200) {
        flash_set('error', '标题需在 1–200 字。');
        redirect($fail);
    }
    if ($body === '') {
        flash_set('error', '正文不能为空。');
        redirect($fail);
    }
    [$uid, $isAnon, $anonNick, $realUid] = resolve_topic_author($user, $_POST);
    $nickLine = ((int) $isAnon === 1 && trim((string) $anonNick) !== '') ? ('匿名显示昵称:' . trim((string) $anonNick)) : '';
    $merr = moderation_check_user_content('标题:' . $title, '正文:' . $body, $nickLine);
    if ($merr !== null) {
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
    flash_set('success', '发帖成功。');
    redirect('/topic/' . $tid);
}

function handle_topic_show(int $id): void
{
    $ad = sql_topic_author_display();
    $st = db()->prepare(
        "SELECT t.*, b.name AS board_name, b.slug AS board_slug, {$ad} AS author_nickname,
                ru.nickname AS author_real_nickname,
                author_u.avatar AS author_avatar,
                CASE WHEN t.is_anonymous = 0 THEN t.user_id END AS author_public_id
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
    if ($current) {
        topic_reply_notifications_mark_topic_read((int) $current['id'], $id);
    }
    render_page($topic['title'], 'topic.php', compact('topic', 'posts', 'current'));
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
    $body = trim((string) ($_POST['body'] ?? ''));
    if ($body === '') {
        flash_set('error', '回复内容不能为空。');
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
        $friends[] = array_merge($fr, ['last_message' => $last]);
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
    $nick = trim((string) ($u['nickname'] ?? ''));
    $merr = moderation_check_user_content('私信:' . $body, $nick !== '' ? ('发送者昵称:' . $nick) : '');
    if ($merr !== null) {
        flash_set('error', $merr);
        redirect('/chat/with/' . $toId);
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
    require_admin();
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

function handle_admin_dashboard(): void
{
    require_admin();
    render_page('管理后台', 'admin/dashboard.php', [], true);
}

function handle_admin_boards(): void
{
    require_admin();
    $st = db()->query('SELECT * FROM boards ORDER BY sort_order ASC, id ASC');
    $boards = $st->fetchAll();
    render_page('版块管理', 'admin/boards.php', compact('boards'), true);
}

function handle_admin_users(): void
{
    require_admin();
    $st = db()->query(
        'SELECT id, email, phone, nickname, role, banned, created_at FROM users ORDER BY id DESC LIMIT 500'
    );
    $users = $st->fetchAll();
    render_page('用户管理', 'admin/users.php', compact('users'), true);
}

function handle_admin_board_save(): void
{
    require_admin();
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
        flash_set('success', '版块已创建。');
    }
    redirect('/admin/boards');
}

function handle_admin_board_delete(): void
{
    require_admin();
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        db()->prepare('DELETE FROM boards WHERE id = ?')->execute([$id]);
        flash_set('success', '版块已删除（下属主题一并删除）。');
    }
    redirect('/admin/boards');
}

function handle_admin_topic_delete(): void
{
    require_admin();
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        db()->prepare('DELETE FROM topics WHERE id = ?')->execute([$id]);
        flash_set('success', '主题已删除。');
    }
    redirect('/admin');
}

function handle_admin_post_delete(): void
{
    require_admin();
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    $topicId = (int) ($_POST['topic_id'] ?? 0);
    if ($id > 0) {
        db()->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
        flash_set('success', '回复已删除。');
    }
    redirect($topicId > 0 ? '/topic/' . $topicId : '/admin');
}

function handle_admin_user_ban(): void
{
    require_admin();
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    $to = (int) ($_POST['banned'] ?? 0);
    if ($id > 0) {
        $st = db()->prepare('SELECT role FROM users WHERE id = ?');
        $st->execute([$id]);
        $u = $st->fetch();
        if ($u && $u['role'] !== 'admin') {
            db()->prepare('UPDATE users SET banned = ? WHERE id = ?')->execute([$to ? 1 : 0, $id]);
            flash_set('success', $to ? '已禁言用户。' : '已解除禁言。');
        } else {
            flash_set('error', '不能对管理员执行此操作。');
        }
    }
    redirect('/admin/users');
}
