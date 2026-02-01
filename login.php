<?php
session_start();

// If already logged in, go straight to profile
if (isset($_SESSION['user'])) {
    header('Location: profile.php');
    exit;
}

$error = '';

// ─── Retrieve & clear any flash messages ────────────────────────────────────
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// ─── Handle POST submission ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);

    $users = json_decode(file_get_contents('users.json'), true);
    if (!is_array($users)) {
        $users = [];
    }

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } elseif (!isset($users[$username])) {
        $error = 'Invalid username or password.';
    } elseif (!password_verify($password, $users[$username]['password'])) {
        $error = 'Invalid username or password.';
    } else {
        // ── Credentials valid ───────────────────────────────────────────────
        $_SESSION['user'] = $username;

        // ── Remember Me ─────────────────────────────────────────────────────
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));   // 64-char cryptographic token
            $users[$username]['remember_token'] = $token;
            file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));

            // Cookie lasts 30 days; HttpOnly hides it from JS
            setcookie('remember_token', $token, [
                'expires'  => time() + (30 * 24 * 60 * 60),
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        $_SESSION['flash_success'] = 'Welcome back, ' . htmlspecialchars($username) . '!';
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
    <title>Login – User Profile System</title>
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
            max-width: 400px;
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
        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 22px;
        }
        .remember-row input { margin: 0; width: auto; }
        .remember-row label {
            text-transform: none;
            font-size: .88rem;
            font-weight: 400;
            color: #718096;
            letter-spacing: 0;
            margin-bottom: 0;
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
        <h1>Welcome Back</h1>
        <p class="subtitle">Log in to your account</p>

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
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <div class="remember-row">
                <input type="checkbox" id="remember_me" name="remember_me">
                <label for="remember_me">Remember me (30 days)</label>
            </div>

            <button type="submit">Log In</button>
        </form>

        <p class="footer-link">Don't have an account? <a href="register.php">Register</a></p>
    </div>
</body>
</html>
