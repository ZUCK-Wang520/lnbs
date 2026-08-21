<?php declare(strict_types=1);
$p = $profile;
$initial = mb_substr((string) $p['nickname'], 0, 1, 'UTF-8');
$phoneDisp = !empty($p['phone']) ? h((string) $p['phone']) : '未绑定';
$profileAvUrl = user_avatar_public_url($p['avatar'] ?? null);
?>
<div class="profile-page">
  <header class="profile-hero card">
    <div class="profile-hero-main">
      <div class="profile-avatar<?= $profileAvUrl ? ' profile-avatar--photo' : '' ?>" aria-hidden="true">
        <?php if ($profileAvUrl) : ?>
          <img src="<?= h($profileAvUrl) ?>" alt="">
        <?php else : ?>
          <?= h($initial) ?>
        <?php endif; ?>
      </div>
      <div class="profile-hero-text">
        <h1 class="profile-name"><?= h($p['nickname']) ?></h1>
        <div class="profile-badges">
          <?php if (($p['role'] ?? '') === 'admin') : ?>
            <span class="profile-badge profile-badge--admin">管理员</span>
          <?php else : ?>
            <span class="profile-badge profile-badge--user">会员</span>
          <?php endif; ?>
          <?php if ((int) ($p['banned'] ?? 0) === 1) : ?>
            <span class="profile-badge profile-badge--ban">已禁言</span>
          <?php endif; ?>
        </div>
        <p class="muted profile-meta-line">注册于 <?= h((string) $p['created_at']) ?></p>
        <p class="muted profile-meta-line" style="margin-top:0.25rem;">
          <a href="<?= h(url('/user/' . (int) $p['id'] . '/topics')) ?>">查看我的全部公开主题</a>
        </p>
      </div>
    </div>
    <div class="profile-stats">
      <div class="profile-stat">
        <span class="profile-stat-num"><?= (int) $topicCount ?></span>
        <span class="profile-stat-label">发布主题</span>
      </div>
      <div class="profile-stat">
        <span class="profile-stat-num"><?= (int) $postCount ?></span>
        <span class="profile-stat-label">回复</span>
      </div>
    </div>
  </header>

  <div class="profile-grid">
    <section class="card profile-panel">
      <h2 class="profile-panel-title">账号信息</h2>
      <dl class="profile-dl">
        <div class="profile-dl-row">
          <dt>登录账号</dt>
          <dd><?= !empty($p['phone']) ? $phoneDisp : h((string) $p['email']) ?></dd>
        </div>
        <?php if (!empty($p['phone']) && !user_email_is_phone_placeholder((string) $p['email'])) : ?>
        <div class="profile-dl-row">
          <dt>绑定邮箱</dt>
          <dd><?= h((string) $p['email']) ?></dd>
        </div>
        <?php endif; ?>
      </dl>
    </section>

    <section class="card profile-panel">
      <h2 class="profile-panel-title">头像</h2>
      <p class="muted" style="margin-top:0;font-size:0.88rem;">支持 JPG / PNG / WebP / GIF，最大 2MB；不压缩、不转码，按原图保存。</p>
      <form method="post" action="<?= h(url('/profile/avatar')) ?>" enctype="multipart/form-data" style="margin-top:0.75rem;">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <div class="field">
          <label for="profile_avatar">选择图片</label>
          <input id="profile_avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp,image/gif" required>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">上传头像</button>
      </form>
    </section>

    <section class="card profile-panel profile-panel--wide">
      <h2 class="profile-panel-title">我的喜欢</h2>
      <?php if (user_profile_likes_column_ok()) : ?>
        <p class="muted" style="margin-top:0;font-size:0.88rem;">一行一条或随意书写；将展示在你的<a href="<?= h(url('/user/' . (int) $p['id'] . '/topics')) ?>">公开主页</a>，所有人可见。</p>
        <form method="post" action="<?= h(url('/profile/likes')) ?>" class="js-moderation-submit" style="margin-top:0.75rem;">
          <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
          <div class="field">
            <label for="profile_likes">喜欢的事物、爱好、偶像等</label>
            <textarea id="profile_likes" name="profile_likes" rows="5" maxlength="2000" placeholder="例如：&#10;羽毛球&#10;科幻小说&#10;周杰伦"><?= h((string) ($p['profile_likes'] ?? '')) ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-sm">保存喜欢</button>
        </form>
      <?php else : ?>
        <p class="muted" style="margin-top:0;">请执行数据库脚本 <code>public/database/migration_user_likes.sql</code> 后在此编辑。</p>
      <?php endif; ?>
    </section>

    <section class="card profile-panel">
      <h2 class="profile-panel-title">修改昵称</h2>
      <form method="post" action="<?= h(url('/profile/nickname')) ?>">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <div class="field">
          <label for="profile_nick">昵称</label>
          <input id="profile_nick" name="nickname" type="text" required maxlength="64" value="<?= h((string) $p['nickname']) ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">保存昵称</button>
      </form>
    </section>

    <section class="card profile-panel profile-panel--wide">
      <h2 class="profile-panel-title">修改密码</h2>
      <form method="post" action="<?= h(url('/profile/password')) ?>">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <div class="field">
          <label for="cur_pw">当前密码</label>
          <input id="cur_pw" name="current_password" type="password" required autocomplete="current-password">
        </div>
        <div class="field">
          <label for="new_pw">新密码（至少 6 位）</label>
          <input id="new_pw" name="new_password" type="password" required minlength="6" autocomplete="new-password">
        </div>
        <div class="field">
          <label for="new_pw2">确认新密码</label>
          <input id="new_pw2" name="new_password_confirm" type="password" required minlength="6" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">更新密码</button>
      </form>
    </section>
  </div>

  <?php if (!empty($recentTopics)) : ?>
    <section class="card profile-recent">
      <h2 class="profile-panel-title">我的主题</h2>
      <ul class="profile-topic-list">
        <?php foreach ($recentTopics as $t) : ?>
          <li>
            <a class="profile-topic-link" href="<?= h(url('/topic/' . (int) $t['id'])) ?>">
              <span class="profile-topic-title"><?= h((string) $t['title']) ?></span>
              <span class="profile-topic-meta muted"><?= h((string) $t['board_name']) ?> · <?= h((string) $t['updated_at']) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>
</div>
