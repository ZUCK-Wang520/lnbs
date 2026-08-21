<?php declare(strict_types=1);
$searchQ = (string) ($searchQ ?? '');
$profileFilter = (string) ($profileFilter ?? '');
$usersView = (string) ($usersView ?? 'users');
if ($usersView !== 'ip_bans') {
    $usersView = 'users';
}
$ipBanTypeFilter = (string) ($ipBanTypeFilter ?? '');
if (!in_array($ipBanTypeFilter, ['', 'firewall', 'login'], true)) {
    $ipBanTypeFilter = '';
}
$ipBanRows = $ipBanRows ?? [];
$isIpBanView = $usersView === 'ip_bans'
    || (function_exists('request_path') && request_path() === '/admin/ip-bans');
$page = max(1, (int) ($page ?? 1));
$pages = max(1, (int) ($pages ?? 1));
$total = (int) ($total ?? 0);
$perPage = (int) ($perPage ?? 25);
$sponsorOk = user_sponsor_column_ok();
$realnameOk = user_realname_columns_ok();
$loginProfileOk = user_login_profile_columns_ok();
$viewerSuper = user_is_super_admin(auth_user());
$l2PermCol = user_moderator_l2_perms_column_ok();
$permLabels = admin_l2_permission_labels();

$from = $total === 0 ? 0 : ($page - 1) * $perPage + 1;
$to = min($total, $page * $perPage);

$buildListUrl = function (string $sq, int $p) use ($profileFilter, $isIpBanView, $ipBanTypeFilter): string {
    if ($isIpBanView) {
        $params = [];
        if ($p > 1) {
            $params['page'] = $p;
        }
        if ($ipBanTypeFilter === 'firewall' || $ipBanTypeFilter === 'login') {
            $params['ip_ban_type'] = $ipBanTypeFilter;
        }

        return url('/admin/ip-bans', $params);
    }
    $params = [];
    if ($sq !== '') {
        $params['q'] = $sq;
    }
    if ($p > 1) {
        $params['page'] = $p;
    }
    if ($profileFilter === 'complete' || $profileFilter === 'incomplete') {
        $params['profile'] = $profileFilter;
    }

    return url('/admin/users', $params);
};

$ipBanFilterUrl = function (string $type): string {
    $params = [];
    if ($type === 'firewall' || $type === 'login') {
        $params['ip_ban_type'] = $type;
    }

    return url('/admin/ip-bans', $params);
};

/** 切换「在读」筛选时回到第 1 页；保留当前搜索词 */
$usersProfileFilterUrl = function (string $which) use ($searchQ): string {
    $params = [];
    if ($searchQ !== '') {
        $params['q'] = $searchQ;
    }
    if ($which === 'complete' || $which === 'incomplete') {
        $params['profile'] = $which;
    }

    return url('/admin/users', $params);
};

