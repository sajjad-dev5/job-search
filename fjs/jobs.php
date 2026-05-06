<?php
// fjs/jobs.php
require '../fjs-be/config/db.php';
require '../fjs-be/auth/auth.php';

$search   = trim($_GET['search']   ?? '');
$location = trim($_GET['location'] ?? '');
$type     = trim($_GET['type']     ?? '');
$posted   = trim($_GET['posted']   ?? '');

$sql    = "SELECT j.*, u.name AS employer_name FROM jobs j JOIN users u ON j.employer_id = u.id WHERE j.status = 'open'";
$params = [];

if ($search) {
    $sql .= " AND (j.title LIKE ? OR j.company LIKE ? OR j.description LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($location) {
    $sql .= " AND j.location LIKE ?";
    $params[] = "%$location%";
}
if ($type) {
    $sql .= " AND j.type = ?";
    $params[] = $type;
}
if ($posted === '7') {
    $sql .= " AND j.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($posted === '30') {
    $sql .= " AND j.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}
$sql .= " ORDER BY j.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$totalJobs  = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'open'")->fetchColumn();
$typeCounts = $pdo->query("SELECT type, COUNT(*) AS total FROM jobs WHERE status = 'open' GROUP BY type")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Jobs — JobSearch</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav>
    <a href="index.php" class="brand">&#128269; JobSearch</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="jobs.php">Browse Jobs</a>
        <?php if (isLoggedIn()): ?>
            <?php if (currentRole() === 'admin'): ?>
                <a href="../fjs-admin/dashboard.php">Admin Panel</a>
            <?php else: ?>
                <a href="dashboard.php">Dashboard</a>
            <?php endif; ?>
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
        <h2>&#128188; Browse Jobs</h2>
        <?php if (isLoggedIn() && currentRole() === 'employer'): ?>
            <a href="add_job.php" class="btn btn-primary">+ Post a Job</a>
        <?php endif; ?>
    </div>

    <!-- SEARCH FORM -->
    <div class="card">
        <form method="GET" action="jobs.php">
            <div class="search-bar">
                <input class="form-control" type="text" name="search"
                       placeholder="&#128269;  Title, company, keyword..."
                       value="<?= htmlspecialchars($search) ?>">
                <input class="form-control" type="text" name="location"
                       placeholder="&#128205;  Location..."
                       value="<?= htmlspecialchars($location) ?>"
                       style="max-width:200px;">
                <select class="form-control" name="type" style="max-width:160px;">
                    <option value="">All Types</option>
                    <?php foreach (['Full-Time','Part-Time','Remote','Internship'] as $t): ?>
                        <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="form-control" name="posted" style="max-width:160px;">
                    <option value="">Any Time</option>
                    <option value="7" <?= $posted === '7' ? 'selected' : '' ?>>Last 7 days</option>
                    <option value="30" <?= $posted === '30' ? 'selected' : '' ?>>Last 30 days</option>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="jobs.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    <!-- TYPE FILTER BADGES -->
    <div class="type-filters">
        <a href="jobs.php" class="type-filter <?= !$type ? 'active' : '' ?>">
            All <span><?= $totalJobs ?></span>
        </a>
        <?php foreach ($typeCounts as $t => $count): ?>
            <a href="jobs.php?type=<?= urlencode($t) ?>" class="type-filter <?= $type === $t ? 'active' : '' ?>">
                <?= $t ?> <span><?= $count ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- RESULTS COUNT -->
    <p style="color:var(--text-muted); margin-bottom:20px; font-size:14px;">
        <?= count($jobs) ?> job(s) found<?= $search ? " for \"<strong>".htmlspecialchars($search)."</strong>\"" : "" ?>
    </p>

    <!-- JOB CARDS -->
    <?php if (empty($jobs)): ?>
        <div class="empty-state">
            <div class="icon">&#128270;</div>
            <p>No jobs match your search.</p>
            <a href="jobs.php" class="btn btn-primary">Clear Filters</a>
        </div>
    <?php else: ?>
        <?php foreach ($jobs as $job): ?>
        <div class="card" style="margin-bottom:16px;">
            <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                <div style="flex:1;">
                    <span class="badge"><?= $job['type'] ?></span>
                    <h3 style="color:var(--dark); margin:8px 0 6px; font-size:19px;">
                        <?= htmlspecialchars($job['title']) ?>
                    </h3>
                    <p style="color:#555; margin-bottom:4px;">
                        &#127970; <strong><?= htmlspecialchars($job['company']) ?></strong>
                        &nbsp;&bull;&nbsp; &#128205; <?= htmlspecialchars($job['location']) ?>
                        <?php if ($job['salary']): ?>
                            &nbsp;&bull;&nbsp; &#128176; <?= htmlspecialchars($job['salary']) ?>
                        <?php endif; ?>
                    </p>
                    <p style="color:#666; font-size:14px; margin-top:10px; line-height:1.7;">
                        <?= htmlspecialchars(substr($job['description'], 0, 200)) ?>...
                    </p>
                </div>
                <div style="text-align:right; min-width:130px;">
                    <small style="color:#bbb; display:block; margin-bottom:12px;">
                        <?= date('M d, Y', strtotime($job['created_at'])) ?>
                    </small>
                    <a href="job.php?id=<?= $job['id'] ?>" class="btn btn-secondary" style="margin-bottom:8px;">View Details</a>
                    <?php if (isLoggedIn() && currentRole() === 'seeker'): ?>
                        <a href="apply.php?job_id=<?= $job['id'] ?>" class="btn btn-primary">Apply Now</a>
                    <?php elseif (!isLoggedIn()): ?>
                        <a href="login.php" class="btn btn-primary">Login to Apply</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="main.js"></script>
</body>
</html>
