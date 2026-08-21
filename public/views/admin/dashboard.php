<?php declare(strict_types=1);
$adminPerms = is_array($adminPerms ?? null) ? $adminPerms : [];
$announcement = is_array($announcement ?? null) ? $announcement : ['enabled' => 0, 'body' => ''];
$shutdown = is_array($shutdown ?? null) ? $shutdown : ['enabled' => 0, 'message' => '', 'eta' => ''];
$shutdownTableOk = function_exists('site_shutdown_table_ok') && site_shutdown_table_ok();
$siteLogoUrl = function_exists('site_logo_url') ? site_logo_url() : null;
$siteLogoUploaded = function_exists('site_logo_uploaded_relative_path') ? site_logo_uploaded_relative_path() : null;
?>
<h1 style="margin-bottom:0.5rem;">管理后台</h1>
<p class="muted" style="margin-top:0;margin-bottom:1.75rem;">维护版块与用户秩序。</p>
<?php if (function_exists('user_is_super_admin') && user_is_super_admin(auth_user())) : ?>
<div class="card" style="max-width:44rem;margin-bottom:1rem;padding:1rem 1.15rem;">
  <h2 style="margin:0 0 0.5rem;font-size:1.05rem;">站点 Logo</h2>
  <p class="muted" style="margin:0 0 1rem;font-size:0.84rem;line-height:1.5;">上传后显示在前台顶栏品牌区。支持 JPG / PNG / WebP / GIF，不超过 2MB、边长不超过 2048。开源部署请上传自己的 Logo，勿使用他人商标。</p>
  <?php if ($siteLogoUrl) : ?>
  <p style="margin:0 0 0.75rem;display:flex;align-items:center;gap:0.75rem;">
    <img src="<?= h($siteLogoUrl) ?>" alt="当前 Logo" width="48" height="48" style="border-radius:10px;object-fit:cover;background:rgba(127,127,127,.15);">
    <span class="muted" style="font-size:0.84rem;"><?= $siteLogoUploaded ? '当前：后台已上传' : '当前：来自配置或静态文件' ?></span>
  </p>
  <?php else : ?>
  <p class="muted" style="margin:0 0 0.75rem;font-size:0.84rem;">尚未设置 Logo，顶栏将只显示站名。</p>
  <?php endif; ?>
  <form method="post" action="<?= h(url('/admin/logo/save')) ?>" enctype="multipart/form-data" class="js-moderation-submit" style="margin-bottom:0.75rem;">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <div class="field" style="margin-bottom:0.75rem;">
      <label for="siteLogoFile">选择图片</label>
      <input id="siteLogoFile" type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif" required>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">上传并应用</button>
  </form>
  <?php if ($siteLogoUploaded) : ?>
  <form method="post" action="<?= h(url('/admin/logo/clear')) ?>" class="js-moderation-submit" onsubmit="return confirm('确定清除已上传的站点 Logo？');">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <button type="submit" class="btn btn-ghost btn-sm">清除已上传 Logo</button>
  </form>
  <?php endif; ?>
</div>
<div class="card" style="max-width:44rem;margin-bottom:1rem;padding:1rem 1.15rem;">
  <h2 style="margin:0 0 0.5rem;font-size:1.05rem;">操作审计</h2>
  <p class="muted" style="margin:0 0 0.75rem;font-size:0.84rem;line-height:1.5;">查看站长与二级管理员在后台的敏感操作记录（仅站长可进入）。</p>
  <a class="btn btn-primary btn-sm" href="<?= h(url('/admin/audit-log')) ?>">查看审计日志</a>
</div>
<div class="card admin-shutdown-editor" style="max-width:44rem;margin-bottom:1rem;padding:1rem 1.15rem;">
  <h2 style="margin:0 0 0.5rem;font-size:1.05rem;">全站维护模式</h2>
  <p class="muted" style="margin:0 0 1rem;font-size:0.84rem;line-height:1.5;">开启后，普通访客将看到维护页（HTTP 503）；<strong>站长与已开通后台权限的二级管理员</strong>登录后可正常浏览全站与后台。维护期间若误点退出，请打开首页点「管理员登录」，或访问 <code>/login</code>、<code>/admin</code> 重新登录。仅站长可编辑。</p>
  <?php if (!$shutdownTableOk) : ?>
    <p class="flash flash-error" style="margin:0;font-size:0.88rem;">请先在数据库执行 <code>public/database/migration_site_shutdown.sql</code> 后再使用本功能。</p>
  <?php else : ?>
  <form method="post" action="<?= h(url('/admin/shutdown/save')) ?>" class="js-moderation-submit">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <div class="field" style="margin-bottom:0.75rem;">
      <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
        <input type="checkbox" name="enabled" value="1"<?= !empty($shutdown['enabled']) ? ' checked' : '' ?>>
        <span>开启全站维护</span>
      </label>
    </div>
    <div class="field" style="margin-bottom:0.75rem;">
      <label for="siteShutdownMessage">维护说明（展示给访客）</label>
      <textarea id="siteShutdownMessage" name="message" class="input" rows="4" style="width:100%;min-height:5.5rem;resize:vertical;" maxlength="4000" placeholder="例如：系统升级中，预计今晚 22:00 恢复"><?= h((string) ($shutdown['message'] ?? '')) ?></textarea>
    </div>
    <div class="field">
      <label for="siteShutdownEta">预计恢复（可选）</label>
      <input type="text" id="siteShutdownEta" name="eta" class="input" maxlength="255" value="<?= h((string) ($shutdown['eta'] ?? '')) ?>" placeholder="例如：2026年5月18日 08:00">
    </div>
    <button type="submit" class="btn btn-primary btn-sm" style="margin-top:0.5rem;">保存维护设置</button>
  </form>
  <?php endif; ?>
