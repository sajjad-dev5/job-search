<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentRole(): string {
    return $_SESSION['role'] ?? '';
}

function currentUser(): array {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['name'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'phone' => $_SESSION['phone'] ?? '',
        'city' => $_SESSION['city'] ?? '',
        'is_active' => $_SESSION['is_active'] ?? 1,
    ];
}

function loginUser(array $user): void {
    $_SESSION['user_id'] = (int) ($user['id'] ?? 0);
    $_SESSION['name'] = $user['name'] ?? '';
    $_SESSION['role'] = $user['role'] ?? '';
    $_SESSION['email'] = $user['email'] ?? '';
    $_SESSION['phone'] = $user['phone'] ?? '';
    $_SESSION['city'] = $user['city'] ?? '';
    $_SESSION['is_active'] = (int) ($user['is_active'] ?? 1);
}

function ensureCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfToken(): string {
    return ensureCsrfToken();
}

function csrfInput(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function verifyCsrf(): void {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(419);
        die("<div style='font-family:Arial;padding:40px;text-align:center;'><h2 style='color:#e74c3c;'>Security Check Failed</h2><p>Please go back and try again.</p></div>");
    }
}

function requireLogin(string $redirect = '../fjs/login.php'): void {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit();
    }
    if ((int) ($_SESSION['is_active'] ?? 1) !== 1) {
        session_unset();
        session_destroy();
        header("Location: $redirect");
        exit();
    }
}

function requireRole(string $role, string $redirect = '../fjs/login.php'): void {
    requireLogin($redirect);
    if (currentRole() !== $role) {
        die("<div style='font-family:Arial;padding:40px;text-align:center;'><h2 style='color:#e74c3c;'>&#9888; Access Denied</h2><p>You must be a <strong>$role</strong> to access this page.</p><a href='../fjs/index.php' style='color:#1abc9c;'>Go to Home</a></div>");
    }
}

function requireAdmin(): void {
    requireLogin('../fjs-admin/index.php');
    if (currentRole() !== 'admin') {
        header("Location: ../fjs-admin/index.php");
        exit();
    }
}
?>
