<?php declare(strict_types=1);
$q = (string) ($q ?? '');
$results = $results ?? [];
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <span>搜索</span>
</nav>
<h1 style="margin-bottom:1rem;">搜索主题</h1>

<form method="get" action="<?= h(url('/search')) ?>" class="card" style="display:flex;flex-wrap:wrap;gap:0.65rem;align-items:flex-end;padding:0.85rem 1rem;margin-bottom:1rem;max-width:52rem;">
  <div class="field" style="margin:0;min-width:14rem;flex:1;">
    <label for="search_q">关键词</label>
    <input id="search_q" name="q" type="search" value="<?= h($q) ?>" placeholder="标题或正文包含…" maxlength="64" autocomplete="off">
  </div>
  <button type="submit" class="btn btn-primary btn-sm">搜索</button>
  <?php if ($q !== '') : ?>
    <a href="<?= h(url('/search')) ?>" class="btn btn-ghost btn-sm">清除</a>
  <?php endif; ?>
</form>

<?php if ($q !== '') : ?>
  <p class="muted" style="margin-top:0;">共找到 <?= (int) count($results) ?> 条结果</p>
<?php endif; ?>

<?php if (!empty($results)) : ?>
  <div class="table-wrap" style="margin-top:0.75rem;">
    <table>
      <thead>
        <tr>
          <th>标题</th>
          <th>版块</th>
          <th>作者</th>
          <th>回复</th>
          <th>点赞</th>
          <th>浏览</th>
          <th>更新</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($results as $r) : ?>
          <tr>
            <td>
              <a href="<?= h(url('/topic/' . (int) $r['id'])) ?>"><?= h((string) $r['title']) ?></a>
              <?php if ((int) ($r['is_anonymous'] ?? 0) === 1) : ?>
                <span class="badge badge-anon">匿名</span>
              <?php endif; ?>
            </td>
            <td class="muted">
              <a href="<?= h(url('/board/' . (string) $r['board_slug'])) ?>"><?= h((string) $r['board_name']) ?></a>
            </td>
            <td><?= h((string) $r['author_nickname']) ?></td>
            <td class="muted"><?= (int) ($r['reply_count'] ?? 0) ?></td>
            <td class="muted"><?= (int) ($r['like_count'] ?? 0) ?></td>
            <td class="muted"><?= (int) ($r['view_count'] ?? 0) ?></td>
            <td class="muted"><?= h((string) ($r['updated_at'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php elseif ($q !== '') : ?>
  <p class="muted">没有找到匹配结果。</p>
<?php endif; ?>

