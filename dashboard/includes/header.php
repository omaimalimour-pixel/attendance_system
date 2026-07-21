<?php
require_once __DIR__ . "/auth.php";
if (!isset($pageTitle)) $pageTitle = "Dashboard";
if (!isset($currentPage)) $currentPage = "dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — ChronoX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="dashboard.css?v=<?= time() ?>">
    <style>
    /* Inline critical — guarantees layout even if external CSS is cached */
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:#F4F6FA;color:#1A1D2E;font-size:14px}
    .sidebar{position:fixed;top:0;left:0;bottom:0;width:260px;background:#fff;border-right:1px solid #E8EAF0;padding:20px 14px;display:flex;flex-direction:column;z-index:1050;overflow-y:auto}
    .sidebar svg{width:18px;height:18px;flex-shrink:0}
    .brand{display:flex;align-items:center;gap:11px;padding:6px 10px 24px}
    .brand-icon{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;background:#4F46E5;color:#fff;flex-shrink:0}
    .brand-icon svg{width:18px;height:18px}
    .brand-name{font-size:18px;font-weight:800;color:#1A1D2E}
    .brand-name span{color:#4F46E5}
    .brand-sub{font-size:11px;color:#9CA3BF}
    .side-link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;color:#6B7194;font-weight:500;font-size:13.5px;text-decoration:none;margin-bottom:2px}
    .side-link:hover{background:#F4F6FA;color:#1A1D2E}
    .side-link.active{background:#EEF0FF;color:#4F46E5;font-weight:600}
    .nav-label{font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#9CA3BF;padding:20px 12px 6px}
    .main{margin-left:260px;min-height:100vh}
    .header{position:sticky;top:0;z-index:1030;height:64px;display:flex;align-items:center;gap:16px;padding:0 28px;background:rgba(255,255,255,.85);backdrop-filter:blur(12px);border-bottom:1px solid #E8EAF0}
    .header-toggle{width:36px;height:36px;border-radius:8px;border:1px solid #E8EAF0;background:#fff;color:#6B7194;display:grid;place-items:center;cursor:pointer}
    .header-toggle svg{width:18px;height:18px}
    .header-title{flex:1}
    .header-title h1{font-size:17px;font-weight:700}
    .header-title p{font-size:12px;color:#9CA3BF}
    .header-search{position:relative;width:240px}
    .header-search svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#9CA3BF}
    .header-search input{width:100%;height:36px;border-radius:8px;padding:0 12px 0 36px;background:#F4F6FA;border:1px solid #E8EAF0;color:#1A1D2E;font-size:13px;font-family:inherit}
    .header-avatar{width:36px;height:36px;border-radius:8px;display:grid;place-items:center;background:#4F46E5;color:#fff;font-weight:700;font-size:13px}
    .content{padding:24px 28px 40px}
    .sidebar-footer{margin-top:auto;padding-top:14px;border-top:1px solid #E8EAF0}
    .user-card{display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px}
    .user-avatar{width:34px;height:34px;border-radius:8px;display:grid;place-items:center;background:#4F46E5;color:#fff;font-weight:700;font-size:13px;flex-shrink:0}
    .user-name{font-weight:600;font-size:13px}.user-role{font-size:11px;color:#9CA3BF}
    .backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.25);z-index:1040}.backdrop.show{display:block}
    @media(max-width:991px){.sidebar{transform:translateX(-100%)}.sidebar.mobile-open{transform:translateX(0)}.main{margin-left:0!important}.header-search{display:none}}
    </style>
</head>
<body>

<?php include __DIR__ . "/sidebar.php"; ?>
<div class="backdrop" id="backdrop"></div>

<div class="main" id="main">
<header class="header">
    <button class="header-toggle" id="toggleSidebar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="header-title">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        <p><?= date("l, F d, Y") ?></p>
    </div>
    <div class="header-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="text" placeholder="Search...">
    </div>
    <div class="header-avatar"><?= isset($_SESSION['username']) ? strtoupper(substr($_SESSION['username'],0,1)) : 'A' ?></div>
</header>
<div class="content">