$pagerStart = max(1, $page - 2);
$pagerEnd = min($pages, $page + 2);
?>
<style>
.admin-users-page{max-width:56rem;margin:0 auto}
.admin-users-hero{margin-bottom:1.25rem}
.admin-users-hero h1{margin:0 0 .35rem;font-size:1.45rem;color:var(--text)}
.admin-users-hero .lead{margin:0;color:var(--muted);font-size:.95rem;line-height:1.45}
.admin-users-toolbar{
  display:flex;flex-wrap:wrap;gap:.75rem 1rem;align-items:flex-end;
  padding:1rem 1.1rem;margin-bottom:1rem;border-radius:16px;
  border:1px solid var(--border);background:var(--surface);box-shadow:var(--shadow)
}
.admin-users-toolbar .field{margin:0;flex:1;min-width:14rem}
.admin-users-meta{font-size:.88rem;color:var(--muted);margin-left:auto;align-self:center}
.admin-users-meta strong{color:var(--text)}
.admin-users-pager{
  display:flex;flex-wrap:wrap;align-items:center;gap:.45rem .6rem;
  margin:.85rem 0;padding:.55rem 0;border-top:1px solid var(--border);
  border-bottom:1px solid var(--border);font-size:.9rem;color:var(--text)
}
.admin-users-pager .muted{color:var(--muted)}
.admin-users-pager__nums{display:flex;flex-wrap:wrap;gap:.25rem;align-items:center}
.admin-user-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.85rem}
.admin-user-card{
  border-radius:16px;border:1px solid var(--border);
  background:var(--surface);overflow:hidden;box-shadow:var(--shadow)
}
.admin-user-card__head{
  display:flex;flex-wrap:wrap;gap:.65rem 1rem;align-items:flex-start;
  justify-content:space-between;padding:.85rem 1rem .65rem;
  border-bottom:1px solid var(--border);background:var(--surface2)
}
.admin-user-card__who{display:flex;flex-wrap:wrap;align-items:baseline;gap:.5rem .65rem}
.admin-user-card__id{font-family:ui-monospace,monospace;font-size:.82rem;color:var(--muted)}
.admin-user-card__name{font-size:1.05rem;font-weight:650;color:var(--text)}
.admin-user-card__badges{display:flex;flex-wrap:wrap;gap:.35rem;align-items:center}
.admin-user-card__body{padding:.75rem 1rem 1rem;display:flex;flex-direction:column;gap:.85rem}
.admin-user-card__grid{
  display:grid;gap:.55rem 1.25rem;
  grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));
  font-size:.9rem;line-height:1.5;color:var(--text)
}
.admin-user-card__grid dt{
  margin:0;color:var(--muted);font-size:.72rem;font-weight:700;
  letter-spacing:.06em;text-transform:uppercase
}
.admin-user-card__grid dd{margin:.2rem 0 0;color:var(--text)}
.admin-user-card__grid .muted{color:var(--muted)}
.admin-pill{
  display:inline-flex;align-items:center;gap:.3rem;padding:.2rem .55rem;border-radius:999px;
  font-size:.78rem;font-weight:600;line-height:1.35;white-space:nowrap;
  border:1px solid var(--border);background:var(--surface2);color:var(--text)
}
.admin-pill--ok{
  background:var(--flash-success-bg);border-color:var(--flash-success-border);color:var(--flash-success-text)
}
.admin-pill--warn{
  background:rgba(245,158,11,.16);border-color:rgba(180,83,9,.45);color:#92400e
}
html[data-theme='dark'] .admin-pill--warn{
  background:rgba(251,191,36,.14);border-color:rgba(251,191,36,.4);color:#fef9c3
}
.admin-pill--bad{
  background:var(--flash-error-bg);border-color:var(--flash-error-border);color:var(--flash-error-text)
}
.admin-pill--role{
  background:rgba(91,77,219,.14);border-color:rgba(91,77,219,.4);color:#3730a3
}
html[data-theme='dark'] .admin-pill--role{
  background:rgba(124,108,248,.18);border-color:rgba(167,139,250,.45);color:#e9d5ff
}
.admin-pill--admin{
  background:rgba(245,158,11,.18);border-color:rgba(180,83,9,.4);color:#92400e
}
html[data-theme='dark'] .admin-pill--admin{
  background:rgba(251,191,36,.14);border-color:rgba(251,191,36,.45);color:#fef3c7
}
.admin-pill--mono{font-family:ui-monospace,monospace;font-size:.76rem;font-weight:500}
.admin-user-actions{display:flex;flex-direction:column;gap:.65rem}
.admin-user-actions__row{display:flex;flex-wrap:wrap;gap:.45rem;align-items:center}
.admin-user-actions__label{
  font-size:.78rem;font-weight:700;color:var(--muted);min-width:4.5rem;margin-right:.15rem
}
.admin-users-mini{display:flex;flex-wrap:wrap;gap:.35rem;align-items:center}
.admin-users-mini input[type="datetime-local"]{width:12.5rem;max-width:100%}
.admin-users-mini .input{padding:.3rem .45rem;border-radius:10px;font-size:.85rem}
.admin-tag{
  display:inline-flex;align-items:center;padding:.12rem .4rem;border:1px solid var(--border);
  border-radius:8px;font-size:.78rem;background:var(--surface2);color:var(--text)
}
.admin-tag--danger{
  background:var(--flash-error-bg);border-color:var(--flash-error-border);color:var(--flash-error-text)
}
.admin-user-more{
  margin:0 1rem 1rem;border:1px solid var(--border);border-radius:12px;
  background:var(--surface2)
}
.admin-user-more summary{
  cursor:pointer;padding:.55rem .75rem;font-weight:600;font-size:.88rem;list-style:none;color:var(--text)
}
.admin-user-more summary::-webkit-details-marker{display:none}
.admin-user-more[open] summary{border-bottom:1px solid var(--border)}
.admin-user-more .inner{padding:.65rem .75rem .85rem;color:var(--text)}
.admin-l2-perms-form{
  border:1px dashed var(--border);border-radius:12px;padding:.55rem .65rem;margin-top:.5rem;
  background:var(--surface)
}
.admin-l2-perms-form .field{margin:.3rem 0 0}
.admin-l2-perms-form .field:first-child{margin-top:0}
.admin-l2-perms-form label{display:flex;align-items:flex-start;gap:.45rem;cursor:pointer;font-size:.85rem;line-height:1.35;color:var(--text)}
.admin-l2-perms-kicker{font-size:.78rem;color:var(--muted);margin:0 0 .35rem}
.admin-user-card--highlight{
  outline:2px solid var(--accent);outline-offset:3px;border-radius:16px;
  transition:outline-color .35s ease, box-shadow .35s ease;
  box-shadow:0 0 0 3px rgba(124,108,248,.2)
}
html[data-theme='light'] .admin-user-card--highlight{box-shadow:0 0 0 3px rgba(91,77,219,.18)}
</style>
<nav class="breadcrumb">
  <a href="<?= h(url('/admin')) ?>">后台</a>
  <span> / </span>
  <a href="<?= h(url('/admin/users')) ?>">用户管理</a>
  <?php if ($isIpBanView) : ?>
  <span> / </span>
  <span>已封禁 IP</span>
  <?php else : ?>
  <span> / </span>
  <span>用户列表</span>
  <?php endif; ?>
