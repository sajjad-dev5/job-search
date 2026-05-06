<?php
require '../fjs-be/config/db.php';
require '../fjs-be/auth/auth.php';
requireAdmin();

$success = '';
$allowedStatuses = ['pending', 'reviewed', 'shortlisted', 'accepted', 'rejected'];
$filterStatus = trim($_GET['status'] ?? $_POST['view_status'] ?? '');
if (!in_array($filterStatus, $allowedStatuses, true)) {
    $filterStatus = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $appId = intval($_POST['app_id'] ?? 0);
    $action = $_POST['action'] ?? 'set_status';

    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM applications WHERE id = ?")->execute([$appId]);
        $success = 'Application deleted.';
    } else {
        $status = in_array($_POST['status'] ?? '', $allowedStatuses, true) ? $_POST['status'] : 'pending';
        $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([$status, $appId]);
        $success = 'Application status updated.';
    }
}

$sql = "
    SELECT a.*, j.title AS job_title, j.company,
           u.name AS seeker_name, u.email AS seeker_email, u.phone AS seeker_phone,
           u.city AS seeker_city, u.skills AS seeker_skills, u.resume_url
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.seeker_id = u.id
";
$params = [];
if ($filterStatus !== '') {
    $sql .= " WHERE a.status = ?";
    $params[] = $filterStatus;
}
$sql .= " ORDER BY a.applied_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();
$counts = $pdo->query("SELECT status, COUNT(*) AS n FROM applications GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications - Admin</title>
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
        <h1>Manage Applications</h1>
        <div class="tb-right">
            <a href="../fjs/index.php" style="color:var(--info);">&#127968; Website</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="content">
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
            <?php
            $tabs = [
                '' => ['All', 'badge-gray', array_sum($counts ?: [0])],
                'pending' => ['Pending', 'badge-orange', $counts['pending'] ?? 0],
                'reviewed' => ['Reviewed', 'badge-blue', $counts['reviewed'] ?? 0],
                'shortlisted' => ['Shortlisted', 'badge-purple', $counts['shortlisted'] ?? 0],
                'accepted' => ['Accepted', 'badge-green', $counts['accepted'] ?? 0],
                'rejected' => ['Rejected', 'badge-red', $counts['rejected'] ?? 0],
            ];
            foreach ($tabs as $val => [$label, $cls, $count]):
                $active = $filterStatus === $val ? 'background:#10273f; color:#fff;' : 'background:#fff; color:#555;';
            ?>
                <a href="applications.php<?= $val ? '?status=' . $val : '' ?>" style="<?= $active ?> padding:8px 18px; border-radius:20px; border:2px solid #e0e0e0; text-decoration:none; font-size:14px; display:inline-flex; align-items:center; gap:6px;">
                    <?= $label ?><span class="badge <?= $cls ?>" style="font-size:11px;"><?= $count ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>&#128228; Applications (<?= count($applications) ?>)</h3>
                <input class="form-control" type="text" id="tableSearch" placeholder="&#128269; Search..." style="max-width:240px;">
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Job</th>
                            <th>Applicant</th>
                            <th>Cover Letter</th>
                            <th>Applied</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td style="color:var(--text-soft);"><?= $app['id'] ?></td>
                                <td><strong><?= htmlspecialchars($app['job_title']) ?></strong><br><small style="color:var(--text-soft);"><?= htmlspecialchars($app['company']) ?></small></td>
                                <td>
                                    <?= htmlspecialchars($app['seeker_name']) ?><br>
                                    <small style="color:var(--text-soft);"><?= htmlspecialchars($app['seeker_email']) ?></small><br>
                                    <?php if (!empty($app['seeker_phone'])): ?><small style="color:var(--text-soft);"><?= htmlspecialchars($app['seeker_phone']) ?></small><br><?php endif; ?>
                                    <?php if (!empty($app['seeker_city'])): ?><small style="color:var(--text-soft);"><?= htmlspecialchars($app['seeker_city']) ?></small><br><?php endif; ?>
                                    <?php if (!empty($app['seeker_skills'])): ?><small style="color:var(--text-soft);">Skills: <?= htmlspecialchars($app['seeker_skills']) ?></small><br><?php endif; ?>
                                    <?php if (!empty($app['resume_url'])): ?>
                                        <a href="<?= htmlspecialchars('../fjs/' . ltrim($app['resume_url'], '/')) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm" style="margin-top:8px;">Resume</a>
                                    <?php else: ?>
                                        <small style="color:var(--text-soft);">No resume uploaded</small>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width:280px; color:var(--text-soft);"><?= $app['cover_letter'] ? nl2br(htmlspecialchars($app['cover_letter'])) : 'No cover letter' ?></td>
                                <td style="color:var(--text-soft);"><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                                <td>
                                    <span class="badge <?= $app['status'] === 'accepted' ? 'badge-green' : ($app['status'] === 'rejected' ? 'badge-red' : ($app['status'] === 'shortlisted' ? 'badge-purple' : ($app['status'] === 'reviewed' ? 'badge-blue' : 'badge-orange'))) ?>">
                                        <?= ucfirst($app['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <?php foreach (['reviewed' => 'Review', 'shortlisted' => 'Shortlist', 'accepted' => 'Accept', 'rejected' => 'Reject'] as $status => $label): ?>
                                            <form method="POST" style="display:inline;">
                                                <?= csrfInput() ?>
                                                <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                                <input type="hidden" name="view_status" value="<?= htmlspecialchars($filterStatus) ?>">
                                                <input type="hidden" name="status" value="<?= $status ?>">
                                                <button type="submit" class="btn <?= $status === 'rejected' ? 'btn-danger' : ($status === 'accepted' ? 'btn-primary' : 'btn-secondary') ?> btn-sm"><?= $label ?></button>
                                            </form>
                                        <?php endforeach; ?>
                                        <form method="POST" style="display:flex; gap:8px; align-items:center;">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                            <input type="hidden" name="view_status" value="<?= htmlspecialchars($filterStatus) ?>">
                                            <select name="status" class="form-control" style="width:120px; padding:8px 10px;">
                                                <?php foreach ($allowedStatuses as $status): ?>
                                                    <option value="<?= $status ?>" <?= $app['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                            <input type="hidden" name="view_status" value="<?= htmlspecialchars($filterStatus) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this application permanently?">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($applications)): ?>
                            <tr><td colspan="7" style="text-align:center; color:var(--text-soft); padding:30px;">No applications found.</td></tr>
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
