<?php
session_start();

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

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard/dashboard.php");
            exit;
        } else {
            $error = "Invalid password. Please try again.";
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
    <title>Sign in — ChronoX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="landing.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body>

<div class="bg-canvas-wrap"></div>
<div class="grain"></div>

<div class="auth-shell">
    <!-- LEFT : BRAND PANEL -->
    <aside class="auth-aside">
        <a href="index.php" class="logo">
            <span class="logo-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 6a6 6 0 1 0 6 6"/><circle cx="12" cy="12" r="2"/></svg>
            </span>
            <span class="logo-text">Chrono<span>X</span></span>
        </a>
        <div class="auth-aside-body">
            <h2>Command your <span class="grad-text">workforce</span> in real time.</h2>
            <p>Sign in to access live attendance insights, biometric sync, and beautiful analytics.</p>
            <ul class="auth-points">
                <li><span class="ap-ic"></span> Real-time biometric attendance</li>
                <li><span class="ap-ic"></span> Instant reports & CSV exports</li>
                <li><span class="ap-ic"></span> Enterprise-grade security</li>
            </ul>
        </div>
        <div class="auth-aside-foot">© <?= date('Y') ?> ChronoX</div>
        <div class="auth-orb"></div>
    </aside>

    <!-- RIGHT : FORM -->
    <main class="auth-main">
        <div class="auth-card">
            <a href="index.php" class="auth-back">← Back to home</a>
            <h1>Welcome back</h1>
            <p class="auth-sub">Sign in to your ChronoX dashboard</p>

            <?php if ($error): ?>
            <div class="auth-alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="auth-field">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter your username"
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required autofocus>
                </div>
                <div class="auth-field">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg auth-submit">
                    <span>Sign In</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </form>

            <div class="auth-hint">
                First time? Run <code>setup.php</code> to create the default admin
                (<b>admin</b> / <b>admin123</b>).
            </div>
        </div>
    </main>
</div>

</body>
</html>
