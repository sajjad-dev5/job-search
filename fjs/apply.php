<?php
// fjs/apply.php
require '../fjs-be/config/db.php';
require '../fjs-be/auth/auth.php';
requireRole('seeker', 'login.php');

$jobId = intval($_GET['job_id'] ?? 0);
$stmt  = $pdo->prepare("SELECT j.*, u.name AS employer_name FROM jobs j JOIN users u ON j.employer_id = u.id WHERE j.id = ?");
$stmt->execute([$jobId]);
$job   = $stmt->fetch();
if (!$job) die("<p style='padding:40px;font-family:Arial;'>Job not found. <a href='jobs.php'>Browse jobs</a></p>");
if (($job['status'] ?? 'open') !== 'open') {
    die("<p style='padding:40px;font-family:Arial;'>This job is not accepting applications right now. <a href='job.php?id=" . $jobId . "'>View job</a></p>");
}

$userId = currentUser()['id'];
$seekerStmt = $pdo->prepare("
    SELECT name, email, phone, city, skills, bio, resume_url
    FROM users
    WHERE id = ?
");
$seekerStmt->execute([$userId]);
$seeker = $seekerStmt->fetch();

if (!$seeker) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

$profileChecks = [
    'Phone number' => !empty($seeker['phone']),
    'City' => !empty($seeker['city']),
    'Skills' => !empty($seeker['skills']),
    'Bio' => !empty($seeker['bio']),
    'Resume' => !empty($seeker['resume_url']),
];
$profileCompletion = (int) round((count(array_filter($profileChecks)) / count($profileChecks)) * 100);
$missingProfileItems = array_keys(array_filter($profileChecks, fn ($filled) => !$filled));
$hasResume = !empty($seeker['resume_url']);

// Check if already applied
$alreadyApplied = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND seeker_id = ?");
$alreadyApplied->execute([$jobId, $userId]);
$alreadyApplied = (bool)$alreadyApplied->fetch();

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyApplied) {
    verifyCsrf();
    $coverLetter = trim($_POST['cover_letter'] ?? '');
    $pdo->prepare("INSERT INTO applications (job_id, seeker_id, cover_letter) VALUES (?,?,?)")
        ->execute([$jobId, $userId, $coverLetter]);
    $success = "Application submitted successfully! <a href='dashboard.php'>View your applications</a>.";
    $alreadyApplied = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply — <?= htmlspecialchars($job['title']) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav>
    <a href="index.php" class="brand">&#128269; JobSearch</a>
    <div class="nav-links">
        <a href="jobs.php">Browse Jobs</a>
        <?php if (currentRole() === 'admin'): ?>
            <a href="../fjs-admin/dashboard.php">Admin Panel</a>
        <?php else: ?>
            <a href="dashboard.php">Dashboard</a>
        <?php endif; ?>
        <span class="nav-user">| <?= htmlspecialchars(currentUser()['name']) ?></span>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="container" style="max-width:720px;">

    <!-- JOB SUMMARY -->
    <div class="card">
        <span class="badge"><?= $job['type'] ?></span>
        <h2 style="margin:10px 0 8px; font-size:22px;"><?= htmlspecialchars($job['title']) ?></h2>
        <p style="color:#555;">
            &#127970; <strong><?= htmlspecialchars($job['company']) ?></strong>
            &nbsp;&bull;&nbsp; &#128205; <?= htmlspecialchars($job['location']) ?>
            <?php if ($job['salary']): ?>
                &nbsp;&bull;&nbsp; &#128176; <?= htmlspecialchars($job['salary']) ?>
            <?php endif; ?>
        </p>
        <hr style="margin:16px 0; border:none; border-top:1px solid #eee;">
        <p style="line-height:1.8; color:#555;"><?= nl2br(htmlspecialchars($job['description'])) ?></p>
    </div>

    <div class="card">
        <div class="page-header" style="margin-bottom:16px;">
            <div>
                <h3 style="margin-bottom:6px; color:var(--accent);">Your Application Package</h3>
                <p style="color:var(--text-soft); margin:0;">This profile information can be reviewed alongside your cover letter.</p>
            </div>
            <a href="profile.php" class="btn btn-secondary btn-sm">Edit Profile</a>
        </div>

        <?php if ($hasResume): ?>
            <div class="alert alert-success">Your current resume is ready to share with the employer.</div>
        <?php else: ?>
            <div class="alert alert-info">
                You can still apply now, but adding a resume will make your application stronger.
                <a href="profile.php">Upload one from your profile</a>.
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px;">
            <div style="padding:16px; border:1px solid var(--border); border-radius:18px; background:rgba(255,255,255,0.6);">
                <strong style="display:block; color:var(--accent); margin-bottom:6px;"><?= htmlspecialchars($seeker['name']) ?></strong>
                <div style="color:var(--text-soft); font-size:14px;"><?= htmlspecialchars($seeker['email']) ?></div>
            </div>
            <div style="padding:16px; border:1px solid var(--border); border-radius:18px; background:rgba(255,255,255,0.6);">
                <strong style="display:block; color:var(--accent); margin-bottom:6px;">Contact</strong>
                <div style="color:var(--text-soft); font-size:14px;"><?= htmlspecialchars($seeker['phone'] ?: 'No phone added yet') ?></div>
                <div style="color:var(--text-soft); font-size:14px;"><?= htmlspecialchars($seeker['city'] ?: 'No city added yet') ?></div>
            </div>
            <div style="padding:16px; border:1px solid var(--border); border-radius:18px; background:rgba(255,255,255,0.6);">
                <strong style="display:block; color:var(--accent); margin-bottom:6px;">Resume</strong>
                <div style="color:var(--text-soft); font-size:14px; margin-bottom:10px;"><?= $hasResume ? 'Resume uploaded and available' : 'No resume uploaded yet' ?></div>
                <?php if ($hasResume): ?>
                    <a href="<?= htmlspecialchars($seeker['resume_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm">View Current Resume</a>
                <?php else: ?>
                    <a href="profile.php" class="btn btn-secondary btn-sm">Upload Resume</a>
                <?php endif; ?>
            </div>
        </div>

        <p style="margin-top:14px; color:var(--text-soft); font-size:14px;">
            Profile completion: <strong><?= $profileCompletion ?>%</strong>
            <?php if (!empty($missingProfileItems)): ?>
                &nbsp;&middot;&nbsp; Still worth adding: <?= htmlspecialchars(implode(', ', $missingProfileItems)) ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- APPLICATION FORM -->
    <div class="card">
        <h2 style="margin-bottom:6px;">Apply for This Job</h2>
        <p style="color:var(--text-muted); font-size:14px; margin-bottom:20px;">
            Applying as: <strong><?= htmlspecialchars($seeker['name']) ?></strong>
        </p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php elseif ($alreadyApplied): ?>
            <div class="alert alert-info">
                &#9989; You have already applied for this job.
                <a href="dashboard.php">View your applications</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <?= csrfInput() ?>
                <div class="form-group">
                    <label>Cover Letter <small style="color:#aaa;">(optional)</small></label>
                    <textarea class="form-control" name="cover_letter" rows="7"
                              placeholder="Tell the employer why you are a great fit for this role..."><?= htmlspecialchars($_POST['cover_letter'] ?? '') ?></textarea>
                </div>
                <div style="display:flex; gap:12px;">
                    <button type="submit" class="btn btn-primary" style="flex:1;">Submit Application</button>
                    <a href="jobs.php" class="btn btn-secondary" style="flex:1; text-align:center; line-height:2.2;">Back to Jobs</a>
                </div>
            </form>
        <?php endif; ?>
    </div>

</div>

<script src="main.js"></script>
</body>
</html>
