<?php
// fjs-admin/logout.php
require '../fjs-be/auth/auth.php';
session_destroy();
header("Location: index.php");
exit();
?>
