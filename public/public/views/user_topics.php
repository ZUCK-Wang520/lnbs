<?php declare(strict_types=1);
$u = $pubUser;
$initial = mb_substr((string) $u['nickname'], 0, 1, 'UTF-8');
$av = user_avatar_public_url($u['avatar'] ?? null);
$publicLikes = user_profile_likes_column_ok() ? trim((string) ($u['profile_likes'] ?? '')) : '';
$likeLines = $publicLikes !== '' ? preg_split('/\R/u', $publicLikes, -1, PREG_SPLIT_NO_EMPTY) : [];
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <span><?= h((string) $u['nickname']) ?> 的主题</span>
</nav>

<header class="card user-topics-head" style="display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem;padding:1.25rem 1.35rem;">
  <?php if ($av) : ?>
    <img class="user-avatar-img user-avatar-img--large" src="<?= h($av) ?>" alt="" width="64" height="64" loading="lazy">
  <?php else : ?>
    <span class="user-avatar-fallback user-avatar-fallback--large" aria-hidden="true"><?= h($initial) ?></span>
  <?php endif; ?>
  <div>
    <h1 style="margin:0;font-size:1.25rem;"><?= h((string) $u['nickname']) ?></h1>
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
