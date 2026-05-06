<?php
// fjs-be/setup.php
// First time setup — creates admin account
// Open: http://localhost/job-search/fjs-be/setup.php
// This page auto-locks after admin is created!

// Reuse the same DB connection config as the rest of the app so there is only
// one place to update credentials.
require __DIR__ . '/config/db.php';

// ── Check if admin already exists — lock if so ────────────
$adminExists = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$adminExists) {
    $name     = trim($_POST['name']             ?? '');
    $email    = trim($_POST['email']            ?? '');
    $password =      $_POST['password']         ?? '';
    $confirm  =      $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)")
            ->execute([$name, $email, $hash, 'admin']);
        $adminUserId = (int) $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO admins (user_id) VALUES (?)")
            ->execute([$adminUserId]);
        $adminExists = true;
        $success = true;
        // Save credentials for display
        $_SESSION_name  = $name;
        $_SESSION_email = $email;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobSearch — First Time Setup</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #1a252f 0%, #2c3e50 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: #fff;
            border-radius: 14px;
            padding: 44px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }

        .logo {
            text-align: center;
            margin-bottom: 28px;
        }
        .logo .icon { font-size: 48px; }
        .logo h1   { font-size: 26px; color: #2c3e50; margin-top: 10px; }
        .logo p    { color: #888; font-size: 14px; margin-top: 6px; }

        .step-badge {
            display: inline-block;
            background: #e8f8f5;
            color: #1abc9c;
            border: 1px solid #1abc9c;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 6px;
        }
        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: #1abc9c;
            box-shadow: 0 0 0 3px rgba(26,188,156,0.15);
            outline: none;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: #1abc9c;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 8px;
        }
        .btn:hover { background: #16a085; }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 18px;
        }
        .alert-error   { background: #fde8e8; color: #c0392b; border: 1px solid #e74c3c; }

        /* ── LOCKED STATE ── */
        .locked {
            text-align: center;
            padding: 10px 0;
        }
        .locked .lock-icon { font-size: 60px; margin-bottom: 16px; }
        .locked h2 { color: #e74c3c; margin-bottom: 10px; }
        .locked p  { color: #888; font-size: 14px; margin-bottom: 8px; line-height: 1.7; }

        /* ── SUCCESS STATE ── */
        .success-box { text-align: center; }
        .success-box .check { font-size: 64px; margin-bottom: 16px; }
        .success-box h2 { color: #1abc9c; margin-bottom: 8px; }
        .success-box p  { color: #888; font-size: 14px; margin-bottom: 6px; }
        .creds {
            background: #f4f6f8;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 20px 0;
            text-align: left;
        }
        .creds p { color: #333; font-size: 14px; margin: 6px 0; }
        .creds strong { color: #2c3e50; }
        .go-btn {
            display: block;
            padding: 14px;
            background: #2c3e50;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            transition: background 0.2s;
            margin-top: 8px;
        }
        .go-btn:hover { background: #1a252f; }
        .warning {
            background: #fef9e7;
            border: 1px solid #f39c12;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 12px;
            color: #d68910;
            margin-top: 14px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="card">

    <?php if ($success): ?>
    <!-- ══════════ SUCCESS ══════════ -->
    <div class="success-box">
        <div class="check">✅</div>
        <h2>Admin Account Created!</h2>
        <p>Your admin account is ready. Here are your login details:</p>
        <div class="creds">
            <p>👤 <strong>Name:</strong> <?= htmlspecialchars($_SESSION_name) ?></p>
            <p>📧 <strong>Email:</strong> <?= htmlspecialchars($_SESSION_email) ?></p>
            <p>🔑 <strong>Password:</strong> (the one you just set)</p>
        </div>
        <a href="../fjs-admin/index.php" class="go-btn">Go to Admin Panel →</a>
        <div class="warning">
            ⚠️ Delete or rename <code>setup.php</code> after login for security!
        </div>
    </div>

    <?php elseif ($adminExists): ?>
    <!-- ══════════ LOCKED ══════════ -->
    <div class="locked">
        <div class="lock-icon">🔒</div>
        <h2>Setup is Locked</h2>
        <p>An admin account already exists.<br>This setup page is now disabled.</p>
        <p style="color:#e74c3c; font-size:13px;">
            If you forgot your password, delete the admin row from the<br>
            <code>users</code> table in phpMyAdmin and reload this page.
        </p>
        <br>
        <a href="../fjs-admin/index.php" style="color:#1abc9c; font-size:15px; font-weight:600;">
            → Go to Admin Login
        </a>
    </div>

    <?php else: ?>
    <!-- ══════════ SETUP FORM ══════════ -->
    <div class="logo">
        <div class="icon">⚙️</div>
        <h1>First Time Setup</h1>
        <p>Create your administrator account to get started</p>
    </div>

    <div class="step-badge">🚀 One-time setup</div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Admin Name</label>
            <input class="form-control" type="text" name="name"
                   placeholder="e.g. Sajjad"
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Admin Email</label>
            <input class="form-control" type="email" name="email"
                   placeholder="e.g. admin@jobsearch.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Password <small style="color:#aaa;">(min 6 characters)</small></label>
            <input class="form-control" type="password" name="password"
                   placeholder="Create a strong password" required>
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input class="form-control" type="password" name="confirm_password"
                   placeholder="Repeat your password" required>
        </div>
        <button type="submit" class="btn">Create Admin Account →</button>
    </form>

    <?php endif; ?>

</div>
</body>
</html>
