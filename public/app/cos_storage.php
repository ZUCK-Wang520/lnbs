<?php

declare(strict_types=1);

/**
 * 腾讯云 COS：发帖插图上传（服务端简单上传，与官方 PHP SDK 一致）。
 *
 * @see https://cloud.tencent.com/document/product/436/64283 上传对象
 */

/** @return array<string, mixed> */
function cos_config(): array
{
    $c = $GLOBALS['APP_CONFIG']['cos'] ?? [];

    return is_array($c) ? $c : [];
}

function cos_can_upload(): bool
{
    $c = cos_config();
    if (empty($c['enabled'])) {
        return false;
    }
    if (!class_exists(\Qcloud\Cos\Client::class)) {
        return false;
    }
    $sid = trim((string) ($c['secret_id'] ?? ''));
    $sk = trim((string) ($c['secret_key'] ?? ''));
    $bucket = trim((string) ($c['bucket'] ?? ''));
    $region = trim((string) ($c['region'] ?? ''));

    return $sid !== '' && $sk !== '' && $bucket !== '' && $region !== '';
}

/** 是否在发帖/回复表单展示 COS 插图区域（与 cos.enabled 一致） */
function cos_show_forum_upload_widget(): bool
{
    return !empty(cos_config()['enabled']);
}

/** 上传未就绪时的说明（就绪时返回空字符串） */
function cos_forum_upload_blocked_message(): string
{
    if (!cos_show_forum_upload_widget()) {
        return '';
    }
    if (cos_can_upload()) {
        return '';
    }
    if (!class_exists(\Qcloud\Cos\Client::class)) {
        return '服务器尚未安装 COS 组件：请在网站 public 目录执行 composer install（需安装 qcloud/cos-sdk-v5）。';
    }
    $c = cos_config();
    $sid = trim((string) ($c['secret_id'] ?? ''));
    $sk = trim((string) ($c['secret_key'] ?? ''));
    if ($sid === '' || $sk === '') {
        return '请在 config.local.php 的 cos 中填写 secret_id 与 secret_key（腾讯云 CAM 访问密钥）。';
    }
    $bucket = trim((string) ($c['bucket'] ?? ''));
    $region = trim((string) ($c['region'] ?? ''));
    if ($bucket === '' || $region === '') {
        return '请在 config.local.php 中填写 cos.bucket 与 cos.region。';
    }
    $pub = trim((string) ($c['public_base_url'] ?? ''));
    if ($pub === '') {
        return '请在 config.local.php 中填写 cos.public_base_url。';
    }

    return 'COS 配置不完整，请对照 config.example.php 检查 cos 各字段。';
}

/**
 * 用于正文渲染：仅允许这些 URL 前缀展示为 &lt;img&gt;（防 XSS）。
 *
 * @return list<string>
 */
function forum_cos_allowed_url_prefixes(): array
{
    $c = cos_config();
    $out = [];
    $base = trim((string) ($c['public_base_url'] ?? ''));
    if ($base !== '') {
        $out[] = rtrim($base, '/');
    }
    $extra = $c['extra_allowed_url_prefixes'] ?? [];
    if (is_string($extra)) {
        $extra = array_filter(array_map('trim', preg_split('/[\s,]+/', $extra, -1, PREG_SPLIT_NO_EMPTY) ?: []));
    }
    if (is_array($extra)) {
        foreach ($extra as $p) {
            $p = trim((string) $p);
            if ($p !== '') {
                $out[] = rtrim($p, '/');
            }
        }
    }

    $bucket = trim((string) ($c['bucket'] ?? ''));
    $region = trim((string) ($c['region'] ?? ''));
    if ($bucket !== '' && $region !== '') {
        // 与简单上传返回的直链一致；public_base_url 常为自定义域名时也必须信任 bucket.cos.region.myqcloud.com
        $out[] = 'https://' . $bucket . '.cos.' . $region . '.myqcloud.com';
    }

    return array_values(array_unique($out));
}

