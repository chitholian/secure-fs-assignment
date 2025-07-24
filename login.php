<?php
require_once __DIR__ . '/includes/init.php';

if (get_logged_in_user()) {
    $next = $_GET['next'] ?? '/';
    header("Location: $next");
    exit();
}

$site_title = 'Login';
ob_start();

$body_content = ob_get_clean();
include __DIR__ . '/templates/login.template.php';
