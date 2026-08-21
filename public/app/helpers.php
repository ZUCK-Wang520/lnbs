<?php

declare(strict_types=1);

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * 解析 HTML datetime-local 控件提交的时间。
 * 不同浏览器可能提交带秒（2026-04-03T12:00:00）或仅到分钟（2026-04-03T12:00），
 * 若只用一种 createFromFormat 容易解析失败导致“设置了时间仍无效”。
 */
function parse_datetime_local_input(string $raw): ?DateTimeImmutable
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    // Some browsers/locales may submit `YYYY/MM/DD HH:mm` while the input is datetime-local.
    $raw = str_replace('/', '-', $raw);
    $s = str_replace('T', ' ', $raw);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $s)) {
        $s .= ':00';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d+$/', $s)) {
        $s = (string) preg_replace('/\.\d+$/', '', $s);
    }
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $s);
    if ($dt !== false) {
        return $dt;
    }
    $ts = strtotime(str_replace('T', ' ', $raw));
    if ($ts !== false) {
        return (new DateTimeImmutable('@' . $ts))->setTimezone(new DateTimeZone(date_default_timezone_get()));
    }

    return null;
}

/** 短信注册：满足 users.email NOT NULL，与 11 位手机号一一对应，不作为对外登录账号 */
function user_placeholder_email_from_phone(string $phone11): string
{
    return $phone11 . '@lnbs.internal';
}

function user_email_is_phone_placeholder(string $email): bool
{
    return (bool) preg_match('/^1[3-9]\d{9}@lnbs\.internal$/', $email);
}

/** 宝塔 Nginx 等未配置伪静态时开启：链接变为 /index.php?r=/路径，避免 /login 直接 404 */
function router_query_mode(): bool
{
    return !empty($GLOBALS['APP_CONFIG']['app']['router_query']);
}

/**
 * @param bool $readyLocation true 表示 $path 已由 url() 等组好（含查询串、#hash），勿再套一层 url()，否则会破坏 index.php?r= 路由
 */
function redirect(string $path, bool $readyLocation = false): never
{
    if ($readyLocation || preg_match('#^https?://#i', $path)) {
        header('Location: ' . $path, true, 302);
        exit;
    }
    header('Location: ' . url($path), true, 302);
    exit;
}

/**
 * 将表单回跳地址规范为站内路径（如 /user/1/topics）。
 * 修复 query 路由下误把 /index.php?r=/path 再交给 redirect() 导致的 r 双重嵌套。
 *
 * @return ?string 合法路径，否则 null（勿跳转）
 */
function internal_redirect_target(string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === '' || str_starts_with($raw, '//')) {
        return null;
    }
    if (preg_match('#^https?://#i', $raw)) {
        $path = parse_url($raw, PHP_URL_PATH) ?: '';
        $query = parse_url($raw, PHP_URL_QUERY);
        if (str_contains($path, 'index.php') && is_string($query)) {
            parse_str($query, $q);
            $raw = isset($q['r']) ? trim((string) $q['r']) : '';
        } else {
            return null;
        }
    }
    for ($i = 0; $i < 5; $i++) {
        $qpos = strpos($raw, '?');
        if ($qpos === false) {
            break;
        }
        $before = substr($raw, 0, $qpos);
        if (!str_contains($before, 'index.php')) {
            return null;
        }
        parse_str(substr($raw, $qpos + 1), $parts);
        if (empty($parts['r']) || !is_string($parts['r'])) {
            return null;
        }
        $raw = trim($parts['r']);
    }
    if ($raw === '' || $raw[0] !== '/') {
        return null;
    }
    if (str_contains($raw, '..')) {
        return null;
    }
    $raw = '/' . ltrim($raw, '/');
    $q2 = strpos($raw, '?');
    if ($q2 !== false) {
        $raw = substr($raw, 0, $q2);
    }
    $h2 = strpos($raw, '#');
    if ($h2 !== false) {
        $raw = substr($raw, 0, $h2);
    }
    if ($raw === '') {
        return '/';
    }

    return $raw;
}

