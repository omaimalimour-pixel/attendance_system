<?php
/**
 * ChronoX – Login page
 * Uses raw mysqli directly — no dependency on core/ helpers that
 * could crash on partial migrations or missing columns.
 */
date_default_timezone_set('Africa/Casablanca');

session_name('chronox');
session_start();

/* Already logged in → go straight to dashboard */
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard/dashboard.php');
    exit;
}

/* DB connection */
$db_host = getenv('CHRONOX_DB_HOST') ?: '127.0.0.1';
$db_name = getenv('CHRONOX_DB_NAME') ?: 'clocking';
$db_user = getenv('CHRONOX_DB_USER') ?: 'root';
$db_pass = getenv('CHRONOX_DB_PASS') ?: '';
$conn    = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';

    } elseif (!$conn) {
        $error = 'Database offline. Please start XAMPP MySQL and try again.';

    } else {
        /* Prepared statement — safe query */
        $stmt = mysqli_prepare($conn, 'SELECT * FROM admin_users WHERE username = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        /* Respect 'status' column only if it exists */
        if ($row && array_key_exists('status', $row) && $row['status'] !== 'active') {
            $row = null;
        }

        if ($row && password_verify($password, $row['password'])) {

            session_regenerate_id(true);
            $_SESSION['user_id']       = (int)$row['id'];
            $_SESSION['username']      = $row['username'];
            $_SESSION['name']          = $row['name'] ?? $row['username'];
            $_SESSION['role']          = $row['role'] ?? 'Administrator';
            $_SESSION['last_activity'] = time();
            $_SESSION['fp']            = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|chronox');

            /* Silent update — fine if column doesn't exist yet */
            @mysqli_query($conn,
                "UPDATE admin_users SET last_login_at = NOW() WHERE id = " . (int)$row['id']
            );

            header('Location: dashboard/dashboard.php');
            exit;
        }

        $error = 'Invalid username or password.';
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

  <!-- LEFT: brand panel -->
  <aside class="auth-aside">
    <a href="index.php" class="logo">
      <span class="logo-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 6a6 6 0 1 0 6 6"/><circle cx="12" cy="12" r="2"/>
        </svg>
      </span>
      <span class="logo-text">Chrono<span>X</span></span>
    </a>

    <div class="auth-aside-body">
      <h2>Command your <span class="grad-text">workforce</span> in real time.</h2>
      <p>Sign in to access live attendance insights, biometric sync, and beautiful analytics.</p>
      <ul class="auth-points">
        <li><span class="ap-ic"></span> Real-time biometric attendance</li>
        <li><span class="ap-ic"></span> Multi-device sync &amp; fingerprint enrolment</li>
        <li><span class="ap-ic"></span> Reports, exports &amp; analytics</li>
      </ul>
    </div>

    <div class="auth-aside-foot">© <?= date('Y') ?> ChronoX</div>
    <div class="auth-orb"></div>
  </aside>

  <!-- RIGHT: sign-in form -->
  <main class="auth-main">
    <div class="auth-card">
      <a href="index.php" class="auth-back">← Back to home</a>
      <h1>Welcome back</h1>
      <p class="auth-sub">Sign in to your ChronoX dashboard</p>

      <?php if ($error): ?>
      <div class="auth-alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <?php if (!$conn): ?>
      <div class="auth-alert" style="background:rgba(245,158,11,.12);color:#FBBF24;border-color:rgba(245,158,11,.25)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
          <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        Database offline — start XAMPP MySQL first.
      </div>
      <?php endif; ?>

      <form method="POST" class="auth-form">
        <div class="auth-field">
          <label>Username</label>
          <input type="text" name="username" placeholder="Enter your username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 required autocomplete="username" autofocus>
        </div>
        <div class="auth-field">
          <label>Password</label>
          <input type="password" name="password" placeholder="Enter your password"
                 required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary btn-lg auth-submit">
          <span>Sign In</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M5 12h14M12 5l7 7-7 7"/>
          </svg>
        </button>
      </form>

      <div class="auth-hint">
        Default credentials: <b>admin</b> / <b>admin123</b>
        — run <code>install.php</code> first if you haven't already.
      </div>
    </div>
  </main>

</div>
</body>
</html>
