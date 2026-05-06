<?php
// fjs/add_job.php
require '../fjs-be/config/db.php';
require '../fjs-be/auth/auth.php';
requireRole('employer', 'login.php');

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
    $salary      = trim($_POST['salary']      ?? '');

    if (empty($title) || empty($company) || empty($location) || empty($description)) {
        $error = "Please fill in all required fields.";
    } else {
        $pdo->prepare("INSERT INTO jobs (employer_id,title,company,location,type,description,requirements,benefits,salary) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([currentUser()['id'], $title, $company, $location, $type, $description, $requirements ?: null, $benefits ?: null, $salary ?: null]);
        $success = "Job posted successfully! <a href='dashboard.php'>View in dashboard</a>.";
        // Clear form
        $_POST = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Job — JobSearch</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav>
    <a href="index.php" class="brand">&#128269; JobSearch</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <?php if (currentRole() === 'admin'): ?>
            <a href="../fjs-admin/dashboard.php">Admin Panel</a>
        <?php else: ?>
            <a href="dashboard.php">Dashboard</a>
        <?php endif; ?>
        <span class="nav-user">| <?= htmlspecialchars(currentUser()['name']) ?></span>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="container" style="max-width:660px;">
    <div class="card">
        <h2 style="margin-bottom:6px;">Post a New Job</h2>
        <p style="color:var(--text-muted); font-size:14px; margin-bottom:24px;">
            Fill in the details below to advertise your position.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfInput() ?>
            <div class="form-group">
                <label>Job Title <span style="color:red;">*</span></label>
                <input class="form-control" type="text" name="title"
                       placeholder="e.g. Web Developer" required
                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label>Company Name <span style="color:red;">*</span></label>
                    <input class="form-control" type="text" name="company"
                           placeholder="e.g. Tech Corp" required
                           value="<?= htmlspecialchars($_POST['company'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Location <span style="color:red;">*</span></label>
                    <input class="form-control" type="text" name="location"
                           placeholder="e.g. Baghdad or Remote" required
                           value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label>Job Type</label>
                    <select class="form-control" name="type">
                        <?php foreach (['Full-Time','Part-Time','Remote','Internship'] as $t): ?>
                            <option value="<?= $t ?>" <?= ($_POST['type']??'Full-Time')===$t?'selected':'' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Salary <small style="color:#aaa;">(optional)</small></label>
                    <input class="form-control" type="text" name="salary"
                           placeholder="e.g. $2000/month"
                           value="<?= htmlspecialchars($_POST['salary'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Job Description <span style="color:red;">*</span></label>
                <textarea class="form-control" name="description" rows="6"
                          placeholder="Describe the role, responsibilities, and requirements..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Requirements</label>
                <textarea class="form-control" name="requirements" rows="4"
                          placeholder="Required experience, tools, education..."><?= htmlspecialchars($_POST['requirements'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Benefits</label>
                <textarea class="form-control" name="benefits" rows="4"
                          placeholder="Health care, flexible work, training budget..."><?= htmlspecialchars($_POST['benefits'] ?? '') ?></textarea>
            </div>
            <div style="display:flex; gap:12px;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Post Job</button>
                <a href="dashboard.php" class="btn btn-secondary" style="flex:1; text-align:center; line-height:2.2;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="main.js"></script>
</body>
</html>
