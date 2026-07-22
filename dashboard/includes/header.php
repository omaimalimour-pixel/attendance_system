<?php
require_once __DIR__."/auth.php";
if(!isset($pageTitle))$pageTitle="Dashboard";
if(!isset($currentPage))$currentPage="dashboard";
$_v=@filemtime(__DIR__.'/../dashboard.css')?:time();
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=htmlspecialchars($pageTitle)?> — ChronoX</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="dashboard.css?v=<?=$_v?>">
<style>
/* DARK THEME — identical to landing.css */
*{margin:0;padding:0;box-sizing:border-box;-webkit-font-smoothing:antialiased}
html{scroll-behavior:smooth}
body{font-family:'Inter',system-ui,sans-serif;background:#05060D!important;color:#EDF0FA!important;font-size:14px;line-height:1.6}
body::before{content:"";position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.04;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='2'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E")}
body::after{content:"";position:fixed;inset:0;z-index:0;pointer-events:none;background:radial-gradient(900px 600px at 80% -5%,rgba(99,102,241,.12),transparent 60%),radial-gradient(700px 500px at 0% 10%,rgba(34,211,238,.07),transparent 55%)}
.sidebar{position:fixed;top:0;left:0;bottom:0;width:260px;background:#0A0C18;border-right:1px solid rgba(255,255,255,.09);padding:20px 14px;display:flex;flex-direction:column;z-index:1050;overflow-y:auto;overflow-x:hidden;transition:transform .28s}
.sidebar::-webkit-scrollbar{width:0}
.brand{display:flex;align-items:center;gap:11px;padding:6px 8px 26px}
.brand-icon{width:38px;height:38px;border-radius:11px;background:linear-gradient(120deg,#818CF8,#22D3EE,#A78BFA);display:grid;place-items:center;color:#05060D;flex-shrink:0;box-shadow:0 6px 20px rgba(99,102,241,.4)}
.brand-icon svg{width:19px;height:19px}
.brand-name{font-family:'Sora',sans-serif;font-size:17px;font-weight:800;color:#EDF0FA}
.brand-name span{background:linear-gradient(120deg,#818CF8,#22D3EE,#A78BFA);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.brand-sub{font-size:10.5px;color:#626A8A;margin-top:1px}
.nav-label{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#626A8A;padding:18px 10px 6px}
.side-link{display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:8px;color:#9AA2C0;font-weight:500;font-size:13.5px;text-decoration:none;margin-bottom:2px;transition:all .15s}
.side-link svg{width:18px;height:18px;flex-shrink:0;opacity:.55}
.side-link:hover{background:rgba(255,255,255,.07);color:#EDF0FA}
.side-link:hover svg{opacity:.9}
.side-link.active{background:rgba(129,140,248,.14);color:#818CF8;font-weight:600;border:1px solid rgba(129,140,248,.18)}
.side-link.active svg{opacity:1;color:#818CF8}
.sidebar-footer{margin-top:auto;padding-top:14px;border-top:1px solid rgba(255,255,255,.09)}
.user-card{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09)}
.user-av{width:32px;height:32px;border-radius:8px;background:linear-gradient(120deg,#818CF8,#22D3EE,#A78BFA);color:#05060D;display:grid;place-items:center;font-weight:700;font-size:12px;flex-shrink:0}
.user-name{font-weight:600;font-size:12.5px;color:#C8CCDB}
.user-role{font-size:10.5px;color:#626A8A}
.logout-link{color:#FB7185!important;margin-top:6px}
.logout-link:hover{background:rgba(251,113,133,.1)!important}
.main{margin-left:260px;min-height:100vh;position:relative;z-index:1}
.hdr{position:sticky;top:0;z-index:1030;height:64px;display:flex;align-items:center;gap:16px;padding:0 28px;background:rgba(5,6,13,.78);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid rgba(255,255,255,.09)}
.hdr-btn{width:36px;height:36px;border-radius:8px;border:1px solid rgba(255,255,255,.09);background:transparent;color:#9AA2C0;display:grid;place-items:center;cursor:pointer;transition:.15s}
.hdr-btn:hover{background:rgba(255,255,255,.07);color:#EDF0FA}
.hdr-btn svg,.hdr-bell svg{width:18px;height:18px}
.hdr-info{flex:1}
.hdr-info h1{font-family:'Sora',sans-serif;font-size:16px;font-weight:700;letter-spacing:-.02em;color:#EDF0FA}
.hdr-info p{font-size:11.5px;color:#626A8A;margin-top:1px}
.hdr-search{position:relative;width:230px}
.hdr-search svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:#626A8A}
.hdr-search input{width:100%;height:36px;border-radius:8px;padding:0 11px 0 34px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);color:#EDF0FA;font-size:12.5px;font-family:inherit}
.hdr-search input::placeholder{color:#626A8A}
.hdr-bell{width:36px;height:36px;border-radius:8px;border:1px solid rgba(255,255,255,.09);background:transparent;color:#9AA2C0;display:grid;place-items:center;cursor:pointer;position:relative}
.hdr-bell .dot{position:absolute;top:8px;right:9px;width:7px;height:7px;border-radius:50%;background:#FB7185;border:2px solid #05060D}
.hdr-av{width:36px;height:36px;border-radius:8px;background:linear-gradient(120deg,#818CF8,#22D3EE,#A78BFA);color:#05060D;display:grid;place-items:center;font-weight:700;font-size:13px;cursor:pointer}
.cnt{padding:24px 28px 40px;position:relative;z-index:1;max-width:1600px}
.bk{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(2px);z-index:1040}
.bk.show{display:block}
@media(max-width:991px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.mobile-open{transform:translateX(0);box-shadow:0 8px 30px rgba(0,0,0,.7)}
  .main{margin-left:0!important}
  .hdr-search{display:none}
  .cnt{padding:16px 14px}
}
</style>
</head><body>
<?php include __DIR__."/sidebar.php";?>
<div class="bk" id="bk"></div>
<div class="main" id="main">
<header class="hdr">
<button class="hdr-btn" id="tog"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
<div class="hdr-info"><h1><?=htmlspecialchars($pageTitle)?></h1><p><?=date("l, F j, Y")?></p></div>
<div class="hdr-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg><input type="text" placeholder="Search..."></div>
<button class="hdr-bell"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg><span class="dot"></span></button>
<div class="hdr-av"><?=isset($_SESSION['username'])?strtoupper(substr($_SESSION['username'],0,1)):'A'?></div>
</header>
<div class="cnt">
