<?php
require_once __DIR__."/auth.php";
if(!isset($pageTitle))$pageTitle="Dashboard";
if(!isset($currentPage))$currentPage="dashboard";
$_v=@filemtime(__DIR__.'/../dashboard.css')?:time();
$_j=@filemtime(__DIR__.'/../dashboard.js')?:time();
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=htmlspecialchars($pageTitle)?> — ChronoX</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="dashboard.css?v=<?=$_v?>">
</head><body>
<?php include __DIR__."/sidebar.php";?>
<div class="bk" id="bk"></div>
<div class="main" id="main">
<header class="hdr">
<button class="hdr-btn" id="tog"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
<div class="hdr-info"><h1><?=htmlspecialchars($pageTitle)?></h1><p><?=date("l, F d, Y")?></p></div>
<div class="hdr-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg><input type="text" placeholder="Search..."></div>
<div class="hdr-av"><?=isset($_SESSION['username'])?strtoupper(substr($_SESSION['username'],0,1)):'A'?></div>
</header>
<div class="cnt">
