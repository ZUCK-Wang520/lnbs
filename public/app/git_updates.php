<?php

declare(strict_types=1);

/**
 * 首页「站点更新」：从本地 git log 或 GitHub API 读取最近提交（带文件缓存）。
 *
 * 配置 $GLOBALS['APP_CONFIG']['git_updates']：
 * - source: local | github（默认 local）
 * - enabled, limit, cache_ttl_seconds, commit_base_url（同前）
 * - repo_path：仅 local，仓库根目录
 * - github_owner, github_repo, github_token：仅 github；私人仓库必须填 token（classic: ghp_… 或 fine-grained: github_pat_…）
 */
function git_updates_config(): array
{
    $c = $GLOBALS['APP_CONFIG']['git_updates'] ?? [];
    if (!is_array($c)) {
        $c = [];
    }
    $limit = (int) ($c['limit'] ?? 12);
    if ($limit < 1) {
        $limit = 1;
    }
    if ($limit > 30) {
        $limit = 30;
    }
    $ttl = (int) ($c['cache_ttl_seconds'] ?? 300);
    if ($ttl < 30) {
        $ttl = 30;
    }
    if ($ttl > 86400) {
        $ttl = 86400;
    }
    $source = strtolower(trim((string) ($c['source'] ?? 'local')));
    if ($source !== 'github') {
        $source = 'local';
    }

    return [
        'enabled' => !array_key_exists('enabled', $c) || $c['enabled'] !== false,
        'source' => $source,
        'limit' => $limit,
        'cache_ttl_seconds' => $ttl,
        'repo_path' => trim((string) ($c['repo_path'] ?? '')),
        'commit_base_url' => rtrim(trim((string) ($c['commit_base_url'] ?? '')), '/'),
        'github_owner' => trim((string) ($c['github_owner'] ?? '')),
        'github_repo' => trim((string) ($c['github_repo'] ?? '')),
        'github_token' => trim((string) ($c['github_token'] ?? '')),
    ];
}

function git_updates_public_root(): string
{
    return dirname(__DIR__);
}

function git_updates_can_shell(): bool
{
    if (!function_exists('shell_exec')) {
        return false;
    }
    $df = (string) ini_get('disable_functions');
    if ($df === '') {
        return true;
    }
    $disabled = array_map('trim', explode(',', $df));

    return !in_array('shell_exec', $disabled, true);
}

/**
 * 用 git 子进程判断是否为仓库根（不依赖 PHP 能否 stat 上一级 .git，可绕过仅含 public 的 open_basedir）。
 */
function git_updates_probe_repo(string $path): bool
{
    if (!git_updates_can_shell()) {
        return false;
    }
    $path = str_replace(["\0", "\r", "\n"], '', $path);
    $path = trim($path);
    if ($path === '') {
        return false;
    }
    $redir = (stripos(PHP_OS, 'WIN') === 0) ? '2>nul' : '2>/dev/null';
    $out = shell_exec('git -C ' . escapeshellarg($path) . ' rev-parse --is-inside-work-tree ' . $redir);
    if (!is_string($out)) {
        return false;
    }

    return strtolower(trim($out)) === 'true';
}

/** @return ?string 含 .git 的仓库根目录 */
function git_updates_resolve_repo_root(): ?string
{
    $cfgPath = git_updates_config()['repo_path'];
    $candidates = [];
    if ($cfgPath !== '') {
        $candidates[] = $cfgPath;
    }
    $pub = git_updates_public_root();
    $candidates[] = dirname($pub);
    $candidates[] = $pub;

    foreach ($candidates as $p) {
        $p = trim((string) $p);
        if ($p === '') {
            continue;
        }
        $variants = [];
        $rp = @realpath($p);
        if ($rp !== false && $rp !== '') {
            $variants[] = $rp;
        }
        $variants[] = $p;
        $variants = array_values(array_unique($variants));
        foreach ($variants as $try) {
            if (git_updates_probe_repo($try)) {
                return $try;
            }
        }
    }

    return null;
}

/**
 * @return list<array{hash:string,subject:string,date:string,author:string}>
 */
