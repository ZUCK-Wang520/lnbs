<?php
declare(strict_types=1);
if (!function_exists('cos_show_forum_upload_widget') || !cos_show_forum_upload_widget()) {
    return;
}
$cosReady = cos_can_upload();
$cosBlocked = cos_forum_upload_blocked_message();
?>
<div class="field cos-forum-upload cos-couple-album-upload" data-cos-upload-wrap data-cos-max="1" data-cos-ready="<?= $cosReady ? '1' : '0' ?>" data-upload-url="<?= h(url('/upload/cos-image')) ?>" data-csrf="<?= h(csrf_token()) ?>">
  <input type="hidden" name="cos_image_urls" class="js-cos-image-urls" value="[]" autocomplete="off">
  <div class="cos-forum-upload-title">上传图片（腾讯云 COS）</div>
  <?php if ($cosBlocked !== '') : ?>
    <p class="cos-forum-upload-warn"><?= h($cosBlocked) ?></p>
  <?php else : ?>
    <p class="muted cos-forum-upload-hint">选择本地图片，上传成功后点击表单下方「添加」写入相册（每次 1 张）。</p>
  <?php endif; ?>
  <div class="cos-forum-upload-row">
    <label class="btn btn-ghost btn-sm cos-forum-upload-label<?= $cosReady ? '' : ' cos-forum-upload-label--disabled' ?>">
      <?= $cosReady ? '选择图片上传' : '上传未就绪' ?>
      <input type="file" class="cos-forum-file-input" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" hidden<?= $cosReady ? '' : ' disabled' ?>>
    </label>
    <span class="cos-forum-upload-status muted" aria-live="polite"></span>
  </div>
  <div class="cos-forum-preview-list" hidden>
    <span class="cos-forum-preview-label muted">当前图片</span>
    <div class="cos-forum-preview-items"></div>
  </div>
</div>