/** 是否为配置中允许的 COS / CDN 前缀下的资源地址（插图与视频共用白名单） */
function forum_cos_is_trusted_public_url(string $url, ?array $prefixes = null): bool
{
    $url = trim($url);
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        return false;
    }
    if (preg_match('#[\s<>"\'`]#u', $url)) {
        return false;
    }
    $prefixes = $prefixes ?? forum_cos_allowed_url_prefixes();
    if ($prefixes === []) {
        return false;
    }

    $urlLower = strtolower($url);
    // 忽略 scheme 差异（https://xxx 与 http://xxx 认为同一“前缀来源”）
    $urlNoScheme = preg_replace('#^https?://#i', '', $urlLower) ?: $urlLower;

    foreach ($prefixes as $pre) {
        $pLower = strtolower(trim((string) $pre));
        if ($pLower === '') {
            continue;
        }
        $pNoScheme = preg_replace('#^https?://#i', '', $pLower) ?: $pLower;
        if (
            $pNoScheme !== ''
            && str_starts_with($urlNoScheme, $pNoScheme)
            && ($urlNoScheme === $pNoScheme || str_starts_with($urlNoScheme, $pNoScheme . '/'))
        ) {
            return true;
        }
    }

    return false;
}

function forum_cos_is_allowed_image_url(string $url, ?array $prefixes = null): bool
{
    return forum_cos_is_trusted_public_url($url, $prefixes);
}

function cos_public_url_for_key(string $key): string
{
    $c = cos_config();
    $base = rtrim(trim((string) ($c['public_base_url'] ?? '')), '/');
    $key = ltrim(str_replace('\\', '/', $key), '/');

    return $base !== '' ? $base . '/' . $key : '';
}

/**
 * @return array{ok:bool,error?:string,url?:string}
 */
function cos_upload_forum_image(int $userId, string $tmpPath, int $sizeBytes): array
{
    if (!cos_can_upload()) {
        return ['ok' => false, 'error' => '对象存储未配置或未安装 SDK。请在 public 目录执行 composer install，并配置 cos。'];
    }
    if ($userId <= 0 || $tmpPath === '' || !is_readable($tmpPath)) {
        return ['ok' => false, 'error' => '文件无效，请重试。'];
    }
    $c = cos_config();
    $max = (int) ($c['max_bytes'] ?? 5242880);
    if ($max < 102400) {
        $max = 5242880;
    }
    if ($sizeBytes > $max) {
        return ['ok' => false, 'error' => '图片须小于 ' . round($max / 1048576, 1) . 'MB。'];
    }

    $info = @getimagesize($tmpPath);
    if ($info === false || ($info[0] ?? 0) < 1 || ($info[1] ?? 0) < 1) {
        return ['ok' => false, 'error' => '无法识别为图片，请换一张试试。'];
    }

    $type = (int) ($info[2] ?? 0);
    /** @var array<int, array{0:string,1:string}> */
    $fromType = [
        IMAGETYPE_JPEG => ['image/jpeg', 'jpg'],
        IMAGETYPE_PNG => ['image/png', 'png'],
        IMAGETYPE_GIF => ['image/gif', 'gif'],
        IMAGETYPE_WEBP => ['image/webp', 'webp'],
    ];

    $mime = '';
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        if ($fi !== false) {
            $mime = strtolower(trim((string) finfo_file($fi, $tmpPath)));
            finfo_close($fi);
        }
    }
    if ($mime === '' && !empty($info['mime'])) {
        $mime = strtolower(trim((string) $info['mime']));
    }

    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/x-png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    $ext = '';
    if ($mime !== '' && isset($allowedMime[$mime])) {
        $ext = $allowedMime[$mime];
    }
    if ($ext === '' && isset($fromType[$type])) {
        $mime = $fromType[$type][0];
        $ext = $fromType[$type][1];
    }
    if ($ext === '' && ($mime === 'application/octet-stream' || $mime === 'binary/octet-stream') && isset($fromType[$type])) {
        $mime = $fromType[$type][0];
        $ext = $fromType[$type][1];
    }
    if ($ext === '') {
        return ['ok' => false, 'error' => '仅支持 JPG、PNG、GIF、WebP。'];
    }

    $prefix = trim((string) ($c['key_prefix'] ?? 'forum'), '/');
    $ym = gmdate('Y/m');
    $uniq = bin2hex(random_bytes(8));
    $key = ($prefix !== '' ? $prefix . '/' : '') . $ym . '/u' . $userId . '/' . $uniq . '.' . $ext;

    $bucket = trim((string) $c['bucket']);
    $region = trim((string) $c['region']);
    $sid = trim((string) $c['secret_id']);
    $sk = trim((string) $c['secret_key']);

    try {
        $client = new \Qcloud\Cos\Client([
            'region' => $region,
            'scheme' => 'https',
            'credentials' => [
                'secretId' => $sid,
                'secretKey' => $sk,
            ],
        ]);
        // 与文档一致：简单上传 / 高级 upload（小文件走 PUT Object）
        $opts = [
            'ContentType' => $mime,
        ];
        if (!empty($c['object_acl'])) {
            $opts['ACL'] = (string) $c['object_acl'];
        }
        $client->upload(
            $bucket,
            $key,
            fopen($tmpPath, 'rb'),
            $opts
        );
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => '上传到 COS 失败，请稍后重试或联系管理员。'];
    }

    $url = cos_public_url_for_key($key);
    if ($url === '') {
        return ['ok' => false, 'error' => '已上传但未配置 public_base_url，无法生成访问地址。'];
    }

    return ['ok' => true, 'url' => $url];
}

