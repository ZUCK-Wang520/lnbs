<?php

declare(strict_types=1);

/**
 * 首次安装（在项目根目录）：php public/database/seed.php
 * 默认管理员 admin@luba.local / admin123 — 登录后请立即修改密码。
 */

$root = dirname(__DIR__);
$configPath = $root . '/config/config.local.php';
if (!is_file($configPath)) {
    fwrite(STDERR, "请先复制 public/config/config.example.php 为 public/config/config.local.php 并配置数据库。\n");
    exit(1);
}

$config = require $configPath;
$dsn = $config['db']['dsn'];
$user = $config['db']['user'];
$pass = $config['db']['pass'];

$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$adminEmail = 'admin@luba.local';
$adminPass = password_hash('admin123', PASSWORD_DEFAULT);

$st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$st->execute([$adminEmail]);
if ($st->fetch()) {
    echo "管理员已存在，跳过用户种子。\n";
} else {
    $pdo->prepare(
        'INSERT INTO users (email, password_hash, nickname, role, banned) VALUES (?,?,?,?,0)'
    )->execute([$adminEmail, $adminPass, '站长', 'admin']);
    echo "已创建管理员：{$adminEmail} / admin123\n";
}

$boards = [
    ['新生指引', 'guide', '报到、选课与校园生活常见问题', 10],
    ['学习交流', 'study', '课程讨论与资料分享', 20],
    ['活动交友', 'club', '社团与校园活动', 30],
];

$anonEmail = '__anon__@internal';
$st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$st->execute([$anonEmail]);
if (!$st->fetch()) {
    $pdo->prepare(
        'INSERT INTO users (email, password_hash, nickname, role, banned) VALUES (?,?,?,?,0)'
    )->execute([$anonEmail, password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT), '（系统匿名）', 'user']);
    echo "已创建匿名发帖占位账号。\n";
}

foreach ($boards as [$name, $slug, $desc, $order]) {
    $st = $pdo->prepare('SELECT id FROM boards WHERE slug = ?');
    $st->execute([$slug]);
    if ($st->fetch()) {
        continue;
    }
    $pdo->prepare(
        'INSERT INTO boards (name, slug, description, sort_order) VALUES (?,?,?,?)'
    )->execute([$name, $slug, $desc, $order]);
    echo "已创建版块：{$name}\n";
}

echo "完成。\n";
