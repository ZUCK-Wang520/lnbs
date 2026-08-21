<?php

declare(strict_types=1);

/** @param string $ext 不含点，如 jpg、png */
function avatar_build_relative_path(int $userId, string $ext): string
{
    return 'uploads/avatars/u' . $userId . '.' . strtolower($ext);
}

/** 可对外展示的头像 URL；无头像或未落盘时返回 null */
function user_avatar_public_url(?string $dbPath): ?string
{
    if ($dbPath === null || trim($dbPath) === '') {
        return null;
    }
    $clean = ltrim(str_replace('\\', '/', $dbPath), '/');
    if (!preg_match('#^uploads/avatars/u\d+\.(jpe?g|png|gif|webp)$#i', $clean)) {
        return null;
    }
    $full = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $clean);
    if (!is_readable($full)) {
        return null;
    }

    return public_url($clean);
}

function avatar_public_root(): string
{
    $root = realpath(dirname(__DIR__));
    if ($root === false) {
        return dirname(__DIR__);
    }

    return $root;
}

/**
 * 处理 $_FILES['avatar']：2MB 以内原样保存，不压缩、不转码（保留 JPG/PNG/WebP/GIF）。
 *
 * @return array{ok:bool,error:?string,path:?string}
 */
function avatar_process_upload(int $userId): array
{
    if (empty($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
        return ['ok' => false, 'error' => '请选择图片文件。', 'path' => null];
    }
    $f = $_FILES['avatar'];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => '请选择图片文件。', 'path' => null];
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
        $msg = $map[$err] ?? '文件上传失败，请重试。';

        return ['ok' => false, 'error' => $msg, 'path' => null];
    }
    $tmp = (string) ($f['tmp_name'] ?? '');
    if ($tmp === '' || !is_file($tmp) || !is_readable($tmp)) {
        return ['ok' => false, 'error' => '临时文件无效，请重试。', 'path' => null];
    }
    if (($f['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['ok' => false, 'error' => '图片须小于 2MB。', 'path' => null];
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
    if ($info[0] > 4096 || $info[1] > 4096) {
        return ['ok' => false, 'error' => '图片宽高不能超过 4096 像素。', 'path' => null];
    }

    $publicRoot = avatar_public_root();
    $publicDir = $publicRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';
    if (!is_dir($publicDir)) {
        if (!@mkdir($publicDir, 0775, true) && !is_dir($publicDir)) {
            return ['ok' => false, 'error' => '无法创建目录 public/uploads/avatars，请手动创建并设为可写。', 'path' => null];
        }
        @chmod($publicDir, 0775);
    }
    // 不在此用 is_writable 拦截：Windows/IIS 及部分主机会误报，直接尝试写入更可靠。
    @chmod($publicDir, 0775);

    $ext = $map[$mime];
    $destRel = avatar_build_relative_path($userId, $ext);
    $destAbs = $publicDir . DIRECTORY_SEPARATOR . 'u' . $userId . '.' . strtolower($ext);

    foreach (glob($publicDir . DIRECTORY_SEPARATOR . 'u' . $userId . '.*') ?: [] as $old) {
        if (is_file($old)) {
            @unlink($old);
        }
    }

    if (is_file($destAbs)) {
        @unlink($destAbs);
    }

    $saved = false;
    if (is_uploaded_file($tmp)) {
        $saved = @move_uploaded_file($tmp, $destAbs);
    }
    if (!$saved) {
        $saved = @copy($tmp, $destAbs);
    }
    if (!$saved) {
        error_log('avatar save failed dest=' . $destAbs . ' upload_tmp=' . $tmp);
        return [
            'ok' => false,
            'error' => '保存头像失败。请检查：① 目录 public/uploads/avatars 存在；② Linux 执行 chmod -R 775 public/uploads，属主改为运行 PHP 的用户（如 www、www-data）；③ Windows/IIS 在资源管理器里给站点用户对 uploads 的「修改」权限；④ php.ini 中 upload_tmp_dir 可写。',
            'path' => null,
        ];
    }

    @chmod($destAbs, 0644);

    return ['ok' => true, 'error' => null, 'path' => $destRel];
}
