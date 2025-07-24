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
