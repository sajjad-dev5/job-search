<?php
require '../fjs-be/config/db.php';
require '../fjs-be/auth/auth.php';

$jobId = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT j.*, u.name AS employer_name, u.company_website, u.company_description, u.city AS employer_city
    FROM jobs j
    JOIN users u ON j.employer_id = u.id
    WHERE j.id = ?
");
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job || $job['status'] === 'archived') {
    die("<p style='padding:40px;font-family:Arial;'>Job not found. <a href='jobs.php'>Browse jobs</a></p>");
}

$alreadyApplied = false;
if (isLoggedIn() && currentRole() === 'seeker') {
    $appliedStmt = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND seeker_id = ?");
    $appliedStmt->execute([$jobId, currentUser()['id']]);
    $alreadyApplied = (bool) $appliedStmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($job['title']) ?> - JobSearch</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav>
    <a href="index.php" class="brand">&#128269; JobSearch</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="jobs.php">Browse Jobs</a>
        <?php if (isLoggedIn()): ?>
            <a href="<?= currentRole() === 'admin' ? '../fjs-admin/dashboard.php' : 'dashboard.php' ?>"><?= currentRole() === 'admin' ? 'Admin Panel' : 'Dashboard' ?></a>
            <span class="nav-user">| <?= htmlspecialchars(currentUser()['name']) ?></span>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <div>
            <span class="badge"><?= htmlspecialchars($job['type']) ?></span>
            <h2 style="margin-top:10px;"><?= htmlspecialchars($job['title']) ?></h2>
            <p style="color:var(--text-soft);"><?= htmlspecialchars($job['company']) ?> · <?= htmlspecialchars($job['location']) ?><?php if ($job['salary']): ?> · <?= htmlspecialchars($job['salary']) ?><?php endif; ?></p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <?php if ($job['status'] !== 'open'): ?>
                <span class="badge badge-gray"><?= ucfirst($job['status']) ?></span>
            <?php elseif (isLoggedIn() && currentRole() === 'seeker'): ?>
                <?php if ($alreadyApplied): ?>
                    <span class="btn btn-secondary">Already Applied</span>
                <?php else: ?>
                    <a href="apply.php?job_id=<?= $job['id'] ?>" class="btn btn-primary">Apply Now</a>
                <?php endif; ?>
            <?php elseif (!isLoggedIn()): ?>
                <a href="login.php" class="btn btn-primary">Login to Apply</a>
            <?php endif; ?>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:minmax(0, 2fr) minmax(280px, 1fr); gap:22px;">
        <div class="card">
            <h3 style="margin-bottom:12px; color:var(--accent);">About This Role</h3>
            <p style="white-space:pre-line; color:var(--text-soft);"><?= htmlspecialchars($job['description']) ?></p>

            <?php if (!empty($job['requirements'])): ?>
                <h3 style="margin:24px 0 12px; color:var(--accent);">Requirements</h3>
                <p style="white-space:pre-line; color:var(--text-soft);"><?= htmlspecialchars($job['requirements']) ?></p>
            <?php endif; ?>

            <?php if (!empty($job['benefits'])): ?>
                <h3 style="margin:24px 0 12px; color:var(--accent);">Benefits</h3>
                <p style="white-space:pre-line; color:var(--text-soft);"><?= htmlspecialchars($job['benefits']) ?></p>
            <?php endif; ?>
        </div>

        <div style="display:flex; flex-direction:column; gap:22px;">
            <div class="card">
                <h3 style="margin-bottom:12px; color:var(--accent);">Job Snapshot</h3>
                <p><strong>Status:</strong> <?= ucfirst(htmlspecialchars($job['status'])) ?></p>
                <p><strong>Type:</strong> <?= htmlspecialchars($job['type']) ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($job['location']) ?></p>
                <p><strong>Posted:</strong> <?= date('M d, Y', strtotime($job['created_at'])) ?></p>
                <?php if ($job['salary']): ?><p><strong>Salary:</strong> <?= htmlspecialchars($job['salary']) ?></p><?php endif; ?>
            </div>

            <div class="card">
                <h3 style="margin-bottom:12px; color:var(--accent);">Employer</h3>
                <p><strong><?= htmlspecialchars($job['employer_name']) ?></strong></p>
                <?php if (!empty($job['employer_city'])): ?><p style="color:var(--text-soft);"><?= htmlspecialchars($job['employer_city']) ?></p><?php endif; ?>
                <?php if (!empty($job['company_description'])): ?><p style="margin-top:10px; color:var(--text-soft);"><?= nl2br(htmlspecialchars($job['company_description'])) ?></p><?php endif; ?>
                <?php if (!empty($job['company_website'])): ?><p style="margin-top:10px;"><a href="<?= htmlspecialchars($job['company_website']) ?>" target="_blank" rel="noopener noreferrer">Visit Company Website</a></p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="main.js"></script>
</body>
</html>
