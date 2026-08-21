<?php

declare(strict_types=1);

require_once __DIR__ . '/render.php';

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
        if ($path === '/register') {
            handle_register_post();
            return;
        }
        if ($path === '/logout') {
            csrf_verify();
            auth_logout();
            flash_set('success', '已退出登录。');
            redirect('/');
            return;
        }
        if (preg_match('#^/board/([a-z0-9-]+)/new$#', $path, $m)) {
            handle_topic_create_post($m[1]);
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
    if ($path === '/register') {
        handle_register_get();
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
    render_page('首页', 'home.php', compact('boards'));
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
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $st = db()->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $row = $st->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) {
        flash_set('error', '邮箱或密码错误。');
        redirect('/login');
    }
    auth_login((int) $row['id']);
    flash_set('success', '欢迎回来。');
    redirect('/');
}

function handle_register_get(): void
{
    if (auth_user()) {
        redirect('/');
    }
    render_page('注册', 'register.php');
}

function handle_register_post(): void
{
    csrf_verify();
    $email = trim((string) ($_POST['email'] ?? ''));
    $nickname = trim((string) ($_POST['nickname'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password_confirm'] ?? '');

    $errors = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '邮箱格式不正确。';
    }
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
        redirect('/register');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        db()->prepare(
            'INSERT INTO users (email, password_hash, nickname, role, banned) VALUES (?,?,?,\'user\',0)'
        )->execute([$email, $hash, $nickname]);
    } catch (PDOException $e) {
        if ((int) $e->errorInfo[1] === 1062) {
            flash_set('error', '该邮箱已被注册。');
        } else {
            flash_set('error', '注册失败，请稍后再试。');
        }
        redirect('/register');
    }
    $id = (int) db()->lastInsertId();
    auth_login($id);
    flash_set('success', '注册成功，欢迎加入鲁巴校园论坛。');
    redirect('/');
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
    $st = db()->prepare(
        'SELECT t.*, u.nickname AS author_nickname,
                (SELECT COUNT(*) FROM posts p WHERE p.topic_id = t.id) AS reply_count
         FROM topics t
         JOIN users u ON u.id = t.user_id
         WHERE t.board_id = ?
         ORDER BY t.pinned DESC, t.updated_at DESC
         LIMIT 200'
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
    $title = trim((string) ($_POST['title'] ?? ''));
    $body = trim((string) ($_POST['body'] ?? ''));
    if ($title === '' || mb_strlen($title) > 200) {
        flash_set('error', '标题需在 1–200 字。');
        redirect('/board/' . $slug . '/new');
    }
    if ($body === '') {
        flash_set('error', '正文不能为空。');
        redirect('/board/' . $slug . '/new');
    }
    db()->prepare(
        'INSERT INTO topics (board_id, user_id, title, body) VALUES (?,?,?,?)'
    )->execute([(int) $board['id'], (int) $user['id'], $title, $body]);
    $tid = (int) db()->lastInsertId();
    flash_set('success', '发帖成功。');
    redirect('/topic/' . $tid);
}

function handle_topic_show(int $id): void
{
    $st = db()->prepare(
        'SELECT t.*, b.name AS board_name, b.slug AS board_slug, u.nickname AS author_nickname
         FROM topics t
         JOIN boards b ON b.id = t.board_id
         JOIN users u ON u.id = t.user_id
         WHERE t.id = ? LIMIT 1'
    );
    $st->execute([$id]);
    $topic = $st->fetch();
    if (!$topic) {
        http_response_code(404);
        render_page('未找到', 'errors/404.php');
        return;
    }
    $pst = db()->prepare(
        'SELECT p.*, u.nickname AS author_nickname
         FROM posts p
         JOIN users u ON u.id = p.user_id
         WHERE p.topic_id = ?
         ORDER BY p.created_at ASC'
    );
    $pst->execute([$id]);
    $posts = $pst->fetchAll();
    $current = auth_user();
    render_page($topic['title'], 'topic.php', compact('topic', 'posts', 'current'));
}

function handle_reply_post(int $topicId): void
{
    csrf_verify();
    $user = require_login();
    if ((int) $user['banned'] === 1) {
        flash_set('error', '您已被禁言，无法回复。');
        redirect('/topic/' . $topicId);
    }
    $st = db()->prepare('SELECT id, locked FROM topics WHERE id = ? LIMIT 1');
    $st->execute([$topicId]);
    $topic = $st->fetch();
    if (!$topic) {
        http_response_code(404);
        exit('Not Found');
    }
    if ((int) $topic['locked'] === 1) {
        flash_set('error', '主题已锁定。');
        redirect('/topic/' . $topicId);
    }
    $body = trim((string) ($_POST['body'] ?? ''));
    if ($body === '') {
        flash_set('error', '回复内容不能为空。');
        redirect('/topic/' . $topicId);
    }
    db()->prepare('INSERT INTO posts (topic_id, user_id, body) VALUES (?,?,?)')
        ->execute([$topicId, (int) $user['id'], $body]);
    flash_set('success', '回复已发布。');
    redirect('/topic/' . $topicId);
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
        'SELECT id, email, nickname, role, banned, created_at FROM users ORDER BY id DESC LIMIT 500'
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
