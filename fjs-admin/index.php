<?php
// fjs-admin/index.php — Admin Login
require '../fjs-be/config/db.php';
require '../fjs-be/auth/auth.php';

if (isLoggedIn() && currentRole() === 'admin') {
    header("Location: dashboard.php"); exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && (int) ($admin['is_active'] ?? 1) !== 1) {
        $error = "This admin account is blocked.";
    } elseif ($admin && password_verify($password, $admin['password'])) {
        loginUser($admin);
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid credentials or not an admin account.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — JobSearch</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body style="margin:0;">
<div class="login-wrap">
    <div class="login-box">
        <h1>&#9881; Admin Panel</h1>
        <p>Sign in with your administrator account</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfInput() ?>
            <div class="form-group">
                <label>Email Address</label>
                <input class="form-control" type="email" name="email"
                       placeholder="admin@jobsearch.com" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="password-field">
                    <input class="form-control password-input" type="password" id="admin_password" name="password"
                           placeholder="Admin password" required>
                    <button class="password-peek" type="button" data-password-peek="#admin_password" aria-label="Show password">
                        <span aria-hidden="true">&#128065;</span>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login to Admin Panel</button>
        </form>

        <p style="text-align:center; margin-top:20px; font-size:13px; color:#aaa;">
            <a href="../fjs/index.php" style="color:#1abc9c;">&#8592; Back to main website</a>
        </p>
    </div>
</div>
<script src="admin.js"></script>
</body>
</html>
