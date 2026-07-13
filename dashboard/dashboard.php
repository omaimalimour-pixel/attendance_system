<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../db.php';

/* ============================================
   Configuration
============================================ */

$startDate = '2026-07-09';

/* ============================================
   Date sélectionnée
============================================ */

if(isset($_GET['date']) && $_GET['date'] != ''){
    $selectedDate = $_GET['date'];
}else{
    $selectedDate = date("Y-m-d");
}

if($selectedDate < $startDate){
    $selectedDate = $startDate;
}

/* ============================================
   Total employés
============================================ */

$sqlEmployees = "
SELECT COUNT(*) AS total
FROM employees
";

$resultEmployees = mysqli_query($conn,$sqlEmployees);

$rowEmployees = mysqli_fetch_assoc($resultEmployees);

$totalEmployees = $rowEmployees['total'];

/* ============================================
   Employés présents
============================================ */

$sqlPresent = "
SELECT COUNT(DISTINCT user_id) AS total
FROM attendance
WHERE date='$selectedDate'
AND type='IN'
AND date>='$startDate'
";

$resultPresent = mysqli_query($conn,$sqlPresent);

$rowPresent = mysqli_fetch_assoc($resultPresent);

$totalPresent = $rowPresent['total'];

/* ============================================
   Nombre total de pointages
============================================ */

$sqlAttendanceCount = "
SELECT COUNT(*) AS total
FROM attendance
WHERE date='$selectedDate'
AND date>='$startDate'
";

$resultAttendanceCount = mysqli_query($conn,$sqlAttendanceCount);

$rowAttendanceCount = mysqli_fetch_assoc($resultAttendanceCount);

$totalAttendance = $rowAttendanceCount['total'];

/* ============================================
   Machine
============================================ */

$sqlDevice = "
SELECT DISTINCT device_id
FROM attendance
LIMIT 1
";

$resultDevice = mysqli_query($conn,$sqlDevice);

$rowDevice = mysqli_fetch_assoc($resultDevice);

$device = "ZKTeco";

if($rowDevice){
    $device = $rowDevice['device_id'];
}

/* ============================================
   Tableau des présences
============================================ */

$sqlAttendanceTable = "

SELECT

e.user_id,
e.first_name,
e.last_name,

MIN(CASE
WHEN a.type='IN'
THEN a.time
END) AS first_in,

MAX(CASE
WHEN a.type='OUT'
THEN a.time
END) AS last_out,

COUNT(a.id) AS punches

FROM employees e

LEFT JOIN attendance a

ON e.user_id=a.user_id

AND a.date='$selectedDate'

AND a.date>='$startDate'

GROUP BY

e.user_id,
e.first_name,
e.last_name

ORDER BY

e.first_name ASC

";

$resultAttendanceTable = mysqli_query($conn,$sqlAttendanceTable);

?>
<!DOCTYPE html>
<!--
  ============================================================================
  ATTENDANCE DASHBOARD — PREMIUM UI/UX REDESIGN (Bootstrap 5 only)
  ============================================================================
  HOW TO USE WITH YOUR EXISTING PHP:
  - This file is a pure PRESENTATION layer. No logic changed.
  - Everywhere you see a  "PHP:"  note, replace the sample value with your
    existing PHP echo. Examples are given inline, e.g.:
        Total Employees big number ->  <?php echo $totalEmployees; ?>
  - Keep all your existing form actions, query params, and endpoints exactly
    as they are. Only the markup/classes are new.
  - Rename this to dashboard.php and paste your PHP back in the marked spots.
  ============================================================================
