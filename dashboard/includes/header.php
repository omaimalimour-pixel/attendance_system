<?php
if(!isset($pageTitle)) $pageTitle = "Dashboard";
if(!isset($currentPage)) $currentPage = "dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - ZKTeco Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="backdrop" id="backdrop"></div>

<div class="app-main" id="main">

<header class="app-header">
    <button class="icon-btn" id="toggleSidebar" title="Toggle Sidebar">
        <i data-lucide="panel-left"></i>
    </button>
    <div class="hdr-title me-auto">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        <p><?= date("l, F d, Y") ?></p>
    </div>
    <div class="search-wrap">
        <i data-lucide="search"></i>
        <input type="text" placeholder="Search..." id="globalSearch">
    </div>
    <div class="header-actions">
        <button class="icon-btn" title="Notifications">
            <i data-lucide="bell"></i>
            <span class="dot-badge"></span>
        </button>
    </div>
    <div class="avatar header-avatar">
        <?= isset($_SESSION['username']) ? strtoupper(substr($_SESSION['username'],0,1)) : 'A' ?>
    </div>
</header>

<div class="app-content">
