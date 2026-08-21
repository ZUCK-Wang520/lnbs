<?php declare(strict_types=1);
$coupleSubnavTitle = '恋爱相册';
require __DIR__ . '/_subnav.php';
$extrasOk = !empty($extrasOk);
$gallery = is_array($gallery ?? null) ? $gallery : [];
?>
<?php
$cosAlbumWidget = function_exists('cos_show_forum_upload_widget') && cos_show_forum_upload_widget();
$cosAlbumReady = $cosAlbumWidget && function_exists('cos_can_upload') && cos_can_upload();
?>
<div class="card couple-lg-page">
  <h1 class="couple-lg-page-title">Love Photo</h1>
  <?php if ($cosAlbumReady) : ?>
    <p class="muted couple-lg-page-lead">通过腾讯云 COS 上传图片，与发帖插图使用同一套配置与接口。</p>
  <?php else : ?>
    <p class="muted couple-lg-page-lead">
      <?php if ($cosAlbumWidget) : ?>
        COS 未就绪时可手动填写已允许的 https 图片地址；配置并就绪后请优先使用下方上传。
      <?php else : ?>
        开启 COS 后可在下方上传图片；未开启时请填写 https 图片链接。
      <?php endif; ?>
    </p>
  <?php endif; ?>
  <?php if (!$extrasOk) : ?>
    <p class="muted">请执行 <code class="couple-lg-code">migration_couple_extras.sql</code> 后刷新本页。</p>
  <?php else : ?>
    <form method="post" action="<?= h(url('/couple/album/add')) ?>" class="couple-lg-form couple-album-form">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <?php if ($cosAlbumReady) : ?>
        <input type="hidden" name="image_url" id="couple_album_cos_mirror" value="">
      <?php endif; ?>
      <?php if ($cosAlbumWidget) : ?>
        <?php require VIEWS . '/partials/cos_couple_album_upload.php'; ?>
      <?php endif; ?>
      <?php if (!$cosAlbumReady) : ?>
        <label class="couple-lg-label" for="image_url">图片地址（https）</label>
        <input type="url" id="image_url" name="image_url" class="couple-lg-input" maxlength="2048" <?= $cosAlbumReady ? '' : 'required' ?> placeholder="https://">
      <?php endif; ?>
      <label class="couple-lg-label" for="caption">说明（可选）</label>
      <input type="text" id="caption" name="caption" class="couple-lg-input" maxlength="200">
      <button type="submit" class="btn btn-primary"><?= $cosAlbumReady ? '添加到相册' : '添加' ?></button>
    </form>
  <?php endif; ?>
</div>
<?php if ($extrasOk && $gallery !== []) : ?>
  <div class="couple-lg-gallery">
    <?php foreach ($gallery as $g) : ?>
      <figure class="card couple-lg-gallery-item">
        <a href="<?= h((string) ($g['image_url'] ?? '')) ?>" target="_blank" rel="noopener noreferrer" class="couple-lg-gallery-link">
          <img src="<?= h((string) ($g['image_url'] ?? '')) ?>" alt="" loading="lazy" class="couple-lg-gallery-img">
        </a>
        <?php if (trim((string) ($g['caption'] ?? '')) !== '') : ?>
          <figcaption class="couple-lg-gallery-cap"><?= h(trim((string) $g['caption'])) ?></figcaption>
        <?php endif; ?>
        <form method="post" action="<?= h(url('/couple/album/delete')) ?>" class="couple-lg-gallery-del" onsubmit="return confirm('删除这张？');">
          <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int) ($g['id'] ?? 0) ?>">
          <button type="submit" class="btn btn-ghost btn-sm">删除</button>
        </form>
      </figure>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
