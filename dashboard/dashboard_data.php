<?php
/**
 * Dashboard KPIs + weekly trend — core db helpers (prepared statements).
 * Assumes bootstrap.php already loaded the core.
 */
$selectedDate = date("Y-m-d");
if (!empty($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) $selectedDate = $_GET['date'];

$workStart = work_start();

$totalEmployees = (int) db_val("SELECT COUNT(*) FROM employees");
$totalPresent   = (int) db_val("SELECT COUNT(DISTINCT user_id) FROM attendance WHERE date=?", [$selectedDate]);
$totalPunches   = (int) db_val("SELECT COUNT(*) FROM attendance WHERE date=?", [$selectedDate]);
$totalAbsent    = max(0, $totalEmployees - $totalPresent);
$attendanceRate = $totalEmployees > 0 ? round(($totalPresent / $totalEmployees) * 100) : 0;
$totalLate      = (int) db_val(
    "SELECT COUNT(*) FROM (SELECT user_id, MIN(time) t FROM attendance WHERE date=? GROUP BY user_id) x WHERE x.t > ?",
    [$selectedDate, $workStart]
);

$weeklyLabels = [];
$weeklyData = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date("Y-m-d", strtotime("-$i days", strtotime($selectedDate)));
    $weeklyLabels[] = date("D", strtotime($d));
    $p = (int) db_val("SELECT COUNT(DISTINCT user_id) FROM attendance WHERE date=?", [$d]);
    $weeklyData[] = $totalEmployees > 0 ? round(($p / $totalEmployees) * 100) : 0;
}

// Fingerprint enrolment pending count (for dashboard KPI)
$pendingEnrollments = 0;
if (db_table_exists('enrollment_requests')) {
    $pendingEnrollments = (int) db_val("SELECT COUNT(*) FROM enrollment_requests WHERE status IN ('pending','failed')");
}
