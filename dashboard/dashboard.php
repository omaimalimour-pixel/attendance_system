<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Casablanca');

$pageTitle = "Dashboard";
$currentPage = "dashboard";

include "dashboard_data.php";
include "includes/header.php";
?>

<!-- Filter -->
<div class="page-top">
    <form method="GET" action="dashboard.php" class="filter-bar">
        <div class="date-input">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <input type="date" name="date" value="<?= $selectedDate ?>">
        </div>
        <button type="submit" class="btn btn-primary">Apply</button>
    </form>
    <div class="filter-bar">
        <a href="sync_attendance.php" class="btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg> Sync</a>
        <a href="dashboard.php" class="btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg> Refresh</a>
    </div>
</div>

<!-- KPI Cards -->
<div class="kpi-grid">
    <div class="kpi">
        <div class="kpi-header">
            <div class="kpi-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
            <span class="kpi-badge up">Active</span>
        </div>
        <div class="kpi-label">Total Employees</div>
        <div class="kpi-value"><?= $totalEmployees ?></div>
        <div class="kpi-sub">Registered in system</div>
    </div>
    <div class="kpi">
        <div class="kpi-header">
            <div class="kpi-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <span class="kpi-badge up"><?= $attendanceRate ?>%</span>
        </div>
        <div class="kpi-label">Present Today</div>
        <div class="kpi-value" style="color:var(--green)"><?= $totalPresent ?></div>
        <div class="kpi-sub">Checked in today</div>
    </div>
    <div class="kpi">
        <div class="kpi-header">
            <div class="kpi-icon rose"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="17" y1="8" x2="23" y2="8"/></svg></div>
            <span class="kpi-badge down"><?= 100 - $attendanceRate ?>%</span>
        </div>
        <div class="kpi-label">Absent Today</div>
        <div class="kpi-value" style="color:var(--rose)"><?= $totalAbsent ?></div>
        <div class="kpi-sub">Not checked in</div>
    </div>
    <div class="kpi">
        <div class="kpi-header">
            <div class="kpi-icon amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <span class="kpi-badge">Today</span>
        </div>
        <div class="kpi-label">Total Punches</div>
        <div class="kpi-value" style="color:var(--amber)"><?= $totalPunches ?></div>
        <div class="kpi-sub">Recorded events</div>
    </div>
</div>

<!-- Charts -->
<div class="row mb-3">
    <div class="col-8">
        <div class="panel h-100">
            <div class="panel-head"><div><h3>Weekly Attendance</h3><p class="sub">Last 7 days trend</p></div></div>
            <div class="panel-body">
                <div class="chart-box"><canvas id="weeklyChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="panel h-100">
            <div class="panel-head"><div><h3>Today's Rate</h3><p class="sub">Presence percentage</p></div></div>
            <div class="panel-body">
                <div class="chart-box-sm">
                    <canvas id="pctChart"></canvas>
                    <div class="pct-center">
                        <div class="big"><?= $attendanceRate ?>%</div>
                        <div class="small">Present</div>
                    </div>
                </div>
                <div class="legend-row">
                    <span class="legend-item"><span class="legend-dot" style="background:var(--indigo)"></span>Present</span>
                    <span class="legend-item"><span class="legend-dot" style="background:var(--surface-3)"></span>Absent</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Table -->
<?php include "dashboard_table.php"; ?>

<script>
window.weeklyLabels = <?= json_encode($weeklyLabels) ?>;
window.weeklyData = <?= json_encode($weeklyData) ?>;
window.attendanceRate = <?= $attendanceRate ?>;
</script>

<?php include "includes/footer.php"; ?>
