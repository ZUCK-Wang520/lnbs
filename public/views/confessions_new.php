<?php declare(strict_types=1); ?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <span>写表白</span>
</nav>

<div class="confession-hub-nav">
  <a class="btn btn-ghost btn-sm" href="<?= h(url('/confessions')) ?>">收件箱</a>
  <a class="btn btn-ghost btn-sm" href="<?= h(url('/confessions/sent')) ?>">我发出的</a>
</div>

<div class="form-panel confession-form-panel">
  <h1 style="margin-top:0;font-size:1.2rem;">写表白</h1>
  <p class="muted" style="margin-top:0;">向对方发送一段话。请填写对方在本站<strong>注册时使用的 11 位手机号</strong>（与登录账号一致）。登录用户专属，文明表达。</p>
  <form method="post" action="<?= h(url('/confessions/send')) ?>">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <div class="field">
      <label for="conf_target">对方手机号</label>
      <input id="conf_target" name="target" type="tel" required inputmode="numeric" autocomplete="off" maxlength="11" placeholder="11 位中国大陆手机号">
    </div>
    <div class="field">
      <label for="conf_body">表白内容</label>
      <textarea id="conf_body" name="body" required maxlength="2000" rows="8" placeholder="写下你想对 Ta 说的话…（1–2000 字）"></textarea>
    </div>
    <div class="field home-checkbox-field">
      <label class="home-checkbox">
        <input type="checkbox" name="anonymous" value="1">
        <span>匿名发送（对方看到的是「匿名」，无法知道你的账号昵称）</span>
      </label>
    </div>
    <button type="submit" class="btn btn-primary">发送表白</button>
  </form>
</div>
