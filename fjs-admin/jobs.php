<?php
require '../fjs-be/config/db.php';
require '../fjs-be/auth/auth.php';
requireAdmin();

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $jobId = intval($_POST['job_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM jobs WHERE id = ?")->execute([$jobId]);
        $success = 'Job deleted successfully.';
    } elseif ($action === 'toggle_featured') {
        $pdo->prepare("UPDATE jobs SET featured = CASE WHEN featured = 1 THEN 0 ELSE 1 END WHERE id = ?")->execute([$jobId]);
        $success = 'Featured flag updated.';
    } elseif ($action === 'set_status') {
        $status = $_POST['status'] ?? 'open';
        if (!in_array($status, ['open', 'closed', 'archived'], true)) {
            $status = 'open';
        }
        $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ?")->execute([$status, $jobId]);
        $success = 'Job status updated.';
    }
}

$jobs = $pdo->query("
    SELECT j.*, u.name AS employer_name,
        (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS app_count
    FROM jobs j
    JOIN users u ON j.employer_id = u.id
    ORDER BY j.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jobs - Admin</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<aside class="sidebar">
    <a href="dashboard.php" class="sidebar-brand">&#9881; JobSearch<small>Admin Panel</small></a>
    <nav>
        <a href="dashboard.php"><span class="icon">&#128202;</span> Dashboard</a>
        <a href="users.php"><span class="icon">&#128100;</span> Users</a>
        <a href="jobs.php"><span class="icon">&#128188;</span> Jobs</a>
        <a href="applications.php"><span class="icon">&#128228;</span> Applications</a>
    </nav>
    <div class="sidebar-footer">Logged in as <strong><?= htmlspecialchars(currentUser()['name']) ?></strong></div>
</aside>

<div class="main">
    <div class="topbar">
        <h1>Manage Jobs</h1>
        <div class="tb-right">
            <a href="../fjs/index.php" style="color:var(--info);">&#127968; Website</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="content">
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>&#128188; All Jobs (<?= count($jobs) ?>)</h3>
                <input class="form-control" type="text" id="tableSearch" placeholder="&#128269; Search jobs..." style="max-width:260px;">
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Company</th>
                            <th>Posted By</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Applicants</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td style="color:var(--text-soft);"><?= $job['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($job['title']) ?></strong><br>
                                    <small style="color:var(--text-soft);"><?= htmlspecialchars($job['location']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($job['company']) ?></td>
                                <td style="color:var(--text-soft);"><?= htmlspecialchars($job['employer_name']) ?></td>
                                <td><span class="badge"><?= htmlspecialchars($job['type']) ?></span></td>
                                <td>
                                    <span class="badge <?= $job['status'] === 'open' ? 'badge-green' : ($job['status'] === 'closed' ? 'badge-orange' : 'badge-gray') ?>">
                                        <?= ucfirst($job['status']) ?>
                                    </span>
                                </td>
                                <td><?= (int) ($job['featured'] ?? 0) === 1 ? 'Yes' : 'No' ?></td>
                                <td><?= $job['app_count'] ?></td>
                                <td>
                                    <div class="actions">
                                        <form method="POST" style="display:flex; gap:8px; align-items:center;">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                            <input type="hidden" name="action" value="set_status">
                                            <select name="status" class="form-control" style="width:120px; padding:8px 10px;">
                                                <?php foreach (['open', 'closed', 'archived'] as $status): ?>
                                                    <option value="<?= $status ?>" <?= $job['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                            <input type="hidden" name="action" value="toggle_featured">
                                            <button type="submit" class="btn btn-secondary btn-sm"><?= (int) ($job['featured'] ?? 0) === 1 ? 'Unfeature' : 'Feature' ?></button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete the job '<?= htmlspecialchars($job['title']) ?>'? All applications for it will also be removed.">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($jobs)): ?>
                            <tr><td colspan="9" style="text-align:center; color:var(--text-soft); padding:30px;">No jobs found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="admin.js"></script>
</body>
</html>
