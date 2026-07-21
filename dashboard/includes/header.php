<?php
require_once __DIR__ . "/auth.php";
if (!isset($pageTitle)) $pageTitle = "Dashboard";
if (!isset($currentPage)) $currentPage = "dashboard";
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> — ChronoX</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="dashboard.css?v=<?= filemtime(__DIR__.'/../dashboard.css') ?>">
<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',system-ui,sans-serif;background:#0f1117;color:#f0f2f8;font-size:13.5px}.sidebar{position:fixed;top:0;left:0;bottom:0;width:250px;background:#161922;border-right:1px solid rgba(255,255,255,.07);padding:16px 10px;display:flex;flex-direction:column;z-index:1050;overflow-y:auto}.sidebar svg{width:16px;height:16px;flex-shrink:0}.brand{display:flex;align-items:center;gap:10px;padding:8px 10px 20px}.brand-icon{width:32px;height:32px;border-radius:8px;display:grid;place-items:center;background:#6c63ff;color:#fff;flex-shrink:0}.brand-icon svg{width:16px;height:16px}.brand-name{font-size:15px;font-weight:700}.brand-name span{color:#6c63ff}.side-link{display:flex;align-items:center;gap:9px;padding:8px 10px;border-radius:8px;color:#7c8298;font-weight:500;font-size:13px;text-decoration:none;margin-bottom:1px}.side-link:hover{background:rgba(255,255,255,.03);color:#f0f2f8}.side-link.active{background:rgba(108,99,255,.12);color:#6c63ff}.nav-label{font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#555b70;padding:16px 10px 4px}.main{margin-left:250px;min-height:100vh}.header{position:sticky;top:0;z-index:1030;height:60px;display:flex;align-items:center;gap:14px;padding:0 24px;background:rgba(15,17,23,.8);backdrop-filter:blur(12px);border-bottom:1px solid rgba(255,255,255,.07)}.header-toggle{width:32px;height:32px;border-radius:8px;border:1px solid rgba(255,255,255,.07);background:transparent;color:#7c8298;display:grid;place-items:center;cursor:pointer}.header-toggle svg{width:16px;height:16px}.header-title{flex:1}.header-title h1{font-size:15px;font-weight:700}.header-title p{font-size:11px;color:#555b70}.header-search{position:relative;width:200px}.header-search svg{position:absolute;left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#555b70}.header-search input{width:100%;height:32px;border-radius:8px;padding:0 10px 0 32px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);color:#f0f2f8;font-size:12px;font-family:inherit}.header-avatar{width:30px;height:30px;border-radius:8px;display:grid;place-items:center;background:#6c63ff;color:#fff;font-weight:700;font-size:12px}.content{padding:20px 24px 32px}.sidebar-footer{margin-top:auto;padding-top:12px;border-top:1px solid rgba(255,255,255,.07)}.user-card{display:flex;align-items:center;gap:9px;padding:8px 10px}.user-avatar{width:30px;height:30px;border-radius:8px;display:grid;place-items:center;background:#6c63ff;color:#fff;font-weight:700;font-size:12px;flex-shrink:0}.user-name{font-weight:600;font-size:12px}.user-role{font-size:10px;color:#555b70}.backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1040}.backdrop.show{display:block}@media(max-width:991px){.sidebar{transform:translateX(-100%)}.sidebar.mobile-open{transform:translateX(0)}.main{margin-left:0!important}.header-search{display:none}}</style>
</head>
<body>
<?php include __DIR__ . "/sidebar.php"; ?>
<div class="backdrop" id="backdrop"></div>
<div class="main" id="main">
<header class="header">
<button class="header-toggle" id="toggleSidebar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
<div class="header-title"><h1><?= htmlspecialchars($pageTitle) ?></h1><p><?= date("l, F d, Y") ?></p></div>
<div class="header-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg><input type="text" placeholder="Search..."></div>
<div class="header-avatar"><?= isset($_SESSION['username'])?strtoupper(substr($_SESSION['username'],0,1)):'A' ?></div>
</header>
<div class="content">