</div>
<div class="card admin-announcement-editor" style="max-width:44rem;margin-bottom:1.5rem;padding:1rem 1.15rem;">
  <h2 style="margin:0 0 0.5rem;font-size:1.05rem;">站点公告</h2>
  <p class="muted" style="margin:0 0 1rem;font-size:0.84rem;line-height:1.5;">保存后将在前台各页顶部显示公告栏（纯文本、自动换行）。仅站长可编辑。</p>
  <form method="post" action="<?= h(url('/admin/announcement/save')) ?>" class="js-moderation-submit">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <div class="field" style="margin-bottom:0.75rem;">
      <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
        <input type="checkbox" name="enabled" value="1"<?= !empty($announcement['enabled']) ? ' checked' : '' ?>>
        <span>全站显示公告栏</span>
      </label>
    </div>
    <div class="field">
      <label for="siteAnnouncementBody">公告内容</label>
      <textarea id="siteAnnouncementBody" name="body" class="input" rows="4" style="width:100%;min-height:5.5rem;resize:vertical;" maxlength="4000" placeholder="例如：系统维护通知、活动说明等"><?= h((string) ($announcement['body'] ?? '')) ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary btn-sm" style="margin-top:0.5rem;">保存公告</button>
  </form>
</div>
<?php endif; ?>
<p class="admin-online-banner">
  <span class="online-pill-dot" aria-hidden="true"></span>
  当前 <strong><?= (int) online_count() ?></strong> 人在线（近 5 分钟有访问；会员按账号去重）
</p>
<div class="admin-grid">
  <?php if (!empty($adminPerms['boards'])) : ?>
  <div class="admin-tile">
    <h3>版块管理</h3>
    <p>创建、编辑或删除版块。</p>
    <a class="btn btn-primary btn-sm" href="<?= h(url('/admin/boards')) ?>">进入</a>
  </div>
  <?php endif; ?>
  <?php if (!empty($adminPerms['users'])) : ?>
  <div class="admin-tile">
    <h3>用户管理</h3>
    <p>禁言违规账号、IP 封禁与解封。</p>
    <a class="btn btn-primary btn-sm" href="<?= h(url('/admin/users')) ?>">进入</a>
    <a class="btn btn-ghost btn-sm" href="<?= h(url('/admin/ip-bans')) ?>" style="margin-left:.35rem">已封禁 IP</a>
    <a class="btn btn-ghost btn-sm" href="<?= h(url('/admin/anon-codes')) ?>" style="margin-left:.35rem">匿名兑换码</a>
  </div>
  <?php endif; ?>
  <?php if (chat_tables_ok() && !empty($adminPerms['chat'])) : ?>
  <div class="admin-tile">
    <h3>私信审计</h3>
    <p>查看全站用户私信记录。</p>
    <a class="btn btn-primary btn-sm" href="<?= h(url('/admin/chat')) ?>">进入</a>
  </div>
  <?php endif; ?>
  <?php if (!empty($adminPerms['sports_meet'])) : ?>
  <div class="admin-tile">
    <h3>运动会管理</h3>
    <p>设置运动会时间、项目赛程与参赛记录。</p>
    <a class="btn btn-primary btn-sm" href="<?= h(url('/admin/sports-meet')) ?>">进入</a>
  </div>
  <?php endif; ?>
  <?php if (!empty($adminPerms['moderation'])) : ?>
  <div class="admin-tile">
    <h3>人工复核</h3>
    <p>被 AI 拦截的内容由二级审核员表决：两人一致通过/拒绝即定案；意见相左时第三人裁定。</p>
    <?php if (!empty($moderationPending)) : ?>
      <p class="muted" style="margin:0.35rem 0;font-size:0.88rem;">待处理 <strong><?= (int) $moderationPending ?></strong> 条</p>
    <?php endif; ?>
    <a class="btn btn-primary btn-sm" href="<?= h(url('/admin/moderation')) ?>">进入</a>
  </div>
  <?php endif; ?>
</div>
<?php
$anyTile = !empty($adminPerms['boards']) || !empty($adminPerms['users'])
    || (chat_tables_ok() && !empty($adminPerms['chat']))
    || !empty($adminPerms['sports_meet'])
    || !empty($adminPerms['moderation']);
?>
<?php if (!$anyTile && empty($adminPerms['content'])) : ?>
<p class="muted" style="font-size:0.9rem;">当前账号未开通任何后台模块，请联系站长在用户管理中勾选权限。</p>
<?php endif; ?>
<?php if (!empty($adminPerms['content'])) : ?>
<p class="muted" style="font-size:0.88rem;">提示：在主题页可删除整条主题或单条回复。</p>
<?php endif; ?>