function url(string $path, array $query = []): string
{
    $path = '/' . ltrim($path, '/');
    if ($path === '//') {
        $path = '/';
    }
    $fragment = '';
    $hashPos = strpos($path, '#');
    if ($hashPos !== false) {
        $fragment = substr($path, $hashPos);
        $path = substr($path, 0, $hashPos);
        if ($path === '' || $path === '/') {
            $path = '/';
        }
    }
    if (router_query_mode()) {
        $bu = rtrim((string) ($GLOBALS['APP_CONFIG']['app']['base_url'] ?? ''), '/');
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $out = $bu . $script . '?r=' . rawurlencode($path);
        foreach ($query as $k => $v) {
            $out .= '&' . rawurlencode((string) $k) . '=' . rawurlencode((string) $v);
        }
        return $out . $fragment;
    }
    $base = $GLOBALS['APP_CONFIG']['app']['base_url'] ?? '';
    $full = $path;
    if ($base !== '' && $base !== '/') {
        $full = rtrim($base, '/') . $path;
    }
    if ($query !== []) {
        $full .= (str_contains($full, '?') ? '&' : '?') . http_build_query($query);
    }
    return $full . $fragment;
}

/** POST 操作后跳回用户管理列表（保留搜索关键词、页码、锚点）。 */
function admin_users_return_url(string $returnQ, int $returnPage, string $anchor = ''): string
{
    $params = [];
    if ($returnQ !== '') {
        $params['q'] = $returnQ;
    }
    if ($returnPage > 1) {
        $params['page'] = $returnPage;
    }
    $base = url('/admin/users', $params);
    if ($anchor !== '') {
        $base .= '#' . rawurlencode($anchor);
    }

    return $base;
}

function asset(string $path): string
{
    $rel = 'assets/' . ltrim($path, '/');
    $app = $GLOBALS['APP_CONFIG']['app'] ?? [];
    if (!empty($app['asset_base'])) {
        return rtrim((string) $app['asset_base'], '/') . '/' . $rel;
    }
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = str_replace('\\', '/', dirname($script));
    if ($dir !== '/' && $dir !== '.' && $dir !== '') {
        return rtrim($dir, '/') . '/' . $rel;
    }
    $bu = rtrim((string) ($app['base_url'] ?? ''), '/');
    if ($bu !== '') {
        return $bu . '/' . $rel;
    }
    return '/' . $rel;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['_flash'])) {
        return null;
    }
    $f = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
    return $f;
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

/** Query 路由下 index.php?phone= 表示私信搜索；无 r= 时仍应进入 /chat（兼容错误链接） */
function request_get_phone_for_chat_nonempty(): bool
{
    return isset($_GET['phone'])
        && is_string($_GET['phone'])
        && trim($_GET['phone']) !== '';
}

