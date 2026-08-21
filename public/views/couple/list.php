<?php declare(strict_types=1);
$coupleSubnavTitle = '恋爱列表';
require __DIR__ . '/_subnav.php';
$extrasOk = !empty($extrasOk);
$promises = is_array($promises ?? null) ? $promises : [];
?>
<div class="card couple-lg-page">
  <h1 class="couple-lg-page-title">Love List</h1>
  <p class="muted couple-lg-page-lead">记下想一起完成的小事，勾选表示已达成。</p>
  <?php if (!$extrasOk) : ?>
    <p class="muted">请执行 <code class="couple-lg-code">migration_couple_extras.sql</code> 后刷新本页。</p>
  <?php else : ?>
    <form method="post" action="<?= h(url('/couple/list/add')) ?>" class="couple-lg-form couple-lg-form--inline">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <input type="text" name="body" class="couple-lg-input couple-lg-input--grow" maxlength="300" required placeholder="例如：一起看一次日出">
      <button type="submit" class="btn btn-primary">添加</button>
    </form>
  <?php endif; ?>
</div>
<?php if ($extrasOk && $promises !== []) : ?>
  <ul class="couple-lg-promise-list">
    <?php foreach ($promises as $pr) : ?>
      <li class="card couple-lg-promise-item<?= !empty($pr['is_done']) ? ' couple-lg-promise-item--done' : '' ?>">
        <span class="couple-lg-promise-text"><?= h((string) ($pr['body'] ?? '')) ?></span>
        <div class="couple-lg-promise-actions">
          <form method="post" action="<?= h(url('/couple/list/toggle')) ?>" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int) ($pr['id'] ?? 0) ?>">
            <button type="submit" class="btn btn-ghost btn-sm"><?= !empty($pr['is_done']) ? '标为未完成' : '完成' ?></button>
          </form>
          <form method="post" action="<?= h(url('/couple/list/delete')) ?>" class="inline-form" onsubmit="return confirm('删除这条约定？');">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int) ($pr['id'] ?? 0) ?>">
            <button type="submit" class="btn btn-ghost btn-sm">删除</button>
          </form>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php elseif ($extrasOk) : ?>
  <p class="muted couple-lg-empty">还没有约定，添一条吧。</p>
<?php endif; ?>
