<?php

declare(strict_types=1);

/** 上传后的站点 Logo 相对 public/ 的路径记录文件 */
function site_logo_meta_path(): string
{
    return dirname(__DIR__) . '/storage/site_logo.json';
}

/** @return array{path:?string} */
function site_logo_meta_read(): array
{
    $file = site_logo_meta_path();
    if (!is_readable($file)) {
        return ['path' => null];
    }
    $raw = @file_get_contents($file);
    if ($raw === false || $raw === '') {
        return ['path' => null];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['path' => null];
    }
    $path = isset($data['path']) ? trim((string) $data['path']) : '';
    if ($path === '' || !preg_match('#^uploads/site/logo\.(jpe?g|png|gif|webp)$#i', $path)) {
        return ['path' => null];
    }

    return ['path' => $path];
}

function site_logo_meta_write(?string $relativePath): void
{
    $dir = dirname(site_logo_meta_path());
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('无法写入 storage 目录。');
    }
    $payload = json_encode(['path' => $relativePath], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        throw new RuntimeException('无法编码 Logo 元数据。');
    }
    if (@file_put_contents(site_logo_meta_path(), $payload . "\n", LOCK_EX) === false) {
        throw new RuntimeException('无法保存 Logo 元数据。');
    }
}

/** 已上传且可读的站点 Logo 相对路径；无则 null */
function site_logo_uploaded_relative_path(): ?string
{
    $meta = site_logo_meta_read();
    $path = $meta['path'] ?? null;
    if ($path === null || $path === '') {
        return null;
    }
    $full = dirname(__DIR__) . '/' . str_replace('\\', '/', $path);
    if (!is_readable($full)) {
        return null;
    }

    return $path;
}

/**
 * 处理站长上传的站点 Logo（JPG/PNG/WebP/GIF，≤2MB）。
 *
 * @return array{ok:bool,error:?string,path:?string}
 */
function site_logo_process_upload(): array
{
    if (empty($_FILES['logo']) || !is_array($_FILES['logo'])) {
        return ['ok' => false, 'error' => '请选择 Logo 图片文件。', 'path' => null];
    }
    $f = $_FILES['logo'];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => '请选择 Logo 图片文件。', 'path' => null];
    }
    if (($f['error'] ?? 0) !== UPLOAD_ERR_OK) {
        $err = (int) ($f['error'] ?? 0);
        $map = [
            UPLOAD_ERR_INI_SIZE => '文件超过服务器允许大小（php.ini 中 upload_max_filesize）。',
            UPLOAD_ERR_FORM_SIZE => '文件超过表单限制。',
            UPLOAD_ERR_PARTIAL => '文件只上传了一部分，请重试。',
            UPLOAD_ERR_NO_TMP_DIR => '服务器临时目录不可用。',
            UPLOAD_ERR_CANT_WRITE => '服务器无法写入临时文件。',
        ];

        return ['ok' => false, 'error' => ($map[$err] ?? '文件上传失败，请重试。'), 'path' => null];
    }
    $tmp = (string) ($f['tmp_name'] ?? '');
    if ($tmp === '' || !is_file($tmp) || !is_readable($tmp)) {
        return ['ok' => false, 'error' => '临时文件无效，请重试。', 'path' => null];
    }
    if (($f['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'Logo 须小于 2MB。', 'path' => null];
    }

    $info = @getimagesize($tmp);
    if ($info === false || ($info[0] ?? 0) < 1 || ($info[1] ?? 0) < 1) {
        return ['ok' => false, 'error' => '无法读取图片，请换一张试试。', 'path' => null];
    }

    $mime = '';
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        if ($fi !== false) {
            $mime = (string) finfo_file($fi, $tmp);
            finfo_close($fi);
        }
    }
    if ($mime === '' && !empty($info['mime'])) {
        $mime = (string) $info['mime'];
    }
    $map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if ($mime === '' || !isset($map[$mime])) {
        return ['ok' => false, 'error' => '仅支持 JPG、PNG、WebP、GIF 图片。', 'path' => null];
    }
    if ($info[0] > 2048 || $info[1] > 2048) {
        return ['ok' => false, 'error' => 'Logo 宽高不能超过 2048 像素。', 'path' => null];
    }

    $ext = $map[$mime];
    $publicRoot = dirname(__DIR__);
    $dir = $publicRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'site';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => '无法创建目录 public/uploads/site，请手动创建并设为可写。', 'path' => null];
    }

    // 清理旧扩展名文件，避免残留多份 logo.*
    foreach (['jpg', 'jpeg', 'png', 'gif', 'webp'] as $oldExt) {
        $old = $dir . DIRECTORY_SEPARATOR . 'logo.' . $oldExt;
        if (is_file($old)) {
            @unlink($old);
        }
    }

    $relative = 'uploads/site/logo.' . $ext;
    $dest = $publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!@move_uploaded_file($tmp, $dest)) {
        if (!@copy($tmp, $dest)) {
            return [
                'ok' => false,
                'error' => '保存 Logo 失败。请检查 public/uploads 是否可写。',
                'path' => null,
            ];
        }
        @unlink($tmp);
    }
    @chmod($dest, 0644);

    try {
        site_logo_meta_write($relative);
    } catch (Throwable $e) {
        @unlink($dest);

        return ['ok' => false, 'error' => $e->getMessage(), 'path' => null];
    }

    return ['ok' => true, 'error' => null, 'path' => $relative];
}

/** 删除已上传的站点 Logo，恢复为文字/默认标记 */
function site_logo_clear_upload(): void
{
    $meta = site_logo_meta_read();
    $path = $meta['path'] ?? null;
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'site';
    foreach (['jpg', 'jpeg', 'png', 'gif', 'webp'] as $ext) {
        $file = $dir . DIRECTORY_SEPARATOR . 'logo.' . $ext;
        if (is_file($file)) {
            @unlink($file);
        }
    }
    if ($path !== null) {
        $full = dirname(__DIR__) . '/' . str_replace('\\', '/', $path);
        if (is_file($full)) {
            @unlink($full);
        }
    }
    site_logo_meta_write(null);
}
