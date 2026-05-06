<?php
// fjs/login.php
require '../fjs-be/config/db.php';
require '../fjs-be/auth/auth.php';

if (isLoggedIn()) {
    header("Location: " . (currentRole() === 'admin' ? '../fjs-admin/dashboard.php' : 'dashboard.php'));
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && (int) ($user['is_active'] ?? 1) !== 1) {
        $error = "This account has been blocked. Please contact the administrator.";
    } elseif ($user && password_verify($password, $user['password'])) {
        loginUser($user);

        // Redirect admin to admin panel
        if ($user['role'] === 'admin') {
            header("Location: ../fjs-admin/dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — JobSearch</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav>
    <a href="index.php" class="brand">&#128269; JobSearch</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="register.php">Register</a>
    </div>
</nav>

<div class="container">
    <div class="form-box">
        <h2>Welcome Back</h2>
        <p class="sub">Login to your JobSearch account</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfInput() ?>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input class="form-control" type="email" id="email" name="email"
                       placeholder="you@example.com" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input class="form-control password-input" type="password" id="password" name="password"
                           placeholder="Your password" required>
                    <button class="password-peek" type="button" data-password-peek="#password" aria-label="Show password">
                        <span aria-hidden="true">&#128065;</span>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>

        <p style="text-align:center; margin-top:20px; font-size:14px;">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>
</div>

<script src="main.js"></script>
</body>
</html>
