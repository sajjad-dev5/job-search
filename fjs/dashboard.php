<?php
// fjs/dashboard.php
require '../fjs-be/config/db.php';
require '../fjs-be/auth/auth.php';
requireLogin('login.php');

$user   = currentUser();
$role   = $user['role'];
$userId = $user['id'];

if ($role === 'employer') {
    $stmt = $pdo->prepare("
        SELECT j.*, (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS app_count
        FROM jobs j WHERE j.employer_id = ? ORDER BY j.created_at DESC
    ");
    $stmt->execute([$userId]);
    $myJobs = $stmt->fetchAll();
}

if ($role === 'seeker') {
    $stmt = $pdo->prepare("
        SELECT a.*, j.title, j.company, j.location, j.type
        FROM applications a JOIN jobs j ON a.job_id = j.id
        WHERE a.seeker_id = ? ORDER BY a.applied_at DESC
    ");
    $stmt->execute([$userId]);
    $myApps = $stmt->fetchAll();

    $profileStmt = $pdo->prepare("
        SELECT phone, city, bio, skills, resume_url
        FROM users
        WHERE id = ?
    ");
    $profileStmt->execute([$userId]);
    $profile = $profileStmt->fetch();

    if (!$profile) {
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit();
    }

    $profileChecks = [
        'Phone number' => !empty($profile['phone']),
        'City' => !empty($profile['city']),
        'Skills' => !empty($profile['skills']),
        'Bio' => !empty($profile['bio']),
        'Resume' => !empty($profile['resume_url']),
    ];
    $profileCompletion = (int) round((count(array_filter($profileChecks)) / count($profileChecks)) * 100);
    $missingProfileItems = array_keys(array_filter($profileChecks, fn ($filled) => !$filled));

    $statusCounts = [
        'pending' => 0,
        'reviewed' => 0,
        'shortlisted' => 0,
        'accepted' => 0,
        'rejected' => 0,
    ];
    foreach ($myApps as $app) {
        if (isset($statusCounts[$app['status']])) {
            $statusCounts[$app['status']]++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — JobSearch</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav>
    <a href="index.php" class="brand">&#128269; JobSearch</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="jobs.php">Browse Jobs</a>
        <?php if (currentRole() === 'admin'): ?>
            <a href="../fjs-admin/dashboard.php">Admin Panel</a>
        <?php else: ?>
            <a href="dashboard.php">Dashboard</a>
        <?php endif; ?>
        <span class="nav-user">| <?= htmlspecialchars($user['name']) ?></span>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <div>
            <h2>Welcome, <?= htmlspecialchars($user['name']) ?>!</h2>
            <p style="color:var(--text-muted); font-size:14px; margin-top:4px;">
                Logged in as: <strong><?= ucfirst($role) ?></strong>
            </p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="profile.php" class="btn btn-secondary">Edit Profile</a>
            <?php if ($role === 'employer'): ?>
                <a href="add_job.php" class="btn btn-primary">+ Post New Job</a>
            <?php else: ?>
                <a href="jobs.php" class="btn btn-primary">&#128269; Browse Jobs</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══════════ EMPLOYER ══════════ -->
    <?php if ($role === 'employer'): ?>
        <div class="card">
            <h3 style="margin-bottom:16px;">Your Job Postings (<?= count($myJobs) ?>)</h3>
            <?php if (empty($myJobs)): ?>
                <div class="empty-state" style="box-shadow:none; padding:30px;">
                    <p>You haven't posted any jobs yet.</p>
                    <a href="add_job.php" class="btn btn-primary">Post Your First Job</a>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Location</th>
                                <th>Type</th>
                                <th>Applicants</th>
                                <th>Posted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myJobs as $job): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($job['title']) ?></strong></td>
                                <td><?= htmlspecialchars($job['location']) ?></td>
                                <td><span class="badge"><?= $job['type'] ?></span></td>
                                <td>
                                    <?= $job['app_count'] ?> applicant(s)
                                    <?php if (($job['status'] ?? 'open') !== 'open'): ?>
                                        <br><small style="color:var(--text-soft);"><?= ucfirst($job['status']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($job['created_at'])) ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="job.php?id=<?= $job['id'] ?>" class="btn btn-secondary btn-sm">Preview</a>
                                        <a href="view_applicants.php?job_id=<?= $job['id'] ?>" class="btn btn-primary btn-sm">Applicants</a>
                                        <a href="edit_job.php?id=<?= $job['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <form method="POST" action="delete_job.php" style="display:inline;">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="id" value="<?= $job['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this job? All applications will be removed.">Delete</button>
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

    <!-- ══════════ SEEKER ══════════ -->
    <?php elseif ($role === 'seeker'): ?>
        <div class="card">
            <div class="page-header" style="margin-bottom:18px;">
                <div>
                    <h3 style="margin-bottom:6px;">Your Candidate Profile</h3>
                    <p style="color:var(--text-soft);">
                        Profile completion: <strong><?= $profileCompletion ?>%</strong>
                        <?php if (!empty($missingProfileItems)): ?>
                            &nbsp;&middot;&nbsp; Still worth adding: <?= htmlspecialchars(implode(', ', $missingProfileItems)) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="profile.php" class="btn btn-secondary">Edit Profile</a>
                    <?php if (!empty($profile['resume_url'])): ?>
                        <a href="<?= htmlspecialchars($profile['resume_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">View Resume</a>
                    <?php else: ?>
                        <a href="profile.php" class="btn btn-primary">Upload Resume</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($profile['resume_url'])): ?>
                <div class="alert alert-info">
                    You do not have a resume uploaded yet. Employers can now open it directly from your application review.
                </div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:16px;">
                <div class="stat-box">
                    <div class="num"><?= count($myApps) ?></div>
                    <div class="lbl">Applications</div>
                </div>
                <div class="stat-box">
                    <div class="num"><?= $statusCounts['pending'] + $statusCounts['reviewed'] ?></div>
                    <div class="lbl">Under Review</div>
                </div>
                <div class="stat-box">
                    <div class="num"><?= $statusCounts['shortlisted'] ?></div>
                    <div class="lbl">Shortlisted</div>
                </div>
                <div class="stat-box">
                    <div class="num"><?= $statusCounts['accepted'] ?></div>
                    <div class="lbl">Accepted</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-bottom:16px;">Your Applications (<?= count($myApps) ?>)</h3>
            <?php if (empty($myApps)): ?>
                <div class="empty-state" style="box-shadow:none; padding:30px;">
                    <p>You haven't applied to any jobs yet.</p>
                    <a href="jobs.php" class="btn btn-primary">Browse Jobs</a>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Company</th>
                                <th>Location</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Applied</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myApps as $app):
                                $colors = ['pending'=>'#f39c12','reviewed'=>'#3498db','shortlisted'=>'#12355b','accepted'=>'#1abc9c','rejected'=>'#e74c3c'];
                                $color  = $colors[$app['status']] ?? '#999';
                                $labels = ['pending'=>'Pending Review','reviewed'=>'Under Review','shortlisted'=>'Shortlisted','accepted'=>'Passed','rejected'=>'Rejected'];
                                $label  = $labels[$app['status']] ?? ucfirst($app['status']);
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($app['title']) ?></strong></td>
                                <td><?= htmlspecialchars($app['company']) ?></td>
                                <td><?= htmlspecialchars($app['location']) ?></td>
                                <td><span class="badge"><?= $app['type'] ?></span></td>
                                <td><strong style="color:<?= $color ?>"><?= htmlspecialchars($label) ?></strong></td>
                                <td><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script src="main.js"></script>
</body>
</html>
