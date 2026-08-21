<?php
declare(strict_types=1);
if (!function_exists('cos_show_forum_upload_widget') || !cos_show_forum_upload_widget()) {
    return;
}
$cosReady = cos_can_upload();
$cosBlocked = cos_forum_upload_blocked_message();
?>
<div class="field cos-forum-upload" data-cos-upload-wrap data-cos-ready="<?= $cosReady ? '1' : '0' ?>" data-upload-url="<?= h(url('/upload/cos-image')) ?>" data-csrf="<?= h(csrf_token()) ?>">
  <input type="hidden" name="cos_image_urls" class="js-cos-image-urls" value="[]" autocomplete="off">
  <div class="cos-forum-upload-title">插图（腾讯云 COS）</div>
  <?php if ($cosBlocked !== '') : ?>
    <p class="cos-forum-upload-warn"><?= h($cosBlocked) ?></p>
  <?php else : ?>
    <p class="muted cos-forum-upload-hint">图片显示在正文下方；正文框只写文字，不必粘贴链接。最多 12 张。下方可另传视频（最多 3 个）。</p>
  <?php endif; ?>
  <div class="cos-forum-upload-row">
    <label class="btn btn-ghost btn-sm cos-forum-upload-label<?= $cosReady ? '' : ' cos-forum-upload-label--disabled' ?>">
      <?= $cosReady ? '选择图片上传' : '上传未就绪' ?>
      <input type="file" class="cos-forum-file-input" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" hidden<?= $cosReady ? '' : ' disabled' ?>>
    </label>
    <span class="cos-forum-upload-status muted" aria-live="polite"></span>
  </div>
  <div class="cos-forum-preview-list" hidden>
    <span class="cos-forum-preview-label muted">已选图片</span>
    <div class="cos-forum-preview-items"></div>
  </div>
</div>
<div class="field cos-forum-upload cos-forum-upload--video" data-cos-video-wrap data-cos-ready="<?= $cosReady ? '1' : '0' ?>" data-upload-url="<?= h(url('/upload/cos-video')) ?>" data-csrf="<?= h(csrf_token()) ?>">
  <input type="hidden" name="cos_video_urls" class="js-cos-video-urls" value="[]" autocomplete="off">
  <div class="cos-forum-upload-title">视频（腾讯云 COS）</div>
  <?php if ($cosBlocked !== '') : ?>
    <p class="cos-forum-upload-warn"><?= h($cosBlocked) ?></p>
  <?php else : ?>
    <p class="muted cos-forum-upload-hint">支持 MP4、WebM、MOV；显示在正文下方，最多 3 个。文件较大时请耐心等待上传完成。</p>
  <?php endif; ?>
  <div class="cos-forum-upload-row">
    <label class="btn btn-ghost btn-sm cos-forum-upload-label<?= $cosReady ? '' : ' cos-forum-upload-label--disabled' ?>">
      <?= $cosReady ? '选择视频上传' : '上传未就绪' ?>
      <input type="file" class="cos-forum-video-input" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov" hidden<?= $cosReady ? '' : ' disabled' ?>>
    </label>
    <span class="cos-forum-video-status muted" aria-live="polite"></span>
  </div>
  <div class="cos-forum-video-preview-list cos-forum-preview-list" hidden>
    <span class="cos-forum-preview-label muted">已选视频</span>
    <div class="cos-forum-video-preview-items cos-forum-preview-items"></div>
  </div>
</div>
