<?php declare(strict_types=1);
$coupleSubnavTitle = '点点滴滴';
require __DIR__ . '/_subnav.php';
$note = (string) ($coupleRow['love_note'] ?? '');
?>
<div class="card couple-lg-page">
  <h1 class="couple-lg-page-title">点点滴滴</h1>
  <p class="muted couple-lg-page-lead">写一句展示在情侣首页的情话（500 字内）。</p>
  <form method="post" action="<?= h(url('/couple/note')) ?>" class="couple-lg-form">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <label class="couple-lg-label" for="love_note">心里话</label>
    <textarea id="love_note" name="love_note" class="couple-lg-textarea" rows="5" maxlength="500" placeholder="例如：想和你把每一天都过成节日。"><?= h($note) ?></textarea>
    <button type="submit" class="btn btn-primary">保存</button>
  </form>
</div>
