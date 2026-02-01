<?php
session_start();

// If already logged in, go straight to profile
if (isset($_SESSION['user'])) {
    header('Location: profile.php');
    exit;
}

$error = '';
$success = '';

// ─── Handle POST submission ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Load existing users
    $users = json_decode(file_get_contents('users.json'), true);
    if (!is_array($users)) {
        $users = [];
    }

    // ── Validation ────────────────────────────────────────────────────────
    if (empty($username)) {
        $error = 'Username is required.';
    } elseif (str_contains($username, ' ')) {
        $error = 'Username cannot contain spaces.';
    } elseif (isset($users[$username])) {
        $error = 'That username is already taken.';
    } elseif (empty($email)) {
        $error = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check email uniqueness
        foreach ($users as $existingUser) {
            if (isset($existingUser['email']) && strtolower($existingUser['email']) === strtolower($email)) {
                $error = 'That email is already registered.';
                break;
            }
        }
    }

    if (empty($error)) {
        if (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        }
    }

    // ── Profile picture upload ────────────────────────────────────────────
    $profilePicFilename = '';

    if (empty($error) && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK && $_FILES['profile_pic']['size'] > 0) {
        $allowed    = ['jpg', 'jpeg', 'png', 'gif'];
        $extension  = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed)) {
            $error = 'Only JPG, PNG, and GIF images are allowed.';
        } elseif ($_FILES['profile_pic']['size'] > 2097152) {
            $error = 'File is too large. Maximum size is 2 MB.';
        } else {
            $profilePicFilename = $username . '_' . time() . '.' . $extension;
            if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], 'uploads/' . $profilePicFilename)) {
                $error = 'Failed to upload profile picture. Please try again.';
                $profilePicFilename = '';
            }
        }
    }

    // ── Save user ─────────────────────────────────────────────────────────
    if (empty($error)) {
        $users[$username] = [
            'password'    => password_hash($password, PASSWORD_DEFAULT),
            'email'       => $email,
            'profile_pic' => $profilePicFilename,
            'created_at'  => date('Y-m-d')
        ];

        file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));

        // Flash a success message and redirect to login
        $_SESSION['flash_success'] = 'Registration successful! You can now log in.';
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – User Profile System</title>
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
            max-width: 440px;
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
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="file"] {
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
        input[type="file"] { padding: 8px 10px; font-size: .85rem; }
        .hint { font-size: .78rem; color: #a0aec0; margin-top: -12px; margin-bottom: 16px; }
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
            margin-top: 4px;
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
        <h1>Create Account</h1>
        <p class="subtitle">Fill in the details below to register</p>

        <?php if (!empty($error)): ?>
            <div class="flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <p class="hint">Must be at least 6 characters</p>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <label for="profile_pic">Profile Picture <span style="font-weight:400;text-transform:none;color:#a0aec0;">(optional)</span></label>
            <input type="file" id="profile_pic" name="profile_pic" accept=".jpg,.jpeg,.png,.gif">
            <p class="hint">JPG, PNG, or GIF — max 2 MB</p>

            <button type="submit">Register</button>
        </form>

        <p class="footer-link">Already have an account? <a href="login.php">Log in</a></p>
    </div>
</body>
</html>
