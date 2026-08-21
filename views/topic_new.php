<?php declare(strict_types=1); ?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <a href="<?= h(url('/board/' . $board['slug'])) ?>"><?= h($board['name']) ?></a>
  <span> / </span>
  <span>发帖</span>
</nav>
<h1 style="margin-bottom:1rem;">发布主题</h1>
<div class="form-panel" style="max-width:640px;margin:0;">
  <form method="post" action="<?= h(url('/board/' . $board['slug'] . '/new')) ?>">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <div class="field">
      <label for="title">标题</label>
      <input id="title" name="title" type="text" required maxlength="200">
    </div>
    <div class="field">
      <label for="body">正文</label>
      <textarea id="body" name="body" required></textarea>
    </div>
    <div class="toolbar" style="margin-bottom:0;">
      <button type="submit" class="btn btn-primary">发布</button>
      <a class="btn btn-ghost" href="<?= h(url('/board/' . $board['slug'])) ?>">取消</a>
    </div>
  </form>
</div>
