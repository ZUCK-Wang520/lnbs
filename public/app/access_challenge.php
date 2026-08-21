<?php

declare(strict_types=1);

/**
 * 访问异常时要求完成 GeeTest 滑块（依赖 geetest.enabled）。
 *
 * @return array{enabled:bool,login_fail_threshold:int,ttl_seconds:int,mark_on_anti_spam_block:bool}
 */
function access_challenge_config(): array
{
    $cfg = $GLOBALS['APP_CONFIG']['access_challenge'] ?? [];
    if (!is_array($cfg)) {
        $cfg = [];
    }
    $enabled = array_key_exists('enabled', $cfg) ? (bool) $cfg['enabled'] : true;
    $loginFails = (int) ($cfg['login_fail_threshold'] ?? 3);
    if ($loginFails < 2) {
        $loginFails = 2;
    }
    if ($loginFails > 20) {
        $loginFails = 20;
    }
    $ttl = (int) ($cfg['ttl_seconds'] ?? 7200);
    if ($ttl < 600) {
        $ttl = 600;
    }
    if ($ttl > 86400) {
        $ttl = 86400;
    }
    $markSpam = array_key_exists('mark_on_anti_spam_block', $cfg) ? (bool) $cfg['mark_on_anti_spam_block'] : true;

    return [
        'enabled' => $enabled,
        'login_fail_threshold' => $loginFails,
        'ttl_seconds' => $ttl,
        'mark_on_anti_spam_block' => $markSpam,
    ];
}

function access_challenge_feature_on(): bool
{
    $c = access_challenge_config();

    return $c['enabled'] && function_exists('geetest_enabled') && geetest_enabled();
}

function access_challenge_required(): bool
{
    if (!access_challenge_feature_on()) {
        unset($_SESSION['_ac_until']);

        return false;
    }
    $until = (int) ($_SESSION['_ac_until'] ?? 0);

    return $until > time();
}

function access_challenge_mark(): void
{
    if (!access_challenge_feature_on()) {
        return;
    }
    $c = access_challenge_config();
    $_SESSION['_ac_until'] = time() + $c['ttl_seconds'];
}

function access_challenge_clear(): void
{
    unset($_SESSION['_ac_until'], $_SESSION['_ac_login_fails']);
}

function access_challenge_on_login_failure(): void
{
    if (!access_challenge_feature_on()) {
        return;
    }
    $c = access_challenge_config();
    $n = (int) ($_SESSION['_ac_login_fails'] ?? 0) + 1;
    $_SESSION['_ac_login_fails'] = $n;
    if ($n >= $c['login_fail_threshold']) {
        access_challenge_mark();
        $_SESSION['_ac_login_fails'] = 0;
    }
}

function access_challenge_on_login_success(): void
{
    unset($_SESSION['_ac_login_fails']);
}

function access_challenge_mark_on_anti_spam_block(): void
{
    $c = access_challenge_config();
    if ($c['mark_on_anti_spam_block']) {
        access_challenge_mark();
    }
}

/** @return list<string> */
function access_challenge_post_whitelist(): array
{
    return [
        '/access-challenge/verify',
        '/logout',
        '/login/complete-profile',
    ];
}

function access_challenge_should_block_post(string $path): bool
{
    if (!access_challenge_required()) {
        return false;
    }

    return !in_array($path, access_challenge_post_whitelist(), true);
}

function access_challenge_sanitize_next(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '' || strlen($raw) > 512) {
        return '/';
    }
    $path = parse_url($raw, PHP_URL_PATH);
    $query = parse_url($raw, PHP_URL_QUERY);
    if (!is_string($path) || $path === '' || $path[0] !== '/' || str_starts_with($path, '//')) {
        return '/';
    }
    $out = $path;
    if (is_string($query) && $query !== '' && strlen($query) < 2048) {
        $out .= '?' . $query;
    }

    return $out;
}

function access_challenge_block_redirect(): never
{
    $to = '/';
    $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    if ($ref !== '') {
        $path = parse_url($ref, PHP_URL_PATH);
        $host = parse_url($ref, PHP_URL_HOST);
        $here = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if (
            is_string($path) && $path !== '' && str_starts_with($path, '/') && !str_starts_with($path, '//')
            && ($host === null || $host === '' || ($here !== '' && strcasecmp((string) $host, $here) === 0))
        ) {
            $q = parse_url($ref, PHP_URL_QUERY);
            $to = $path;
            if (is_string($q) && $q !== '') {
                $to .= '?' . $q;
            }
        }
    }
    if ($to === '/') {
        $to = access_challenge_sanitize_next((string) ($_SERVER['REQUEST_URI'] ?? '/'));
    } else {
        $to = access_challenge_sanitize_next($to);
    }
    flash_set('error', '请先完成页面上的安全验证（滑块）后再提交。');
    redirect($to);
}

function access_challenge_current_next(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');

    return access_challenge_sanitize_next($uri);
}

function handle_access_challenge_verify_post(): void
{
    csrf_verify();
    $next = access_challenge_sanitize_next((string) ($_POST['next'] ?? '/'));
    if (!access_challenge_required()) {
        redirect($next);
    }
    if (!geetest_enabled()) {
        access_challenge_clear();
        redirect($next);
    }
    $gc = (string) ($_POST['geetest_challenge'] ?? '');
    $gv = (string) ($_POST['geetest_validate'] ?? '');
    $gs = (string) ($_POST['geetest_seccode'] ?? '');
    $ge = geetest_validate_or_error($gc, $gv, $gs);
    if ($ge !== null) {
        flash_set('error', $ge);
        redirect($next);
    }
    access_challenge_clear();
    flash_set('success', '验证通过，请继续操作。');
    redirect($next);
}
