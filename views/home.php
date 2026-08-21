<?php declare(strict_types=1); ?>
<section class="hero">
  <h1>欢迎来到鲁巴校园论坛</h1>
  <p>分享学习与生活，发现同好。选择下方版块进入讨论。</p>
</section>
<div class="grid-boards">
  <?php foreach ($boards as $b) : ?>
    <a href="<?= h(url('/board/' . $b['slug'])) ?>" class="card" style="text-decoration:none;color:inherit;display:block;">
      <h2><?= h($b['name']) ?></h2>
      <div class="meta"><?= (int) $b['topic_count'] ?> 个主题</div>
      <p class="muted" style="margin:0;font-size:0.92rem;"><?= h($b['description']) ?></p>
    </a>
  <?php endforeach; ?>
</div>
<?php if (empty($boards)) : ?>
  <p class="muted">暂无版块，请管理员在后台创建。</p>
<?php endif; ?>
