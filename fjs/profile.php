<?php
require '../fjs-be/config/db.php';
require '../fjs-be/auth/auth.php';
requireLogin('login.php');

$user = currentUser();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

if (!$profile) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $companyWebsite = trim($_POST['company_website'] ?? '');
    $companyDescription = trim($_POST['company_description'] ?? '');
    $resumeUrl = $profile['resume_url'] ?? null;
    $avatarUrl = $profile['avatar_url'] ?? null;
    $uploadBaseDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
    $avatarDir = $uploadBaseDir . DIRECTORY_SEPARATOR . 'avatars';
    $resumeDir = $uploadBaseDir . DIRECTORY_SEPARATOR . 'resumes';
    $publicBase = 'uploads/';

    if ($name === '' || $email === '') {
        $error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($companyWebsite !== '' && !filter_var($companyWebsite, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid company website URL.';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->execute([$email, $profile['id']]);
        if ($check->fetch()) {
            $error = 'That email is already in use.';
        } else {
            if (!is_dir($avatarDir) && !mkdir($avatarDir, 0777, true) && !is_dir($avatarDir)) {
                $error = 'Could not prepare the avatar upload folder.';
            }
            if (!$error && !is_dir($resumeDir) && !mkdir($resumeDir, 0777, true) && !is_dir($resumeDir)) {
                $error = 'Could not prepare the resume upload folder.';
            }

            if (!$error && isset($_FILES['avatar_file']) && ($_FILES['avatar_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['avatar_file']['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Avatar upload failed.';
                } else {
                    $avatarExt = strtolower(pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION));
                    $allowedAvatarExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    if (!in_array($avatarExt, $allowedAvatarExt, true)) {
                        $error = 'Avatar must be a JPG, PNG, WEBP, or GIF image.';
                    } else {
                        $avatarName = 'avatar_' . $profile['id'] . '_' . time() . '.' . $avatarExt;
                        $avatarTarget = $avatarDir . DIRECTORY_SEPARATOR . $avatarName;
                        if (!move_uploaded_file($_FILES['avatar_file']['tmp_name'], $avatarTarget)) {
                            $error = 'Could not save the uploaded avatar.';
                        } else {
                            if (!empty($avatarUrl) && str_starts_with($avatarUrl, $publicBase)) {
                                $oldAvatar = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $avatarUrl);
                                if (is_file($oldAvatar)) {
                                    @unlink($oldAvatar);
                                }
                            }
                            $avatarUrl = $publicBase . 'avatars/' . $avatarName;
                        }
                    }
                }
            }

            if (!$error && $profile['role'] === 'seeker' && isset($_FILES['resume_file']) && ($_FILES['resume_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['resume_file']['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Resume upload failed.';
                } else {
                    $resumeExt = strtolower(pathinfo($_FILES['resume_file']['name'], PATHINFO_EXTENSION));
                    $allowedResumeExt = ['pdf', 'doc', 'docx'];
                    if (!in_array($resumeExt, $allowedResumeExt, true)) {
                        $error = 'Resume must be a PDF, DOC, or DOCX file.';
                    } else {
                        $resumeName = 'resume_' . $profile['id'] . '_' . time() . '.' . $resumeExt;
                        $resumeTarget = $resumeDir . DIRECTORY_SEPARATOR . $resumeName;
                        if (!move_uploaded_file($_FILES['resume_file']['tmp_name'], $resumeTarget)) {
                            $error = 'Could not save the uploaded resume.';
                        } else {
                            if (!empty($resumeUrl) && str_starts_with($resumeUrl, $publicBase)) {
                                $oldResume = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $resumeUrl);
                                if (is_file($oldResume)) {
                                    @unlink($oldResume);
                                }
                            }
                            $resumeUrl = $publicBase . 'resumes/' . $resumeName;
                        }
                    }
                }
            }

            if (!$error && isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1') {
                if (!empty($avatarUrl) && str_starts_with($avatarUrl, $publicBase)) {
                    $oldAvatar = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $avatarUrl);
                    if (is_file($oldAvatar)) {
                        @unlink($oldAvatar);
                    }
                }
                $avatarUrl = null;
            }

            if (!$error && $profile['role'] === 'seeker' && isset($_POST['remove_resume']) && $_POST['remove_resume'] === '1') {
                if (!empty($resumeUrl) && str_starts_with($resumeUrl, $publicBase)) {
                    $oldResume = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $resumeUrl);
                    if (is_file($oldResume)) {
                        @unlink($oldResume);
                    }
                }
                $resumeUrl = null;
            }

            if (!$error) {
            $update = $pdo->prepare("
                UPDATE users
                SET name = ?, email = ?, phone = ?, city = ?, bio = ?, skills = ?, resume_url = ?, avatar_url = ?, company_website = ?, company_description = ?
                WHERE id = ?
            ");
            $update->execute([
                $name,
                $email,
                $phone ?: null,
                $city ?: null,
                $bio ?: null,
                $skills ?: null,
                $resumeUrl ?: null,
                $avatarUrl ?: null,
                $profile['role'] === 'employer' ? ($companyWebsite ?: null) : null,
                $profile['role'] === 'employer' ? ($companyDescription ?: null) : null,
                $profile['id'],
            ]);

            $stmt->execute([$profile['id']]);
            $profile = $stmt->fetch();
            loginUser($profile);
            $success = 'Profile updated successfully.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - JobSearch</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav>
    <a href="index.php" class="brand">&#128269; JobSearch</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="jobs.php">Browse Jobs</a>
        <a href="dashboard.php">Dashboard</a>
        <span class="nav-user">| <?= htmlspecialchars($profile['name']) ?></span>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <div>
            <h2>Your Profile</h2>
            <p style="color:var(--text-soft);"><?= ucfirst($profile['role']) ?> account settings and public details.</p>
        </div>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="card">
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($profile['role'] === 'seeker' && empty($profile['resume_url'])): ?>
            <div class="alert alert-info">
                Upload a resume to strengthen your applications. Employers can now open it directly from the applicant review screen.
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <?= csrfInput() ?>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input class="form-control" type="text" id="name" name="name" value="<?= htmlspecialchars($profile['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input class="form-control" type="email" id="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input class="form-control" type="text" id="phone" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="city">City</label>
                    <input class="form-control" type="text" id="city" name="city" value="<?= htmlspecialchars($profile['city'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="avatar_file">Profile Image</label>
                <input class="form-control" type="file" id="avatar_file" name="avatar_file" accept=".jpg,.jpeg,.png,.webp,.gif">
                <?php if (!empty($profile['avatar_url'])): ?>
                    <div style="margin-top:12px; display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                        <img src="<?= htmlspecialchars($profile['avatar_url']) ?>" alt="Profile avatar" style="width:72px; height:72px; border-radius:18px; object-fit:cover; border:1px solid var(--border);">
                        <label style="display:flex; align-items:center; gap:8px; color:var(--text-soft);">
                            <input type="checkbox" name="remove_avatar" value="1">
                            Remove current image
                        </label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="bio"><?= $profile['role'] === 'employer' ? 'About Company' : 'Bio' ?></label>
                <textarea class="form-control" id="bio" name="bio" rows="4"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
            </div>

            <?php if ($profile['role'] === 'seeker'): ?>
                <div class="form-group">
                    <label for="skills">Skills</label>
                    <input class="form-control" type="text" id="skills" name="skills" value="<?= htmlspecialchars($profile['skills'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="resume_file">Resume File</label>
                    <input class="form-control" type="file" id="resume_file" name="resume_file" accept=".pdf,.doc,.docx">
                    <p style="margin-top:8px; color:var(--text-soft); font-size:13px;">
                        Accepted formats: PDF, DOC, DOCX. Employers reviewing your applications can open this file directly.
                    </p>
                    <?php if (!empty($profile['resume_url'])): ?>
                        <div style="margin-top:12px; display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                            <a href="<?= htmlspecialchars($profile['resume_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm">View Current Resume</a>
                            <label style="display:flex; align-items:center; gap:8px; color:var(--text-soft);">
                                <input type="checkbox" name="remove_resume" value="1">
                                Remove current resume
                            </label>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($profile['role'] === 'employer'): ?>
                <div class="form-group">
                    <label for="company_website">Company Website</label>
                    <input class="form-control" type="url" id="company_website" name="company_website" value="<?= htmlspecialchars($profile['company_website'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="company_description">Company Description</label>
                    <textarea class="form-control" id="company_description" name="company_description" rows="4"><?= htmlspecialchars($profile['company_description'] ?? '') ?></textarea>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">Save Profile</button>
        </form>
    </div>
 </div>

<script src="main.js"></script>
</body>
</html>
