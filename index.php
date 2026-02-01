<?php
session_start();

// If logged in via session, redirect to profile
if (isset($_SESSION['user'])) {
    header('Location: profile.php');
    exit;
}

// --- Remember Me cookie check ---
if (isset($_COOKIE['remember_token'])) {
    $users = json_decode(file_get_contents('users.json'), true);

    // Search all users for a matching remember token
    foreach ($users as $username => $data) {
        if (isset($data['remember_token']) && $data['remember_token'] === $_COOKIE['remember_token']) {
            // Token matched — restore the session
            $_SESSION['user'] = $username;
            header('Location: profile.php');
            exit;
        }
    }

    // Token was invalid or expired — clear the bad cookie
    setcookie('remember_token', '', time() - 1, '/');
}

// Not logged in by any method — go to login
header('Location: login.php');
exit;
