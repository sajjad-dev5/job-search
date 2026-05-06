<?php
require '../fjs-be/config/db.php';
require '../fjs-be/auth/auth.php';
requireAdmin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = intval($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id === currentUser()['id']) {
        $error = 'You cannot change your own admin account here.';
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'")->execute([$id]);
        $success = 'User deleted successfully.';
    } elseif ($action === 'toggle_active') {
        $pdo->prepare("UPDATE users SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ? AND role != 'admin'")->execute([$id]);
        $success = 'User status updated.';
    }
}

$users = $pdo->query("
    SELECT u.*,
        (SELECT COUNT(*) FROM jobs j WHERE j.employer_id = u.id) AS job_count,
        (SELECT COUNT(*) FROM applications a WHERE a.seeker_id = u.id) AS app_count
    FROM users u
    WHERE u.role != 'admin'
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Admin</title>
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
        <h1>Manage Users</h1>
        <div class="tb-right">
            <a href="../fjs/index.php" style="color:var(--info);">&#127968; Website</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="content">
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>&#128100; All Users (<?= count($users) ?>)</h3>
                <input class="form-control" type="text" id="tableSearch" placeholder="&#128269; Search users..." style="max-width:260px;">
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Jobs / Apps</th>
                            <th>City</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td style="color:var(--text-soft);"><?= $u['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($u['name']) ?></strong>
                                    <?php if (!empty($u['phone'])): ?><br><small style="color:var(--text-soft);"><?= htmlspecialchars($u['phone']) ?></small><?php endif; ?>
                                </td>
                                <td style="color:var(--text-soft);"><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <span class="badge <?= $u['role'] === 'employer' ? 'badge-purple' : 'badge-blue' ?>"><?= ucfirst($u['role']) ?></span>
                                </td>
                                <td style="color:var(--text-soft);">
                                    <?= $u['role'] === 'employer' ? $u['job_count'] . ' jobs' : $u['app_count'] . ' apps' ?>
                                </td>
                                <td><?= htmlspecialchars($u['city'] ?? '-') ?></td>
                                <td>
                                    <span class="badge <?= (int) ($u['is_active'] ?? 1) === 1 ? 'badge-green' : 'badge-red' ?>">
                                        <?= (int) ($u['is_active'] ?? 1) === 1 ? 'Active' : 'Blocked' ?>
                                    </span>
                                </td>
                                <td style="color:var(--text-soft);"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <div class="actions">
                                        <form method="POST" style="display:inline;">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <button type="submit" class="btn btn-secondary btn-sm">
                                                <?= (int) ($u['is_active'] ?? 1) === 1 ? 'Block' : 'Unblock' ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete user <?= htmlspecialchars($u['name']) ?>? All their jobs and applications will also be deleted.">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="9" style="text-align:center; color:var(--text-soft); padding:30px;">No users found.</td></tr>
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
