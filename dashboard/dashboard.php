<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Casablanca');

$pageTitle = "Attendance Dashboard";
$currentPage = "dashboard";

include "dashboard_data.php";
include "includes/header.php";
?>

<!-- ================= FILTER BAR ================= -->
<div class="section-head">
    <form method="GET" action="dashboard.php" class="filter-bar">
        <div class="date-field">
            <i data-lucide="calendar"></i>
            <input type="date" name="date" value="<?= $selectedDate ?>">
        </div>
        <button type="submit" class="qa-btn qa-primary">
            <i data-lucide="filter"></i> Apply
        </button>
    </form>
    <div class="filter-bar">
        <a href="sync_attendance.php" class="qa-btn qa-primary">
            <i data-lucide="refresh-cw"></i> Sync Device
        </a>
        <a href="dashboard.php" class="qa-btn">
            <i data-lucide="rotate-cw"></i> Refresh
        </a>
    </div>
</div>

<!-- ================= KPI CARDS ================= -->
<?php include "dashboard_cards.php"; ?>

<!-- ================= CHARTS ================= -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="panel h-100">
            <div class="panel-head">
                <div>
                    <h3>Weekly Attendance</h3>
                    <p class="sub">Attendance rate during the last 7 days</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="chart-box">
                    <canvas id="monthlyChart"></canvas>
                </div>
                <div class="legend-row">
                    <span class="legend-item">
                        <span class="legend-swatch" style="background:var(--success)"></span>
                        Attendance rate
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="panel h-100">
            <div class="panel-head">
                <div>
                    <h3>Today's Percentage</h3>
                    <p class="sub">Presence Rate</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="chart-box-sm">
                    <canvas id="pctChart"></canvas>
                    <div class="pct-center">
                        <div class="big" style="color:var(--primary);">
                            <?= $attendanceRate ?>%
                        </div>
                        <div class="small">Present</div>
                    </div>
                </div>
                <div class="legend-row justify-content-center">
                    <span class="legend-item">
                        <span class="legend-swatch" style="background:var(--primary)"></span>
                        Present
                    </span>
                    <span class="legend-item">
                        <span class="legend-swatch" style="background:#EEF2F7"></span>
                        Absent
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= ATTENDANCE TABLE ================= -->
<?php include "dashboard_table.php"; ?>

<!-- Chart Data from PHP -->
<script>
    window.weeklyLabels = <?= json_encode($weeklyLabels) ?>;
    window.weeklyData = <?= json_encode($weeklyData) ?>;
    window.attendanceRate = <?= $attendanceRate ?>;
</script>

<?php include "includes/footer.php"; ?>
