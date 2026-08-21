<?php

declare(strict_types=1);

/**
 * PHP 内置服务器路由：php -S localhost:8000 -t public public/router.php
 */
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $file = __DIR__ . $path;
    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

require __DIR__ . '/index.php';
