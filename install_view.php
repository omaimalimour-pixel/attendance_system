<?php
// Guard: never access this file directly
if (!isset($steps) || !is_array($steps)) { header('Location: install.php'); exit; }
$allOk = true;
foreach ($steps as $s) { if (!$s[0]) { $allOk = false; break; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install — ChronoX</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',system-ui,sans-serif;background:radial-gradient(1000px 500px at 80% -10%,#EEF0FF,transparent),#F5F6FA;color:#0B1020;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:32px}
.card{width:100%;max-width:620px;background:#fff;border:1px solid #ECEEF3;border-radius:20px;box-shadow:0 18px 40px rgba(16,24,40,.1);overflow:hidden}
.hd{padding:28px 30px;border-bottom:1px solid #F1F2F6;display:flex;align-items:center;gap:14px}
.logo{width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:grid;place-items:center;color:#fff}
.logo svg{width:24px;height:24px}
.hd h1{font-size:20px;font-weight:800;letter-spacing:-.02em}
.hd p{font-size:13px;color:#6B7385}
.bd{padding:22px 30px}
.row{display:flex;align-items:center;gap:11px;padding:9px 0;font-size:14px;border-bottom:1px solid #F5F6FA}
.row:last-child{border-bottom:none}
.ic{width:22px;height:22px;border-radius:7px;display:grid;place-items:center;flex-shrink:0}
.ok{background:#E7F8F0;color:#0EA372}.bad{background:#FDECEC;color:#E5484D}
.ic svg{width:14px;height:14px}
.err{font-size:12px;color:#E5484D;margin-left:auto}
.ft{padding:22px 30px;background:#FAFBFD;border-top:1px solid #F1F2F6}
.banner{padding:14px 16px;border-radius:12px;font-size:13.5px;font-weight:600;margin-bottom:16px}
.banner.ok{background:#E7F8F0;color:#0EA372;border:1px solid #A7F3D0}
.banner.bad{background:#FDECEC;color:#E5484D;border:1px solid #FECACA}
.creds{background:#EFF0FF;border:1px solid #C7D2FE;border-radius:12px;padding:14px 16px;font-size:13px;color:#3730A3;margin-bottom:16px}
.creds code{background:#fff;padding:2px 8px;border-radius:6px;font-weight:700}
.btn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff;text-decoration:none;padding:12px 22px;border-radius:11px;font-weight:700;font-size:14px}
</style>
</head>
<body>
<div class="card">
  <div class="hd">
    <div class="logo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 6a6 6 0 1 0 6 6"/><circle cx="12" cy="12" r="2"/></svg></div>
    <div><h1>ChronoX Setup</h1><p>Database installation & migration</p></div>
  </div>
  <div class="bd">
    <?php if ($allOk): ?>
      <div class="banner ok">✓ Installation completed successfully — your database is ready.</div>
      <div class="creds">Default sign-in: <code>admin</code> / <code>admin123</code> &nbsp;·&nbsp; <strong>Change this password immediately</strong> after your first login.</div>
    <?php else: ?>
      <div class="banner bad">Some steps failed. Review the errors below and re-run this installer.</div>
    <?php endif; ?>
    <?php foreach ($steps as [$ok, $label, $err]): ?>
      <div class="row">
        <span class="ic <?= $ok ? 'ok' : 'bad' ?>">
          <?php if ($ok): ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          <?php else: ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg><?php endif; ?>
        </span>
        <span><?= e($label) ?></span>
        <?php if ($err): ?><span class="err"><?= e($err) ?></span><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="ft">
    <a class="btn" href="login.php">Go to Login →</a>
  </div>
</div>
</body>
</html>
