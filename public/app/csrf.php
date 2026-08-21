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
    if (!csrf_check_post()) {
        http_response_code(419);
        exit('会话已失效，请返回刷新页面后重试。');
    }
}

/** 供 JSON 接口等自行返回错误，不直接 exit */
function csrf_check_post(): bool
{
    $sent = $_POST['_csrf'] ?? '';

    return is_string($sent) && isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $sent);
}
