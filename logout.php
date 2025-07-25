<?php
require_once __DIR__ . '/includes/init.php';

unset_logged_in_user();
session_destroy();

// Redirecting to home, it may or may not redirect to login page based on other criteria.
header('Location: /');
exit();
