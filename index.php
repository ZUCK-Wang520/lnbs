<?php

declare(strict_types=1);

/**
 * 当网站「根目录」指向项目根（含 public、src 的上一级）而非 public 时，
 * 由本文件进入应用。推荐仍在宝塔中将「运行目录」设为 public，可不必依赖本文件。
 */
require __DIR__ . '/public/index.php';
