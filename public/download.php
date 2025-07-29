<?php
require_once __DIR__ . '/../includes/init.php';

// If it is true, then we shall mark the download token as used.
$should_increment_token_usage = false;

// Allow logged-in user to download his/her own files using file id.
if (isset($_GET['file_id'])) {
    $user = get_logged_in_user();
    if (!$user) {
        flash_message('error', 'Please log in to download your file.');
        go_back();
        exit();
    }
    $file_id = $_GET['file_id'];
    $db = MyDB::getInstance();
    $file = $db->execute("SELECT * FROM files WHERE id = :id AND created_by = :uid", [
        'id' => $file_id,
        'uid' => $user['id'],
    ])->fetch();
    if (!$file) {
        flash_message('error', 'File not found.');
        go_back();
        exit();
    }
} elseif (isset($_GET['token'])) {
    $token = $_GET['token'];
    $db = MyDB::getInstance();
    $file = $db->execute("SELECT f.*, t.created_at AS token_created_at, t.last_used_at FROM download_tokens t JOIN files f ON t.file_id = f.id WHERE t.token = :token", [
        'token' => $token,
    ])->fetch();

    $err_msg = null;
    $err_code = null;
    if (!$file) {
        $err_msg = 'Invalid download token.';
        $err_code = 404;
    } elseif (isset($file['last_used_at'])) {
        $err_code = 410;
        $err_msg = 'Download link has exceeded its usage limit.';
    } elseif (is_link_expired($file['token_created_at'], DOWNLOAD_LINK_EXPIRY)) {
        $err_msg = 'Download link has expired.';
        $err_code = 410;
    }
    if ($err_code) {
        http_response_code($err_code);
        echo $err_msg;
        exit();
    }
    $should_increment_token_usage = true;
}

if (isset($file)) {
    $stored_path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $file['stored_path'];
    $err_msg = null;
    $err_code = null;
    if (!file_exists($stored_path) || !is_file($stored_path)) {
        $err_msg = 'Sorry, the file does not exist.';
        $err_code = 404;
    } elseif (!is_readable($stored_path)) {
        $err_msg = 'Sorry, the file is not readable.';
        $err_code = 403;
    }
    if ($err_code) {
        if (isset($user)) {
            flash_message('error', $err_msg);
            go_back();
        } else {
            http_response_code($err_code);
            echo $err_msg;
        }
        exit();
    }

    $mime_type = mime_content_type($stored_path);
    $file_name = $file['file_name'];

    header('Content-Type: ' . $mime_type);
    header('Content-Length: ' . filesize($stored_path));
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    echo file_get_contents($stored_path);
} else {
    http_response_code(404);
}

if (isset($token) && $should_increment_token_usage) {
    $db = MyDB::getInstance();
    $db->execute("UPDATE download_tokens SET last_used_at = NOW() WHERE token = :token", [
        'token' => $token,
    ]);
}
