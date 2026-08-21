<?php

declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_verify(): void
{
    $sent = $_POST['_csrf'] ?? '';
    $ok = is_string($sent) && isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $sent);
    if (!$ok) {
        http_response_code(419);
        exit('会话已失效，请返回刷新页面后重试。');
    }
}
