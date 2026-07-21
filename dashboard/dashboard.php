<?php
session_start();
date_default_timezone_set('Africa/Casablanca');
$pageTitle="Dashboard"; $currentPage="dashboard";
include "dashboard_data.php";
include "includes/header.php";
$hour=(int)date("G");
$greet=$hour<12?"Good morning":($hour<18?"Good afternoon":"Good evening");
$who=isset($_SESSION['username'])?htmlspecialchars($_SESSION['username']):'Admin';
?>
<!-- WELCOME -->
<div class="welcome">
  <div class="welcome-inner">
    <div>
      <h2><?=$greet?>, <?=$who?> 👋</h2>
      <p>Here's what's happening with your workforce today. <?=$totalPresent?> of <?=$totalEmployees?> employees are currently checked in.</p>
    </div>
    <div class="w-date"><div class="d"><?=date("d")?></div><div class="m"><?=date("M Y")?></div></div>
  </div>
</div>

<!-- FILTER -->
<div class="page-top">
  <div class="greet"><h2 style="font-size:18px">Overview</h2><p>Attendance summary for <?=date("F j, Y",strtotime($selectedDate))?></p></div>
  <div class="flt">
    <form method="GET" class="flt"><div class="di"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg><input type="date" name="date" value="<?=$selectedDate?>"></div><button class="btn btn-primary">Apply</button></form>
    <a href="sync_attendance.php" class="btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>Sync</a>
  </div>
</div>

<!-- KPI -->
<?php
function spark($seed){$h='';for($i=0;$i<12;$i++){$v=30+(($seed*($i+3))%70);$h.='<i style="height:'.$v.'%"></i>';}return $h;}
?>
<div class="kpi-grid">
  <div class="kpi">
    <div class="kpi-top"><div class="kpi-ic bl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div><span class="kpi-tag nt">Total</span></div>
    <div class="kpi-lbl">Total Employees</div><div class="kpi-val"><?=$totalEmployees?></div>
    <div class="spark"><?=spark(4)?></div>
  </div>
  <div class="kpi">
    <div class="kpi-top"><div class="kpi-ic gr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><span class="kpi-tag up"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg><?=$attendanceRate?>%</span></div>
    <div class="kpi-lbl">Present Today</div><div class="kpi-val" style="color:var(--green)"><?=$totalPresent?></div>
    <div class="spark"><?php for($i=0;$i<12;$i++){$v=40+(($i*7)%55);echo '<i style="height:'.$v.'%;background:linear-gradient(180deg,#0EA372,rgba(14,163,114,.25))"></i>';}?></div>
  </div>
  <div class="kpi">
    <div class="kpi-top"><div class="kpi-ic rd"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="17" y1="8" x2="22" y2="13"/><line x1="22" y1="8" x2="17" y2="13"/></svg></div><span class="kpi-tag dn"><?=100-$attendanceRate?>%</span></div>
    <div class="kpi-lbl">Absent Today</div><div class="kpi-val" style="color:var(--red)"><?=$totalAbsent?></div>
    <div class="spark"><?php for($i=0;$i<12;$i++){$v=25+(($i*5)%45);echo '<i style="height:'.$v.'%;background:linear-gradient(180deg,#E5484D,rgba(229,72,77,.2))"></i>';}?></div>
  </div>
  <div class="kpi">
    <div class="kpi-top"><div class="kpi-ic am"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><span class="kpi-tag nt"><?=$totalLate?> late</span></div>
    <div class="kpi-lbl">Total Punches</div><div class="kpi-val" style="color:var(--amber)"><?=$totalPunches?></div>
    <div class="spark"><?php for($i=0;$i<12;$i++){$v=35+(($i*9)%60);echo '<i style="height:'.$v.'%;background:linear-gradient(180deg,#D98A0B,rgba(217,138,11,.2))"></i>';}?></div>
  </div>
</div>

<!-- CHARTS -->
<div class="row mb">
  <div class="col-8"><div class="panel h-100"><div class="panel-hd"><div><h3>Weekly Attendance</h3><p class="sub">Attendance rate over the last 7 days</p></div><span class="badge b-dp">Live</span></div><div class="panel-bd"><div class="chart-box"><canvas id="wChart"></canvas></div></div></div></div>
  <div class="col-4"><div class="panel h-100"><div class="panel-hd"><div><h3>Today</h3><p class="sub">Presence rate</p></div></div><div class="panel-bd"><div class="chart-sm"><canvas id="pChart"></canvas><div class="pct-c"><div class="big"><?=$attendanceRate?>%</div><div class="sm">Present</div></div></div><div class="legend"><span><i style="background:#5B54E8"></i>Present</span><span><i style="background:#ECEEF3"></i>Absent</span></div></div></div></div>
</div>

<?php include "dashboard_table.php"; ?>

<script>window.wL=<?=json_encode($weeklyLabels)?>;window.wD=<?=json_encode($weeklyData)?>;window.aR=<?=$attendanceRate?>;</script>
<?php include "includes/footer.php"; ?>
