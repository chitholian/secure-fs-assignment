<?php
require_once __DIR__ . '/includes/init.php';

if (get_logged_in_user()) {
    $next = $_GET['next'] ?? '/';
    if (preg_match('|^/login\.php|', $next)) {
        $next = '/';
    }
    header("Location: $next");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        flash_message('error', 'Please enter a username and password.');
    }
    $db = MyDB::getInstance();
    // Find user with username
    $q = $db->execute("SELECT * FROM users WHERE BINARY username = ?", [$username]);
    $user = $q->fetch();
    if (!$user) {
        flash_message('error', 'Invalid username.');
    } else {
        if (!password_verify($password, $user['password'])) {
            flash_message('error', 'Invalid password.');
        } elseif ($user['status'] === 'PENDING') {
            flash_message('error', 'Your account is not approved yet.');
        } elseif ($user['status'] !== 'ENABLED') {
            flash_message('error', 'Your account is not enabled.');
        } else {
            unset($user['password']);
            $now = date('Y-m-d H:i:s');
            $user['last_login_at'] = $now;
            $db->execute("UPDATE users SET last_login_at = ? WHERE id = ?", [
                $now,
                $user['id'],
            ]);
            session_regenerate_id(true);
            set_logged_in_user($user);
            flash_message('success', 'Welcome back ' . $user['username'] . '!');
            $next = $_GET['next'] ?? '/';
            header("Location: $next");
            exit();
        }
    }
}

$site_title = 'Login';

include __DIR__ . '/templates/login.template.php';
