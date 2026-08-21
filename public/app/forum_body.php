<?php

declare(strict_types=1);

/**
 * 主题/回复正文：转义 HTML、换行，并将受信任的 COS 图片 / 视频 Markdown 渲染为标签。
 */
function forum_cos_body_url_looks_like_video(string $url): bool
{
    return (bool) preg_match('~\.(mp4|webm|mov)(\?|#|$)~i', trim($url));
}

/**
 * 安全过滤：只允许 http(s) 且不包含空白/引号等字符，避免拼接到 HTML 属性时出问题。
 */
function forum_cos_body_url_basic_safety_ok(string $url): bool
{
    $url = trim($url);
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        return false;
    }
    // 禁止空白与常见引号/尖括号，避免属性注入/HTML 破坏。
    if (preg_match('#[\s<>"\'`]#u', $url)) {
        return false;
    }
    return true;
}

function forum_cos_video_type_from_url(string $url): string
{
    $u = strtolower($url);
    if (preg_match('~\.webm(\?|#|$)~', $u)) {
        return 'video/webm';
    }
    if (preg_match('~\.mov(\?|#|$)~', $u)) {
        return 'video/quicktime';
    }

    return 'video/mp4';
}

function forum_body_format_html(string $body): string
{
    $prefixes = forum_cos_allowed_url_prefixes();
    $pattern = '/!\[([^\]]*)\]\((https?:[^)\s]+)\)/u';
    $out = '';
    $offset = 0;
    while (preg_match($pattern, $body, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $whole = $m[0][0];
        $pos = $m[0][1];
        $before = substr($body, $offset, $pos - $offset);
        $out .= forum_body_escape_nl2br($before);
        $alt = (string) $m[1][0];
        $url = (string) $m[2][0];
        // 图片仍使用“受信任前缀”校验；视频在你当前场景下更容易出现前缀误判，
        // 因此放宽为：只要是 http(s) + 且看起来是视频后缀，并进行基础字符安全过滤即可。
        if (forum_cos_body_url_looks_like_video($url)) {
            if (forum_cos_body_url_basic_safety_ok($url)) {
                $vtype = forum_cos_video_type_from_url($url);
                $out .= '<video class="body-inline-video" controls playsinline preload="metadata" title="' . h($alt) . '">'
                    . '<source src="' . h($url) . '" type="' . h($vtype) . '">'
                    . '</video>';
            } else {
                $out .= h($whole);
            }
        } elseif (forum_cos_is_trusted_public_url($url, $prefixes)) {
            $out .= '<img class="body-inline-img" src="' . h($url) . '" alt="' . h($alt) . '" loading="lazy" decoding="async">';
        } else {
            $out .= h($whole);
        }
        $offset = $pos + strlen($whole);
    }
    $out .= forum_body_escape_nl2br(substr($body, $offset));

    return $out;
}

function forum_body_escape_nl2br(string $s): string
{
    return nl2br(h($s), false);
}

/**
 * 将表单中的 COS 插图 URL 列表（JSON 数组）合并到正文末尾，用于入库；URL 须通过白名单校验。
 */
function forum_merge_body_with_cos_image_urls(string $bodyText, string $jsonUrls): string
{
    $arr = json_decode($jsonUrls, true);
    if (!is_array($arr)) {
        return $bodyText;
    }
    $prefixes = forum_cos_allowed_url_prefixes();
    $lines = [];
    $max = 12;
    foreach ($arr as $u) {
        if (count($lines) >= $max) {
            break;
        }
        if (!is_string($u)) {
            continue;
        }
        $u = trim($u);
        if ($u === '') {
            continue;
        }
        if (!forum_cos_is_trusted_public_url($u, $prefixes)) {
            continue;
        }
        if (forum_cos_body_url_looks_like_video($u)) {
            continue;
        }
        $lines[] = '![](' . $u . ')';
    }
    if ($lines === []) {
        return $bodyText;
    }
    $block = implode("\n", $lines);
    $t = trim($bodyText);

    return $t === '' ? $block : $bodyText . "\n\n" . $block;
}

/**
 * 将 COS 视频 URL 合并到正文（与插图分列，最多 3 个）。
 */
function forum_merge_body_with_cos_video_urls(string $bodyText, string $jsonUrls): string
{
    $arr = json_decode($jsonUrls, true);
    if (!is_array($arr)) {
        return $bodyText;
    }
    $lines = [];
    $max = 3;
    foreach ($arr as $u) {
        if (count($lines) >= $max) {
            break;
        }
        if (!is_string($u)) {
            continue;
        }
        $u = trim($u);
        if ($u === '') {
            continue;
        }
        if (!forum_cos_body_url_looks_like_video($u)) {
            continue;
        }
        if (!forum_cos_body_url_basic_safety_ok($u)) {
            continue;
        }
        $lines[] = '![](' . $u . ')';
    }
    if ($lines === []) {
        return $bodyText;
    }
    $block = implode("\n", $lines);
    $t = trim($bodyText);

    return $t === '' ? $block : $bodyText . "\n\n" . $block;
}

/**
 * 发帖正文：先合并插图再合并视频（供路由调用）。
 */
function forum_merge_topic_body_from_post(string $bodyText, string $jsonImageUrls, string $jsonVideoUrls): string
{
    $b = forum_merge_body_with_cos_image_urls($bodyText, $jsonImageUrls);

    return forum_merge_body_with_cos_video_urls($b, $jsonVideoUrls);
}
