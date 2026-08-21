<?php declare(strict_types=1);
$rows = $appeals ?? [];
$vid = (int) ($viewerId ?? 0);
$actionLabels = [
    'topic_new' => '发帖（版块页）',
    'topic_quick' => '首页快发',
    'post_reply' => '主题回复',
    'profile_likes' => '个人喜欢',
    'chat_send' => '私信',
];
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/admin')) ?>">后台</a>
  <span> / </span>
  <span>人工复核</span>
</nav>

<h1 style="margin-bottom:0.5rem;">人工复核</h1>
<p class="muted" style="margin-top:0;margin-bottom:1.25rem;font-size:0.9rem;line-height:1.55;">
  规则：两名审核员<strong>均通过</strong>则自动发布；<strong>均拒绝</strong>则维持拦截；若<strong>一票通过、一票拒绝</strong>，须由<strong>第三位</strong>审核员表决定案。站长与已标记的二级审核员均可表决；提交者本人不可审自己的条目。
</p>

<?php if (empty($tableOk)) : ?>
  <p class="muted">请先执行数据库脚本 <code>public/database/migration_moderation_appeals.sql</code>。</p>
<?php elseif (empty($rows)) : ?>
  <p class="muted">暂无记录。</p>
<?php else : ?>
  <div style="display:flex;flex-direction:column;gap:1rem;">
    <?php foreach ($rows as $a) : ?>
      <?php
        $aid = (int) $a['id'];
        $authorId = (int) $a['author_user_id'];
        $status = (string) $a['status'];
        $votes = $a['votes'] ?? [];
        $payload = json_decode((string) $a['payload_json'], true);
        if (!is_array($payload)) {
            $payload = [];
        }
        $voted = false;
        foreach ($votes as $vv) {
            if ((int) ($vv['voter_user_id'] ?? 0) === $vid) {
                $voted = true;
                break;
            }
        }
        $canVote = $status === 'pending' && $vid > 0 && $authorId !== $vid && !$voted;
        $alabel = $actionLabels[(string) $a['action']] ?? (string) $a['action'];
      ?>
      <section class="card" style="padding:1rem 1.15rem;">
        <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:0.5rem;align-items:baseline;">
          <strong>#<?= $aid ?></strong>
          <span class="muted" style="font-size:0.85rem;"><?= h((string) $a['created_at']) ?></span>
        </div>
        <p style="margin:0.35rem 0 0;font-size:0.9rem;">
          <span class="muted">类型</span> <?= h($alabel) ?> ·
          <span class="muted">作者</span> <?= h((string) $a['author_nickname']) ?> (<?= $authorId ?>) ·
          <span class="muted">状态</span>
          <?php if ($status === 'pending') : ?>
            <span style="color:var(--accent,#7c6cf8);font-weight:600;">待表决</span>
          <?php elseif ($status === 'approved') : ?>
            <span style="color:var(--success,#4ade80);font-weight:600;">已通过</span>
          <?php else : ?>
            <span style="color:var(--danger,#fb7185);font-weight:600;">已拒绝</span>
          <?php endif; ?>
        </p>
        <?php if (!empty($a['ai_hint'])) : ?>
          <p class="muted" style="margin:0.5rem 0 0;font-size:0.85rem;">AI 提示：<?= h((string) $a['ai_hint']) ?></p>
        <?php endif; ?>
        <details style="margin-top:0.65rem;">
          <summary style="cursor:pointer;font-size:0.88rem;">查看提交内容（JSON）</summary>
          <pre style="margin:0.5rem 0 0;padding:0.65rem;border-radius:8px;font-size:0.78rem;overflow:auto;max-height:14rem;background:var(--surface,rgba(255,255,255,.06));white-space:pre-wrap;word-break:break-word;"><?= h(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre>
        </details>
        <?php if (!empty($votes)) : ?>
          <ul class="muted" style="margin:0.65rem 0 0;padding-left:1.1rem;font-size:0.85rem;">
            <?php foreach ($votes as $v) : ?>
              <li><?= h((string) $v['voter_nick']) ?>：
                <?= (string) $v['decision'] === 'approve' ? '通过' : '拒绝' ?>
                <span class="muted">（<?= h((string) $v['created_at']) ?>）</span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <?php if ($status === 'pending' && $authorId === $vid) : ?>
          <p class="muted" style="margin:0.65rem 0 0;font-size:0.85rem;">你是提交者，不能参与表决。</p>
        <?php elseif ($canVote) : ?>
          <div style="margin-top:0.75rem;display:flex;flex-wrap:wrap;gap:0.5rem;">
            <form class="inline-form" method="post" action="<?= h(url('/admin/moderation/vote')) ?>">
              <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="appeal_id" value="<?= $aid ?>">
              <input type="hidden" name="decision" value="approve">
              <button type="submit" class="btn btn-primary btn-sm">通过</button>
            </form>
            <form class="inline-form" method="post" action="<?= h(url('/admin/moderation/vote')) ?>">
              <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="appeal_id" value="<?= $aid ?>">
              <input type="hidden" name="decision" value="reject">
              <button type="submit" class="btn btn-ghost btn-sm">拒绝</button>
            </form>
          </div>
        <?php elseif ($status === 'pending' && $voted) : ?>
          <p class="muted" style="margin:0.65rem 0 0;font-size:0.85rem;">你已表决，请等待其他审核员。</p>
        <?php elseif ($status !== 'pending' && !empty($a['resolved_at'])) : ?>
          <p class="muted" style="margin:0.5rem 0 0;font-size:0.82rem;">结案于 <?= h((string) $a['resolved_at']) ?></p>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<p style="margin-top:1.5rem;"><a href="<?= h(url('/admin')) ?>" class="btn btn-ghost btn-sm">返回后台首页</a></p>
