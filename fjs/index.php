<?php
// fjs/index.php — Home Page
require '../fjs-be/config/db.php';
require '../fjs-be/auth/auth.php';

// ── Live stats ────────────────────────────────────────────
$totalJobs      = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$totalUsers     = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
$totalEmployers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employer'")->fetchColumn();
$totalApps      = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();

// ── Latest 6 jobs ─────────────────────────────────────────
$latestJobs = $pdo->query("
    SELECT j.*, u.name AS employer_name
    FROM jobs j JOIN users u ON j.employer_id = u.id
    WHERE j.status = 'open'
    ORDER BY j.featured DESC, j.created_at DESC LIMIT 6
")->fetchAll();

// ── Type counts ───────────────────────────────────────────
$typeCounts = $pdo->query("
    SELECT type, COUNT(*) AS total FROM jobs WHERE status = 'open' GROUP BY type
")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobSearch — Find Your Dream Job</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- NAV -->
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

<!-- HERO -->
<div class="hero">
    <h1>Find Your Dream Job</h1>
    <p>
        <?php if ($totalJobs > 0): ?>
            <strong><?= $totalJobs ?></strong> live jobs from <strong><?= $totalEmployers ?></strong> companies
        <?php else: ?>
            Thousands of jobs waiting for you
        <?php endif; ?>
    </p>
    <form action="jobs.php" method="GET">
        <div class="hero-search">
            <input type="text" name="search"   placeholder="&#128269;  Job title, skill, or company...">
            <input type="text" name="location" placeholder="&#128205;  City or location..." style="max-width:220px;">
            <button type="submit">Search Jobs</button>
        </div>
    </form>
</div>

<div class="container">

    <!-- STATS -->
    <div class="stats-row">
        <div class="stat-box">
            <div class="num"><?= $totalJobs ?></div>
            <div class="lbl">&#128188; Jobs Posted</div>
        </div>
        <div class="stat-box">
            <div class="num"><?= $totalEmployers ?></div>
            <div class="lbl">&#127970; Companies</div>
        </div>
        <div class="stat-box">
            <div class="num"><?= $totalUsers ?></div>
            <div class="lbl">&#128100; Registered Users</div>
        </div>
        <div class="stat-box">
            <div class="num"><?= $totalApps ?></div>
            <div class="lbl">&#128228; Applications</div>
        </div>
    </div>

    <!-- LATEST JOBS -->
    <div class="section-header">
        <h2>&#128293; Latest Jobs</h2>
        <a href="jobs.php" class="btn btn-primary">View All &rarr;</a>
    </div>

    <!-- TYPE FILTERS -->
    <?php if (!empty($typeCounts)): ?>
    <div class="type-filters">
        <a href="jobs.php" class="type-filter">All <span><?= $totalJobs ?></span></a>
        <?php foreach ($typeCounts as $type => $count): ?>
            <a href="jobs.php?type=<?= urlencode($type) ?>" class="type-filter">
                <?= $type ?> <span><?= $count ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- JOB CARDS -->
    <?php if (empty($latestJobs)): ?>
        <div class="empty-state">
            <div class="icon">&#128188;</div>
            <p>No jobs posted yet.</p>
            <?php if (isLoggedIn() && currentRole() === 'employer'): ?>
                <a href="add_job.php" class="btn btn-primary">Post the First Job</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary">Register as Employer</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="job-grid">
            <?php foreach ($latestJobs as $job): ?>
            <div class="job-card">
                <span class="badge"><?= $job['type'] ?></span>
                <h3><?= htmlspecialchars($job['title']) ?></h3>
                <div class="company">&#127970; <?= htmlspecialchars($job['company']) ?></div>
                <div class="meta">&#128205; <?= htmlspecialchars($job['location']) ?></div>
                <div class="desc"><?= htmlspecialchars(substr($job['description'], 0, 100)) ?>...</div>
                <div class="jc-footer">
                    <span class="salary">
                        <?= $job['salary'] ? '&#128176; '.htmlspecialchars($job['salary']) : 'Negotiable' ?>
                    </span>
                    <small style="color:#bbb;"><?= date('M d', strtotime($job['created_at'])) ?></small>
                </div>
                <a href="job.php?id=<?= $job['id'] ?>" class="btn btn-secondary btn-block" style="margin-top:6px;">View Details</a>
                <?php if (isLoggedIn() && currentRole() === 'seeker'): ?>
                    <a href="apply.php?job_id=<?= $job['id'] ?>" class="btn btn-primary btn-block" style="margin-top:6px;">Apply Now</a>
                <?php elseif (!isLoggedIn()): ?>
                    <a href="login.php" class="btn btn-primary btn-block" style="margin-top:6px;">Login to Apply</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($totalJobs > 6): ?>
        <div style="text-align:center; margin-bottom:30px;">
            <a href="jobs.php" class="btn btn-dark" style="padding:13px 40px;">
                See All <?= $totalJobs ?> Jobs &rarr;
            </a>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- HOW IT WORKS -->
    <h2 style="color:var(--dark); margin-bottom:20px;">&#128161; How It Works</h2>
    <div class="steps">
        <div class="step">
            <div class="step-num">1</div>
            <span class="step-icon">&#128221;</span>
            <h3>Register</h3>
            <p>Create a free account as a Job Seeker or Employer.</p>
        </div>
        <div class="step">
            <div class="step-num">2</div>
            <span class="step-icon">&#128269;</span>
            <h3>Search</h3>
            <p>Filter jobs by keyword, location, or type.</p>
        </div>
        <div class="step">
            <div class="step-num">3</div>
            <span class="step-icon">&#128228;</span>
            <h3>Apply</h3>
            <p>Send your application with a cover letter.</p>
        </div>
        <div class="step">
            <div class="step-num">4</div>
            <span class="step-icon">&#127881;</span>
            <h3>Get Hired</h3>
            <p>Track status and land your dream job.</p>
        </div>
    </div>

    <!-- CTA -->
    <?php if (!isLoggedIn()): ?>
    <div class="cta-banner">
        <h2>Ready to Get Started?</h2>
        <p>Join <?= $totalUsers ?> users already on JobSearch.</p>
        <a href="register.php" class="btn-white">&#128100; Find a Job</a>
        <a href="register.php" class="btn-outline-white">&#127970; Post a Job</a>
    </div>
    <?php elseif (currentRole() === 'employer'): ?>
    <div class="cta-banner">
        <h2>Need to Hire Someone?</h2>
        <p>Post a new job and reach qualified candidates today.</p>
        <a href="add_job.php" class="btn-white">+ Post New Job</a>
    </div>
    <?php endif; ?>

</div>

<script src="main.js"></script>
</body>
</html>