function git_updates_read_cache(string $cacheFile, int $ttl): array
{
    if (!is_readable($cacheFile)) {
        return [];
    }
    $raw = @file_get_contents($cacheFile);
    if (!is_string($raw) || $raw === '') {
        return [];
    }
    $j = json_decode($raw, true);
    if (!is_array($j) || empty($j['ts']) || !isset($j['commits']) || !is_array($j['commits'])) {
        return [];
    }
    if ((time() - (int) $j['ts']) > $ttl) {
        return [];
    }
    $out = [];
    foreach ($j['commits'] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $h = (string) ($row['hash'] ?? '');
        if (strlen($h) < 7) {
            continue;
        }
        $out[] = [
            'hash' => $h,
            'subject' => (string) ($row['subject'] ?? ''),
            'date' => (string) ($row['date'] ?? ''),
            'author' => (string) ($row['author'] ?? ''),
        ];
    }

    return $out;
}

function git_updates_write_cache(string $cacheFile, array $commits): void
{
    $dir = dirname($cacheFile);
    if (!is_dir($dir)) {
        return;
    }
    if (!is_writable($dir)) {
        return;
    }
    $payload = json_encode(['ts' => time(), 'commits' => $commits], JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return;
    }
    @file_put_contents($cacheFile, $payload, LOCK_EX);
}

function git_updates_cache_path(string $repoRoot, int $limit): string
{
    $pub = git_updates_public_root();
    $suffix = '_l' . $limit;
    $primary = $pub . '/storage/git_updates_cache' . $suffix . '.json';
    if (is_dir(dirname($primary)) && is_writable(dirname($primary))) {
        return $primary;
    }

    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'lnbs_git_updates_' . md5($repoRoot . '|' . $limit) . '.json';
}

function git_updates_cache_path_github(string $owner, string $repo, int $limit): string
{
    $key = md5(strtolower($owner) . '/' . strtolower($repo) . '|' . $limit);
    $pub = git_updates_public_root();
    $primary = $pub . '/storage/git_updates_github_' . $key . '.json';
    if (is_dir(dirname($primary)) && is_writable(dirname($primary))) {
        return $primary;
    }

    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'lnbs_git_updates_gh_' . $key . '.json';
}

/**
 * @return list<array{hash:string,subject:string,date:string,author:string}>
 */
function git_updates_fetch_from_github(array $cfg): array
{
    $owner = $cfg['github_owner'];
    $repo = $cfg['github_repo'];
    if ($owner === '' || $repo === '') {
        return [];
    }
    $limit = (int) $cfg['limit'];
    $url = sprintf(
        'https://api.github.com/repos/%s/%s/commits?per_page=%d',
        rawurlencode($owner),
        rawurlencode($repo),
        $limit
    );
    $headers = [
        'Accept: application/vnd.github+json',
        'X-GitHub-Api-Version: 2022-11-28',
        'User-Agent: LnbsCampusForum/1.0',
    ];
    if ($cfg['github_token'] !== '') {
        $headers[] = 'Authorization: Bearer ' . $cfg['github_token'];
    }
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => implode("\r\n", $headers),
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    if (!is_string($body) || $body === '') {
        return [];
    }
    $j = json_decode($body, true);
    if (!is_array($j)) {
        return [];
    }
    $commits = [];
    foreach ($j as $row) {
        if (!is_array($row)) {
            continue;
        }
        $sha = (string) ($row['sha'] ?? '');
        if (strlen($sha) < 7) {
            continue;
        }
        $commit = $row['commit'] ?? null;
        if (!is_array($commit)) {
            continue;
        }
        $msg = (string) ($commit['message'] ?? '');
        $firstLine = preg_split('/\r\n|\r|\n/', $msg, 2)[0] ?? $msg;
        $authorBlock = $commit['author'] ?? null;
        $name = '';
        $iso = '';
        if (is_array($authorBlock)) {
            $name = (string) ($authorBlock['name'] ?? '');
            $iso = (string) ($authorBlock['date'] ?? '');
        }
        if ($name === '' && isset($commit['committer']) && is_array($commit['committer'])) {
            $name = (string) ($commit['committer']['name'] ?? '');
        }
        if ($iso === '' && isset($commit['committer']) && is_array($commit['committer'])) {
            $iso = (string) ($commit['committer']['date'] ?? '');
        }
        $date = $iso;
        if ($iso !== '' && ($t = strtotime($iso)) !== false) {
            $date = date('Y-m-d H:i', $t);
        }
        $commits[] = [
            'hash' => $sha,
            'subject' => git_updates_sanitize_line($firstLine, 240),
            'date' => $date,
            'author' => git_updates_sanitize_line($name, 80),
        ];
    }

    return $commits;
}

