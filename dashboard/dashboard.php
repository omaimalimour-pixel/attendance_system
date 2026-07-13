<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "dashboard_data.php";

$totalAbsent = $totalEmployees - $totalPresent;

if ($totalAbsent < 0) {
    $totalAbsent = 0;
}

$attendanceRate = 0;

if ($totalEmployees > 0) {
    $attendanceRate = round(($totalPresent / $totalEmployees) * 100);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Attendance Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="preconnect" href="https://fonts.googleapis.com">

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<script src="https://unpkg.com/lucide@latest"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<link rel="stylesheet" href="dashboard.css">

</head>

<body>

<!-- ================= SIDEBAR ================= -->

<aside class="app-sidebar">

<div class="brand">

<div class="logo">
<i data-lucide="fingerprint"></i>
</div>

<div class="brand-text">

<div class="brand-name">
ZKTeco
</div>

<div class="brand-sub">
Attendance Suite
</div>

</div>

</div>

<div class="nav-section-label">
Menu
</div>

<a href="dashboard.php" class="side-link active">
<i data-lucide="layout-dashboard"></i>
<span class="link-text">Dashboard</span>
</a>

<a href="employees.php" class="side-link">
<i data-lucide="users"></i>
<span class="link-text">Employees</span>
</a>

<a href="attendance.php" class="side-link">
<i data-lucide="calendar-check"></i>
<span class="link-text">Attendance</span>
</a>

<a href="users.php" class="side-link">
<i data-lucide="settings"></i>
<span class="link-text">Users</span>
</a>

<div class="nav-section-label">
Reports
</div>

<a href="#" class="side-link">
<i data-lucide="bar-chart-3"></i>
<span class="link-text">Analytics</span>
</a>

<a href="#" class="side-link">
<i data-lucide="file-text"></i>
<span class="link-text">Exports</span>
</a>

<div class="sidebar-footer">

<div class="user-mini">

<div class="avatar">
A
</div>

<div class="um-text">

<div style="font-weight:600">
Admin
</div>

<div style="font-size:12px;color:gray">
Administrator
</div>

</div>

</div>

</div>

</aside>

<div class="app-main">

<header class="app-header">

<button class="icon-btn" id="toggleSidebar">
<i data-lucide="panel-left"></i>
</button>

<div class="hdr-title me-auto">

<h1>
Attendance Dashboard
</h1>

<p>
<?= date("l, F d, Y") ?>
</p>

</div>

<div class="search-wrap">

<i data-lucide="search"></i>

<input type="text" placeholder="Search...">

</div>

<div class="avatar">
A
</div>

</header>

<div class="app-content">

<div class="section-head">

<form method="GET" action="dashboard.php" class="filter-bar">

<div class="date-field">

<i data-lucide="calendar"></i>

<input
type="date"
name="date"
value="<?= $selectedDate ?>"
>

</div>

<button class="qa-btn qa-primary">

<i data-lucide="filter"></i>

Apply

</button>

</form>

<div class="filter-bar">

<a href="sync_device.php" class="qa-btn qa-primary">

<i data-lucide="refresh-cw"></i>

Sync Device

</a>

<a href="dashboard.php" class="qa-btn">

<i data-lucide="rotate-cw"></i>

Refresh

</a>

</div>

</div>

<!-- ================= KPI CARDS ================= -->

<?php include "dashboard_cards.php"; ?>
<!-- ================= CHARTS ================= -->

<div class="row g-3 mb-4">

    <!-- Monthly Chart -->

    <div class="col-lg-8">

        <div class="panel h-100">

            <div class="panel-head">

                <div>

                    <h3>Monthly Attendance</h3>

                    <p class="sub">
                        Attendance rate over the month
                    </p>

                </div>

            </div>

            <div class="panel-body">

                <div class="chart-box">

                    <canvas id="monthlyChart"></canvas>

                </div>

            </div>

        </div>

    </div>

    <!-- Percentage -->

    <div class="col-lg-4">

        <div class="panel h-100">

            <div class="panel-head">

                <div>

                    <h3>Today's Percentage</h3>

                    <p class="sub">
                        Presence Rate
                    </p>

                </div>

            </div>

            <div class="panel-body">

                <div class="chart-box-sm">

                    <canvas id="pctChart"></canvas>

                    <div class="pct-center">

                        <div class="big">

                            <?= $attendanceRate ?>%

                        </div>

                        <div class="small">

                            Present

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<!-- ================= TABLE ================= -->

<?php include "dashboard_table.php"; ?>

<p class="text-center mt-4 mb-0"
style="color:var(--muted-2);font-size:13px;">

Attendance Management System · Powered by ZKTeco

</p>

</div>

</div>

<!-- Bootstrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Lucide -->

<script src="https://unpkg.com/lucide@latest"></script>

<script>

lucide.createIcons();

</script>

<!-- Dashboard JS -->

<script src="dashboard.js"></script>

</body>

</html>