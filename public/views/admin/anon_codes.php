<?php declare(strict_types=1);
$codes = is_array($codes ?? null) ? $codes : [];
$kindLabels = ['topic' => '匿名发帖', 'reply' => '匿名回复', 'both' => '发帖+回复'];
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/admin')) ?>">管理后台</a>
  <span> / </span>
  <span>匿名兑换码</span>
</nav>
<h1 style="margin-bottom:0.5rem;">匿名兑换码</h1>
<p class="muted" style="margin-top:0;margin-bottom:1.25rem;">生成兑换码供用户增加匿名发帖/回复次数（超出每日 3 次免费额度后消耗兑换次数）。</p>

<div class="card" style="max-width:44rem;margin-bottom:1.25rem;padding:1rem 1.15rem;">
  <h2 style="margin:0 0 0.75rem;font-size:1.05rem;">生成兑换码</h2>
  <form method="post" action="<?= h(url('/admin/anon-codes/generate')) ?>">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <div class="field">
      <label for="anon_code_kind">兑换类型</label>
      <select id="anon_code_kind" name="kind" required>
        <option value="topic">仅匿名发帖次数</option>
        <option value="reply">仅匿名回复次数</option>
        <option value="both">发帖 + 回复（可分别填写次数）</option>
      </select>
    </div>
    <div class="field">
      <label for="anon_topic_grants">匿名发帖次数</label>
      <input id="anon_topic_grants" name="topic_grants" type="number" min="0" max="999" value="5">
    </div>
    <div class="field">
      <label for="anon_reply_grants">匿名回复次数</label>
      <input id="anon_reply_grants" name="reply_grants" type="number" min="0" max="999" value="0">
    </div>
    <div class="field">
      <label for="anon_max_redemptions">每码可被不同用户兑换次数上限</label>
      <input id="anon_max_redemptions" name="max_redemptions" type="number" min="1" max="100000" value="1" required>
      <p class="muted" style="margin:0.35rem 0 0;font-size:0.82rem;">每位用户同一兑换码仅可使用一次。</p>
    </div>
    <div class="field">
      <label for="anon_batch">一次生成数量（最多 50）</label>
      <input id="anon_batch" name="batch" type="number" min="1" max="50" value="1" required>
    </div>
    <div class="field">
      <label for="anon_expires_at">过期时间（可选）</label>
      <input id="anon_expires_at" name="expires_at" type="datetime-local">
    </div>
    <div class="field">
      <label for="anon_note">备注（可选）</label>
      <input id="anon_note" name="note" type="text" maxlength="255" placeholder="例如：活动赠送">
    </div>
    <button type="submit" class="btn btn-primary btn-sm">生成</button>
  </form>
</div>

<div class="card" style="padding:1rem 1.15rem;">
  <h2 style="margin:0 0 0.75rem;font-size:1.05rem;">最近兑换码</h2>
  <?php if (empty($codes)) : ?>
    <p class="muted" style="margin:0;">暂无记录。</p>
  <?php else : ?>
    <div style="overflow-x:auto;">
      <table class="admin-table" style="width:100%;font-size:0.86rem;">
        <thead>
          <tr>
            <th>兑换码</th>
            <th>类型</th>
            <th>次数</th>
            <th>已兑/上限</th>
            <th>创建</th>
            <th>过期</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($codes as $c) : ?>
          <tr>
            <td><code><?= h((string) $c['code']) ?></code></td>
            <td><?= h($kindLabels[(string) ($c['kind'] ?? '')] ?? (string) $c['kind']) ?></td>
            <td>
              <?php if ((int) ($c['topic_grants'] ?? 0) > 0) : ?>发 <?= (int) $c['topic_grants'] ?><?php endif; ?>
              <?php if ((int) ($c['reply_grants'] ?? 0) > 0) : ?><?= (int) ($c['topic_grants'] ?? 0) > 0 ? ' · ' : '' ?>回 <?= (int) $c['reply_grants'] ?><?php endif; ?>
            </td>
            <td><?= (int) ($c['redemption_count'] ?? 0) ?> / <?= (int) ($c['max_redemptions'] ?? 0) ?></td>
            <td class="muted"><?= h((string) ($c['created_at'] ?? '')) ?><br><?= h((string) ($c['creator_nickname'] ?? '')) ?></td>
            <td class="muted"><?= !empty($c['expires_at']) ? h((string) $c['expires_at']) : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
