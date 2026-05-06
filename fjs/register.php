<?php
require '../fjs-be/config/db.php';
require '../fjs-be/auth/auth.php';

if (isLoggedIn()) {
    header("Location: " . (currentRole() === 'admin' ? '../fjs-admin/dashboard.php' : 'dashboard.php'));
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'seeker';
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $companyWebsite = trim($_POST['company_website'] ?? '');
    $companyDescription = trim($_POST['company_description'] ?? '');

    if (!in_array($role, ['seeker', 'employer'], true)) {
        $role = 'seeker';
    }

    if ($name === '' || $email === '' || $password === '') {
        $error = 'All required fields must be completed.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($companyWebsite !== '' && !filter_var($companyWebsite, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid company website URL.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'This email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare("
                INSERT INTO users (name, email, password, role, phone, city, bio, skills, company_website, company_description)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ");
            $insert->execute([
                $name,
                $email,
                $hash,
                $role,
                $phone ?: null,
                $city ?: null,
                $bio ?: null,
                $skills ?: null,
                $role === 'employer' ? ($companyWebsite ?: null) : null,
                $role === 'employer' ? ($companyDescription ?: null) : null,
            ]);

            $user = [
                'id' => (int) $pdo->lastInsertId(),
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'phone' => $phone,
                'city' => $city,
                'is_active' => 1,
            ];
            loginUser($user);
            header("Location: profile.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - JobSearch</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav>
    <a href="index.php" class="brand">&#128269; JobSearch</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
    </div>
</nav>

<div class="container">
    <div class="form-box" style="max-width:760px;">
        <h2>Create an Account</h2>
        <p class="sub">Build your job profile once, then apply or post jobs faster.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfInput() ?>
            <div class="form-group">
                <label>I am registering as</label>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <label style="display:flex; align-items:center; gap:8px; padding:14px; border:2px solid var(--border); border-radius:16px; cursor:pointer;" id="lbl-seeker">
                        <input type="radio" name="role" value="seeker" <?= ($_POST['role'] ?? 'seeker') === 'seeker' ? 'checked' : '' ?> onchange="styleRoleLabels()">
                        Job Seeker
                    </label>
                    <label style="display:flex; align-items:center; gap:8px; padding:14px; border:2px solid var(--border); border-radius:16px; cursor:pointer;" id="lbl-employer">
                        <input type="radio" name="role" value="employer" <?= ($_POST['role'] ?? '') === 'employer' ? 'checked' : '' ?> onchange="styleRoleLabels()">
                        Employer
                    </label>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input class="form-control" type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input class="form-control" type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input class="form-control" type="text" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="city">City</label>
                    <input class="form-control" type="text" id="city" name="city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="bio">Short Bio</label>
                <textarea class="form-control" id="bio" name="bio" rows="4" placeholder="Tell employers or seekers a bit about yourself."><?= htmlspecialchars($_POST['bio'] ?? '') ?></textarea>
            </div>

            <div class="form-group" id="skills_group">
                <label for="skills">Skills</label>
                <input class="form-control" type="text" id="skills" name="skills" placeholder="PHP, MySQL, UI Design" value="<?= htmlspecialchars($_POST['skills'] ?? '') ?>">
            </div>

            <div id="employer_fields" style="display:none;">
                <div class="form-group">
                    <label for="company_website">Company Website</label>
                    <input class="form-control" type="url" id="company_website" name="company_website" placeholder="https://example.com" value="<?= htmlspecialchars($_POST['company_website'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="company_description">Company Description</label>
                    <textarea class="form-control" id="company_description" name="company_description" rows="4" placeholder="Tell candidates about your company."><?= htmlspecialchars($_POST['company_description'] ?? '') ?></textarea>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input class="form-control password-input" type="password" id="password" name="password" required>
                        <button class="password-peek" type="button" data-password-peek="#password" aria-label="Show password"><span aria-hidden="true">&#128065;</span></button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-field">
                        <input class="form-control password-input" type="password" id="confirm_password" name="confirm_password" required>
                        <button class="password-peek" type="button" data-password-peek="#confirm_password" aria-label="Show password"><span aria-hidden="true">&#128065;</span></button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>

        <p style="text-align:center; margin-top:20px; font-size:14px;">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>
</div>

<script src="main.js"></script>
<script>
function styleRoleLabels() {
    const seeker = document.getElementById('lbl-seeker');
    const employer = document.getElementById('lbl-employer');
    const employerFields = document.getElementById('employer_fields');
    const skillsGroup = document.getElementById('skills_group');
    const val = document.querySelector('input[name="role"]:checked')?.value;
    seeker.style.borderColor = val === 'seeker' ? 'var(--primary)' : 'var(--border)';
    employer.style.borderColor = val === 'employer' ? 'var(--primary)' : 'var(--border)';
    employerFields.style.display = val === 'employer' ? 'block' : 'none';
    skillsGroup.style.display = val === 'employer' ? 'none' : 'block';
}
styleRoleLabels();
</script>
</body>
</html>