/** 是否已存在 users.profile_likes（migration_user_likes.sql） */
function user_profile_likes_column_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT profile_likes FROM users LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/** 是否已存在 users.is_sponsor（migration_user_sponsor.sql） */
function user_sponsor_column_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT is_sponsor FROM users LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/** 是否已存在 users.profile_grade（migration_user_login_profile.sql） */
function user_login_profile_columns_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT profile_grade FROM users LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/** 是否已存在 topics.view_count 与 topic_likes 表（migration_topic_likes_and_views.sql） */
function topic_views_and_likes_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT view_count FROM topics LIMIT 1');
        db()->query('SELECT id FROM topic_likes LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/** topic_likes 是否含 created_at（当周点赞统计依赖） */
function topic_likes_has_created_at_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT created_at FROM topic_likes LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/** 是否已存在 topic_view_events（migration_topic_view_events.sql） */
function topic_view_events_table_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT id FROM topic_view_events LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/** 首页「当周」热门榜所需结构是否齐备 */
function home_hot_weekly_board_ready(): bool
{
    return topic_views_and_likes_ok() && topic_view_events_table_ok() && topic_likes_has_created_at_ok();
}

/**
 * 当前自然周（周一至周日）：左闭右开 [start, end)，end 为下周一 0 点。
 *
 * @return array{start:string,end:string,key:string,label:string}
 */
function home_hot_board_week_bounds(): array
{
    $app = $GLOBALS['APP_CONFIG']['app'] ?? [];
    $tzName = trim((string) ($app['timezone'] ?? ''));
    if ($tzName === '') {
        $tzName = @date_default_timezone_get() ?: 'Asia/Shanghai';
    }
    try {
        $tz = new DateTimeZone($tzName);
    } catch (Throwable $e) {
        $tz = new DateTimeZone('Asia/Shanghai');
    }
    $now = new DateTimeImmutable('now', $tz);
    $dow = (int) $now->format('N');
    $monday = $now->setTime(0, 0, 0)->modify('-' . ($dow - 1) . ' days');
    $nextMonday = $monday->modify('+7 days');
    $sunday = $monday->modify('+6 days');

    return [
        'start' => $monday->format('Y-m-d H:i:s'),
        'end' => $nextMonday->format('Y-m-d H:i:s'),
        'key' => $monday->format('Y-m-d'),
        'label' => $monday->format('n月j日') . '–' . $sunday->format('n月j日'),
    ];
}

/** 热门榜 JSON 快照刷新间隔（秒），过期后首页会重查库；可在 app.home_hot_board_snapshot_ttl_seconds 覆盖，默认 120 */
function home_hot_board_snapshot_ttl_seconds(): int
{
    $app = $GLOBALS['APP_CONFIG']['app'] ?? [];
    $s = (int) ($app['home_hot_board_snapshot_ttl_seconds'] ?? 120);
    if ($s < 15) {
        $s = 15;
    }
    if ($s > 3600) {
        $s = 3600;
    }

    return $s;
}

function home_hot_board_cache_path_for_week(string $weekKey): string
{
    $safe = preg_replace('/[^0-9-]/', '', $weekKey);
    if ($safe === '' || strlen($safe) !== 10) {
        $safe = 'invalid';
    }
    $pub = dirname(__DIR__);
    $primary = $pub . '/storage/home_hot_board_wk_' . $safe . '.json';
    if (is_dir(dirname($primary)) && is_writable(dirname($primary))) {
        return $primary;
    }

    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'lnbs_home_hot_board_wk_' . $safe . '.json';
}

/**
 * @return array{
 *   by_views: list<array<string,mixed>>,
 *   by_likes: list<array<string,mixed>>,
 *   week_key: string,
 *   week_label: string,
 *   generated_at: int,
 *   views_cumulative: bool
 * }|null
 */
function home_hot_board_read_weekly_snapshot(): ?array
{
    $bounds = home_hot_board_week_bounds();
    $weekKey = $bounds['key'];
    $file = home_hot_board_cache_path_for_week($weekKey);
    if (!is_readable($file)) {
        return null;
    }
    $raw = @file_get_contents($file);
    if (!is_string($raw) || $raw === '') {
        return null;
    }
    $j = json_decode($raw, true);
    if (!is_array($j) || ($j['week_key'] ?? '') !== $weekKey) {
        return null;
    }
    $snapTs = (int) ($j['ts'] ?? 0);
    if ($snapTs <= 0 || (time() - $snapTs) >= home_hot_board_snapshot_ttl_seconds()) {
        return null;
    }
    $v = $j['by_views'] ?? null;
    $l = $j['by_likes'] ?? null;
    if (!is_array($v) || !is_array($l)) {
        return null;
    }
    // 旧快照：左侧空、右侧有数据（先缓存了「尚无浏览事件」的周），强制重算并走累计浏览回退
    if ($v === [] && $l !== []) {
        return null;
    }

    return [
        'by_views' => $v,
        'by_likes' => $l,
        'week_key' => $weekKey,
        'week_label' => $bounds['label'],
        'generated_at' => (int) ($j['ts'] ?? time()),
        'views_cumulative' => !empty($j['views_cumulative']),
    ];
}

/**
 * @param list<array<string,mixed>> $byViews
 * @param list<array<string,mixed>> $byLikes
 */
function home_hot_board_write_weekly_snapshot(string $weekKey, array $byViews, array $byLikes, bool $viewsCumulative = false): void
{
    $file = home_hot_board_cache_path_for_week($weekKey);
    $dir = dirname($file);
    if (!is_dir($dir)) {
        return;
    }
    if (!is_writable($dir)) {
        return;
    }
    $payload = json_encode(
        [
            'week_key' => $weekKey,
            'ts' => time(),
            'by_views' => $byViews,
            'by_likes' => $byLikes,
            'views_cumulative' => $viewsCumulative,
        ],
        JSON_UNESCAPED_UNICODE
    );
    if ($payload === false) {
        return;
    }
    @file_put_contents($file, $payload, LOCK_EX);
}

/** @return array<int, bool> */
function &user_login_profile_incomplete_cache_bucket(): array
{
    static $cache = [];

    return $cache;
}

/** 清除「在读信息是否未填」的内存缓存（后台代用户修改后调用） */
function user_login_profile_invalidate_incomplete_cache(int $userId): void
{
    if ($userId < 1) {
        return;
    }
    $cache = &user_login_profile_incomplete_cache_bucket();
    unset($cache[$userId]);
}

/** 年级 / 班级 / 真实姓名是否尚未登记完整（需先执行 migration_user_login_profile.sql） */
function user_login_profile_incomplete_for_user_id(int $userId): bool
{
    $cache = &user_login_profile_incomplete_cache_bucket();
    if ($userId < 1) {
        return false;
    }
    if (isset($cache[$userId])) {
        return $cache[$userId];
    }
    if (!user_login_profile_columns_ok()) {
        return false;
    }
    try {
        $st = db()->prepare('SELECT profile_grade, profile_class, profile_real_name FROM users WHERE id = ? LIMIT 1');
        $st->execute([$userId]);
        $row = $st->fetch();
    } catch (Throwable $e) {
        $cache[$userId] = false;

        return false;
    }
    if (!$row) {
        $cache[$userId] = false;

        return false;
    }
    $incomplete = trim((string) ($row['profile_grade'] ?? '')) === ''
        || trim((string) ($row['profile_class'] ?? '')) === ''
        || trim((string) ($row['profile_real_name'] ?? '')) === '';
    $cache[$userId] = $incomplete;

    return $incomplete;
}

/** 若存在与他人重复的昵称则提示并重定向到个人资料（否则不跳转）。 */
function user_login_redirect_if_nickname_duplicate(int $userId): void
{
    try {
        $st = db()->prepare('SELECT nickname FROM users WHERE id = ? LIMIT 1');
        $st->execute([$userId]);
        $me = $st->fetch();
        $nick = $me ? trim((string) ($me['nickname'] ?? '')) : '';
        if ($nick !== '') {
            $st = db()->prepare('SELECT COUNT(*) FROM users WHERE nickname = ? AND id <> ?');
            $st->execute([$nick, $userId]);
            if ((int) $st->fetchColumn() > 0) {
                flash_set('error', '检测到昵称重复，请先修改昵称后再继续使用。');
                redirect('/profile');
            }
        }
    } catch (Throwable $e) {
        // ignore
    }
}

/** 是否已存在 users.realname_allowed（migration_user_realname.sql） */
function user_realname_columns_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT realname_allowed FROM users LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/** 是否已存在 users.realname_name_enc（migration_user_realname_identity.sql） */
function user_realname_identity_columns_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT realname_name_enc FROM users LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/** 是否已存在 users.deleted_at（migration_user_account_deletion.sql） */
function user_deletion_columns_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT deleted_at FROM users LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/** 是否已存在 users.birthday（migration_user_birthday.sql） */
function user_birthday_column_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT birthday FROM users LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/** 是否已存在 users.moderator_l2（migration_moderation_appeals.sql 或 schema） */
function user_moderator_l2_column_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT moderator_l2 FROM users LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

/** 是否已存在 users.moderator_l2_perms（migration_moderator_l2_permissions.sql） */
function user_moderator_l2_perms_column_ok(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT moderator_l2_perms FROM users LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }

    return $ok;
}

