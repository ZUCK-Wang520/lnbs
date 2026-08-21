<?php declare(strict_types=1);
$p = $profile;
$initial = mb_substr((string) $p['nickname'], 0, 1, 'UTF-8');
$phoneDisp = !empty($p['phone']) ? h((string) $p['phone']) : '未绑定';
$profileAvUrl = user_avatar_public_url($p['avatar'] ?? null);
$levelOk = user_level_columns_ok();
$lb = is_array($levelBar ?? null) ? $levelBar : null;
$checkedToday = $levelOk && (string) ($p['last_checkin_date'] ?? '') === date('Y-m-d');
$postedXpToday = $levelOk && (string) ($p['last_daily_post_xp_date'] ?? '') === date('Y-m-d');
$isSponsor = user_sponsor_column_ok() && (int) ($p['is_sponsor'] ?? 0) === 1;
$realnameOk = user_realname_columns_ok();
$realnameAllowed = $realnameOk && (int) ($p['realname_allowed'] ?? 0) === 1;
$realnameVerified = $realnameOk && (int) ($p['realname_verified'] ?? 0) === 1;
$birthdayOk = user_birthday_column_ok();
$birthdayVal = '';
if ($birthdayOk && !empty($p['birthday'])) {
    $bds = (string) $p['birthday'];
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $bds, $m)) {
        $birthdayVal = $m[0];
    }
}
$loginProfileVoluntaryPrompt = user_login_profile_columns_ok() && user_login_profile_incomplete_for_user_id((int) $p['id']);
$sportsMatches = is_array($sportsMatches ?? null) ? $sportsMatches : [];
?>
<div class="profile-page">
  <?php if ($loginProfileVoluntaryPrompt) : ?>
  <div class="card" style="margin-bottom:1rem;padding:0.9rem 1rem;border:2px solid var(--accent);background:var(--surface2);">
    <p style="margin:0 0 0.45rem;font-size:1rem;font-weight:700;color:var(--text);">请完善在读信息：年级、班级、真实姓名</p>
    <p class="muted" style="margin:0;font-size:0.93rem;">用于匹配你的运动会参赛项目与时间（<strong>自愿</strong>填写，不填也可正常使用）。示例：年级「初2027」、班级「26」。</p>
    <p style="margin:0.5rem 0 0;">
      <a class="btn btn-primary btn-sm" href="<?= h(url('/login/complete-profile')) ?>">立即填写</a>
    </p>
  </div>
  <?php endif; ?>
  <header class="profile-hero card">
    <div class="profile-hero-main">
      <div class="profile-avatar-frame<?= $isSponsor ? ' profile-avatar-frame--sponsor' : '' ?>">
        <div class="profile-avatar<?= $profileAvUrl ? ' profile-avatar--photo' : '' ?>" aria-hidden="true">
          <?php if ($profileAvUrl) : ?>
            <img src="<?= h($profileAvUrl) ?>" alt="">
          <?php else : ?>
            <?= h($initial) ?>
          <?php endif; ?>
        </div>
      </div>
      <div class="profile-hero-text">
        <h1 class="profile-name"><?= h($p['nickname']) ?></h1>
        <div class="profile-badges">
          <?php if (($p['role'] ?? '') === 'admin') : ?>
            <span class="profile-badge profile-badge--admin">管理员</span>
          <?php else : ?>
            <span class="profile-badge profile-badge--user">会员</span>
          <?php endif; ?>
          <?php if ((int) ($p['moderator_l2'] ?? 0) === 1) : ?>
            <span class="profile-badge profile-badge--mod2">二级管理员</span>
          <?php endif; ?>
          <?php if ($isSponsor) : ?>
            <span class="profile-badge profile-badge--sponsor" title="感谢支持本站">赞助者</span>
          <?php endif; ?>
          <?php if ($realnameVerified) : ?>
            <span class="profile-badge profile-badge--realname" title="已完成实名认证">已实名</span>
          <?php endif; ?>
          <?php if ((int) ($p['banned'] ?? 0) === 1) : ?>
            <span class="profile-badge profile-badge--ban">已禁言</span>
          <?php endif; ?>
          <?php if ($levelOk && $lb) : ?>
            <span class="profile-badge profile-badge--level" title="用户等级">Lv.<?= (int) $lb['level'] ?></span>
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
    <?php if ($levelOk && $lb) : ?>
    <section class="card profile-panel profile-panel--wide profile-level-card">
      <h2 class="profile-panel-title">等级与签到</h2>
      <div class="profile-level-row">
        <div class="profile-level-main">
          <p class="profile-level-meta">
            当前 <strong class="profile-level-num">Lv.<?= (int) $lb['level'] ?></strong>
            <span class="muted">· 经验 <?= (int) $lb['xp'] ?></span>
          </p>
          <div class="profile-xp-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) round($lb['pct']) ?>" aria-label="距离下一级的进度">
            <div class="profile-xp-bar-fill" style="width:<?= h((string) min(100, max(0, round($lb['pct'])))) ?>%"></div>
          </div>
          <p class="muted profile-level-hint" style="margin:0.4rem 0 0;font-size:0.82rem;">
            升至 Lv.<?= (int) $lb['level'] + 1 ?> 还需 <?= max(0, (int) $lb['xp_next'] - (int) $lb['xp']) ?> 经验
            · 每日签到 +<?= (int) USER_LEVEL_XP_CHECKIN ?> · 每日首次发帖或回复 +<?= (int) USER_LEVEL_XP_DAILY_POST ?>
            <?php if ($postedXpToday) : ?>
              <span class="profile-level-done">（今日发帖/回复经验已领取）</span>
            <?php endif; ?>
          </p>
        </div>
        <div class="profile-checkin-wrap">
          <?php if ($checkedToday) : ?>
            <span class="btn btn-ghost btn-sm is-disabled" aria-disabled="true">今日已签到</span>
          <?php else : ?>
            <form method="post" action="<?= h(url('/profile/checkin')) ?>" class="inline-form">
              <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
              <button type="submit" class="btn btn-primary btn-sm profile-checkin-btn">每日签到</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </section>
    <?php elseif (!$levelOk) : ?>
    <section class="card profile-panel profile-panel--wide">
      <h2 class="profile-panel-title">等级与签到</h2>
      <p class="muted" style="margin:0;">请执行数据库脚本 <code>public/database/migration_user_level.sql</code> 后启用。</p>
    </section>
    <?php endif; ?>

    <?php if (!empty($anonQuota)) : ?>
    <section class="card profile-panel profile-panel--wide" id="anon-quota">
      <h2 class="profile-panel-title">匿名发帖 / 回复额度</h2>
      <?php if (!empty($anonQuota['setup_needed'])) : ?>
        <p class="flash flash-error" style="margin:0;font-size:0.88rem;">数据库尚未完成升级，请联系管理员执行 <code>migration_anon_quota.sql</code>。</p>
      <?php else : ?>
        <p class="muted" style="margin:0 0 0.75rem;font-size:0.88rem;line-height:1.5;">
          每日可免费匿名发帖 <strong><?= (int) $anonQuota['topic']['daily_limit'] ?></strong> 次、匿名回复 <strong><?= (int) $anonQuota['reply']['daily_limit'] ?></strong> 次。
          超出后消耗兑换获得的额外次数（不随日重置）。
        </p>
        <dl class="profile-dl" style="margin-bottom:0.75rem;">
          <div class="profile-dl-row">
            <dt>匿名发帖</dt>
            <dd>今日 <?= (int) $anonQuota['topic']['daily_used'] ?> / <?= (int) $anonQuota['topic']['daily_limit'] ?>，兑换剩余 <?= (int) $anonQuota['topic']['bonus'] ?>，还可 <?= (int) $anonQuota['topic']['remaining'] ?> 次</dd>
          </div>
          <div class="profile-dl-row">
            <dt>匿名回复</dt>
            <dd>今日 <?= (int) $anonQuota['reply']['daily_used'] ?> / <?= (int) $anonQuota['reply']['daily_limit'] ?>，兑换剩余 <?= (int) $anonQuota['reply']['bonus'] ?>，还可 <?= (int) $anonQuota['reply']['remaining'] ?> 次</dd>
          </div>
        </dl>
        <?php if (!empty($anonQuota['redeem_ok'])) : ?>
        <form method="post" action="<?= h(url('/profile/anon-redeem')) ?>" class="inline-form" style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:flex-end;">
          <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
          <div class="field" style="margin:0;flex:1;min-width:12rem;">
            <label for="profile_anon_code">兑换码</label>
            <input id="profile_anon_code" name="anon_code" type="text" maxlength="32" placeholder="例如 ANON-XXXX-XXXX" required autocomplete="off" style="text-transform:uppercase;">
          </div>
          <button type="submit" class="btn btn-primary btn-sm">兑换</button>
        </form>
        <?php else : ?>
        <p class="muted" style="margin:0;font-size:0.88rem;">兑换码功能尚未就绪（需创建 <code>anon_redeem_codes</code> 表）。</p>
        <?php endif; ?>
      <?php endif; ?>
    </section>
    <?php endif; ?>

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

    <section class="card profile-panel profile-panel--wide">
      <h2 class="profile-panel-title">我的运动会参赛信息</h2>
      <?php if (!sports_meet_tables_ok()) : ?>
        <p class="muted" style="margin-top:0;">请联系管理员先执行数据库脚本 <code>public/database/migration_sports_meet.sql</code>。</p>
      <?php elseif (empty($sportsMatches)) : ?>
        <p class="muted" style="margin-top:0;">暂未匹配到你的参赛记录。请先在个人资料完善年级、班级、姓名，并由管理员录入后查看。</p>
      <?php else : ?>
        <div style="display:flex;flex-direction:column;gap:0.55rem;">
          <?php foreach ($sportsMatches as $sm) : ?>
          <div style="border:1px solid var(--border);border-radius:12px;padding:0.6rem 0.7rem;background:var(--surface2);">
            <div><strong><?= h((string) ($sm['event_name'] ?? '')) ?></strong></div>
            <div class="muted" style="font-size:0.84rem;"><?= h((string) ($sm['meet_title'] ?? '')) ?></div>
            <div class="muted" style="font-size:0.84rem;">参赛时间：<?= h((string) ($sm['event_starts_at'] ?? '')) ?> - <?= h((string) ($sm['event_ends_at'] ?? '')) ?></div>
            <div class="muted" style="font-size:0.84rem;">比赛成就：<?= !empty($sm['achievement_text']) ? h((string) $sm['achievement_text']) : '待更新' ?></div>
            <?php if (!empty($sm['result_text'])) : ?>
              <div class="muted" style="font-size:0.84rem;">成绩：<?= h((string) $sm['result_text']) ?></div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="card profile-panel">
      <h2 class="profile-panel-title">生日</h2>
      <?php if (!$birthdayOk) : ?>
        <p class="muted" style="margin-top:0;">请执行数据库脚本 <code>public/database/migration_user_birthday.sql</code> 后在此设置。</p>
      <?php else : ?>
        <p class="muted" style="margin-top:0;font-size:0.88rem;">填写公历生日。到你生日当天，<strong>全站首页</strong>会向所有访客展示生日祝福（可点击昵称查看其公开主题）。</p>
        <form method="post" action="<?= h(url('/profile/birthday')) ?>" style="margin-top:0.75rem;">
          <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
          <div class="field">
            <label for="profile_birthday">出生日期</label>
            <input id="profile_birthday" name="birthday" type="date" value="<?= h($birthdayVal) ?>" max="<?= h(date('Y-m-d')) ?>">
          </div>
          <button type="submit" class="btn btn-primary btn-sm">保存生日</button>
        </form>
        <?php if ($birthdayVal !== '') : ?>
        <form method="post" action="<?= h(url('/profile/birthday')) ?>" class="inline-form" style="margin-top:0.65rem;">
          <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="birthday_clear" value="1">
          <button type="submit" class="btn btn-ghost btn-sm">清空生日</button>
        </form>
        <?php endif; ?>
      <?php endif; ?>
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
      <h2 class="profile-panel-title">实名认证</h2>
      <?php if (!$realnameOk) : ?>
        <p class="muted" style="margin-top:0;">请执行数据库脚本 <code>public/database/migration_user_realname.sql</code> 后启用。</p>
      <?php elseif ($realnameVerified) : ?>
        <p class="muted" style="margin-top:0;">你已完成实名认证<?= !empty($p['realname_verified_at']) ? ('（' . h((string) $p['realname_verified_at']) . '）') : '' ?>。</p>
      <?php elseif (!$realnameAllowed) : ?>
        <p class="muted" style="margin-top:0;">当前账号未被管理员授权实名认证。</p>
      <?php else : ?>
        <p class="muted" style="margin-top:0;font-size:0.88rem;">二要素实名认证：仅校验「姓名 + 身份证号」是否一致。系统只保存“是否通过”，不保存你的身份证号。</p>
        <form method="post" action="<?= h(url('/profile/realname-verify')) ?>" style="margin-top:0.75rem;">
          <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
          <div class="field">
            <label for="rn_name">姓名</label>
            <input id="rn_name" name="realname_name" type="text" required maxlength="32" autocomplete="name" placeholder="请输入真实姓名">
          </div>
          <div class="field">
            <label for="rn_idcard">身份证号</label>
            <input id="rn_idcard" name="realname_idcard" type="text" required maxlength="18" autocomplete="off" placeholder="18 位身份证号">
          </div>
          <button type="submit" class="btn btn-primary btn-sm">提交实名认证</button>
        </form>
      <?php endif; ?>
    </section>

    <section class="card profile-panel">
      <h2 class="profile-panel-title">注销账号</h2>
      <?php if (!user_deletion_columns_ok()) : ?>
        <p class="muted" style="margin-top:0;">请执行数据库脚本 <code>public/database/migration_user_account_deletion.sql</code> 后启用注销功能。</p>
      <?php else : ?>
        <p class="muted" style="margin-top:0;">注销后将无法登录；系统会保留注销记录供安全审计。</p>
        <a class="btn btn-danger btn-sm" href="<?= h(url('/profile/delete-account')) ?>">前往注销</a>
      <?php endif; ?>
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
