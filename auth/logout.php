<?php
require_once '../includes/functions.php';
session_destroy();
header("Location: " . SITE_URL . "/auth/login.php");
exit();
?>
