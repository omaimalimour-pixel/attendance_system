<?php
session_start();

// Redirect to dashboard if logged in, otherwise to login page
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/dashboard.php");
} else {
    header("Location: login.php");
}
exit;
?>
