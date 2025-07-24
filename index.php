<?php
require_once __DIR__ . '/includes/init.php';

ensure_user_login();
$site_title = 'Dashboard';
ob_start();
?>
    OK I am Here
<?php

$body_content = ob_get_clean();
include __DIR__ . '/templates/dashboard.template.php';
