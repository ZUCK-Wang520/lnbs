<?php

declare(strict_types=1);

/**
 * 请求防火墙：识别恶意扫描、SQL 注入、XSS 等，自动封禁 IP 并展示拦截页。
 *
 * @return array{enabled:bool,ban_hours:int,max_field_bytes:int,whitelist:list<string>}
 */
function firewall_config(): array
{
    $cfg = $GLOBALS['APP_CONFIG']['firewall'] ?? [];
    if (!is_array($cfg)) {
        $cfg = [];
    }
    $banHours = (int) ($cfg['ban_hours'] ?? 0);
    if ($banHours < 0) {
        $banHours = 0;
    }
    if ($banHours > 8760) {
        $banHours = 8760;
    }
    $maxField = (int) ($cfg['max_field_bytes'] ?? 65536);
    if ($maxField < 4096) {
        $maxField = 4096;
    }
    if ($maxField > 262144) {
        $maxField = 262144;
    }
    $whitelist = [];
    foreach ((array) ($cfg['whitelist_ips'] ?? []) as $wip) {
        $wip = trim((string) $wip);
        if ($wip !== '' && filter_var($wip, FILTER_VALIDATE_IP)) {
            $whitelist[] = $wip;
        }
    }

    return [
        'enabled' => array_key_exists('enabled', $cfg) ? (bool) $cfg['enabled'] : true,
        'ban_hours' => $banHours,
        'max_field_bytes' => $maxField,
        'whitelist' => $whitelist,
    ];
}

function firewall_user_bypasses(): bool
{
    if (!function_exists('auth_user') || !function_exists('user_can_access_admin_backend')) {
        return false;
    }
    $u = auth_user();

    return user_can_access_admin_backend($u);
}

/**
 * @return list<array{label:string,pattern:string}>
 */
function firewall_threat_patterns(): array
{
    return [
        ['label' => '路径探测', 'pattern' => '#/(?:\.env|\.git|wp-admin|wp-login|xmlrpc\.php|phpmyadmin|pma/|vendor/phpunit|actuator/|\.well-known/security\.txt)#i'],
        ['label' => '路径穿越', 'pattern' => '#(?:\.\./|\.\.\\\\|%2e%2e%2f|%2e%2e/)#i'],
        ['label' => 'SQL注入', 'pattern' => '#\bunion\s+(?:all\s+)?select\b#i'],
        ['label' => 'SQL注入', 'pattern' => '#(?:\'|")\s*or\s+(?:\'|\"|\d)#i'],
        ['label' => 'SQL注入', 'pattern' => '#\bor\s+1\s*=\s*1\b#i'],
        ['label' => 'SQL注入', 'pattern' => '#\b(?:sleep|benchmark)\s*\(\s*\d+#i'],
        ['label' => 'SQL注入', 'pattern' => '#\binformation_schema\b#i'],
        ['label' => 'SQL注入', 'pattern' => '#\b(?:load_file|into\s+(?:outfile|dumpfile))\b#i'],
        ['label' => 'SQL注入', 'pattern' => '#;\s*--\s#'],
        ['label' => 'XSS', 'pattern' => '#<\s*script[\s>]#i'],
        ['label' => 'XSS', 'pattern' => '#javascript\s*:#i'],
        ['label' => 'XSS', 'pattern' => '#<\s*(?:iframe|object|embed|svg)[\s>]#i'],
        ['label' => 'XSS', 'pattern' => '#\bon(?:error|load|click|mouse\w+|focus)\s*=#i'],
        ['label' => 'XSS', 'pattern' => '#<\?php\b#i'],
        ['label' => '命令注入', 'pattern' => '#[;&|]\s*(?:cat|ls|wget|curl|bash|sh|nc|powershell|cmd)\b#i'],
        ['label' => '命令注入', 'pattern' => '#`[^`\n]{3,}`#'],
        ['label' => '扫描器', 'pattern' => '#(?:sqlmap|nikto|acunetix|nessus|masscan|zgrab|dirbuster)#i'],
    ];
}

/**
 * @param array<string|int, mixed> $data
 * @return list<string>
 */
function firewall_flatten_values(array $data, int $depth = 0): array
{
    if ($depth > 6) {
        return [];
    }
    $out = [];
    foreach ($data as $k => $v) {
        if (is_array($v)) {
            foreach (firewall_flatten_values($v, $depth + 1) as $piece) {
                $out[] = $piece;
            }
            continue;
        }
        if (!is_string($v) && !is_numeric($v)) {
            continue;
        }
        $s = (string) $v;
        if ($s === '') {
            continue;
        }
        $out[] = (string) $k . '=' . $s;
        $out[] = $s;
    }

    return $out;
}

/**
 * @return ?array{label:string,snippet:string}
 */
function firewall_scan_request(array $cfg): ?array
{
    $chunks = [];
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    if ($uri !== '') {
        $chunks[] = $uri;
    }
    $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
    if ($qs !== '') {
        $chunks[] = $qs;
    }
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua !== '') {
        $chunks[] = $ua;
    }
    $chunks = array_merge(
        $chunks,
        firewall_flatten_values($_GET),
        firewall_flatten_values($_POST)
    );
    $max = $cfg['max_field_bytes'];
    $patterns = firewall_threat_patterns();
    foreach ($chunks as $raw) {
        if (strlen($raw) > $max) {
            $raw = substr($raw, 0, $max);
        }
        $decoded = rawurldecode($raw);
        $haystacks = [$raw];
        if ($decoded !== $raw) {
            $haystacks[] = $decoded;
        }
        foreach ($haystacks as $hay) {
            foreach ($patterns as $rule) {
                if (@preg_match($rule['pattern'], $hay) === 1) {
                    $snippet = $hay;
                    if (mb_strlen($snippet) > 120) {
                        $snippet = mb_substr($snippet, 0, 120) . '…';
                    }

                    return ['label' => $rule['label'], 'snippet' => $snippet];
                }
            }
        }
    }

    return null;
}

function firewall_respond_blocked(?string $detail = null): void
{
    $appName = (string) ($GLOBALS['APP_CONFIG']['app']['name'] ?? '鲁巴校园论坛');
    http_response_code(403);
    if (function_exists('render_page')) {
        render_page('访问已拦截', 'firewall_blocked.php', [
            'firewallDetail' => $detail,
            'layout_minimal' => true,
            'layout_minimal_mode' => 'shutdown',
        ]);
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8"><title>访问已拦截</title></head><body>';
    echo '<h1>访问已拦截</h1><p>安全防护由 ' . htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') . ' 提供</p>';
    if ($detail !== null && $detail !== '') {
        echo '<p>' . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    echo '</body></html>';
    exit;
}

function firewall_enforce(): void
{
    $cfg = firewall_config();
    if (!$cfg['enabled']) {
        return;
    }
    if (!function_exists('client_ip')) {
        return;
    }
    $ip = client_ip();
    if ($ip === '' || $ip === '0.0.0.0') {
        return;
    }
    if (in_array($ip, $cfg['whitelist'], true)) {
        return;
    }
    if (firewall_user_bypasses()) {
        return;
    }
    if (ip_is_firewall_blocked($ip)) {
        firewall_respond_blocked();
    }
    $hit = firewall_scan_request($cfg);
    if ($hit === null) {
        return;
    }
    $detail = $hit['label'] . '：' . $hit['snippet'];
    ip_firewall_auto_ban($ip, $hit['label'], $cfg['ban_hours'] > 0 ? $cfg['ban_hours'] : null);
    error_log('firewall: blocked ' . $ip . ' — ' . $detail);
    firewall_respond_blocked($detail);
}
