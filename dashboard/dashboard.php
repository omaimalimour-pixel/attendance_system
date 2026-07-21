<?php
session_start();
date_default_timezone_set('Africa/Casablanca');
$pageTitle="Dashboard";$currentPage="dashboard";
include "dashboard_data.php";
include "includes/header.php";
?>
<div class="page-top">
<form method="GET" class="flt"><div class="di"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg><input type="date" name="date" value="<?=$selectedDate?>"></div><button class="btn btn-primary">Apply</button></form>
<div class="flt"><a href="sync_attendance.php" class="btn">Sync</a><a href="dashboard.php" class="btn">Refresh</a></div>
</div>
<div class="kpi-grid">
<div class="kpi"><div class="kpi-top"><div class="kpi-ic bl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><span class="kpi-tag up">Active</span></div><div class="kpi-lbl">Total Employees</div><div class="kpi-val"><?=$totalEmployees?></div><div class="kpi-sub">Registered</div></div>
<div class="kpi"><div class="kpi-top"><div class="kpi-ic gr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><span class="kpi-tag up"><?=$attendanceRate?>%</span></div><div class="kpi-lbl">Present</div><div class="kpi-val" style="color:var(--green)"><?=$totalPresent?></div><div class="kpi-sub">Today</div></div>
<div class="kpi"><div class="kpi-top"><div class="kpi-ic rd"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div><span class="kpi-tag dn"><?=100-$attendanceRate?>%</span></div><div class="kpi-lbl">Absent</div><div class="kpi-val" style="color:var(--red)"><?=$totalAbsent?></div><div class="kpi-sub">Today</div></div>
<div class="kpi"><div class="kpi-top"><div class="kpi-ic am"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div></div><div class="kpi-lbl">Punches</div><div class="kpi-val" style="color:var(--amber)"><?=$totalPunches?></div><div class="kpi-sub">Events today</div></div>
</div>
<div class="row mb">
<div class="col-8"><div class="panel h100"><div class="panel-hd"><div><h3>Weekly Trend</h3><p class="sub">Last 7 days</p></div></div><div class="panel-bd"><div class="chart-box"><canvas id="wChart"></canvas></div></div></div></div>
<div class="col-4"><div class="panel h100"><div class="panel-hd"><div><h3>Today</h3><p class="sub">Presence</p></div></div><div class="panel-bd"><div class="chart-sm"><canvas id="pChart"></canvas><div class="pct-c"><div class="big"><?=$attendanceRate?>%</div><div class="sm">Present</div></div></div><div class="legend"><span><i style="background:var(--accent)"></i>Present</span><span><i style="background:rgba(255,255,255,.06)"></i>Absent</span></div></div></div></div>
</div>
<?php include "dashboard_table.php";?>
<script>window.wL=<?=json_encode($weeklyLabels)?>;window.wD=<?=json_encode($weeklyData)?>;window.aR=<?=$attendanceRate?>;</script>
<?php include "includes/footer.php";?>