/**
 * @return array{ok:bool,error?:string,url?:string}
 */
function cos_upload_forum_video(int $userId, string $tmpPath, int $sizeBytes, string $origName = ''): array
{
    if (!cos_can_upload()) {
        return ['ok' => false, 'error' => '对象存储未配置或未安装 SDK。请在 public 目录执行 composer install，并配置 cos。'];
    }
    if ($userId <= 0 || $tmpPath === '' || !is_readable($tmpPath)) {
        return ['ok' => false, 'error' => '文件无效，请重试。'];
    }
    $c = cos_config();
    $max = (int) ($c['max_video_bytes'] ?? 83886080);
    if ($max < 1048576) {
        $max = 83886080;
    }
    if ($sizeBytes > $max) {
        return ['ok' => false, 'error' => '视频须小于 ' . round($max / 1048576, 0) . 'MB。'];
    }

    $mime = '';
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        if ($fi !== false) {
            $mime = strtolower(trim((string) finfo_file($fi, $tmpPath)));
            finfo_close($fi);
        }
    }

    $allowed = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
    ];
    $ext = $allowed[$mime] ?? '';
    if ($ext === '' && $origName !== '') {
        $bx = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $map = ['mp4' => 'mp4', 'webm' => 'webm', 'mov' => 'mov'];
        if (isset($map[$bx])) {
            $ext = $map[$bx];
            $mime = $ext === 'mov' ? 'video/quicktime' : ('video/' . $ext);
        }
    }
    if ($ext === '') {
        return ['ok' => false, 'error' => '仅支持 MP4、WebM、MOV（H.264 等常见编码）。'];
    }

    $prefix = trim((string) ($c['key_prefix'] ?? 'forum'), '/');
    $ym = gmdate('Y/m');
    $uniq = bin2hex(random_bytes(8));
    $key = ($prefix !== '' ? $prefix . '/' : '') . 'video/' . $ym . '/u' . $userId . '/' . $uniq . '.' . $ext;

    $bucket = trim((string) $c['bucket']);
    $region = trim((string) $c['region']);
    $sid = trim((string) $c['secret_id']);
    $sk = trim((string) $c['secret_key']);

    try {
        $client = new \Qcloud\Cos\Client([
            'region' => $region,
            'scheme' => 'https',
            'credentials' => [
                'secretId' => $sid,
                'secretKey' => $sk,
            ],
        ]);
        $opts = [
            'ContentType' => $mime,
        ];
        if (!empty($c['object_acl'])) {
            $opts['ACL'] = (string) $c['object_acl'];
        }
        $client->upload(
            $bucket,
            $key,
            fopen($tmpPath, 'rb'),
            $opts
        );
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => '上传到 COS 失败，请稍后重试或联系管理员。'];
    }

    $url = cos_public_url_for_key($key);
    if ($url === '') {
        return ['ok' => false, 'error' => '已上传但未配置 public_base_url，无法生成访问地址。'];
    }

    return ['ok' => true, 'url' => $url];
}
