<?php
// fjs/edit_job.php
require '../fjs-be/config/db.php';
require '../fjs-be/auth/auth.php';
requireRole('employer', 'login.php');

$jobId = intval($_GET['id'] ?? 0);
$stmt  = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ?");
$stmt->execute([$jobId, currentUser()['id']]);
$job   = $stmt->fetch();
if (!$job) die("<p style='padding:40px;font-family:Arial;'>Job not found. <a href='dashboard.php'>Go back</a></p>");

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $title       = trim($_POST['title']       ?? '');
    $company     = trim($_POST['company']     ?? '');
    $location    = trim($_POST['location']    ?? '');
    $type        =      $_POST['type']        ?? 'Full-Time';
    $description = trim($_POST['description'] ?? '');
    $requirements= trim($_POST['requirements'] ?? '');
    $benefits    = trim($_POST['benefits'] ?? '');
    $status      = $_POST['status'] ?? 'open';
    $salary      = trim($_POST['salary']      ?? '');
    $allowedStatus = ['open', 'closed', 'archived'];
    if (!in_array($status, $allowedStatus, true)) {
        $status = 'open';
    }

    if (empty($title) || empty($company) || empty($location) || empty($description)) {
        $error = "Please fill in all required fields.";
    } else {
        $pdo->prepare("UPDATE jobs SET title=?,company=?,location=?,type=?,status=?,description=?,requirements=?,benefits=?,salary=? WHERE id=? AND employer_id=?")
            ->execute([$title,$company,$location,$type,$status,$description,$requirements ?: null,$benefits ?: null,$salary ?: null,$jobId,currentUser()['id']]);
        $success = "Job updated! <a href='dashboard.php'>Back to dashboard</a>";
        // Refresh
        $stmt->execute([$jobId, currentUser()['id']]);
        $job = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job — JobSearch</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav>
    <a href="index.php" class="brand">&#128269; JobSearch</a>
    <div class="nav-links">
        <?php if (currentRole() === 'admin'): ?>
            <a href="../fjs-admin/dashboard.php">Admin Panel</a>
        <?php else: ?>
            <a href="dashboard.php">Dashboard</a>
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    </div>
</nav>
<div class="container" style="max-width:660px;">
    <div class="card">
        <h2 style="margin-bottom:6px;">Edit Job</h2>
        <p style="color:var(--text-muted); font-size:14px; margin-bottom:24px;">
            Editing: <strong><?= htmlspecialchars($job['title']) ?></strong>
        </p>
        <?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <form method="POST">
            <?= csrfInput() ?>
            <div class="form-group">
                <label>Job Title *</label>
                <input class="form-control" type="text" name="title" required value="<?= htmlspecialchars($job['title']) ?>">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label>Company *</label>
                    <input class="form-control" type="text" name="company" required value="<?= htmlspecialchars($job['company']) ?>">
                </div>
                <div class="form-group">
                    <label>Location *</label>
                    <input class="form-control" type="text" name="location" required value="<?= htmlspecialchars($job['location']) ?>">
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label>Job Type</label>
                    <select class="form-control" name="type">
                        <?php foreach (['Full-Time','Part-Time','Remote','Internship'] as $t): ?>
                            <option value="<?= $t ?>" <?= $job['type']===$t?'selected':'' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Salary</label>
                    <input class="form-control" type="text" name="salary" value="<?= htmlspecialchars($job['salary']) ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select class="form-control" name="status">
                        <?php foreach (['open','closed','archived'] as $jobStatus): ?>
                            <option value="<?= $jobStatus ?>" <?= ($job['status'] ?? 'open') === $jobStatus ? 'selected' : '' ?>><?= ucfirst($jobStatus) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Description *</label>
                <textarea class="form-control" name="description" rows="6" required><?= htmlspecialchars($job['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Requirements</label>
                <textarea class="form-control" name="requirements" rows="4"><?= htmlspecialchars($job['requirements'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Benefits</label>
                <textarea class="form-control" name="benefits" rows="4"><?= htmlspecialchars($job['benefits'] ?? '') ?></textarea>
            </div>
            <div style="display:flex; gap:12px;">
                <button type="submit" class="btn btn-warning" style="flex:1;">Update Job</button>
                <a href="dashboard.php" class="btn btn-secondary" style="flex:1; text-align:center; line-height:2.2;">Cancel</a>
            </div>
        </form>
    </div>
</div>
<script src="main.js"></script>
</body>
</html>
