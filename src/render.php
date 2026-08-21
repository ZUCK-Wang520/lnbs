<?php

declare(strict_types=1);

function render_page(string $title, string $viewFile, array $vars = [], bool $isAdmin = false): void
{
    $pageTitle = $title;
    $__view = $viewFile;
    $isAdminSection = $isAdmin;
    extract($vars, EXTR_SKIP);
    require VIEWS . '/layout.php';
}
