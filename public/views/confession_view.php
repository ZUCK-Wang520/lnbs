<?php declare(strict_types=1);
$c = $c ?? [];
$fromLabel = (int) ($c['is_anonymous'] ?? 0) === 1 ? '匿名' : (string) ($c['from_nickname'] ?? '用户');
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <a href="<?= h(url('/confessions')) ?>">表白收件箱</a>
  <span> / </span>
  <span>查看</span>
</nav>

<article class="card confession-detail">
  <header class="confession-detail-head">
    <p class="muted" style="margin:0 0 0.35rem;">来自</p>
    <h1 style="margin:0;font-size:1.25rem;"><?= h($fromLabel) ?></h1>
    <p class="muted" style="margin:0.5rem 0 0;font-size:0.88rem;">
      <?= h((string) ($c['created_at'] ?? '')) ?>
      <?php if (($c['status'] ?? '') === 'ignored') : ?>
        <span class="badge badge-anon" style="margin-left:0.5rem;">你已忽略</span>
      <?php endif; ?>
    </p>
  </header>
  <div class="body-text confession-detail-body"><?= h((string) ($c['body'] ?? '')) ?></div>
  <div class="toolbar confession-detail-actions">
    <a class="btn btn-ghost" href="<?= h(url('/confessions')) ?>">返回收件箱</a>
    <?php if (($c['status'] ?? '') !== 'ignored') : ?>
      <form class="inline-form" method="post" action="<?= h(url('/confessions/ignore')) ?>" onsubmit="return confirm('忽略后可在「已忽略」列表中再次查看。确定？');">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int) ($c['id'] ?? 0) ?>">
        <button type="submit" class="btn btn-ghost">忽略此条</button>
      </form>
    <?php endif; ?>
  </div>
</article>
