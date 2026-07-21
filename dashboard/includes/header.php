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
    <link rel="stylesheet" href="dashboard.css">
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
