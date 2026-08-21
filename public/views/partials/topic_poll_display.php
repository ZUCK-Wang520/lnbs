<?php declare(strict_types=1);

/** @var array $topicPoll from topic_poll_for_topic */

/** @var ?array $current */

/** @var bool $topicLocked */

$votesPerUser = (int) ($topicPoll['votes_per_user'] ?? 1);

$myOptionIds = $topicPoll['my_option_ids'] ?? [];

$myOptionCounts = $topicPoll['my_option_counts'] ?? [];

if ($myOptionIds === [] && !empty($topicPoll['my_option_id'])) {

    $myOptionIds = [(int) $topicPoll['my_option_id']];

    $myOptionCounts = [(int) $topicPoll['my_option_id'] => 1];

}

$myVoteCount = (int) ($topicPoll['my_vote_count'] ?? count($myOptionIds));

$hasVoted = $myVoteCount > 0;

$total = (int) $topicPoll['total_votes'];

$participantCount = (int) ($topicPoll['participant_count'] ?? 0);

$multiVote = $votesPerUser > 1;

$canVote = !empty($current) && !$hasVoted && empty($topicLocked);

$canCancelVote = $hasVoted && !empty($current) && empty($topicLocked) && (int) ($current['banned'] ?? 0) === 0;

$pollCancelUrl = url('/topic/' . (int) $topicPoll['topic_id'] . '/poll/vote/cancel');

$allowAdd = !empty($topicPoll['allow_user_options'])

    && !empty($current)

    && empty($topicLocked)

    && (int) ($current['banned'] ?? 0) === 0;

$optCount = count($topicPoll['options']);

$pollActorId = !empty($current) ? (int) $current['id'] : 0;

$pollOwnerId = (int) ($topicPoll['topic_owner_id'] ?? 0);

$canTryDelete = $pollActorId > 0 && empty($topicLocked) && (int) ($current['banned'] ?? 0) === 0;

$canEditPollSettings = $pollActorId > 0

    && $pollActorId === $pollOwnerId

    && empty($topicLocked)

    && topic_poll_votes_per_user_column_ok();

$pollDeleteUrl = url('/topic/' . (int) $topicPoll['topic_id'] . '/poll/option/delete');

$pollVoteFormId = 'topic-poll-vote-' . (int) $topicPoll['topic_id'];

$pollVoteUrl = url('/topic/' . (int) $topicPoll['topic_id'] . '/poll/vote');

$pollSettingsUrl = url('/topic/' . (int) $topicPoll['topic_id'] . '/poll/settings');

$votesPerUserCap = topic_poll_votes_per_user_cap();

?>

