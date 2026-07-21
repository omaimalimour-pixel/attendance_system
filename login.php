<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/dashboard.php");
    exit;
}

include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM admin_users WHERE username='$username'");

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard/dashboard.php");
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ZKTeco Attendance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard/dashboard.css">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 10a2 2 0 0 0-2 2c0 1.02.76 2 2 2 1.24 0 2-.98 2-2a2 2 0 0 0-2-2z"/>
                <path d="M12 2a10 10 0 0 0-6.88 17.23l1.5-1.5A7.98 7.98 0 0 1 4 12a8 8 0 1 1 13.38 5.73l1.5 1.5A10 10 0 0 0 12 2z"/>
                <path d="M12 6a6 6 0 0 0-4.24 10.24l1.5-1.5A3.98 3.98 0 0 1 8 12a4 4 0 1 1 6.74 2.74l1.5 1.5A6 6 0 0 0 12 6z"/>
            </svg>
        </div>
        <h1>Welcome Back</h1>
        <p class="login-sub">Sign in to your attendance management system</p>

        <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom:20px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter your username"
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="login-btn">Sign In</button>
        </form>
    </div>
</body>
</html>
