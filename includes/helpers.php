<?php

/**
 * @return mixed|null If a user exists in the session, returns it.
 */
function get_logged_in_user(): mixed
{
    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    return null;
}


/**
 * Should be called when a user logs in.
 *
 * @param array $user User that logged in just now.
 * @return void
 */
function set_logged_in_user(array $user): void
{
    $_SESSION['user'] = $user;
}


/**
 * Should be called when a user logs out.
 *
 * @return void
 */
function unset_logged_in_user(): void
{
    unset($_SESSION['user']);
}

/**
 * Check if a user is logged in, do nothing if logged in, otherwise redirect to login page.
 *
 * @return void
 */
function ensure_user_login(): void
{
    if (!get_logged_in_user()) {
        header("Location: login.php?next=" . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

/**
 * Put a message data to the session to be shown on next page load.
 *
 * @param string $type Type of the flash message, e.g. error, success etc.
 * @param string $message Content of the flash message.
 * @return void
 */
function flash_message(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

/**
 * @return mixed|null Return flash-data currently set in session and clear it.
 */
function get_flash_messages(): mixed
{
    if (isset($_SESSION['flash'])) {
        $data = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $data;
    }
    return null;
}

/**
 * Redirect to referer URL or home-page in case of no-referer.
 *
 * @return void
 */
function go_back(): void
{
    $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header("Location: $referer");
}

/**
 * Return an extension i.e. pdf, jpeg, png etc. from mime type.
 * This is useful for very simple file types.
 *
 * @param string $type Mime type of the file, e.g. <b>application/pdf</b>.
 * @return string Returns the file extension guessed from the mime, e.g. <b>pdf</b>.
 */
function get_mime_to_extension(string $type): string
{
    return explode('/', $type)[1] ?? 'bin';
}

/**
 * A simple function to generate random UUID v4 value
 * @return string Returns generated UUID string.
 */
function generate_uuid(): string
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * Generate a random token string
 *
 * @param int $length Expected length of the token.
 * @return string Returns the generated token string.
 */
function generate_token(int $length): string
{
    if ($length < 1) {
        $length = 120;
    }
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $token = '';
    $max = strlen($characters) - 1;
    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[mt_rand(0, $max)];
    }
    return $token;
}

/**
 * Convert number of bytes to human-readable format file size, e.g. 1024 to 1 KB.
 *
 * @param int $bytes Number of bytes.
 * @param int $decimals Decimal places in case of fractional size.
 * @return string Human-readable file size.
 */
function human_readable_size(int $bytes, int $decimals = 2): string
{
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    if ($bytes <= 0) return '0 B';
    $factor = floor(log($bytes, 1024));
    return sprintf("%.{$decimals}f %s", $bytes / pow(1024, $factor), $sizes[$factor]);
}

/**
 * Check if a download token has expired.
 *
 * @param string $created_at Time when the token was generated.
 * @param int $expires_in Number of seconds from the time of creation after when the link should be expired.
 * <br/> 0 means never expires.
 *
 * @return bool Returns true if the link is expired, false otherwise.
 */
function is_link_expired(string $created_at, int $expires_in): bool
{
    if (!$expires_in) {
        return false;
    }
    if (strtotime($created_at) + $expires_in >= time()) {
        return false;
    }
    return true;
}

/**
 * Returns the time when the token will be expired.
 *
 * @param string $created_at Time when the token was generated.
 * @param int $expires_in Number of seconds from the time of creation after when the link should be expired.
 * <br/> 0 means never expires.
 *
 * @return null|string Returns null if token would never expire, expiry time in <b>Y-m-d H:i:s</b> format otherwise.
 */
function get_expiry_time(string $created_at, int $expires_in): ?string
{
    if (!$expires_in) {
        return null;
    }
    return date('Y-m-d H:i:s', $expires_in + strtotime($created_at));
}
