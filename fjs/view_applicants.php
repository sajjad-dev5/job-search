<?php
require '../fjs-be/config/db.php';
require '../fjs-be/auth/auth.php';
requireRole('employer', 'login.php');

$jobId = intval($_GET['job_id'] ?? $_POST['job_id'] ?? 0);
$userId = currentUser()['id'];
$allowedStatuses = ['pending', 'reviewed', 'shortlisted', 'accepted', 'rejected'];
$filterStatus = trim($_GET['status'] ?? $_POST['view_status'] ?? '');
if (!in_array($filterStatus, $allowedStatuses, true)) {
    $filterStatus = '';
}

$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ?");
$stmt->execute([$jobId, $userId]);
$job = $stmt->fetch();

if (!$job) {
    die("<p style='padding:40px;font-family:Arial;'>Job not found. <a href='dashboard.php'>Go back</a></p>");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'], $_POST['status'])) {
    verifyCsrf();
    $status = in_array($_POST['status'], $allowedStatuses, true) ? $_POST['status'] : 'pending';

    $update = $pdo->prepare("
        UPDATE applications a
        JOIN jobs j ON a.job_id = j.id
        SET a.status = ?
        WHERE a.id = ? AND j.employer_id = ?
    ");
    $update->execute([$status, intval($_POST['app_id']), $userId]);

    if ($update->rowCount() > 0) {
        $success = "Application status updated to <strong>" . ucfirst($status) . "</strong>.";
    } else {
        $error = "Unable to update that application.";
    }
}

$statusCountsStmt = $pdo->prepare("
    SELECT status, COUNT(*) AS total
    FROM applications a
    WHERE a.job_id = ?
    GROUP BY a.status
");
$statusCountsStmt->execute([$jobId]);
$statusCounts = $statusCountsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$totalApplications = array_sum($statusCounts ?: [0]);

$applicationsSql = "
    SELECT a.*, u.name AS seeker_name, u.email AS seeker_email, u.phone AS seeker_phone,
           u.city AS seeker_city, u.skills AS seeker_skills, u.bio AS seeker_bio,
           u.resume_url, u.avatar_url
    FROM applications a
    JOIN users u ON a.seeker_id = u.id
    WHERE a.job_id = ?
";
$applicationParams = [$jobId];

if ($filterStatus !== '') {
    $applicationsSql .= " AND a.status = ?";
    $applicationParams[] = $filterStatus;
}

$applicationsSql .= " ORDER BY a.applied_at DESC";

$appsStmt = $pdo->prepare($applicationsSql);
$appsStmt->execute($applicationParams);
$applications = $appsStmt->fetchAll();

$statusMap = [
    'pending' => 'badge-warning',
    'reviewed' => 'badge-blue',
    'shortlisted' => 'badge-gray',
    'accepted' => 'badge-success',
    'rejected' => 'badge-danger',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicants - <?= htmlspecialchars($job['title']) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav>
    <a href="index.php" class="brand">&#128269; JobSearch</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <span class="nav-user">| <?= htmlspecialchars(currentUser()['name']) ?></span>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <div>
            <h2>Applicants for <?= htmlspecialchars($job['title']) ?></h2>
            <p style="color:var(--text-muted); font-size:14px; margin-top:4px;">
                <?= htmlspecialchars($job['company']) ?> - <?= htmlspecialchars($job['location']) ?>
            </p>
        </div>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

    <div class="type-filters">
        <a href="view_applicants.php?job_id=<?= $jobId ?>" class="type-filter <?= $filterStatus === '' ? 'active' : '' ?>">
            All <span><?= $totalApplications ?></span>
        </a>
        <?php foreach ($allowedStatuses as $status): ?>
            <a href="view_applicants.php?job_id=<?= $jobId ?>&status=<?= urlencode($status) ?>" class="type-filter <?= $filterStatus === $status ? 'active' : '' ?>">
                <?= ucfirst($status) ?> <span><?= $statusCounts[$status] ?? 0 ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="page-header" style="margin-bottom:16px;">
            <div>
                <h3 style="margin-bottom:6px;">Applications (<?= count($applications) ?>)</h3>
                <p style="color:var(--text-soft);">
                    Review resumes, skills, and profile details without leaving this page.
                </p>
            </div>
            <a href="job.php?id=<?= $jobId ?>" class="btn btn-secondary btn-sm">Preview Job</a>
        </div>

        <?php if (empty($applications)): ?>
            <div class="empty-state" style="box-shadow:none; padding:30px;">
                <p><?= $filterStatus ? 'No applicants match this status yet.' : 'No seekers have applied to this job yet.' ?></p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Application Package</th>
                            <th>Status</th>
                            <th>Applied</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app):
                            $badgeClass = $statusMap[$app['status']] ?? 'badge-gray';
                            $bioPreview = trim((string) ($app['seeker_bio'] ?? ''));
                            if ($bioPreview !== '' && strlen($bioPreview) > 140) {
                                $bioPreview = substr($bioPreview, 0, 140) . '...';
                            }
                            $initial = strtoupper(substr($app['seeker_name'], 0, 1));
                        ?>
                        <tr>
                            <td>
                                <div style="display:flex; gap:12px; align-items:flex-start; min-width:240px;">
                                    <?php if (!empty($app['avatar_url'])): ?>
                                        <img src="<?= htmlspecialchars($app['avatar_url']) ?>" alt="<?= htmlspecialchars($app['seeker_name']) ?>" style="width:52px; height:52px; border-radius:16px; object-fit:cover; border:1px solid var(--border);">
                                    <?php else: ?>
                                        <div style="width:52px; height:52px; border-radius:16px; display:flex; align-items:center; justify-content:center; background:#e7edf3; color:var(--accent); font-weight:800;">
                                            <?= htmlspecialchars($initial) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($app['seeker_name']) ?></strong><br>
                                        <small style="color:var(--text-muted);"><?= htmlspecialchars($app['seeker_email']) ?></small>
                                        <?php if (!empty($app['seeker_phone'])): ?><br><small style="color:var(--text-soft);"><?= htmlspecialchars($app['seeker_phone']) ?></small><?php endif; ?>
                                        <?php if (!empty($app['seeker_city'])): ?><br><small style="color:var(--text-soft);"><?= htmlspecialchars($app['seeker_city']) ?></small><?php endif; ?>
                                        <?php if (!empty($app['seeker_skills'])): ?>
                                            <div style="margin-top:8px; color:var(--text-soft); font-size:13px;">
                                                <strong style="color:var(--accent);">Skills:</strong> <?= htmlspecialchars($app['seeker_skills']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($bioPreview !== ''): ?>
                                            <div style="margin-top:8px; color:var(--text-soft); font-size:13px; line-height:1.6;">
                                                <?= htmlspecialchars($bioPreview) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td style="max-width:320px; color:#555;">
                                <div style="display:flex; flex-direction:column; gap:10px;">
                                    <div>
                                        <?php if (!empty($app['resume_url'])): ?>
                                            <a href="<?= htmlspecialchars($app['resume_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm">View Resume</a>
                                        <?php else: ?>
                                            <span class="badge badge-gray">No Resume</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="line-height:1.7;">
                                        <?= $app['cover_letter'] ? nl2br(htmlspecialchars($app['cover_letter'])) : '<span style="color:var(--text-muted);">No cover letter</span>' ?>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge <?= $badgeClass ?>"><?= ucfirst($app['status']) ?></span></td>
                            <td><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                            <td>
                                <div class="actions" style="flex-wrap:wrap;">
                                    <?php if ($app['status'] === 'pending' || $app['status'] === 'reviewed'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="job_id" value="<?= $jobId ?>">
                                            <input type="hidden" name="view_status" value="<?= htmlspecialchars($filterStatus) ?>">
                                            <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                            <input type="hidden" name="status" value="accepted">
                                            <?= csrfInput() ?>
                                            <button type="submit" class="btn btn-primary btn-sm">Accept</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="job_id" value="<?= $jobId ?>">
                                            <input type="hidden" name="view_status" value="<?= htmlspecialchars($filterStatus) ?>">
                                            <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <?= csrfInput() ?>
                                            <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="job_id" value="<?= $jobId ?>">
                                            <input type="hidden" name="view_status" value="<?= htmlspecialchars($filterStatus) ?>">
                                            <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                            <input type="hidden" name="status" value="shortlisted">
                                            <?= csrfInput() ?>
                                            <button type="submit" class="btn btn-secondary btn-sm">Shortlist</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display:flex; gap:6px; align-items:center;">
                                        <input type="hidden" name="job_id" value="<?= $jobId ?>">
                                        <input type="hidden" name="view_status" value="<?= htmlspecialchars($filterStatus) ?>">
                                        <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                        <?= csrfInput() ?>
                                        <select name="status" class="form-control" style="padding:5px 8px; font-size:12px; width:110px;">
                                            <?php foreach ($allowedStatuses as $status): ?>
                                                <option value="<?= $status ?>" <?= $app['status'] === $status ? 'selected' : '' ?>>
                                                    <?= ucfirst($status) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="main.js"></script>
</body>
</html>
