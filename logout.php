<?php
session_start();

// ─── Remove the remember_token from users.json (if present) ─────────────────
if (isset($_SESSION['user'])) {
    $username = $_SESSION['user'];
    $users    = json_decode(file_get_contents('users.json'), true);

    if (isset($users[$username]['remember_token'])) {
        unset($users[$username]['remember_token']);
        file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));
    }
}

// ─── Destroy session ─────────────────────────────────────────────────────────
$_SESSION = [];
session_destroy();

// ─── Clear the remember_me cookie ────────────────────────────────────────────
setcookie('remember_token', '', time() - 1, '/');

// ─── Flash message and redirect ──────────────────────────────────────────────
// Start a fresh session just long enough to set the flash message
session_start();
$_SESSION['flash_success'] = 'You have been logged out.';
session_write_close();

header('Location: login.php');
exit;