<section class="topic-poll" aria-label="主题投票">

  <h2 class="topic-poll__title">投票</h2>

  <p class="topic-poll__meta muted">

    <?= $participantCount ?> 人参与 · 共 <?= $total ?> 票 · 每人最多 <?= (int) $votesPerUser ?> 票

    <?php if ($hasVoted) : ?> · 您已投 <?= (int) $myVoteCount ?> 票<?php endif; ?>

    <?php if (!empty($topicPoll['allow_user_options'])) : ?> · 楼主已开放补充选项<?php endif; ?>

  </p>

  <?php if ($canEditPollSettings) : ?>

    <form class="topic-poll__settings" method="post" action="<?= h($pollSettingsUrl) ?>" style="margin:0 0 0.75rem;display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center;">

      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

      <label class="muted" style="font-size:0.88rem;margin:0;">每人可投票数</label>

      <input type="number" name="poll_votes_per_user" class="input" min="1" max="<?= (int) $votesPerUserCap ?>" value="<?= (int) $votesPerUser ?>" style="width:4.5rem;" required>

      <button type="submit" class="btn btn-ghost btn-sm">保存设置</button>

    </form>

  <?php endif; ?>

  <?php if ($canCancelVote) : ?>

    <form class="inline-form topic-poll__cancel-form" method="post" action="<?= h($pollCancelUrl) ?>" style="margin:0 0 0.65rem;">

      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

      <button type="submit" class="btn btn-ghost btn-sm">取消投票</button>

    </form>

  <?php endif; ?>

  <?php if ($canVote) : ?>

    <p class="muted" style="margin:0 0 0.5rem;font-size:0.88rem;">

      <?php if ($multiVote) : ?>

        为各选项填写票数，合计 1–<?= (int) $votesPerUser ?> 票（可全部投给同一选项）

      <?php else : ?>

        请选择 1 项

      <?php endif; ?>

    </p>

    <ul class="topic-poll__choices">

      <?php foreach ($topicPoll['options'] as $opt) :

          $canDelOpt = $canTryDelete && topic_poll_user_can_delete_option(

              $pollActorId,

              $pollOwnerId,

              $opt['added_by_user_id'] ?? null

          );

          $oid = (int) $opt['id'];

      ?>

        <li class="topic-poll__choice-row">

          <?php if ($multiVote) : ?>

            <label class="topic-poll__choice topic-poll__choice--count">

              <span class="topic-poll__choice-label"><?= h((string) $opt['label']) ?></span>

              <input type="number" name="poll_vote_count[<?= $oid ?>]" form="<?= h($pollVoteFormId) ?>" class="input topic-poll__count-input" min="0" max="<?= (int) $votesPerUser ?>" value="0" inputmode="numeric" aria-label="<?= h((string) $opt['label']) ?> 票数">

              <span class="muted" style="font-size:0.82rem;">票</span>

            </label>

          <?php else : ?>

            <label class="topic-poll__choice">

              <input type="radio" name="option_id" value="<?= $oid ?>" form="<?= h($pollVoteFormId) ?>" required>

              <span><?= h((string) $opt['label']) ?></span>

            </label>

          <?php endif; ?>

          <?php if ($canDelOpt) : ?>

            <form class="inline-form topic-poll__delete-form" method="post" action="<?= h($pollDeleteUrl) ?>" onsubmit="return confirm('确定删除该投票选项？已有票将一并清除。');">

              <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

              <input type="hidden" name="option_id" value="<?= $oid ?>">

              <button type="submit" class="btn btn-danger btn-sm">删除</button>

            </form>

          <?php endif; ?>

        </li>

      <?php endforeach; ?>

    </ul>

    <form id="<?= h($pollVoteFormId) ?>" class="topic-poll__form" method="post" action="<?= h($pollVoteUrl) ?>">

      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

      <button type="submit" class="btn btn-primary btn-sm" style="margin-top:0.65rem;">提交投票</button>

    </form>

    <?php if ($multiVote) : ?>

    <script>

    (function(){

      var form = document.getElementById(<?= json_encode($pollVoteFormId) ?>);

      if (!form) return;

      var max = <?= (int) $votesPerUser ?>;

      var inputs = document.querySelectorAll('input[name^="poll_vote_count["][form="' + form.id + '"]');

      function totalVotes(){

        var n = 0;

        inputs.forEach(function(inp){ n += Math.max(0, parseInt(inp.value, 10) || 0); });

        return n;

      }

      form.addEventListener('submit', function(e){

        var n = totalVotes();

        if (n < 1) {

          e.preventDefault();

          alert('请至少投 1 票。');

          return;

        }

        if (n > max) {

          e.preventDefault();

          alert('合计不能超过 ' + max + ' 票。');

        }

      });

      inputs.forEach(function(inp){

        inp.addEventListener('change', function(){

          var self = parseInt(inp.value, 10) || 0;

          if (self < 0) inp.value = '0';

          if (self > max) inp.value = String(max);

          var rest = max - totalVotes();

          if (rest < 0) {

            inp.value = String(Math.max(0, self + rest));

            alert('合计不能超过 ' + max + ' 票。');

          }

        });

      });

    })();

    </script>

    <?php endif; ?>

  <?php else : ?>

    <ul class="topic-poll__results">

      <?php foreach ($topicPoll['options'] as $opt) :

          $cnt = (int) $opt['vote_count'];

          $pct = $total > 0 ? round($cnt * 100 / $total) : 0;

          $oid = (int) $opt['id'];

          $myCnt = (int) ($myOptionCounts[$oid] ?? 0);

          $mine = $myCnt > 0;

          $canDelOpt = $canTryDelete && topic_poll_user_can_delete_option(

              $pollActorId,

              $pollOwnerId,

              $opt['added_by_user_id'] ?? null

          );

      ?>

        <li class="topic-poll__result<?= $mine ? ' topic-poll__result--mine' : '' ?>">

          <div class="topic-poll__result-head">

            <span class="topic-poll__result-label">

              <?= h((string) $opt['label']) ?>

              <?php if ($mine) : ?>

                <span class="topic-poll__mine-tag">我的<?= $myCnt > 1 ? ' ×' . $myCnt : '' ?></span>

              <?php endif; ?>

            </span>

            <span class="topic-poll__result-actions">

              <span class="topic-poll__result-stat"><?= $cnt ?> 票 · <?= $pct ?>%</span>

              <?php if ($canDelOpt) : ?>

                <form class="inline-form topic-poll__delete-form" method="post" action="<?= h($pollDeleteUrl) ?>" onsubmit="return confirm('确定删除该投票选项？已有票将一并清除。');">

                  <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

                  <input type="hidden" name="option_id" value="<?= $oid ?>">

                  <button type="submit" class="btn btn-danger btn-sm">删除</button>

                </form>

              <?php endif; ?>

            </span>

          </div>

          <div class="topic-poll__bar-track" role="presentation">

            <div class="topic-poll__bar-fill" style="width:<?= $pct ?>%;"></div>

          </div>

        </li>

      <?php endforeach; ?>

    </ul>

    <?php if (empty($current)) : ?>

      <p class="muted topic-poll__login-hint"><a href="<?= h(url('/login')) ?>">登录</a> 后可参与投票<?= !empty($topicPoll['allow_user_options']) ? '或补充选项' : '' ?>。</p>

    <?php endif; ?>

  <?php endif; ?>



  <?php if ($allowAdd) : ?>

    <form class="topic-poll__add-option" method="post" action="<?= h(url('/topic/' . (int) $topicPoll['topic_id'] . '/poll/option')) ?>" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border-subtle, rgba(255,255,255,0.08));">

      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

      <p class="muted" style="margin:0 0 0.5rem;font-size:0.88rem;">补充一个选项（当前共 <?= (int) $optCount ?> 项，数量不限）</p>

      <div style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center;">

        <input type="text" name="poll_option_label" maxlength="80" required placeholder="新选项内容" class="input" style="flex:1;min-width:10rem;">

        <button type="submit" class="btn btn-ghost btn-sm">添加选项</button>

      </div>

    </form>

  <?php endif; ?>

</section>