</nav>

<div class="admin-users-page">
  <header class="admin-users-hero">
    <h1><?= $isIpBanView ? '已封禁 IP' : '用户管理' ?></h1>
    <p class="lead"><?= $isIpBanView
        ? '仅显示当前仍生效的 IP 封禁记录，可在此集中解除防火墙或登录封禁。'
        : '按手机号或昵称搜索；可筛选已填写「在读信息」的用户；禁言、登录封禁、赞助与二级权限等均在此处理。封禁 IP 可在「已封禁 IP」中集中解封。' ?></p>
  </header>

  <div class="admin-users-profile-filter" style="display:flex;flex-wrap:wrap;align-items:center;gap:0.4rem 0.5rem;margin:0 0 0.9rem 0.15rem;padding:0 0.15rem;">
    <span class="muted" style="font-size:0.88rem;">列表</span>
    <a class="btn btn-sm<?= !$isIpBanView ? ' btn-primary' : ' btn-ghost' ?>" href="<?= h(url('/admin/users', $searchQ !== '' ? ['q' => $searchQ] : [])) ?>">全部用户</a>
    <a class="btn btn-sm<?= $isIpBanView ? ' btn-primary' : ' btn-ghost' ?>" href="<?= h($ipBanFilterUrl('')) ?>">已封禁 IP</a>
    <a class="btn btn-sm btn-ghost" href="<?= h(url('/admin/anon-codes')) ?>">匿名兑换码</a>
  </div>

  <?php if ($isIpBanView) : ?>
  <div class="admin-users-profile-filter" style="display:flex;flex-wrap:wrap;align-items:center;gap:0.4rem 0.5rem;margin:-0.15rem 0 0.9rem 0.15rem;padding:0 0.15rem;">
    <span class="muted" style="font-size:0.88rem;">封禁类型</span>
    <a class="btn btn-sm<?= $ipBanTypeFilter === '' ? ' btn-primary' : ' btn-ghost' ?>" href="<?= h($ipBanFilterUrl('')) ?>">全部</a>
    <a class="btn btn-sm<?= $ipBanTypeFilter === 'firewall' ? ' btn-primary' : ' btn-ghost' ?>" href="<?= h($ipBanFilterUrl('firewall')) ?>">防火墙</a>
    <a class="btn btn-sm<?= $ipBanTypeFilter === 'login' ? ' btn-primary' : ' btn-ghost' ?>" href="<?= h($ipBanFilterUrl('login')) ?>">登录封禁</a>
  </div>
  <?php endif; ?>

  <?php if (!$isIpBanView) : ?>
  <form method="get" action="<?= h(url('/admin/users')) ?>" class="admin-users-toolbar">
    <div class="field">
      <label for="admin_users_q">搜索</label>
      <input id="admin_users_q" name="q" type="search" value="<?= h($searchQ) ?>" placeholder="手机号或昵称" maxlength="64" autocomplete="off">
    </div>
    <input type="hidden" name="page" value="1">
    <button type="submit" class="btn btn-primary btn-sm">搜索</button>
    <?php if ($searchQ !== '') : ?>
      <a href="<?= h(url('/admin/users')) ?>" class="btn btn-ghost btn-sm">清除条件</a>
    <?php endif; ?>
    <span class="admin-users-meta">
      <?php if ($total > 0) : ?>
        第 <strong><?= (int) $from ?></strong>–<strong><?= (int) $to ?></strong> 条，共 <strong><?= (int) $total ?></strong> 人
      <?php else : ?>
        共 0 人
      <?php endif; ?>
    </span>
  </form>
  <?php endif; ?>

  <?php if ($loginProfileOk && !$isIpBanView) : ?>
  <div class="admin-users-profile-filter" style="display:flex;flex-wrap:wrap;align-items:center;gap:0.4rem 0.5rem;margin:-0.15rem 0 0.9rem 0.15rem;padding:0 0.15rem;">
    <span class="muted" style="font-size:0.88rem;">在读信息筛选</span>
    <a class="btn btn-sm<?= $profileFilter === '' ? ' btn-primary' : ' btn-ghost' ?>" href="<?= h($usersProfileFilterUrl('')) ?>">全部</a>
    <a class="btn btn-sm<?= $profileFilter === 'complete' ? ' btn-primary' : ' btn-ghost' ?>" href="<?= h($usersProfileFilterUrl('complete')) ?>">已填写</a>
    <a class="btn btn-sm<?= $profileFilter === 'incomplete' ? ' btn-primary' : ' btn-ghost' ?>" href="<?= h($usersProfileFilterUrl('incomplete')) ?>">未填完</a>
  </div>
  <?php endif; ?>

  <?php if ($pages > 1) : ?>
  <nav class="admin-users-pager" aria-label="分页">
    <?php if ($page > 1) : ?>
      <a class="btn btn-ghost btn-sm" href="<?= h($buildListUrl($searchQ, $page - 1)) ?>">上一页</a>
    <?php endif; ?>
    <span class="muted">第 <?= (int) $page ?> / <?= (int) $pages ?> 页</span>
    <div class="admin-users-pager__nums" aria-hidden="true">
      <?php for ($pi = $pagerStart; $pi <= $pagerEnd; $pi++) : ?>
        <?php if ($pi === $page) : ?>
          <span class="btn btn-primary btn-sm" style="pointer-events:none"><?= (int) $pi ?></span>
        <?php else : ?>
          <a class="btn btn-ghost btn-sm" href="<?= h($buildListUrl($searchQ, $pi)) ?>"><?= (int) $pi ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
    <?php if ($page < $pages) : ?>
      <a class="btn btn-ghost btn-sm" href="<?= h($buildListUrl($searchQ, $page + 1)) ?>">下一页</a>
    <?php endif; ?>
  </nav>
  <?php endif; ?>

  <?php if ($isIpBanView) : ?>
    <p class="admin-users-meta" style="margin:0 0 0.85rem 0.15rem;">
      <?php if ($total > 0) : ?>
        第 <strong><?= (int) $from ?></strong>–<strong><?= (int) $to ?></strong> 条，共 <strong><?= (int) $total ?></strong> 个已封禁 IP
      <?php else : ?>
        当前没有生效中的 IP 封禁
      <?php endif; ?>
    </p>
    <?php if (empty($ipBanRows)) : ?>
      <p class="muted card" style="padding:1.25rem;">暂无封禁记录。防火墙误封或测试封禁后，可在此解封。</p>
    <?php else : ?>
    <ul class="admin-user-list admin-ip-ban-list">
      <?php foreach ($ipBanRows as $ban) : ?>
        <?php
          $banIp = (string) ($ban['ip'] ?? '');
          $banUntil = (string) ($ban['banned_until'] ?? '');
          $banReason = trim((string) ($ban['reason'] ?? ''));
          $isFw = !empty($ban['is_firewall']);
          $linked = $ban['linked_users'] ?? [];
        ?>
        <li class="admin-user-card">
          <div class="admin-user-card__head">
            <div class="admin-user-card__who">
              <span class="admin-pill admin-pill--mono admin-pill--bad"><?= h($banIp) ?></span>
              <?php if ($isFw) : ?>
                <span class="admin-pill admin-pill--warn">防火墙 · 全站拦截</span>
              <?php else : ?>
                <span class="admin-pill admin-pill--warn">登录封禁</span>
              <?php endif; ?>
              <?php if ($banUntil !== '' && ip_ban_mysql_until_is_indefinite($banUntil)) : ?>
                <span class="admin-pill admin-pill--bad">不限期</span>
              <?php elseif ($banUntil !== '') : ?>
                <span class="admin-pill">至 <?= h($banUntil) ?></span>
              <?php endif; ?>
            </div>
            <form class="inline-form" method="post" action="<?= h(url('/admin/ip-ban/clear')) ?>">
              <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="_return_page" value="<?= (int) $page ?>">
              <input type="hidden" name="_return_view" value="ip_bans">
              <input type="hidden" name="_return_ip_ban_type" value="<?= h($ipBanTypeFilter) ?>">
              <input type="hidden" name="ip" value="<?= h($banIp) ?>">
              <button type="submit" class="btn btn-primary btn-sm">解除封禁</button>
            </form>
          </div>
          <div class="admin-user-card__body" style="padding-top:.65rem">
            <?php if ($banReason !== '') : ?>
              <p class="muted" style="margin:0 0 .5rem;font-size:.88rem;"><strong>原因：</strong><?= h($banReason) ?></p>
            <?php endif; ?>
            <?php if (!empty($ban['created_at'])) : ?>
              <p class="muted" style="margin:0 0 .5rem;font-size:.82rem;">记录时间：<?= h((string) $ban['created_at']) ?></p>
            <?php endif; ?>
            <?php if ($linked !== []) : ?>
              <p class="muted" style="margin:0;font-size:.82rem;">最近使用该 IP 登录：
                <?php foreach ($linked as $li => $lu) : ?><?php if ($li > 0) : ?>、<?php endif; ?><a href="<?= h(url('/admin/users', ['q' => (string) ($lu['nickname'] ?? '')])) ?>#u-<?= (int) ($lu['id'] ?? 0) ?>"><?= h((string) ($lu['nickname'] ?? '')) ?></a><?php endforeach; ?>
              </p>
            <?php else : ?>
              <p class="muted" style="margin:0;font-size:.82rem;">暂无用户最近登录记录关联此 IP</p>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  <?php elseif ($total === 0 && $searchQ !== '') : ?>
    <p class="muted card" style="padding:1.25rem;">没有匹配「<?= h($searchQ) ?>」的用户，请换个关键词或<a href="<?= h(url('/admin/users')) ?>">查看全部</a>。</p>
  <?php elseif (empty($users)) : ?>
    <p class="muted card" style="padding:1.25rem;">暂无用户数据。</p>
  <?php elseif (!$isIpBanView) : ?>
  <ul class="admin-user-list">
    <?php foreach ($users as $row) : ?>
      <?php
        $uid = (int) $row['id'];
        $isAdminRow = ($row['role'] ?? '') === 'admin';
        $defaultUntil = date('Y-m-d\TH:i', time() + 86400);
        $curUntil = '';
        if (!empty($row['login_banned_until'])) {
            $ts = strtotime((string) $row['login_banned_until']);
            if ($ts !== false) {
                $curUntil = date('Y-m-d\TH:i', $ts);
            }
        }
      ?>
      <li class="admin-user-card" id="u-<?= $uid ?>">
        <div class="admin-user-card__head">
          <div class="admin-user-card__who">
            <span class="admin-user-card__id">#<?= $uid ?></span>
            <span class="admin-user-card__name"><?= h($row['nickname']) ?></span>
            <span class="admin-user-card__badges">
              <?php if ($isAdminRow) : ?>
                <span class="admin-pill admin-pill--admin">站长</span>
              <?php else : ?>
                <span class="admin-pill admin-pill--role">会员</span>
              <?php endif; ?>
              <?php if ((int) ($row['banned'] ?? 0) === 1) : ?>
                <span class="admin-pill admin-pill--bad">已禁言</span>
              <?php else : ?>
                <span class="admin-pill admin-pill--ok">发言正常</span>
              <?php endif; ?>
              <?php if (!$isAdminRow && (int) ($row['moderator_l2'] ?? 0) === 1) : ?>
                <span class="admin-pill admin-pill--warn">二级管理</span>
              <?php endif; ?>
              <?php if (user_deletion_columns_ok() && !empty($row['deleted_at'])) : ?>
                <span class="admin-pill admin-pill--bad">已注销</span>
              <?php endif; ?>
            </span>
          </div>
        </div>
        <div class="admin-user-card__body">
          <dl class="admin-user-card__grid">
            <div>
              <dt>联系方式</dt>
              <dd>
                <span class="muted">手机</span> <?= !empty($row['phone']) ? h((string) $row['phone']) : '—' ?>
                <span class="muted" style="margin-left:.65rem;">邮箱</span>
                <?= user_email_is_phone_placeholder((string) $row['email']) ? '—' : h($row['email']) ?>
              </dd>
            </div>
            <div>
              <dt>在读信息</dt>
              <dd>
                <?php if (!$loginProfileOk) : ?>
                  <span class="muted" title="migration_user_login_profile.sql">未启用</span>
                <?php else : ?>
                  <form method="post" action="<?= h(url('/admin/users/save-login-profile')) ?>" class="js-moderation-submit" style="margin:0;">
                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="_return_page" value="<?= (int) $page ?>">
                    <input type="hidden" name="_return_anchor" value="u-<?= $uid ?>">
                    <?php if ($searchQ !== '') : ?>
                      <input type="hidden" name="_return_q" value="<?= h($searchQ) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="id" value="<?= $uid ?>">
                    <div class="admin-users-mini" style="flex-wrap:wrap;align-items:flex-end;gap:0.45rem 0.6rem;">
                      <div class="field" style="margin:0;min-width:5.5rem;">
                        <label for="pg-<?= $uid ?>" class="muted" style="font-size:0.72rem;display:block;">年级</label>
                        <input id="pg-<?= $uid ?>" class="input input-sm" name="profile_grade" type="text" maxlength="32" value="<?= h((string) ($row['profile_grade'] ?? '')) ?>" placeholder="初2027" autocomplete="off" style="min-width:6rem;max-width:9rem;">
                      </div>
                      <div class="field" style="margin:0;min-width:5.5rem;">
                        <label for="pc-<?= $uid ?>" class="muted" style="font-size:0.72rem;display:block;">班级</label>
                        <input id="pc-<?= $uid ?>" class="input input-sm" name="profile_class" type="text" maxlength="64" value="<?= h((string) ($row['profile_class'] ?? '')) ?>" placeholder="26" autocomplete="off" style="min-width:4rem;max-width:8rem;">
                      </div>
                      <div class="field" style="margin:0;flex:1;min-width:5rem;max-width:12rem;">
                        <label for="pn-<?= $uid ?>" class="muted" style="font-size:0.72rem;display:block;">真实姓名</label>
                        <input id="pn-<?= $uid ?>" class="input input-sm" name="profile_real_name" type="text" maxlength="32" value="<?= h((string) ($row['profile_real_name'] ?? '')) ?>" placeholder="姓名" autocomplete="name" style="max-width:12rem;">
                      </div>
                      <button type="submit" class="btn btn-primary btn-sm" style="margin-top:0.1rem;">保存</button>
                    </div>
                    <p class="muted" style="margin:0.45rem 0 0;font-size:0.78rem;">可清空表示未登记；保存时班级会去掉末尾「班」字以与运动会匹配一致。</p>
                  </form>
                <?php endif; ?>
              </dd>
            </div>
            <div>
              <dt>注册时间</dt>
              <dd class="muted"><?= h((string) $row['created_at']) ?></dd>
            </div>
            <div>
              <dt>最近登录</dt>
              <dd>
                <?php if (!empty($row['last_login_ip'])) : ?>
                  <span class="admin-pill admin-pill--mono" title="IP"><?= h((string) $row['last_login_ip']) ?></span>
                <?php else : ?>
                  <span class="muted">—</span>
                <?php endif; ?>
                <?php if (!empty($row['last_login_at'])) : ?>
                  <span class="muted" style="margin-left:.35rem;"><?= h((string) $row['last_login_at']) ?></span>
                <?php endif; ?>
              </dd>
            </div>
            <div>
              <dt>登录封禁</dt>
              <dd>
                <?php if (!empty($row['login_banned_until'])) : ?>
                  <span class="admin-pill admin-pill--warn"><?= h((string) $row['login_banned_until']) ?></span>
                <?php else : ?>
                  <span class="muted">未限制</span>
                <?php endif; ?>
              </dd>
            </div>
            <div>
              <dt>赞助</dt>
              <dd>
                <?php if (!$sponsorOk) : ?>
                  <span class="muted" title="migration_user_sponsor.sql">未启用</span>
                <?php elseif ((int) ($row['is_sponsor'] ?? 0) === 1) : ?>
                  <span class="admin-pill admin-pill--ok">赞助展示</span>
                <?php else : ?>
                  <span class="muted">否</span>
                <?php endif; ?>
              </dd>
            </div>
            <div>
              <dt>实名</dt>
              <dd>
                <?php if (!$realnameOk) : ?>
                  <span class="muted" title="migration_user_realname.sql">未启用</span>
                <?php elseif ((int) ($row['realname_verified'] ?? 0) === 1) : ?>
                  <span class="admin-pill admin-pill--ok">已实名</span>
                <?php elseif ((int) ($row['realname_allowed'] ?? 0) === 1) : ?>
                  <span class="admin-pill admin-pill--warn">可发起认证</span>
                <?php else : ?>
                  <span class="muted">未开放</span>
                <?php endif; ?>
              </dd>
            </div>
          </dl>

          <?php if (!$isAdminRow) : ?>
          <div class="admin-user-actions">
            <div class="admin-user-actions__row">
              <span class="admin-user-actions__label">发言</span>
              <form class="inline-form" method="post" action="<?= h(url('/admin/users/toggle-ban')) ?>">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="_return_page" value="<?= (int) $page ?>">
                <?php if ($searchQ !== '') : ?>
                  <input type="hidden" name="_return_q" value="<?= h($searchQ) ?>">
                <?php endif; ?>
                <input type="hidden" name="_return_anchor" value="u-<?= $uid ?>">
                <input type="hidden" name="id" value="<?= $uid ?>">
                <input type="hidden" name="banned" value="<?= (int) $row['banned'] === 1 ? '0' : '1' ?>">
                <button type="submit" class="btn btn-ghost btn-sm"><?= (int) $row['banned'] === 1 ? '解除禁言' : '禁言' ?></button>
              </form>
            </div>
            <div class="admin-user-actions__row">
              <span class="admin-user-actions__label">封登录</span>
              <form class="inline-form admin-users-mini" method="post" action="<?= h(url('/admin/users/set-login-ban')) ?>">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="_return_page" value="<?= (int) $page ?>">
                <?php if ($searchQ !== '') : ?>
                  <input type="hidden" name="_return_q" value="<?= h($searchQ) ?>">
                <?php endif; ?>
                <input type="hidden" name="id" value="<?= $uid ?>">
                <input type="hidden" name="_return_anchor" value="u-<?= $uid ?>">
                <input name="ban_until" type="datetime-local" value="<?= h($curUntil !== '' ? $curUntil : $defaultUntil) ?>" class="input input-sm">
                <button type="submit" class="btn btn-primary btn-sm">保存到期</button>
                <?php if (!empty($row['login_banned_until'])) : ?>
                <button type="submit" class="btn btn-ghost btn-sm" name="ban_until" value="">解除封禁</button>
                <?php endif; ?>
              </form>
            </div>
            <?php if ($sponsorOk) : ?>
            <div class="admin-user-actions__row">
              <span class="admin-user-actions__label">赞助</span>
              <form class="inline-form" method="post" action="<?= h(url('/admin/users/toggle-sponsor')) ?>">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="_return_page" value="<?= (int) $page ?>">
                <?php if ($searchQ !== '') : ?>
                  <input type="hidden" name="_return_q" value="<?= h($searchQ) ?>">
                <?php endif; ?>
                <input type="hidden" name="_return_anchor" value="u-<?= $uid ?>">
                <input type="hidden" name="id" value="<?= $uid ?>">
                <button type="submit" class="btn btn-ghost btn-sm"><?= (int) ($row['is_sponsor'] ?? 0) === 1 ? '取消赞助标识' : '设为赞助展示' ?></button>
              </form>
            </div>
            <?php endif; ?>
            <?php if ($realnameOk) : ?>
            <div class="admin-user-actions__row">
              <span class="admin-user-actions__label">实名</span>
              <form class="inline-form" method="post" action="<?= h(url('/admin/users/toggle-realname-allowed')) ?>">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="_return_page" value="<?= (int) $page ?>">
                <?php if ($searchQ !== '') : ?>
                  <input type="hidden" name="_return_q" value="<?= h($searchQ) ?>">
                <?php endif; ?>
                <input type="hidden" name="_return_anchor" value="u-<?= $uid ?>">
                <input type="hidden" name="id" value="<?= $uid ?>">
                <button type="submit" class="btn btn-ghost btn-sm"><?= (int) ($row['realname_allowed'] ?? 0) === 1 ? '关闭认证入口' : '允许发起认证' ?></button>
              </form>
              <?php if ((int) ($row['realname_verified'] ?? 0) === 1) : ?>
                <a class="btn btn-ghost btn-sm" href="<?= h(url('/admin/users/realname', ['id' => $uid])) ?>">查看实名资料</a>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <?php if (!$isAdminRow && (!empty($row['last_login_ip']) || $viewerSuper)) : ?>
          <details class="admin-user-more">
            <summary><?= $viewerSuper ? '网络封禁 · 二级管理（站长）' : '按 IP 封禁登录' ?></summary>
            <div class="inner">
              <?php if (!empty($row['last_login_ip'])) : ?>
              <?php
                $lipRow = (string) $row['last_login_ip'];
                $ipBanMap = $ipBanActive ?? [];
                $thisIpLoginBanned = $lipRow !== '' && isset($ipBanMap[$lipRow]);
              ?>
              <p class="muted" style="margin:0 0 .5rem;font-size:.82rem;">当前记录 IP：<strong class="admin-pill admin-pill--mono"><?= h($lipRow) ?></strong>
                <?php if ($thisIpLoginBanned) : ?>
                  <span class="admin-tag admin-tag--danger" style="margin-left:.35rem;font-weight:700;">已封禁</span>
                  <?php if (ip_ban_mysql_until_is_indefinite((string) $ipBanMap[$lipRow])) : ?>
                    <span class="muted" style="margin-left:.25rem;font-size:.78rem;">（不限期）</span>
                  <?php endif; ?>
                <?php endif; ?>
              </p>
              <div class="admin-user-actions__row" style="margin-bottom:.5rem;">
                <form class="inline-form admin-users-mini" method="post" action="<?= h(url('/admin/ip-ban/set')) ?>">
                  <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                  <input type="hidden" name="_return_page" value="<?= (int) $page ?>">
                  <?php if ($searchQ !== '') : ?>
                    <input type="hidden" name="_return_q" value="<?= h($searchQ) ?>">
                  <?php endif; ?>
                  <input type="hidden" name="_return_anchor" value="u-<?= $uid ?>">
                  <input type="hidden" name="ip" value="<?= h((string) $row['last_login_ip']) ?>">
                  <span class="admin-tag admin-tag--danger">封禁该 IP 登录</span>
                  <span class="muted" style="font-size:.78rem;margin:0 .35rem;">不限期，解除请点右侧按钮</span>
                  <button type="submit" class="btn btn-danger btn-sm">封禁 IP</button>
                </form>
                <form class="inline-form" method="post" action="<?= h(url('/admin/ip-ban/clear')) ?>">
                  <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                  <input type="hidden" name="_return_page" value="<?= (int) $page ?>">
                  <?php if ($searchQ !== '') : ?>
                    <input type="hidden" name="_return_q" value="<?= h($searchQ) ?>">
                  <?php endif; ?>
                  <input type="hidden" name="_return_anchor" value="u-<?= $uid ?>">
                  <input type="hidden" name="ip" value="<?= h((string) $row['last_login_ip']) ?>">
                  <button type="submit" class="btn btn-ghost btn-sm">解除 IP 封禁</button>
                </form>
              </div>
              <?php endif; ?>

              <?php if ($viewerSuper) : ?>
              <form class="inline-form" method="post" action="<?= h(url('/admin/users/toggle-moderator-l2')) ?>" style="margin-top:.35rem;">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="_return_page" value="<?= (int) $page ?>">
                <?php if ($searchQ !== '') : ?>
                  <input type="hidden" name="_return_q" value="<?= h($searchQ) ?>">
                <?php endif; ?>
                <input type="hidden" name="_return_anchor" value="u-<?= $uid ?>">
                <input type="hidden" name="id" value="<?= $uid ?>">
                <button type="submit" class="btn btn-ghost btn-sm"><?= (int) ($row['moderator_l2'] ?? 0) === 1 ? '取消二级管理身份' : '设为二级管理员' ?></button>
              </form>
              <?php
              if ($l2PermCol && (int) ($row['moderator_l2'] ?? 0) === 1) :
                  $rp = array_fill_keys(array_keys($permLabels), false);
                  $rawP = trim((string) ($row['moderator_l2_perms'] ?? ''));
                  if ($rawP !== '') {
                      $dj = json_decode($rawP, true);
                      if (is_array($dj)) {
                          foreach (array_keys($permLabels) as $pk) {
                              $rp[$pk] = !empty($dj[$pk]);
                          }
                      }
                  } else {
                      $rp['moderation'] = true;
                      $rp['anon_identity'] = true;
                  }
              ?>
              <form class="admin-l2-perms-form" method="post" action="<?= h(url('/admin/users/save-moderator-l2-perms')) ?>">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="_return_page" value="<?= (int) $page ?>">
                <?php if ($searchQ !== '') : ?>
                  <input type="hidden" name="_return_q" value="<?= h($searchQ) ?>">
                <?php endif; ?>
                <input type="hidden" name="_return_anchor" value="u-<?= $uid ?>">
                <input type="hidden" name="id" value="<?= $uid ?>">
                <p class="admin-l2-perms-kicker muted">该账号可使用的后台能力（逐项勾选后保存）</p>
                <?php foreach ($permLabels as $pkey => $plab) : ?>
                  <div class="field">
                    <label>
                      <input type="checkbox" name="perm_<?= h($pkey) ?>" value="1"<?= !empty($rp[$pkey]) ? ' checked' : '' ?>>
                      <span><?= h($plab) ?></span>
                    </label>
                  </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary btn-sm" style="margin-top:.45rem;">保存权限</button>
              </form>
              <?php endif; ?>
              <?php endif; ?>
            </div>
          </details>
          <?php endif; ?>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <?php if ($pages > 1 && $total > 0) : ?>
  <nav class="admin-users-pager" aria-label="分页" style="margin-top:1rem;border-bottom:none">
    <?php if ($page > 1) : ?>
      <a class="btn btn-ghost btn-sm" href="<?= h($buildListUrl($searchQ, $page - 1)) ?>">上一页</a>
    <?php endif; ?>
    <span class="muted">第 <?= (int) $page ?> / <?= (int) $pages ?> 页 · 每页 <?= (int) $perPage ?> <?= $isIpBanView ? '个 IP' : '人' ?></span>
    <div class="admin-users-pager__nums">
      <?php for ($pi = $pagerStart; $pi <= $pagerEnd; $pi++) : ?>
        <?php if ($pi === $page) : ?>
          <span class="btn btn-primary btn-sm" style="pointer-events:none"><?= (int) $pi ?></span>
        <?php else : ?>
          <a class="btn btn-ghost btn-sm" href="<?= h($buildListUrl($searchQ, $pi)) ?>"><?= (int) $pi ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
    <?php if ($page < $pages) : ?>
      <a class="btn btn-ghost btn-sm" href="<?= h($buildListUrl($searchQ, $page + 1)) ?>">下一页</a>
    <?php endif; ?>
  </nav>
  <?php endif; ?>
</div>
<script>
(function(){
  try {
    var h = location.hash;
    if (!h || h.length < 2) return;
    var id = decodeURIComponent(h.slice(1));
    if (!/^u-\d+$/.test(id)) return;
    var el = document.getElementById(id);
    if (!el) return;
    requestAnimationFrame(function(){
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      el.classList.add('admin-user-card--highlight');
      setTimeout(function(){ el.classList.remove('admin-user-card--highlight'); }, 2200);
    });
  } catch (e) {}
})();
</script>
