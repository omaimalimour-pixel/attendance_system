<?php
/**
 * Authentication guard.
 * Include this at the very top of every protected dashboard page.
 * If admin_users table doesn't exist yet, skip auth (first-time setup).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Skip auth if session is already valid
if (isset($_SESSION['user_id'])) {
    return;
}

// Check if admin_users table exists; if not, allow access (fresh install)
include_once __DIR__ . "/../../db.php";
$tableCheck = @mysqli_query($conn, "SHOW TABLES LIKE 'admin_users'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    // Table doesn't exist yet — skip auth, allow access
    return;
}

// Table exists but user not logged in — redirect to login
header("Location: ../login.php");
exit;
?>