-->
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Attendance Dashboard</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Inter font -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <!-- Lucide icons -->
  <script src="https://unpkg.com/lucide@latest"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

  <style>
    :root {
      --primary: #2563EB;
      --primary-dark: #1D4ED8;
      --primary-soft: #EFF4FF;
      --success: #22C55E;
      --success-soft: #E9FBF0;
      --warning: #F59E0B;
      --warning-soft: #FEF6E7;
      --danger: #EF4444;
      --danger-soft: #FDECEC;
      --bg: #F8FAFC;
      --card: #FFFFFF;
      --border: #EAEEF3;
      --text: #0F172A;
      --muted: #64748B;
      --muted-2: #94A3B8;
      --radius: 16px;
      --sidebar-w: 264px;
      --sidebar-w-collapsed: 82px;
      --shadow-sm: 0 1px 2px rgba(15, 23, 42, .04), 0 1px 3px rgba(15, 23, 42, .06);
      --shadow-md: 0 4px 16px rgba(15, 23, 42, .06), 0 2px 6px rgba(15, 23, 42, .04);
      --shadow-lg: 0 18px 40px rgba(15, 23, 42, .10);
    }

    * { -webkit-font-smoothing: antialiased; }

    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color: var(--text);
      margin: 0;
      font-size: 14px;
    }

    ::selection { background: rgba(37,99,235,.18); }

    /* ---------- SIDEBAR ---------- */
    .app-sidebar {
      position: fixed;
      top: 0; left: 0; bottom: 0;
      width: var(--sidebar-w);
      background: #fff;
      border-right: 1px solid var(--border);
      padding: 22px 16px;
      display: flex;
      flex-direction: column;
      z-index: 1040;
      transition: width .28s cubic-bezier(.4,0,.2,1);
    }
    .app-sidebar.collapsed { width: var(--sidebar-w-collapsed); }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 6px 8px 22px;
    }
    .brand .logo {
      width: 40px; height: 40px;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--primary), #4F86FF);
      display: grid; place-items: center;
      color: #fff; flex: 0 0 auto;
      box-shadow: 0 6px 16px rgba(37,99,235,.35);
    }
    .brand .brand-name { font-weight: 800; font-size: 18px; letter-spacing: -.3px; }
    .brand .brand-sub { font-size: 11px; color: var(--muted-2); font-weight: 500; }
    .collapsed .brand .brand-text { display: none; }

    .nav-section-label {
      font-size: 11px; font-weight: 600; letter-spacing: .06em;
      text-transform: uppercase; color: var(--muted-2);
      padding: 14px 12px 8px;
    }
    .collapsed .nav-section-label { opacity: 0; height: 8px; padding: 0; overflow: hidden; }

    .side-link {
      display: flex; align-items: center; gap: 12px;
      padding: 11px 12px;
      border-radius: 12px;
      color: var(--muted);
      font-weight: 500;
      text-decoration: none;
      margin-bottom: 4px;
      position: relative;
      transition: background .18s, color .18s, transform .18s;
      white-space: nowrap;
    }
    .side-link svg { width: 20px; height: 20px; flex: 0 0 auto; }
    .side-link:hover { background: var(--bg); color: var(--text); transform: translateX(2px); }
    .side-link.active {
      background: var(--primary-soft);
      color: var(--primary);
      font-weight: 600;
    }
    .side-link.active::before {
      content: ""; position: absolute; left: -16px; top: 50%;
      transform: translateY(-50%);
      width: 4px; height: 22px; border-radius: 0 4px 4px 0;
      background: var(--primary);
    }
    .collapsed .side-link .link-text { display: none; }
    .collapsed .side-link { justify-content: center; }

    .sidebar-footer { margin-top: auto; }
    .user-mini {
      display: flex; align-items: center; gap: 10px;
      padding: 10px; border-radius: 12px; border: 1px solid var(--border);
      background: var(--bg);
    }
    .collapsed .user-mini .um-text { display: none; }
    .collapsed .user-mini { justify-content: center; }

    /* ---------- MAIN ---------- */
    .app-main {
      margin-left: var(--sidebar-w);
      transition: margin-left .28s cubic-bezier(.4,0,.2,1);
      min-height: 100vh;
    }
    .app-main.expanded { margin-left: var(--sidebar-w-collapsed); }

    /* ---------- HEADER ---------- */
    .app-header {
      position: sticky; top: 0; z-index: 1030;
      background: rgba(255,255,255,.72);
      backdrop-filter: saturate(180%) blur(14px);
      -webkit-backdrop-filter: saturate(180%) blur(14px);
      border-bottom: 1px solid var(--border);
      padding: 14px 28px;
      display: flex; align-items: center; gap: 18px;
    }
    .hdr-title h1 { font-size: 20px; font-weight: 800; margin: 0; letter-spacing: -.4px; }
    .hdr-title p { margin: 0; font-size: 12.5px; color: var(--muted); font-weight: 500; }

    .icon-btn {
      width: 40px; height: 40px; border-radius: 12px;
      border: 1px solid var(--border); background: #fff;
      display: grid; place-items: center; color: var(--muted);
      cursor: pointer; position: relative;
      transition: background .18s, color .18s, box-shadow .18s;
    }
    .icon-btn:hover { background: var(--bg); color: var(--text); box-shadow: var(--shadow-sm); }
    .icon-btn svg { width: 20px; height: 20px; }
    .dot-badge {
      position: absolute; top: 8px; right: 9px;
      width: 8px; height: 8px; border-radius: 50%;
      background: var(--danger); border: 2px solid #fff;
    }

    .search-wrap { position: relative; max-width: 340px; width: 100%; }
    .search-wrap svg {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      width: 18px; height: 18px; color: var(--muted-2);
    }
    .search-wrap input {
      width: 100%; height: 42px; border-radius: 12px;
      border: 1px solid var(--border); background: #fff;
      padding: 0 14px 0 42px; font-size: 13.5px; color: var(--text);
      transition: border-color .18s, box-shadow .18s;
    }
    .search-wrap input:focus {
      outline: none; border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(37,99,235,.12);
    }

    .avatar {
      width: 40px; height: 40px; border-radius: 12px;
      background: linear-gradient(135deg, #2563EB, #60A5FA);
      color: #fff; font-weight: 700; font-size: 14px;
      display: grid; place-items: center; cursor: pointer;
    }

    /* ---------- CONTENT ---------- */
    .app-content { padding: 26px 28px 40px; }

    .section-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
    .section-head h2 { font-size: 17px; font-weight: 700; margin: 0; letter-spacing: -.3px; }
    .section-head .muted { font-size: 13px; color: var(--muted); }

    /* KPI CARDS */
    .kpi-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 20px;
      box-shadow: var(--shadow-sm);
      transition: transform .22s cubic-bezier(.4,0,.2,1), box-shadow .22s;
      position: relative; overflow: hidden; height: 100%;
    }
    .kpi-card::after {
      content: ""; position: absolute; inset: 0;
      background: linear-gradient(135deg, transparent 60%, rgba(37,99,235,.05));
      opacity: 0; transition: opacity .22s;
    }
    .kpi-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
    .kpi-card:hover::after { opacity: 1; }

    .kpi-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .kpi-icon {
      width: 46px; height: 46px; border-radius: 13px;
      display: grid; place-items: center;
    }
    .kpi-icon svg { width: 22px; height: 22px; }
    .ic-blue   { background: var(--primary-soft); color: var(--primary); }
    .ic-green  { background: var(--success-soft); color: var(--success); }
    .ic-red    { background: var(--danger-soft);  color: var(--danger); }
    .ic-amber  { background: var(--warning-soft); color: var(--warning); }

    .kpi-label { font-size: 13px; color: var(--muted); font-weight: 500; }
    .kpi-value { font-size: 32px; font-weight: 800; letter-spacing: -1px; line-height: 1.1; margin: 2px 0 4px; }
    .kpi-sub { font-size: 12px; font-weight: 500; display: flex; align-items: center; gap: 5px; }
    .kpi-sub svg { width: 14px; height: 14px; }
    .trend-up { color: var(--success); }
    .trend-down { color: var(--danger); }

    .status-pill {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 12px; font-weight: 600;
      padding: 5px 10px; border-radius: 999px;
    }
    .pill-online { background: var(--success-soft); color: #15803D; }
    .pill-online .live-dot {
      width: 7px; height: 7px; border-radius: 50%; background: var(--success);
      box-shadow: 0 0 0 0 rgba(34,197,94,.5); animation: pulse 1.8s infinite;
    }
    @keyframes pulse {
      0% { box-shadow: 0 0 0 0 rgba(34,197,94,.5); }
      70% { box-shadow: 0 0 0 7px rgba(34,197,94,0); }
      100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
    }

    /* QUICK ACTIONS */
    .qa-btn {
      display: inline-flex; align-items: center; gap: 8px;
      border-radius: 12px; padding: 9px 15px; font-size: 13px; font-weight: 600;
      border: 1px solid var(--border); background: #fff; color: var(--text);
      cursor: pointer; transition: all .18s; text-decoration: none;
    }
    .qa-btn svg { width: 17px; height: 17px; }
    .qa-btn:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
    .qa-primary { background: var(--primary); border-color: var(--primary); color: #fff; }
    .qa-primary:hover { background: var(--primary-dark); color: #fff; }

    /* PANEL / CARD */
    .panel {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow-sm);
      overflow: hidden;
    }
    .panel-head {
      padding: 18px 22px; border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    }
    .panel-head h3 { font-size: 15.5px; font-weight: 700; margin: 0; letter-spacing: -.2px; }
    .panel-head .sub { font-size: 12.5px; color: var(--muted); margin: 2px 0 0; }
    .panel-body { padding: 22px; }

    /* PREMIUM DATE FILTER */
    .filter-bar {
      display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }
    .seg {
      display: inline-flex; background: var(--bg); border: 1px solid var(--border);
      border-radius: 12px; padding: 4px;
    }
    .seg button {
      border: 0; background: transparent; color: var(--muted);
      font-size: 12.5px; font-weight: 600; padding: 7px 14px; border-radius: 9px;
      cursor: pointer; transition: all .15s;
    }
    .seg button.active { background: #fff; color: var(--primary); box-shadow: var(--shadow-sm); }
    .date-field {
      display: flex; align-items: center; gap: 8px;
      border: 1px solid var(--border); background: #fff; border-radius: 12px;
      padding: 8px 12px; color: var(--text);
    }
    .date-field svg { width: 17px; height: 17px; color: var(--muted); }
    .date-field input {
      border: 0; outline: 0; font-size: 13px; font-weight: 600; color: var(--text);
      background: transparent; font-family: inherit;
    }

    /* TABLE */
    .table-scroll { max-height: 460px; overflow: auto; border-radius: 0 0 var(--radius) var(--radius); }
    table.att {
      width: 100%; border-collapse: separate; border-spacing: 0;
    }
    table.att thead th {
      position: sticky; top: 0; z-index: 2;
      background: #F9FAFC;
      font-size: 11.5px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase;
      color: var(--muted); text-align: left;
      padding: 13px 22px; border-bottom: 1px solid var(--border);
      white-space: nowrap;
    }
    table.att tbody td {
      padding: 14px 22px; font-size: 13.5px; border-bottom: 1px solid #F1F5F9;
      color: var(--text); vertical-align: middle; white-space: nowrap;
    }
    table.att tbody tr:nth-child(even) { background: #FCFDFE; }
    table.att tbody tr { transition: background .15s; }
    table.att tbody tr:hover { background: var(--primary-soft); }
    table.att tbody tr:last-child td { border-bottom: 0; }

    .emp { display: flex; align-items: center; gap: 12px; }
    .emp-av {
      width: 38px; height: 38px; border-radius: 10px; flex: 0 0 auto;
      display: grid; place-items: center; font-weight: 700; font-size: 13px; color: #fff;
    }
    .emp-name { font-weight: 600; }
    .emp-id { font-size: 11.5px; color: var(--muted-2); }
    .mono { font-variant-numeric: tabular-nums; font-weight: 600; }
    .dash { color: var(--muted-2); }

    .badge-status {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 12px; font-weight: 600; padding: 5px 11px; border-radius: 999px;
    }
    .badge-status .bdot { width: 6px; height: 6px; border-radius: 50%; }
    .b-present { background: var(--success-soft); color: #15803D; }
    .b-present .bdot { background: var(--success); }
    .b-absent { background: var(--danger-soft); color: #B91C1C; }
    .b-absent .bdot { background: var(--danger); }
    .b-late { background: var(--warning-soft); color: #B45309; }
    .b-late .bdot { background: var(--warning); }

    .row-action {
      width: 34px; height: 34px; border-radius: 9px; border: 1px solid var(--border);
      background: #fff; display: grid; place-items: center; color: var(--muted); cursor: pointer;
      transition: all .15s;
    }
    .row-action svg { width: 17px; height: 17px; }
    .row-action:hover { color: var(--primary); border-color: var(--primary); background: var(--primary-soft); }

    /* CHART CARDS */
    .chart-box { position: relative; height: 260px; }
    .chart-box-sm { position: relative; height: 220px; }
    .legend-row { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 4px; }
    .legend-item { display: flex; align-items: center; gap: 7px; font-size: 12.5px; color: var(--muted); font-weight: 500; }
    .legend-swatch { width: 10px; height: 10px; border-radius: 3px; }

    .pct-center {
      position: absolute; inset: 0; display: flex; flex-direction: column;
      align-items: center; justify-content: center; pointer-events: none;
    }
    .pct-center .big { font-size: 34px; font-weight: 800; letter-spacing: -1px; }
    .pct-center .small { font-size: 12px; color: var(--muted); font-weight: 500; }

    /* DROPDOWN restyle */
    .dropdown-menu {
      border-radius: 14px; border: 1px solid var(--border);
      box-shadow: var(--shadow-lg); padding: 8px; font-size: 13.5px;
    }
    .dropdown-item { border-radius: 9px; padding: 9px 12px; font-weight: 500; display:flex; align-items:center; gap:10px; }
    .dropdown-item svg { width: 17px; height: 17px; color: var(--muted); }
    .dropdown-item:hover { background: var(--bg); }
    .dropdown-header { font-weight: 700; color: var(--text); }

    /* MOBILE */
    .backdrop {
      display: none; position: fixed; inset: 0; background: rgba(15,23,42,.4);
      z-index: 1039; backdrop-filter: blur(2px);
    }
    @media (max-width: 991.98px) {
      .app-sidebar { transform: translateX(-100%); width: var(--sidebar-w); }
      .app-sidebar.mobile-open { transform: translateX(0); box-shadow: var(--shadow-lg); }
      .app-main { margin-left: 0; }
      .backdrop.show { display: block; }
      .search-wrap { display: none; }
    }
    @media (min-width: 992px) { .mobile-only { display: none !important; } }
  </style>
</head>
<body>

  <!-- ============================ SIDEBAR ============================ -->
  <aside class="app-sidebar" id="sidebar">
    <div class="brand">
      <div class="logo"><i data-lucide="fingerprint"></i></div>
      <div class="brand-text">
        <div class="brand-name">ZKTeco</div>
        <div class="brand-sub">Attendance Suite</div>
      </div>
    </div>

    <div class="nav-section-label">Menu</div>
    <!-- Keep your existing hrefs -->
    <a href="dashboard.php" class="side-link active">
      <i data-lucide="layout-dashboard"></i><span class="link-text">Dashboard</span>
    </a>
    <a href="employees.php" class="side-link">
      <i data-lucide="users"></i><span class="link-text">Employees</span>
    </a>
    <a href="attendance.php" class="side-link">
      <i data-lucide="calendar-check"></i><span class="link-text">Attendance</span>
    </a>
    <a href="users.php" class="side-link">
      <i data-lucide="settings"></i><span class="link-text">Users</span>
    </a>

    <div class="nav-section-label">Reports</div>
    <a href="#" class="side-link">
      <i data-lucide="bar-chart-3"></i><span class="link-text">Analytics</span>
    </a>
    <a href="#" class="side-link">
      <i data-lucide="file-text"></i><span class="link-text">Exports</span>
    </a>

    <div class="sidebar-footer">
      <div class="user-mini">
        <div class="avatar" style="width:36px;height:36px;border-radius:10px;">A</div>
        <div class="um-text">
          <!-- PHP: <?php echo $currentUserName; ?> -->
          <div style="font-weight:600;font-size:13px;">Admin</div>
          <div style="font-size:11px;color:var(--muted-2);">Administrator</div>
        </div>
      </div>
    </div>
  </aside>

  <div class="backdrop" id="backdrop"></div>

  <!-- ============================ MAIN ============================ -->
  <div class="app-main" id="main">

    <!-- HEADER -->
    <header class="app-header">
      <button class="icon-btn" id="toggleSidebar" title="Toggle sidebar">
        <i data-lucide="panel-left"></i>
      </button>

      <div class="hdr-title me-auto">
        <h1>Attendance Dashboard</h1>
        <!-- PHP: <?php echo date('l, F j, Y'); ?> -->
        <p id="todayDate">Friday, July 10, 2026</p>
      </div>

      <div class="search-wrap">
        <i data-lucide="search"></i>
        <input type="text" placeholder="Search employees, records…" />
      </div>

      <button class="icon-btn" title="Notifications">
        <i data-lucide="bell"></i>
        <span class="dot-badge"></span>
      </button>

      <!-- Profile dropdown -->
      <div class="dropdown">
        <div class="avatar" data-bs-toggle="dropdown" aria-expanded="false">A</div>
        <ul class="dropdown-menu dropdown-menu-end">
          <li class="dropdown-header">
            <!-- PHP: <?php echo $currentUserName; ?> -->
            Admin<br><span style="font-weight:400;color:var(--muted);font-size:12px;">admin@company.com</span>
          </li>
          <li><hr class="dropdown-divider" /></li>
          <li><a class="dropdown-item" href="#"><i data-lucide="user"></i> Profile</a></li>
          <li><a class="dropdown-item" href="#"><i data-lucide="settings"></i> Settings</a></li>
          <li><hr class="dropdown-divider" /></li>
          <!-- Keep your existing logout endpoint -->
          <li><a class="dropdown-item" href="logout.php" style="color:var(--danger);"><i data-lucide="log-out"></i> Sign out</a></li>
        </ul>
      </div>
    </header>

    <!-- CONTENT -->
    <div class="app-content">

      <!-- ===== TOP BAR: DATE FILTER + QUICK ACTIONS ===== -->
      <div class="section-head">
        <!--
          PREMIUM DATE FILTER — keep your existing GET flow.
          Your original: ?date=2026-07-10 submitted via "Show".
          This form keeps the same field name & submit behavior.
        -->
        <form method="get" action="dashboard.php" class="filter-bar" id="dateForm">
          <div class="seg" role="group" aria-label="Quick date range">
            <button type="button" class="active" data-range="today">Today</button>
            <button type="button" data-range="week">This Week</button>
            <button type="button" data-range="month">This Month</button>
          </div>
          <div class="date-field">
            <i data-lucide="calendar"></i>
            <!-- PHP: value="<?php echo $selectedDate; ?>" -->
            <input type="date" name="date" value="2026-07-10" />
          </div>
          <button type="submit" class="qa-btn qa-primary">
            <i data-lucide="filter"></i> Apply
          </button>
        </form>

        <!-- ===== QUICK ACTIONS ===== -->
        <div class="filter-bar">
          <!-- Point each to your existing endpoints -->
          <a href="sync_device.php" class="qa-btn qa-primary"><i data-lucide="refresh-cw"></i> Sync Device</a>
          <a href="add_employee.php" class="qa-btn"><i data-lucide="user-plus"></i> Add Employee</a>
          <a href="export_excel.php" class="qa-btn"><i data-lucide="sheet"></i> Excel</a>
          <a href="export_pdf.php" class="qa-btn"><i data-lucide="file-down"></i> PDF</a>
          <a href="dashboard.php" class="qa-btn"><i data-lucide="rotate-cw"></i> Refresh</a>
        </div>
      </div>

      <!-- ===== KPI CARDS ===== -->
      <div class="row g-3 mb-4">
        <!-- Total Employees -->
        <div class="col-12 col-sm-6 col-xl">
          <div class="kpi-card">
            <div class="kpi-top">
              <div class="kpi-icon ic-blue"><i data-lucide="users"></i></div>
              <span class="kpi-sub trend-up"><i data-lucide="trending-up"></i> Active</span>
            </div>
            <div class="kpi-label">Total Employees</div>
            <!-- PHP: <?php echo $totalEmployees; ?> -->
            <div class="kpi-value">2</div>
            <div class="kpi-sub" style="color:var(--muted);">Registered in system</div>
          </div>
        </div>

        <!-- Present Today -->
        <div class="col-12 col-sm-6 col-xl">
          <div class="kpi-card">
            <div class="kpi-top">
              <div class="kpi-icon ic-green"><i data-lucide="check-circle-2"></i></div>
              <span class="kpi-sub trend-up"><i data-lucide="trending-up"></i> 50%</span>
            </div>
            <div class="kpi-label">Present Today</div>
            <!-- PHP: <?php echo $presentToday; ?> -->
            <div class="kpi-value" style="color:var(--success);">1</div>
            <div class="kpi-sub" style="color:var(--muted);">Checked in today</div>
          </div>
        </div>

        <!-- Absent Today -->
        <div class="col-12 col-sm-6 col-xl">
          <div class="kpi-card">
            <div class="kpi-top">
              <div class="kpi-icon ic-red"><i data-lucide="user-x"></i></div>
              <span class="kpi-sub trend-down"><i data-lucide="trending-down"></i> 50%</span>
            </div>
            <div class="kpi-label">Absent Today</div>
            <!-- PHP: <?php echo $absentToday; ?> -->
            <div class="kpi-value" style="color:var(--danger);">1</div>
            <div class="kpi-sub" style="color:var(--muted);">Not checked in</div>
          </div>
        </div>

        <!-- Total Punches -->
        <div class="col-12 col-sm-6 col-xl">
          <div class="kpi-card">
            <div class="kpi-top">
              <div class="kpi-icon ic-amber"><i data-lucide="clock"></i></div>
              <span class="kpi-sub" style="color:var(--muted);">Today</span>
            </div>
            <div class="kpi-label">Total Punches</div>
            <!-- PHP: <?php echo $totalPunches; ?> -->
            <div class="kpi-value" style="color:var(--warning);">1</div>
            <div class="kpi-sub" style="color:var(--muted);">Recorded events</div>
          </div>
        </div>

        <!-- Device Status -->
        <div class="col-12 col-sm-6 col-xl">
          <div class="kpi-card">
            <div class="kpi-top">
              <div class="kpi-icon ic-blue"><i data-lucide="server"></i></div>
              <span class="status-pill pill-online"><span class="live-dot"></span> Online</span>
            </div>
            <div class="kpi-label">Connected Device</div>
            <!-- PHP: <?php echo $deviceName; ?> -->
            <div class="kpi-value" style="font-size:24px;color:var(--primary);">ZKTeco</div>
            <div class="kpi-sub" style="color:var(--muted);">
              <!-- PHP: <?php echo $deviceIp; ?> --> 192.168.1.201
            </div>
          </div>
        </div>
      </div>

      <!-- ===== CHARTS ===== -->
      <div class="row g-3 mb-4">
        <div class="col-12 col-lg-5">
          <div class="panel h-100">
            <div class="panel-head">
              <div>
                <h3>Weekly Attendance</h3>
                <p class="sub">Present vs Absent · last 7 days</p>
              </div>
              <span class="status-pill pill-online"><i data-lucide="calendar-days" style="width:14px;height:14px;"></i> This week</span>
            </div>
            <div class="panel-body">
              <div class="chart-box"><canvas id="weeklyChart"></canvas></div>
              <div class="legend-row">
                <span class="legend-item"><span class="legend-swatch" style="background:var(--primary)"></span> Present</span>
                <span class="legend-item"><span class="legend-swatch" style="background:#E2E8F0"></span> Absent</span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-4">
          <div class="panel h-100">
            <div class="panel-head">
              <div>
                <h3>Monthly Trend</h3>
                <p class="sub">Attendance rate over the month</p>
              </div>
            </div>
            <div class="panel-body">
              <div class="chart-box"><canvas id="monthlyChart"></canvas></div>
              <div class="legend-row">
                <span class="legend-item"><span class="legend-swatch" style="background:var(--success)"></span> Attendance rate</span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-3">
          <div class="panel h-100">
            <div class="panel-head">
              <div>
                <h3>Percentage</h3>
                <p class="sub">Today's rate</p>
              </div>
            </div>
            <div class="panel-body">
              <div class="chart-box-sm">
                <canvas id="pctChart"></canvas>
                <div class="pct-center">
                  <div class="big" style="color:var(--primary)">50%</div>
                  <div class="small">Present</div>
                </div>
              </div>
              <div class="legend-row justify-content-center">
                <span class="legend-item"><span class="legend-swatch" style="background:var(--primary)"></span> Present</span>
                <span class="legend-item"><span class="legend-swatch" style="background:#EEF2F7"></span> Absent</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ===== ATTENDANCE TABLE ===== -->
      <div class="panel">
        <div class="panel-head">
          <div>
            <h3>Today's Attendance</h3>
            <p class="sub">Live punch records from connected device</p>
          </div>
          <div class="filter-bar">
            <div class="date-field" style="padding:7px 12px;">
              <i data-lucide="search"></i>
              <input type="text" id="tableSearch" placeholder="Filter…" style="width:120px;" />
            </div>
            <a href="export_excel.php" class="qa-btn"><i data-lucide="download"></i> Export</a>
          </div>
        </div>

        <div class="table-scroll">
          <table class="att">
            <thead>
              <tr>
                <th>Employee</th>
                <th>First IN</th>
                <th>Last OUT</th>
                <th>Work Hours</th>
                <th>Punches</th>
                <th>Status</th>
                <th style="text-align:right;">Action</th>
              </tr>
            </thead>
            <tbody id="attBody">
              <!--
                ============================================================
                PHP LOOP GOES HERE — keep your existing while/foreach.
                Example structure to preserve your variables:

                <?php foreach ($attendance as $row): ?>
                <tr>
                  <td>
                    <div class="emp">
                      <div class="emp-av" style="background: <?php echo stringToColor($row['name']); ?>">
                        <?php echo strtoupper(substr($row['name'],0,1)); ?>
                      </div>
                      <div>
                        <div class="emp-name"><?php echo $row['name']; ?></div>
                        <div class="emp-id">ID #<?php echo $row['emp_id']; ?></div>
                      </div>
                    </div>
                  </td>
                  <td class="mono"><?php echo $row['first_in'] ?: '<span class="dash">--</span>'; ?></td>
                  <td class="mono"><?php echo $row['last_out'] ?: '<span class="dash">--</span>'; ?></td>
                  <td class="mono"><?php echo $row['work_hours'] ?: '<span class="dash">--</span>'; ?></td>
                  <td class="mono"><?php echo $row['punches']; ?></td>
                  <td>
                    <?php if ($row['status']=='Present'): ?>
                      <span class="badge-status b-present"><span class="bdot"></span> Present</span>
                    <?php elseif ($row['status']=='Late'): ?>
                      <span class="badge-status b-late"><span class="bdot"></span> Late</span>
                    <?php else: ?>
                      <span class="badge-status b-absent"><span class="bdot"></span> Absent</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align:right;">
                    <a class="row-action" href="view.php?id=<?php echo $row['emp_id']; ?>"><i data-lucide="eye"></i></a>
                  </td>
                </tr>
                <?php endforeach; ?>
                ============================================================
                Below are static sample rows so you can preview the design.
              -->

              <tr>
                <td>
                  <div class="emp">
                    <div class="emp-av" style="background:#EF4444;">F</div>
                    <div>
                      <div class="emp-name">FIDAE EL Azar</div>
                      <div class="emp-id">ID #1001</div>
                    </div>
                  </div>
                </td>
                <td><span class="dash">--</span></td>
                <td><span class="dash">--</span></td>
                <td><span class="dash">--</span></td>
                <td class="mono">0</td>
                <td><span class="badge-status b-absent"><span class="bdot"></span> Absent</span></td>
                <td style="text-align:right;"><a class="row-action" href="#"><i data-lucide="eye"></i></a></td>
              </tr>

              <tr>
                <td>
                  <div class="emp">
                    <div class="emp-av" style="background:#2563EB;">O</div>
                    <div>
                      <div class="emp-name">OUMAIMA LIMOUR</div>
                      <div class="emp-id">ID #1002</div>
                    </div>
                  </div>
                </td>
                <td class="mono">14:19:11</td>
                <td><span class="dash">--</span></td>
                <td><span class="dash">--</span></td>
                <td class="mono">1</td>
                <td><span class="badge-status b-present"><span class="bdot"></span> Present</span></td>
                <td style="text-align:right;"><a class="row-action" href="#"><i data-lucide="eye"></i></a></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <p class="text-center mt-4 mb-0" style="color:var(--muted-2);font-size:12.5px;">
        Attendance Management System · Powered by ZKTeco
      </p>
    </div>
  </div>

  <!-- ============================ SCRIPTS ============================ -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Render Lucide icons
    lucide.createIcons();

    // Sidebar collapse (desktop) + slide (mobile)
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('main');
    const backdrop = document.getElementById('backdrop');
    document.getElementById('toggleSidebar').addEventListener('click', () => {
      if (window.innerWidth < 992) {
        sidebar.classList.toggle('mobile-open');
        backdrop.classList.toggle('show');
      } else {
        sidebar.classList.toggle('collapsed');
        main.classList.toggle('expanded');
      }
    });
    backdrop.addEventListener('click', () => {
      sidebar.classList.remove('mobile-open');
      backdrop.classList.remove('show');
    });

    // Segmented date range (visual state only — does not alter your form field)
    document.querySelectorAll('.seg button').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.seg button').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
      });
    });

    // Simple client-side table filter (pure presentation helper)
    document.getElementById('tableSearch').addEventListener('input', function () {
      const q = this.value.toLowerCase();
      document.querySelectorAll('#attBody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });

    // ---------- CHARTS ----------
    const gridColor = '#EEF2F7';
    const fontColor = '#94A3B8';
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = fontColor;

    // Weekly Attendance (stacked bars)
    // PHP: feed labels/data from your weekly query arrays.
    new Chart(document.getElementById('weeklyChart'), {
      type: 'bar',
      data: {
        labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        datasets: [
          { label: 'Present', data: [2,1,2,2,1,0,0], backgroundColor: '#2563EB', borderRadius: 8, maxBarThickness: 26 },
          { label: 'Absent',  data: [0,1,0,0,1,2,2], backgroundColor: '#E2E8F0', borderRadius: 8, maxBarThickness: 26 }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { stacked: true, grid: { display: false }, border: { display: false } },
          y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: gridColor }, border: { display: false } }
        }
      }
    });

    // Monthly Trend (line)
    // PHP: feed labels/data from your monthly query arrays.
    const mctx = document.getElementById('monthlyChart').getContext('2d');
    const grad = mctx.createLinearGradient(0, 0, 0, 260);
    grad.addColorStop(0, 'rgba(34,197,94,.22)');
    grad.addColorStop(1, 'rgba(34,197,94,0)');
    new Chart(mctx, {
      type: 'line',
      data: {
        labels: ['W1','W2','W3','W4'],
        datasets: [{
          label: 'Attendance %',
          data: [78, 85, 72, 90],
          borderColor: '#22C55E', backgroundColor: grad,
          fill: true, tension: .4, borderWidth: 3,
          pointBackgroundColor: '#22C55E', pointRadius: 4, pointHoverRadius: 6
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, border: { display: false } },
          y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' }, grid: { color: gridColor }, border: { display: false } }
        }
      }
    });

    // Percentage doughnut
    // PHP: present vs absent counts.
    new Chart(document.getElementById('pctChart'), {
      type: 'doughnut',
      data: {
        labels: ['Present','Absent'],
        datasets: [{ data: [1,1], backgroundColor: ['#2563EB','#EEF2F7'], borderWidth: 0 }]
      },
      options: {
        responsive: true, maintainAspectRatio: false, cutout: '74%',
        plugins: { legend: { display: false } }
      }
    });
  </script>
</body>
</html>
