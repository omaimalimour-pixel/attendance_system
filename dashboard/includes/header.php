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
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="dashboard.css?v=4">
    <style>
    /* Critical inline fallback — guarantees layout even if CSS file fails */
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Inter',system-ui,sans-serif;background:#05060D;color:#EDF0FA;font-size:14px}
    .sidebar{position:fixed;top:0;left:0;bottom:0;width:270px;background:#0A0C18;border-right:1px solid rgba(255,255,255,.09);padding:24px 16px;display:flex;flex-direction:column;z-index:1050;overflow-y:auto}
    .sidebar svg{width:19px;height:19px;flex-shrink:0}
    .brand{display:flex;align-items:center;gap:12px;padding:4px 8px 28px}
    .brand-icon{width:38px;height:38px;border-radius:11px;display:grid;place-items:center;background:linear-gradient(120deg,#818CF8,#22D3EE,#A78BFA);color:#05060D;flex-shrink:0}
    .brand-icon svg{width:20px;height:20px}
    .brand-name{font-family:'Sora',sans-serif;font-size:20px;font-weight:800}
    .brand-name span{background:linear-gradient(120deg,#818CF8,#22D3EE,#A78BFA);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
    .brand-sub{font-size:11px;color:#626A8A}
    .side-link{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:12px;color:#9AA2C0;font-weight:500;font-size:13.5px;text-decoration:none;margin-bottom:3px}
    .side-link:hover{background:rgba(255,255,255,.06);color:#EDF0FA}
    .side-link.active{background:linear-gradient(135deg,rgba(99,102,241,.18),rgba(34,211,238,.08));color:#EDF0FA;border:1px solid rgba(255,255,255,.09)}
    .nav-label{font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#626A8A;padding:18px 12px 8px}
    .main{margin-left:270px;min-height:100vh}
    .header{position:sticky;top:0;z-index:1030;height:68px;display:flex;align-items:center;gap:16px;padding:0 28px;background:rgba(5,6,13,.75);backdrop-filter:blur(16px);border-bottom:1px solid rgba(255,255,255,.09)}
    .header-toggle{width:38px;height:38px;border-radius:8px;border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.04);color:#9AA2C0;display:grid;place-items:center;cursor:pointer}
    .header-toggle svg{width:18px;height:18px}
    .header-title{flex:1}
    .header-title h1{font-family:'Sora',sans-serif;font-size:18px;font-weight:700;margin:0}
    .header-title p{font-size:12px;color:#626A8A;margin:2px 0 0}
    .header-search{position:relative;width:260px}
    .header-search svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#626A8A}
    .header-search input{width:100%;height:38px;border-radius:8px;padding:0 14px 0 38px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);color:#EDF0FA;font-size:13px;font-family:inherit}
    .header-avatar{width:38px;height:38px;border-radius:10px;display:grid;place-items:center;background:linear-gradient(120deg,#818CF8,#22D3EE,#A78BFA);color:#05060D;font-weight:700;font-size:14px}
    .content{padding:28px}
    .sidebar-footer{margin-top:auto;padding-top:16px}
    .user-card{display:flex;align-items:center;gap:11px;padding:12px;border-radius:12px;border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.04)}
    .user-avatar{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;background:linear-gradient(120deg,#818CF8,#22D3EE,#A78BFA);color:#05060D;font-weight:700;font-size:14px;flex-shrink:0}
    .user-name{font-weight:600;font-size:13px}
    .user-role{font-size:11px;color:#626A8A}
    .logout-link{color:#FB7185!important;margin-top:8px}
    .backdrop{display:none;position:fixed;inset:0;background:rgba(5,6,13,.7);z-index:1040}
    .backdrop.show{display:block}
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
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="text" placeholder="Search...">
    </div>
    <div class="header-avatar">
        <?= isset($_SESSION['username']) ? strtoupper(substr($_SESSION['username'], 0, 1)) : 'A' ?>
    </div>
</header>

<div class="content">
