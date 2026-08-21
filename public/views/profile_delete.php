<?php declare(strict_types=1);
$u = is_array($u ?? null) ? $u : null;
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/profile')) ?>">个人中心</a>
  <span> / </span>
  <span>注销账号</span>
</nav>
<h1 style="margin-bottom:0.75rem;">注销账号</h1>
<div class="card" style="max-width:640px;padding:1.05rem 1.15rem;">
  <p style="margin-top:0;"><strong>重要：</strong>注销后将无法再登录该账号。</p>
  <ul class="muted" style="margin:0.35rem 0 1rem;padding-left:1.25rem;">
    <li>系统会保留注销记录（时间、IP 等）用于安全审计。</li>
    <li>你已发布的内容将仍可能保留展示，但账号信息会被替换为“已注销用户”。</li>
    <li>若你已完成实名认证，管理员仍可在后台查看你的实名审计信息。</li>
  </ul>
  <form method="post" action="<?= h(url('/profile/delete-account')) ?>">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <div class="field">
      <label for="del_reason">注销原因（可选）</label>
      <input id="del_reason" name="reason" type="text" maxlength="255" placeholder="例如：不再使用 / 误注册等">
    </div>
    <div class="field">
      <label for="del_pw">当前密码（必填）</label>
      <input id="del_pw" name="current_password" type="password" required autocomplete="current-password" placeholder="请输入当前密码确认">
    </div>
    <div class="toolbar" style="margin-bottom:0;">
      <button type="submit" class="btn btn-danger">确认注销</button>
      <a class="btn btn-ghost" href="<?= h(url('/profile')) ?>">取消</a>
    </div>
  </form>
</div>

