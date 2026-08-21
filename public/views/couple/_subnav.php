<?php declare(strict_types=1); ?>
<nav class="breadcrumb couple-lg-subnav">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <a href="<?= h(url('/couple')) ?>">情侣空间</a>
  <span> / </span>
  <span><?= h((string) ($coupleSubnavTitle ?? '')) ?></span>
</nav>
