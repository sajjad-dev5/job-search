<?php
// fjs/delete_job.php
require '../fjs-be/config/db.php';
require '../fjs-be/auth/auth.php';
requireRole('employer', 'login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

verifyCsrf();

$jobId = intval($_POST['id'] ?? 0);
$pdo->prepare("DELETE FROM jobs WHERE id = ? AND employer_id = ?")
    ->execute([$jobId, currentUser()['id']]);

header("Location: dashboard.php");
exit();
?>
