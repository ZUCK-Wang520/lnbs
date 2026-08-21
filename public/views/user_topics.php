<?php declare(strict_types=1);
$u = $pubUser;
$initial = mb_substr((string) $u['nickname'], 0, 1, 'UTF-8');
$av = user_avatar_public_url($u['avatar'] ?? null);
$publicLikes = user_profile_likes_column_ok() ? trim((string) ($u['profile_likes'] ?? '')) : '';
$likeLines = $publicLikes !== '' ? preg_split('/\R/u', $publicLikes, -1, PREG_SPLIT_NO_EMPTY) : [];
$isSponsor = user_sponsor_column_ok() && (int) ($u['is_sponsor'] ?? 0) === 1;
$realnameVerified = user_realname_columns_ok() && (int) ($u['realname_verified'] ?? 0) === 1;
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <span><?= h((string) $u['nickname']) ?> 的主题</span>
</nav>

<header class="card user-topics-head" style="display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem;padding:1.25rem 1.35rem;">
  <div class="user-pub-avatar-frame<?= $isSponsor ? ' user-pub-avatar-frame--sponsor' : '' ?>">
    <?php if ($av) : ?>
      <img class="user-avatar-img user-avatar-img--large" src="<?= h($av) ?>" alt="" width="64" height="64" loading="lazy">
    <?php else : ?>
      <span class="user-avatar-fallback user-avatar-fallback--large" aria-hidden="true"><?= h($initial) ?></span>
    <?php endif; ?>
  </div>
  <div style="min-width:0;">
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.45rem;">
      <h1 style="margin:0;font-size:1.25rem;"><?= h((string) $u['nickname']) ?></h1>
      <?php if ($isSponsor) : ?>
        <span class="user-sponsor-badge" title="网站赞助者 · 感谢支持">赞助者</span>
      <?php endif; ?>
      <?php if ($realnameVerified) : ?>
        <span class="user-realname-badge" title="已完成实名认证">已实名</span>
      <?php endif; ?>
      <?php if ((int) ($u['moderator_l2'] ?? 0) === 1) : ?>
        <span class="user-mod2-badge" title="参与内容人工复核的二级管理员">二级管理员</span>
      <?php endif; ?>
    </div>
    <p class="muted" style="margin:0.35rem 0 0;font-size:0.9rem;">以下为该用户公开发布的主题（不含匿名帖）。</p>
    <?php
      $viewerRow = $viewer ?? null;
      $cps = $chatPeerState ?? null;
    ?>
    <?php if ($viewerRow && (int) $viewerRow['id'] !== (int) $u['id'] && is_array($cps) && chat_tables_ok()) : ?>
      <div style="margin-top:0.75rem;display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center;">
        <?php if (!empty($cps['is_friend'])) : ?>
          <a class="btn btn-primary btn-sm" href="<?= h(url('/chat/with/' . (int) $u['id'])) ?>">发私信</a>
        <?php elseif (!empty($cps['out_pending'])) : ?>
          <span class="muted" style="font-size:0.88rem;">好友申请已发送，待对方同意</span>
        <?php elseif (!empty($cps['in_pending'])) : ?>
          <span class="muted" style="font-size:0.88rem;">对方已向你发起好友申请，请到</span>
          <a href="<?= h(url('/chat')) ?>">私信中心</a>
          <span class="muted" style="font-size:0.88rem;">处理</span>
        <?php elseif (!empty($cps['can_request'])) : ?>
          <form method="post" action="<?= h(url('/chat/friend-request')) ?>" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="to_user_id" value="<?= (int) $u['id'] ?>">
            <input type="hidden" name="_ref" value="<?= h('/user/' . (int) $u['id'] . '/topics') ?>">
            <button type="submit" class="btn btn-primary btn-sm">加好友</button>
          </form>
        <?php endif; ?>
        <?php
          $coup = $couplePeerState ?? null;
        ?>
        <?php if (is_array($coup) && !empty($coup['tables_ok'])) : ?>
          <?php if (!empty($coup['we_are_couple'])) : ?>
            <a class="btn btn-ghost btn-sm" href="<?= h(url('/couple')) ?>">情侣空间</a>
          <?php elseif (!empty($coup['out_pending'])) : ?>
            <span class="muted" style="font-size:0.88rem;">情侣邀请已发送，等待对方同意</span>
          <?php elseif (!empty($coup['in_pending'])) : ?>
            <span class="muted" style="font-size:0.88rem;">对方向你发起了情侣邀请，请到</span>
            <a href="<?= h(url('/couple')) ?>">情侣空间</a>
            <span class="muted" style="font-size:0.88rem;">处理</span>
          <?php elseif (!empty($coup['can_invite'])) : ?>
            <details class="couple-invite-details">
              <summary class="btn btn-ghost btn-sm" style="cursor:pointer;list-style:none;">邀请绑定情侣</summary>
              <form method="post" action="<?= h(url('/couple/invite')) ?>" class="couple-invite-form card" style="margin-top:0.65rem;padding:0.85rem 1rem;">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="to_user_id" value="<?= (int) $u['id'] ?>">
                <input type="hidden" name="_ref" value="<?= h('/user/' . (int) $u['id'] . '/topics') ?>">
                <label class="muted" style="display:block;font-size:0.85rem;margin-bottom:0.35rem;">附言（可选）</label>
                <textarea name="message" class="couple-invite-ta" rows="2" maxlength="300" placeholder="一句话告诉 Ta…"></textarea>
                <button type="submit" class="btn btn-primary btn-sm" style="margin-top:0.5rem;">发送邀请</button>
              </form>
            </details>
          <?php elseif (!empty($coup['i_coupled_elsewhere'])) : ?>
            <span class="muted" style="font-size:0.88rem;">你已与他人绑定情侣</span>
          <?php elseif (!empty($coup['they_coupled_elsewhere'])) : ?>
            <span class="muted" style="font-size:0.88rem;">对方已与他人绑定</span>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</header>

<?php if ($publicLikes !== '' && $likeLines !== []) : ?>
  <section class="card user-profile-likes" style="margin-bottom:1.25rem;padding:1.1rem 1.25rem;">
    <h2 style="margin:0 0 0.65rem;font-size:1.05rem;"><?= h((string) $u['nickname']) ?> 的喜欢</h2>
    <ul class="user-profile-likes-list" style="margin:0;padding-left:1.2rem;line-height:1.55;">
      <?php foreach ($likeLines as $line) : ?>
        <li><?= h(trim($line)) ?></li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>

<?php if (empty($topics)) : ?>
  <p class="muted">暂无公开发布的主题。</p>
<?php else : ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>主题</th>
          <th>版块</th>
          <th>更新</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($topics as $t) : ?>
          <tr>
            <td><a href="<?= h(url('/topic/' . (int) $t['id'])) ?>"><?= h((string) $t['title']) ?></a></td>
            <td><a href="<?= h(url('/board/' . (string) $t['board_slug'])) ?>"><?= h((string) $t['board_name']) ?></a></td>
            <td class="muted"><?= h((string) $t['updated_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
