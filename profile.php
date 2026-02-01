<?php
session_start();

// ─── Auth guard ──────────────────────────────────────────────────────────────
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['user'];
$users    = json_decode(file_get_contents('users.json'), true);

// Safety: if the logged-in user somehow no longer exists in JSON
if (!isset($users[$username])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$user  = $users[$username];
$error = '';

// ─── Retrieve & clear flash messages ─────────────────────────────────────────
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// ─── Handle profile picture update (POST) ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    if ($_FILES['profile_pic']['error'] === UPLOAD_ERR_OK && $_FILES['profile_pic']['size'] > 0) {
        $allowed   = ['jpg', 'jpeg', 'png', 'gif'];
        $extension = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed)) {
            $error = 'Only JPG, PNG, and GIF images are allowed.';
        } elseif ($_FILES['profile_pic']['size'] > 2097152) {
            $error = 'File is too large. Maximum size is 2 MB.';
        } else {
            // Delete old profile picture if one exists
            if (!empty($user['profile_pic']) && file_exists('uploads/' . $user['profile_pic'])) {
                unlink('uploads/' . $user['profile_pic']);
            }

            $newFilename = $username . '_' . time() . '.' . $extension;
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], 'uploads/' . $newFilename)) {
                $users[$username]['profile_pic'] = $newFilename;
                file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));

                $_SESSION['flash_success'] = 'Profile picture updated!';
                header('Location: profile.php');
                exit;
            } else {
                $error = 'Failed to upload image. Please try again.';
            }
        }
    }
    // If no file was actually selected, just do nothing (no error needed)
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile – <?= htmlspecialchars($username) ?></title>
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
            text-align: center;
        }
        .avatar {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #667eea;
            margin: 0 auto 18px;
            display: block;
        }
        .avatar-placeholder {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 3rem;
            color: #fff;
        }
        h1 { color: #1a202c; font-size: 1.5rem; margin-bottom: 4px; }
        .email-label { color: #a0aec0; font-size: .85rem; margin-bottom: 18px; }
        .flash-error, .flash-success {
            border-radius: 6px;
            padding: 10px 14px;
            font-size: .88rem;
            margin-bottom: 20px;
            text-align: left;
        }
        .flash-error { background: #fff0f0; color: #c0392b; border: 1px solid #f5c6cb; }
        .flash-success { background: #f0fff4; color: #276749; border: 1px solid #c6f6d5; }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #edf2f7;
            font-size: .9rem;
        }
        .info-row:last-of-type { border-bottom: none; }
        .info-row .label { color: #718096; font-weight: 600; text-transform: uppercase; font-size: .75rem; letter-spacing: .5px; }
        .info-row .value { color: #2d3748; }
        .upload-section { margin-top: 24px; }
        .upload-section label {
            display: block;
            font-size: .82rem;
            font-weight: 600;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 6px;
            text-align: left;
        }
        .upload-section input[type="file"] {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            font-size: .85rem;
            color: #2d3748;
            background: #f7fafc;
            margin-bottom: 10px;
        }
        .upload-section .hint { font-size: .78rem; color: #a0aec0; text-align: left; margin-bottom: 10px; }
        .btn {
            display: inline-block;
            padding: 9px 20px;
            border-radius: 6px;
            font-size: .9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: opacity .2s;
        }
        .btn:hover { opacity: .85; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
        .btn-outline { background: transparent; color: #667eea; border: 2px solid #667eea; }
        .btn-danger { background: #e53e3e; color: #fff; }
        .btn-upload { width: 100%; padding: 10px; }
        .action-row {
            display: flex;
            gap: 10px;
            margin-top: 28px;
            justify-content: center;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="card">
        <!-- Avatar -->
        <?php if (!empty($user['profile_pic'])): ?>
            <img class="avatar" src="uploads/<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile picture">
        <?php else: ?>
            <div class="avatar-placeholder">
                <?= strtoupper(htmlspecialchars($username[0])) ?>
            </div>
        <?php endif; ?>

        <h1><?= htmlspecialchars($username) ?></h1>
        <p class="email-label"><?= htmlspecialchars($user['email'] ?? '') ?></p>

        <!-- Flash messages -->
        <?php if (!empty($flashSuccess)): ?>
            <div class="flash-success"><?= htmlspecialchars($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if (!empty($flashError)): ?>
            <div class="flash-error"><?= htmlspecialchars($flashError) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Info rows -->
        <div class="info-row">
            <span class="label">Username</span>
            <span class="value"><?= htmlspecialchars($username) ?></span>
        </div>
        <div class="info-row">
            <span class="label">Email</span>
            <span class="value"><?= htmlspecialchars($user['email'] ?? '—') ?></span>
        </div>
        <div class="info-row">
            <span class="label">Joined</span>
            <span class="value"><?= htmlspecialchars($user['created_at'] ?? '—') ?></span>
        </div>

        <!-- Upload new profile picture -->
        <div class="upload-section">
            <label for="profile_pic">Update Profile Picture</label>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" id="profile_pic" name="profile_pic" accept=".jpg,.jpeg,.png,.gif">
                <p class="hint">JPG, PNG, or GIF — max 2 MB. Replaces current picture.</p>
                <button type="submit" class="btn btn-primary btn-upload">Upload</button>
            </form>
        </div>

        <!-- Action buttons -->
        <div class="action-row">
            <a href="change-password.php" class="btn btn-outline">Change Password</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</body>
</html>
