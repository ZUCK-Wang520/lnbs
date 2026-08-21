<?php

declare(strict_types=1);

/**
 * 按 PHP 会话统计「最近 N 秒内有过请求」的访客数，数据存于 public/storage（需可写）。
 */
function online_touch_and_count(): int
{
    $dir = dirname(__DIR__) . '/storage';
    $path = $dir . '/online_sessions.json';
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

    foreach ($data as $id => $ts) {
        if (!is_string($id)) {
            unset($data[$id]);
            continue;
        }
        $ts = is_numeric($ts) ? (int) $ts : 0;
        if ($ts <= 0 || ($now - $ts) > $ttl) {
            unset($data[$id]);
        }
    }

    $data[$sid] = $now;
    $count = count($data);

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $count;
}