/**
 * @return list<array{hash:string,subject:string,date:string,author:string}>
 */
function git_updates_run_log(string $repoRoot, int $limit): array
{
    if (!git_updates_can_shell()) {
        return [];
    }
    $redir = (stripos(PHP_OS, 'WIN') === 0) ? '2>nul' : '2>/dev/null';
    $fmt = '%H' . "\x1f" . '%s' . "\x1f" . '%cI' . "\x1f" . '%aN';
    $cmd = 'git -C ' . escapeshellarg($repoRoot) . ' log -n ' . $limit
        . ' --no-pager --pretty=format:' . escapeshellarg($fmt) . ' ' . $redir;
    $out = shell_exec($cmd);
    if (!is_string($out) || trim($out) === '') {
        return [];
    }
    $lines = preg_split('/\r\n|\r|\n/', trim($out)) ?: [];
    $commits = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = explode("\x1f", $line, 4);
        if (count($parts) < 4) {
            continue;
        }
        [$hash, $subject, $iso, $author] = $parts;
        $hash = trim($hash);
        if (strlen($hash) < 7) {
            continue;
        }
        $subject = git_updates_sanitize_line($subject, 240);
        $author = git_updates_sanitize_line($author, 80);
        $date = $iso;
        if ($iso !== '' && ($t = strtotime($iso)) !== false) {
            $date = date('Y-m-d H:i', $t);
        }
        $commits[] = [
            'hash' => $hash,
            'subject' => $subject,
            'date' => $date,
            'author' => $author,
        ];
    }

    return $commits;
}

function git_updates_sanitize_line(string $s, int $maxLen): string
{
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '';
    $s = trim($s);
    if (mb_strlen($s) > $maxLen) {
        $s = mb_substr($s, 0, $maxLen) . '…';
    }

    return $s;
}

/**
 * 供首页使用：返回最近提交列表；无 Git 或失败时返回空数组。
 *
 * @param int|null $limitOverride 非 null 时覆盖配置中的条数（1–30），缓存键与请求条数一致。
 * @return list<array{hash:string,subject:string,date:string,author:string}>
 */
function git_updates_for_home(?int $limitOverride = null): array
{
    $cfg = git_updates_config();
    if (!$cfg['enabled']) {
        return [];
    }
    $limit = $limitOverride !== null ? max(1, min(30, $limitOverride)) : $cfg['limit'];
    $cfgFetch = $cfg;
    $cfgFetch['limit'] = $limit;

    if ($cfg['source'] === 'github') {
        if ($cfg['github_owner'] === '' || $cfg['github_repo'] === '') {
            return [];
        }
        $cacheFile = git_updates_cache_path_github($cfg['github_owner'], $cfg['github_repo'], $limit);
        $cached = git_updates_read_cache($cacheFile, $cfg['cache_ttl_seconds']);
        if ($cached !== []) {
            return $cached;
        }
        $commits = git_updates_fetch_from_github($cfgFetch);
        if ($commits !== []) {
            git_updates_write_cache($cacheFile, $commits);
        }

        return $commits;
    }

    $repo = git_updates_resolve_repo_root();
    if ($repo === null) {
        return [];
    }
    $cacheFile = git_updates_cache_path($repo, $limit);
    $cached = git_updates_read_cache($cacheFile, $cfg['cache_ttl_seconds']);
    if ($cached !== []) {
        return $cached;
    }
    $commits = git_updates_run_log($repo, $limit);
    if ($commits !== []) {
        git_updates_write_cache($cacheFile, $commits);
    }

    return $commits;
}

function git_updates_commit_base_url(): string
{
    $c = git_updates_config();
    if ($c['commit_base_url'] !== '') {
        return $c['commit_base_url'];
    }
    if ($c['source'] === 'github' && $c['github_owner'] !== '' && $c['github_repo'] !== '') {
        return 'https://github.com/' . rawurlencode($c['github_owner']) . '/' . rawurlencode($c['github_repo']) . '/commit';
    }

    return '';
}
