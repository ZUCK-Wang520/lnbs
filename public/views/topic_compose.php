<?php declare(strict_types=1); ?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <span>发帖</span>
</nav>
<h1 style="margin-bottom:1rem;">发布主题</h1>
<div class="form-panel" style="max-width:640px;margin:0;">
  <form id="topic-compose-form" method="post" action="<?= h(url('/topic/quick')) ?>" class="js-moderation-submit">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <?php $geetest_form_id = 'topic-compose-form'; require VIEWS . '/partials/geetest_bind.php'; ?>
    <div class="field">
      <label for="board_slug">版块</label>
      <select id="board_slug" name="board_slug" required>
        <?php foreach (($boards ?? []) as $b) : ?>
          <option value="<?= h((string) ($b['slug'] ?? '')) ?>"><?= h((string) ($b['name'] ?? '')) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="title">标题</label>
      <input id="title" name="title" type="text" required maxlength="200" placeholder="想聊点什么？">
    </div>
    <div class="field">
      <label for="body">正文</label>
      <textarea id="body" name="body" placeholder="文明发言，友善交流…"></textarea>
      <?php require VIEWS . '/partials/cos_forum_upload.php'; ?>
    </div>
    <div class="field home-checkbox-field">
      <label class="home-checkbox">
        <input type="checkbox" name="anonymous" value="1">
        <span>匿名发帖（不显示账号昵称；管理员可见真实昵称）</span>
      </label>
      <?php $anonQuotaSlot = 'topic'; require VIEWS . '/partials/anon_quota_hint.php'; ?>
    </div>
    <div class="field">
      <label for="display_nickname">匿名显示名（可选）</label>
      <input id="display_nickname" name="display_nickname" type="text" maxlength="16" placeholder="留空显示为「匿名」">
    </div>
    <?php $pollFormId = 'topic-compose'; require VIEWS . '/partials/topic_poll_fields.php'; ?>
    <div class="toolbar" style="margin-bottom:0;">
      <button type="submit" class="btn btn-primary">发布</button>
      <a class="btn btn-ghost" href="<?= h(url('/')) ?>">取消</a>
    </div>
  </form>
</div>

