<?php declare(strict_types=1);
$f = $filter ?? 'all';
$active = (int) $box['is_active'] === 1;
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <a href="<?= h(url('/ask')) ?>">匿名提问箱</a>
  <span> / </span>
  <span><?= h((string) $box['title']) ?></span>
</nav>

<section class="ask-manage-head card">
  <div class="ask-manage-head-main">
    <h1 class="ask-manage-title"><?= h((string) $box['title']) ?>
      <span class="ask-status-pill <?= $active ? 'is-on' : 'is-off' ?>"><?= $active ? '接收中' : '已暂停' ?></span>
    </h1>
    <?php if (trim((string) ($box['intro'] ?? '')) !== '') : ?>
      <p class="muted" style="margin:0.35rem 0 0;"><?= h((string) $box['intro']) ?></p>
    <?php endif; ?>
    <p class="ask-privacy-note">🔒 你只能看到问题内容，<strong>无法看到提问者是谁</strong>。</p>
  </div>
  <div class="ask-share-box">
    <label class="ask-share-label">分享链接</label>
    <div class="ask-share-row">
      <input id="askShareUrl" class="ask-share-input" type="text" readonly value="<?= h($shareUrl) ?>">
      <button type="button" class="btn btn-ghost btn-sm" id="askCopyBtn" data-copy-target="#askShareUrl">复制</button>
    </div>
    <div class="ask-manage-btns">
      <a class="btn btn-primary btn-sm" href="<?= h(url('/ask/box/' . (int) $box['id'] . '/poster')) ?>">生成二维码海报</a>
      <a class="btn btn-ghost btn-sm" href="<?= h($shareUrl) ?>" target="_blank" rel="noopener">预览提问页</a>
      <form class="inline-form" method="post" action="<?= h(url('/ask/box/toggle')) ?>">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="box_id" value="<?= (int) $box['id'] ?>">
        <button type="submit" class="btn btn-ghost btn-sm"><?= $active ? '暂停接收' : '重新开启' ?></button>
      </form>
      <form class="inline-form" method="post" action="<?= h(url('/ask/box/delete')) ?>" onsubmit="return confirm('确定删除该提问箱？其下所有提问将一并删除，且无法恢复。');">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="box_id" value="<?= (int) $box['id'] ?>">
        <button type="submit" class="btn btn-ghost btn-sm ask-danger-btn">删除提问箱</button>
      </form>
    </div>
  </div>
</section>

<div class="ask-filter-tabs">
  <a href="<?= h(url('/ask/box/' . (int) $box['id'])) ?>" class="<?= $f === 'all' ? 'is-active' : '' ?>">全部</a>
  <a href="<?= h(url('/ask/box/' . (int) $box['id'], ['filter' => 'pending'])) ?>" class="<?= $f === 'pending' ? 'is-active' : '' ?>">待回复</a>
  <a href="<?= h(url('/ask/box/' . (int) $box['id'], ['filter' => 'answered'])) ?>" class="<?= $f === 'answered' ? 'is-active' : '' ?>">已回复</a>
</div>

<?php if (empty($questions)) : ?>
  <p class="muted">还没有收到提问。把二维码海报分享出去，等待第一条匿名提问吧。</p>
<?php else : ?>
  <ul class="ask-q-list">
    <?php foreach ($questions as $q) : ?>
      <?php $answered = (string) $q['status'] === 'answered' && $q['answer'] !== null && $q['answer'] !== ''; ?>
      <li class="ask-q-card card">
        <div class="ask-q-head">
          <span class="ask-q-avatar" aria-hidden="true">?</span>
          <div class="ask-q-meta">
            <span class="ask-q-from">匿名同学</span>
            <span class="muted ask-q-date"><?= h((string) $q['created_at']) ?></span>
          </div>
          <?php if (!$answered) : ?><span class="badge badge-pin">待回复</span><?php endif; ?>
        </div>
        <p class="ask-q-content"><?= anon_ask_text_html((string) $q['content']) ?></p>

        <?php if ($answered) : ?>
          <div class="ask-a-block">
            <div class="ask-a-head">
              <span class="ask-a-label">我的回复</span>
              <?php if ((int) $q['is_public'] === 1) : ?>
                <span class="ask-public-badge">已公开</span>
              <?php else : ?>
                <span class="muted" style="font-size:0.78rem;">仅自己可见</span>
              <?php endif; ?>
            </div>
            <p class="ask-a-content"><?= anon_ask_text_html((string) $q['answer']) ?></p>
          </div>
        <?php endif; ?>

        <div class="ask-q-actions">
          <details class="ask-reply-details">
            <summary class="btn btn-primary btn-sm"><?= $answered ? '修改回复' : '回复' ?></summary>
            <form class="ask-reply-form js-moderation-submit" method="post" action="<?= h(url('/ask/answer')) ?>">
              <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="question_id" value="<?= (int) $q['id'] ?>">
              <textarea name="answer" required maxlength="2000" rows="4" placeholder="写下你的回复…"><?= h((string) ($q['answer'] ?? '')) ?></textarea>
              <label class="home-checkbox ask-public-toggle">
                <input type="checkbox" name="is_public" value="1" <?= (int) $q['is_public'] === 1 ? 'checked' : '' ?>>
                <span>公开到问答墙（只显示问题与回复，不含任何提问者信息）</span>
              </label>
              <button type="submit" class="btn btn-primary btn-sm">发布回复</button>
            </form>
          </details>
          <form class="inline-form" method="post" action="<?= h(url('/ask/question/hide')) ?>" onsubmit="return confirm('确定删除这条提问？');">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="question_id" value="<?= (int) $q['id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm ask-danger-btn">删除</button>
          </form>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<script>
(function(){
  var btn=document.getElementById('askCopyBtn');
  if(!btn)return;
  btn.addEventListener('click',function(){
    var sel=btn.getAttribute('data-copy-target');
    var inp=sel?document.querySelector(sel):null;
    if(!inp)return;
    var text=inp.value;
    function done(){var t=btn.textContent;btn.textContent='已复制';setTimeout(function(){btn.textContent=t;},1500);}
    if(navigator.clipboard&&navigator.clipboard.writeText){
      navigator.clipboard.writeText(text).then(done,function(){inp.select();try{document.execCommand('copy');done();}catch(e){}});
    }else{inp.select();try{document.execCommand('copy');done();}catch(e){}}
  });
})();
</script>
