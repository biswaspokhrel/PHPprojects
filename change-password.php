<?php
session_start();

// ─── Auth guard ──────────────────────────────────────────────────────────────
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['user'];
$error    = '';

// ─── Retrieve & clear flash messages ─────────────────────────────────────────
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// ─── Handle POST submission ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password']   ?? '';
    $newPassword     = $_POST['new_password']       ?? '';
    $confirmPassword = $_POST['confirm_new_password'] ?? '';

    $users = json_decode(file_get_contents('users.json'), true);

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'All fields are required.';
    } elseif (!password_verify($currentPassword, $users[$username]['password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } else {
        // All checks passed — update password
        $users[$username]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));

        $_SESSION['flash_success'] = 'Password changed successfully!';
        header('Location: profile.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password – User Profile System</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #eef2f7;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 16px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            width: 100%;
            max-width: 420px;
            padding: 40px 36px 32px;
        }
        h1 {
            text-align: center;
            color: #1a202c;
            margin-bottom: 8px;
            font-size: 1.6rem;
        }
        .subtitle {
            text-align: center;
            color: #718096;
            font-size: .9rem;
            margin-bottom: 28px;
        }
        .flash-error, .flash-success {
            border-radius: 6px;
            padding: 10px 14px;
            font-size: .88rem;
            margin-bottom: 20px;
        }
        .flash-error { background: #fff0f0; color: #c0392b; border: 1px solid #f5c6cb; }
        .flash-success { background: #f0fff4; color: #276749; border: 1px solid #c6f6d5; }
        label {
            display: block;
            font-size: .82rem;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            font-size: .95rem;
            color: #2d3748;
            background: #f7fafc;
            transition: border-color .2s;
            margin-bottom: 16px;
        }
        input:focus { outline: none; border-color: #667eea; background: #fff; }
        .hint { font-size: .78rem; color: #a0aec0; margin-top: -12px; margin-bottom: 16px; }
        .divider {
            border: none;
            border-top: 1px solid #edf2f7;
            margin: 22px 0;
        }
        button {
            width: 100%;
            padding: 11px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .2s;
        }
        button:hover { opacity: .88; }
        .footer-link {
            text-align: center;
            margin-top: 22px;
            font-size: .88rem;
            color: #718096;
        }
        .footer-link a { color: #667eea; text-decoration: none; font-weight: 600; }
        .footer-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Change Password</h1>
        <p class="subtitle">Enter your current password to continue</p>

        <?php if (!empty($flashSuccess)): ?>
            <div class="flash-success"><?= htmlspecialchars($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if (!empty($flashError)): ?>
            <div class="flash-error"><?= htmlspecialchars($flashError) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required>

            <hr class="divider">

            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" required>
            <p class="hint">Must be at least 6 characters</p>

            <label for="confirm_new_password">Confirm New Password</label>
            <input type="password" id="confirm_new_password" name="confirm_new_password" required>

            <button type="submit">Update Password</button>
        </form>

        <p class="footer-link">← <a href="profile.php">Back to profile</a></p>
    </div>
</body>
</html>
