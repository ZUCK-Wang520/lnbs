<?php declare(strict_types=1);
/** @var ?array $current */
$isAdmin = $current && ($current['role'] ?? '') === 'admin';
$topicOwnerUid = forum_row_real_author_id($topic);
$isOwnTopic = $current && (int) $current['id'] === $topicOwnerUid;
$threadedReplies = forum_posts_parent_ok();
$canReply = $current && (int) $current['banned'] === 0 && (int) $topic['locked'] === 0;
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <a href="<?= h(url('/board/' . $topic['board_slug'])) ?>"><?= h($topic['board_name']) ?></a>
  <span> / </span>
  <span>主题</span>
</nav>
<article class="topic-head">
  <h1>
    <?= h($topic['title']) ?>
    <?php if ((int) $topic['pinned'] === 1) : ?><span class="badge badge-pin">置顶</span><?php endif; ?>
    <?php if ((int) $topic['locked'] === 1) : ?><span class="badge badge-lock">锁定</span><?php endif; ?>
    <?php if ((int) ($topic['is_anonymous'] ?? 0) === 1) : ?><span class="badge badge-anon">匿名</span><?php endif; ?>
  </h1>
  <?php
    $topicPubId = (int) ($topic['author_public_id'] ?? 0);
    $topicAv = $topicPubId > 0 ? user_avatar_public_url($topic['author_avatar'] ?? null) : null;
    $topicNick = (string) $topic['author_nickname'];
    $topicInitial = $topicNick !== '' ? mb_substr($topicNick, 0, 1, 'UTF-8') : '?';
  ?>
  <div class="topic-author-row">
    <?php if ($topicPubId > 0) : ?>
      <a href="<?= h(url('/user/' . $topicPubId . '/topics')) ?>" class="user-avatar-link" title="查看其全部主题">
        <?php if ($topicAv) : ?>
          <img class="user-avatar-img user-avatar-img--topic" src="<?= h($topicAv) ?>" alt="" width="48" height="48" loading="lazy">
        <?php else : ?>
          <span class="user-avatar-fallback user-avatar-fallback--topic"><?= h($topicInitial) ?></span>
        <?php endif; ?>
      </a>
    <?php else : ?>
      <span class="user-avatar-link user-avatar-link--nohref" aria-hidden="true">
        <span class="user-avatar-fallback user-avatar-fallback--topic user-avatar-fallback--anon"><?= h($topicInitial) ?></span>
      </span>
    <?php endif; ?>
    <div class="topic-meta topic-meta--inline">
      <?= h($topic['author_nickname']) ?>
      <?php if ($isAdmin && !empty($topic['author_real_nickname'])) : ?>
        <span class="badge badge-real">真实：<?= h((string) $topic['author_real_nickname']) ?></span>
      <?php endif; ?>
      <span class="topic-meta-sep">·</span>
      <?= h($topic['created_at']) ?>
    </div>
  </div>
  <div class="body-text" style="margin-top:1rem;"><?= h($topic['body']) ?></div>
  <?php if ($current && $isOwnTopic && !$isAdmin) : ?>
    <div class="toolbar topic-own-toolbar" style="margin-top:1rem;">
      <form class="inline-form" method="post" action="<?= h(url('/topic/' . (int) $topic['id'] . '/delete')) ?>" onsubmit="return confirm('确定删除整个主题？下属所有回复将一并删除。');">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <button type="submit" class="btn btn-danger btn-sm">删除我的主题</button>
      </form>
    </div>
  <?php endif; ?>
</article>

