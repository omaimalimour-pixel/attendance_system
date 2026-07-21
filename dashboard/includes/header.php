<?php
require_once __DIR__."/auth.php";
if(!isset($pageTitle))$pageTitle="Dashboard";
if(!isset($currentPage))$currentPage="dashboard";
$_v=@filemtime(__DIR__.'/../dashboard.css')?:time();
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=htmlspecialchars($pageTitle)?> — ChronoX</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="dashboard.css?v=<?=$_v?>">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',system-ui,sans-serif;background:#F5F6FA;color:#0B1020;font-size:14px;-webkit-font-smoothing:antialiased}
.sidebar{position:fixed;top:0;left:0;bottom:0;width:264px;background:#fff;border-right:1px solid #ECEEF3;padding:18px 14px;display:flex;flex-direction:column;z-index:1050;overflow-y:auto}
.sidebar svg{width:18px;height:18px;flex-shrink:0}
.brand{display:flex;align-items:center;gap:11px;padding:6px 8px 22px}
.brand-icon{width:38px;height:38px;border-radius:11px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:grid;place-items:center;color:#fff;flex-shrink:0}
.brand-icon svg{width:19px;height:19px}
.brand-name{font-size:16.5px;font-weight:800;letter-spacing:-.03em}
.brand-name span{color:#5B54E8}
.brand-sub{font-size:10.5px;color:#98A0B3;margin-top:1px}
.nav-label{font-size:10.5px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#98A0B3;padding:18px 12px 7px}
.side-link{display:flex;align-items:center;gap:11px;padding:9px 12px;border-radius:10px;color:#6B7385;font-weight:500;font-size:13.5px;text-decoration:none;margin-bottom:2px}
.side-link:hover{background:#F5F6FA;color:#0B1020}
.side-link.active{background:#EFF0FF;color:#5B54E8;font-weight:600}
.main{margin-left:264px;min-height:100vh}
.hdr{position:sticky;top:0;z-index:1030;height:68px;display:flex;align-items:center;gap:16px;padding:0 28px;background:rgba(255,255,255,.72);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);border-bottom:1px solid #ECEEF3}
.hdr-btn{width:38px;height:38px;border-radius:10px;border:1px solid #ECEEF3;background:#fff;color:#6B7385;display:grid;place-items:center;cursor:pointer}
.hdr-btn svg{width:18px;height:18px}
.hdr-info{flex:1;min-width:0}.hdr-info h1{font-size:17px;font-weight:750;letter-spacing:-.02em}.hdr-info p{font-size:12px;color:#98A0B3;margin-top:1px}
.hdr-search{position:relative;width:250px}
.hdr-search svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#98A0B3}
.hdr-search input{width:100%;height:40px;border-radius:10px;padding:0 12px 0 37px;background:#F5F6FA;border:1px solid #ECEEF3;color:#0B1020;font-size:13px;font-family:inherit}
.hdr-bell{width:40px;height:40px;border-radius:10px;border:1px solid #ECEEF3;background:#fff;color:#6B7385;display:grid;place-items:center;cursor:pointer;position:relative}
.hdr-bell svg{width:18px;height:18px}.hdr-bell .dot{position:absolute;top:9px;right:10px;width:7px;height:7px;border-radius:50%;background:#E5484D;border:2px solid #fff}
.hdr-av{width:40px;height:40px;border-radius:10px;display:grid;place-items:center;background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff;font-weight:700;font-size:14px}
.cnt{padding:26px 28px 40px;max-width:1500px}
.sidebar-footer{margin-top:auto;padding-top:14px;border-top:1px solid #F1F2F6}
.user-card{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:10px}
.user-av{width:34px;height:34px;border-radius:10px;display:grid;place-items:center;background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff;font-weight:700;font-size:13px;flex-shrink:0}
.user-name{font-weight:600;font-size:12.5px}.user-role{font-size:10.5px;color:#98A0B3}
.logout-link{color:#E5484D!important;margin-top:2px}
.bk{display:none;position:fixed;inset:0;background:rgba(11,16,32,.4);z-index:1040}.bk.show{display:block}
@media(max-width:991px){.sidebar{transform:translateX(-100%)}.sidebar.mobile-open{transform:translateX(0)}.main{margin-left:0!important}.hdr-search{display:none}.cnt{padding:18px 16px}}
</style>
</head><body>
<?php include __DIR__."/sidebar.php";?>
<div class="bk" id="bk"></div>
<div class="main" id="main">
<header class="hdr">
<button class="hdr-btn" id="tog"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
<div class="hdr-info"><h1><?=htmlspecialchars($pageTitle)?></h1><p><?=date("l, F j, Y")?></p></div>
<div class="hdr-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg><input type="text" placeholder="Search anything..."></div>
<button class="hdr-bell"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg><span class="dot"></span></button>
<div class="hdr-av"><?=isset($_SESSION['username'])?strtoupper(substr($_SESSION['username'],0,1)):'A'?></div>
</header>
<div class="cnt">
