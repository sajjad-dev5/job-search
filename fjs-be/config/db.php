<?php
// ── fjs-be/config/db.php ──────────────────────────────────
// Central database connection using PDO
// Required by all pages in fjs/ and fjs-admin/

$host   = 'localhost';
$dbname = 'jobsearch_db';
$user   = 'root';
$pass   = 'sajjad';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}
?>