function request_path(): string
{
    if (router_query_mode()) {
        if (isset($_GET['r']) && is_string($_GET['r'])) {
            $r = trim($_GET['r']);
            // 误生成 r=/index.php?r=/real/path 时解包为站内路径
            for ($unwrap = 0; $unwrap < 5; $unwrap++) {
                $qpos = strpos($r, '?');
                if ($qpos === false) {
                    break;
                }
                $beforeQ = substr($r, 0, $qpos);
                if (!str_contains($beforeQ, 'index.php')) {
                    break;
                }
                parse_str(substr($r, $qpos + 1), $parts);
                if (empty($parts['r']) || !is_string($parts['r'])) {
                    break;
                }
                $r = trim($parts['r']);
            }
            $q = strpos($r, '?');
            if ($q !== false) {
                $r = substr($r, 0, $q);
            }
            $h = strpos($r, '#');
            if ($h !== false) {
                $r = substr($r, 0, $h);
            }
            $r = trim($r);
            if ($r === '' || $r === '/') {
                if (request_get_phone_for_chat_nonempty()) {
                    return '/chat';
                }
                return '/';
            }
            $p = '/' . ltrim($r, '/');
            if (str_contains($p, '..')) {
                return '/';
            }
            return $p;
        }
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $pathOnly = parse_url($uri, PHP_URL_PATH) ?: '/';
        if ($pathOnly === '/index.php' || str_ends_with($pathOnly, '/index.php')) {
            // index.php?phone=… 未带 r=（错误分享/旧链接）仍进私信搜索，GET 参数保留
            if (request_get_phone_for_chat_nonempty()) {
                return '/chat';
            }
            // index.php?q=… 常被误当成「用户搜索」；站长进入后台用户管理并保留 q（正确写法应为 ?r=/admin/users&q=…）
            $rawQ = $_GET['q'] ?? null;
            if (is_string($rawQ) && trim($rawQ) !== '' && !isset($_GET['r'])) {
                $au = auth_user();
                if ($au && user_has_admin_permission($au, 'users')) {
                    return '/admin/users';
                }
            }
            return '/';
        }
    }

    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    $base = $GLOBALS['APP_CONFIG']['app']['base_url'] ?? '';
    $baseNorm = rtrim($base, '/');
    if ($baseNorm !== '' && ($path === $baseNorm || str_starts_with($path, $baseNorm . '/'))) {
        $path = substr($path, strlen($baseNorm)) ?: '/';
    }
    if ($path === '') {
        $path = '/';
    }
    // 误用 http://域名/public/xxx 时去掉 /public 前缀
    if ($path === '/public' || str_starts_with($path, '/public/')) {
        $path = substr($path, strlen('/public')) ?: '/';
    }
    if ($path === '') {
        $path = '/';
    }
    // 直接访问 /index.php 时视为首页（站点根目录指向 public 时常见）
    if (str_ends_with($path, '/index.php')) {
        $path = substr($path, 0, -strlen('/index.php'));
        $path = $path === '' ? '/' : $path;
    }
    if ($path === '') {
        $path = '/';
    }
    return $path;
}

