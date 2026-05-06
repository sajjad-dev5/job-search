<?php
// fjs/logout.php
require '../fjs-be/auth/auth.php';
session_destroy();
header("Location: login.php");
exit();
?>
