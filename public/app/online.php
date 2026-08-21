<?php

declare(strict_types=1);

/**
 * 「在线」统计（近 N 秒内有页面请求）：
 * - 已登录：按用户 ID 去重（u:123），同一账号同一时刻只计 1 人。
 * - 未登录：按会话去重（g:sessid）；登录后去掉本会话的访客键，只保留会员键，避免「先游客再登录」被算成两人。
 * 数据：public/storage/online_presence.json（旧版 online_sessions.json 不再读取，上线后自然过渡到新文件）
 */
function online_touch_and_count(): int
{
    $dir = dirname(__DIR__) . '/storage';
    $path = $dir . '/online_presence.json';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $sid = session_id();
    if ($sid === '') {
        return 0;
    }

    $now = time();
    $ttl = 300;

    $fp = @fopen($path, 'c+');
    if ($fp === false) {
        return 1;
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);

        return 1;
    }

    $raw = stream_get_contents($fp);
    $data = [];
    if ($raw !== false && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }

    $fresh = [];
    foreach ($data as $key => $ts) {
        if (!is_string($key)) {
            continue;
        }
        $ts = is_numeric($ts) ? (int) $ts : 0;
        if ($ts <= 0 || ($now - $ts) > $ttl) {
            continue;
        }
        if (!str_contains($key, ':')) {
            $fresh['g:' . $key] = $ts;
        } else {
            $fresh[$key] = $ts;
        }
    }
    $data = $fresh;

    $uid = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    if ($uid > 0) {
        unset($data['g:' . $sid]);
        $data['u:' . $uid] = $now;
    } else {
        $data['g:' . $sid] = $now;
    }

    $count = count($data);

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $count;
}
