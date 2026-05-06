<?php
// fjs-admin/dashboard.php
require '../fjs-be/config/db.php';
require '../fjs-be/auth/auth.php';
requireAdmin();

$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
$totalJobs     = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$totalApps     = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$totalEmployers= $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employer'")->fetchColumn();
$totalSeekers  = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seeker'")->fetchColumn();
$pendingApps   = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();

// Latest 5 registrations
$recentUsers = $pdo->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Latest 5 jobs
$recentJobs = $pdo->query("
    SELECT j.*, u.name AS employer_name FROM jobs j
    JOIN users u ON j.employer_id = u.id
    ORDER BY j.created_at DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Admin</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <a href="dashboard.php" class="sidebar-brand">
        &#9881; JobSearch
        <small>Admin Panel</small>
    </a>
    <nav>
        <a href="dashboard.php"><span class="icon">&#128202;</span> Dashboard</a>
        <a href="users.php"><span class="icon">&#128100;</span> Users</a>
        <a href="jobs.php"><span class="icon">&#128188;</span> Jobs</a>
        <a href="applications.php"><span class="icon">&#128228;</span> Applications</a>
    </nav>
    <div class="sidebar-footer">
        Logged in as <strong><?= htmlspecialchars(currentUser()['name']) ?></strong>
    </div>
</aside>

<!-- MAIN -->
<div class="main">
    <div class="topbar">
        <h1>Dashboard</h1>
        <div class="tb-right">
            <span>&#128100; <?= htmlspecialchars(currentUser()['name']) ?></span>
            <a href="../fjs/index.php" style="color:var(--info);">&#127968; Website</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="content">

        <!-- STAT CARDS -->
        <div class="stat-grid">
            <div class="stat-card">
                <span class="stat-icon">&#128100;</span>
                <div class="stat-info">
                    <div class="num"><?= $totalUsers ?></div>
                    <div class="lbl">Total Users</div>
                </div>
            </div>
            <div class="stat-card blue">
                <span class="stat-icon">&#128188;</span>
                <div class="stat-info">
                    <div class="num"><?= $totalJobs ?></div>
                    <div class="lbl">Total Jobs</div>
                </div>
            </div>
            <div class="stat-card orange">
                <span class="stat-icon">&#128228;</span>
                <div class="stat-info">
                    <div class="num"><?= $totalApps ?></div>
                    <div class="lbl">Applications</div>
                </div>
            </div>
            <div class="stat-card red">
                <span class="stat-icon">&#9203;</span>
                <div class="stat-info">
                    <div class="num"><?= $pendingApps ?></div>
                    <div class="lbl">Pending Apps</div>
                </div>
            </div>
            <div class="stat-card" style="border-color:#8e44ad;">
                <span class="stat-icon">&#127970;</span>
                <div class="stat-info">
                    <div class="num"><?= $totalEmployers ?></div>
                    <div class="lbl">Employers</div>
                </div>
            </div>
            <div class="stat-card" style="border-color:#27ae60;">
                <span class="stat-icon">&#128203;</span>
                <div class="stat-info">
                    <div class="num"><?= $totalSeekers ?></div>
                    <div class="lbl">Job Seekers</div>
                </div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">

            <!-- RECENT USERS -->
            <div class="card">
                <div class="card-header">
                    <h3>&#128100; Recent Registrations</h3>
                    <a href="users.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <table>
                    <thead><tr><th>Name</th><th>Role</th><th>Joined</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['name']) ?></td>
                            <td>
                                <span class="badge <?= $u['role']==='employer' ? 'badge-purple' : 'badge-blue' ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td style="color:var(--text-muted);"><?= date('M d', strtotime($u['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- RECENT JOBS -->
            <div class="card">
                <div class="card-header">
                    <h3>&#128188; Recent Jobs</h3>
                    <a href="jobs.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <table>
                    <thead><tr><th>Title</th><th>Type</th><th>Posted</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentJobs as $j): ?>
                        <tr>
                            <td><?= htmlspecialchars($j['title']) ?><br>
                                <small style="color:var(--text-muted);"><?= htmlspecialchars($j['company']) ?></small>
                            </td>
                            <td><span class="badge badge-green"><?= $j['type'] ?></span></td>
                            <td style="color:var(--text-muted);"><?= date('M d', strtotime($j['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<script src="admin.js"></script>
</body>
</html>