<div class="post-list">
  <?php foreach ($posts as $p) : ?>
    <?php
      $pPub = (int) ($p['author_public_id'] ?? 0);
      $pAv = $pPub > 0 ? user_avatar_public_url($p['author_avatar'] ?? null) : null;
      $pNick = (string) $p['author_nickname'];
      $pIni = $pNick !== '' ? mb_substr($pNick, 0, 1, 'UTF-8') : '?';
    ?>
    <?php $isOwnPost = $current && (int) $current['id'] === forum_row_real_author_id($p); ?>
    <?php
      $ppid = isset($p['parent_post_id']) ? (int) $p['parent_post_id'] : 0;
      $parnick = isset($p['parent_author_nickname']) && $p['parent_author_nickname'] !== null && $p['parent_author_nickname'] !== ''
        ? (string) $p['parent_author_nickname'] : '';
      $postClasses = 'post';
      if ($threadedReplies && $ppid > 0 && $parnick !== '') {
          $postClasses .= ' post--nested';
      }
    ?>
    <article class="<?= h($postClasses) ?>" id="post-<?= (int) $p['id'] ?>">
      <div class="post-side">
        <div class="post-avatar-row">
          <?php if ($pPub > 0) : ?>
            <a href="<?= h(url('/user/' . $pPub . '/topics')) ?>" class="user-avatar-link" title="查看其全部主题">
              <?php if ($pAv) : ?>
                <img class="user-avatar-img user-avatar-img--post" src="<?= h($pAv) ?>" alt="" width="40" height="40" loading="lazy">
              <?php else : ?>
                <span class="user-avatar-fallback user-avatar-fallback--post"><?= h($pIni) ?></span>
              <?php endif; ?>
            </a>
          <?php else : ?>
            <span class="user-avatar-link user-avatar-link--nohref" aria-hidden="true">
              <span class="user-avatar-fallback user-avatar-fallback--post user-avatar-fallback--anon"><?= h($pIni) ?></span>
            </span>
          <?php endif; ?>
        </div>
        <strong><?= h($p['author_nickname']) ?></strong>
        <?php if ((int) ($p['is_anonymous'] ?? 0) === 1) : ?><span class="badge badge-anon badge-tiny">匿</span><?php endif; ?>
        <?php if ($isAdmin && !empty($p['author_real_nickname'])) : ?>
          <div class="post-real-nick muted">真实：<?= h((string) $p['author_real_nickname']) ?></div>
        <?php endif; ?>
        <?= h($p['created_at']) ?>
      </div>
      <div>
        <?php if ($threadedReplies && $ppid > 0 && $parnick !== '') : ?>
          <div class="post-reply-ref muted">
            回复 <a href="#post-<?= (int) $ppid ?>">@<?= h($parnick) ?></a>
            <?php if ($isAdmin && !empty($p['parent_author_real_nickname'])) : ?>
              <span class="badge badge-real" style="font-size:0.7rem;margin-left:0.25rem;">真实：<?= h((string) $p['parent_author_real_nickname']) ?></span>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <div class="body-text"><?= h($p['body']) ?></div>
        <?php if ($threadedReplies && $canReply) : ?>
          <div class="post-actions-bar" style="margin-top:0.5rem;">
            <button type="button" class="btn btn-ghost btn-sm js-reply-to-post" data-post-id="<?= (int) $p['id'] ?>" data-author="<?= h($pNick) ?>">回复 TA</button>
          </div>
        <?php endif; ?>
        <?php if ($isAdmin) : ?>
          <form class="inline-form" method="post" action="<?= h(url('/admin/posts/delete')) ?>" style="margin-top:0.75rem;" onsubmit="return confirm('删除该回复？');">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <input type="hidden" name="topic_id" value="<?= (int) $topic['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">删除回复</button>
          </form>
        <?php elseif ($isOwnPost) : ?>
          <form class="inline-form" method="post" action="<?= h(url('/post/' . (int) $p['id'] . '/delete')) ?>" style="margin-top:0.75rem;" onsubmit="return confirm('确定删除这条回复？');">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="topic_id" value="<?= (int) $topic['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">删除我的回复</button>
          </form>
        <?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<?php if ((int) $topic['locked'] === 0) : ?>
  <?php if ($current && (int) $current['banned'] === 0) : ?>
    <div class="form-panel" style="max-width:100%;margin-top:1.25rem;" id="topic-reply-form-wrap">
      <h2 style="margin-top:0;font-size:1.05rem;">发表回复</h2>
      <?php if ($threadedReplies) : ?>
        <p class="muted topic-reply-target-line" id="topic-reply-target-line" hidden style="margin:0 0 0.75rem;font-size:0.9rem;">
          正在回复 <strong id="topic-reply-target-name"></strong>
          <button type="button" class="btn btn-ghost btn-sm" id="topic-reply-target-clear" style="margin-left:0.35rem;">取消</button>
        </p>
      <?php endif; ?>
      <form method="post" action="<?= h(url('/topic/' . (int) $topic['id'] . '/reply')) ?>" class="js-moderation-submit">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <?php if ($threadedReplies) : ?>
          <input type="hidden" name="parent_post_id" id="reply_parent_post_id" value="">
        <?php endif; ?>
        <div class="field">
          <label for="reply_body">内容</label>
          <textarea id="reply_body" name="body" required></textarea>
        </div>
        <div class="field home-checkbox-field">
          <label class="home-checkbox">
            <input type="checkbox" name="anonymous" value="1">
            <span>匿名回复（管理员可见真实昵称）</span>
          </label>
        </div>
        <div class="field">
          <label for="reply_display">匿名显示名（可选）</label>
          <input id="reply_display" name="display_nickname" type="text" maxlength="16" placeholder="留空为「匿名」">
        </div>
        <button type="submit" class="btn btn-primary">回复</button>
      </form>
    </div>
  <?php elseif (!$current) : ?>
    <p class="muted" style="margin-top:1.25rem;"><a href="<?= h(url('/login')) ?>">登录</a> 后参与回复。</p>
  <?php elseif ((int) $current['banned'] === 1) : ?>
    <p class="muted" style="margin-top:1.25rem;">您已被禁言，无法回复。</p>
  <?php endif; ?>
<?php else : ?>
  <p class="muted" style="margin-top:1.25rem;">主题已锁定。</p>
<?php endif; ?>

<?php if ($threadedReplies && $canReply) : ?>
<script>
(function(){
  var hid = document.getElementById('reply_parent_post_id');
  var line = document.getElementById('topic-reply-target-line');
  var nameEl = document.getElementById('topic-reply-target-name');
  var clearBtn = document.getElementById('topic-reply-target-clear');
  var wrap = document.getElementById('topic-reply-form-wrap');
  if (!hid || !line || !nameEl || !clearBtn || !wrap) return;
  function clearTarget(){
    hid.value = '';
    line.hidden = true;
    nameEl.textContent = '';
  }
  clearBtn.addEventListener('click', clearTarget);
  document.querySelectorAll('.js-reply-to-post').forEach(function(btn){
    btn.addEventListener('click', function(){
      var id = btn.getAttribute('data-post-id');
      var author = btn.getAttribute('data-author') || '该用户';
      if (!id) return;
      hid.value = id;
      nameEl.textContent = '@' + author;
      line.hidden = false;
      wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      var ta = document.getElementById('reply_body');
      if (ta) { ta.focus(); }
    });
  });
})();
</script>
<?php endif; ?>

<?php if ($current && ($current['role'] ?? '') === 'admin') : ?>
  <div class="toolbar" style="margin-top:1.5rem;">
    <form class="inline-form" method="post" action="<?= h(url('/admin/topics/delete')) ?>" onsubmit="return confirm('删除整个主题？');">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int) $topic['id'] ?>">
      <button type="submit" class="btn btn-danger">删除主题</button>
    </form>
  </div>
<?php endif; ?>
