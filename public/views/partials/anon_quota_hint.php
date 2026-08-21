<?php declare(strict_types=1);
$anonQuota = is_array($anonQuota ?? null) ? $anonQuota : null;
if (!$anonQuota || empty($anonQuota['enabled'])) {
    return;
}
$slot = ($anonQuotaSlot ?? 'topic') === 'reply' ? $anonQuota['reply'] : $anonQuota['topic'];
$label = ($anonQuotaSlot ?? 'topic') === 'reply' ? '匿名回复' : '匿名发帖';
?>
<p class="muted anon-quota-hint" style="margin:0.35rem 0 0;font-size:0.84rem;line-height:1.45;">
  <?= h($label) ?>：今日已用 <strong><?= (int) $slot['daily_used'] ?></strong> / <?= (int) $slot['daily_limit'] ?> 次免费额度
  <?php if ((int) $slot['bonus'] > 0) : ?>
    ，兑换剩余 <strong><?= (int) $slot['bonus'] ?></strong> 次
  <?php endif; ?>
  ，还可使用 <strong><?= (int) $slot['remaining'] ?></strong> 次。
  <a href="<?= h(url('/profile')) ?>#anon-quota">兑换码</a>
</p>