/** 静态文件 URL（路径相对于 public/，不含域名） */
function public_url(string $pathUnderPublic): string
{
    $rel = ltrim(str_replace('\\', '/', $pathUnderPublic), '/');
    $app = $GLOBALS['APP_CONFIG']['app'] ?? [];
    if (!empty($app['asset_base'])) {
        return rtrim((string) $app['asset_base'], '/') . '/' . $rel;
    }
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = str_replace('\\', '/', dirname($script));
    if ($dir !== '/' && $dir !== '.' && $dir !== '') {
        return rtrim($dir, '/') . '/' . $rel;
    }
    $bu = rtrim((string) ($app['base_url'] ?? ''), '/');
    if ($bu !== '') {
        return $bu . '/' . $rel;
    }

    return '/' . $rel;
}

/**
 * 站点 Logo 的 URL；未找到文件时返回 null（顶栏显示默认图形）。
 * 优先级：config app.logo_file → public 下常见文件名。
 */
/**
 * 站点 Logo URL。
 * 优先级：后台上传（uploads/site/logo.*）→ config app.logo_file → 可选静态文件。
 * 开源仓库默认不附带 Logo，由站长在后台上传。
 */
function site_logo_url(): ?string
{
    $public = dirname(__DIR__);

    if (function_exists('site_logo_uploaded_relative_path')) {
        $uploaded = site_logo_uploaded_relative_path();
        if ($uploaded !== null) {
            $url = public_url($uploaded);
            // 避免浏览器强缓存旧 Logo
            $full = $public . '/' . $uploaded;
            $mtime = @filemtime($full);
            if ($mtime) {
                $sep = str_contains($url, '?') ? '&' : '?';

                return $url . $sep . 'v=' . $mtime;
            }

            return $url;
        }
    }

    $configured = trim((string) ($GLOBALS['APP_CONFIG']['app']['logo_file'] ?? ''));
    if ($configured !== '') {
        $clean = ltrim(str_replace('\\', '/', $configured), '/');
        if (is_readable($public . '/' . $clean)) {
            return public_url($clean);
        }
    }

    // 兼容自部署者手动放置的静态文件（仓库默认不包含这些文件）
    $candidates = [
        'assets/images/logo.png',
        'assets/images/logo.jpg',
        'assets/images/logo.jpeg',
        'assets/images/logo.webp',
        'assets/images/logo.svg',
    ];
    foreach ($candidates as $rel) {
        if (is_readable($public . '/' . $rel)) {
            return public_url($rel);
        }
    }

    return null;
}

/** 在线人数（bootstrap 中更新，见 app/online.php：会员按账号去重 + 访客按会话） */
function online_count(): int
{
    return (int) ($GLOBALS['ONLINE_COUNT'] ?? 0);
}

