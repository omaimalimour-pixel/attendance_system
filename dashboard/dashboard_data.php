<?php
date_default_timezone_set('Africa/Casablanca');
include "../db.php";

$selectedDate = date("Y-m-d");
if (!empty($_GET['date'])) $selectedDate = $_GET['date'];

$totalEmployees = 0; $totalPresent = 0; $totalPunches = 0; $totalAbsent = 0; $attendanceRate = 0; $totalLate = 0;
$weeklyLabels = []; $weeklyData = [];

$r = @mysqli_query($conn, "SELECT COUNT(*) c FROM employees"); if ($r) $totalEmployees = (int)mysqli_fetch_assoc($r)['c'];
$r = @mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) c FROM attendance WHERE date='$selectedDate'"); if ($r) $totalPresent = (int)mysqli_fetch_assoc($r)['c'];
$r = @mysqli_query($conn, "SELECT COUNT(*) c FROM attendance WHERE date='$selectedDate'"); if ($r) $totalPunches = (int)mysqli_fetch_assoc($r)['c'];
$totalAbsent = max(0, $totalEmployees - $totalPresent);
if ($totalEmployees > 0) $attendanceRate = round(($totalPresent / $totalEmployees) * 100);
$r = @mysqli_query($conn, "SELECT COUNT(*) c FROM (SELECT user_id,MIN(time) t FROM attendance WHERE date='$selectedDate' GROUP BY user_id) x WHERE t>'09:00:00'"); if ($r) $totalLate = (int)mysqli_fetch_assoc($r)['c'];

for ($i = 6; $i >= 0; $i--) {
    $d = date("Y-m-d", strtotime("-$i days"));
    $weeklyLabels[] = date("D", strtotime($d));
    $p = 0; $r = @mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) c FROM attendance WHERE date='$d'"); if ($r) $p = (int)mysqli_fetch_assoc($r)['c'];
    $weeklyData[] = $totalEmployees > 0 ? round(($p/$totalEmployees)*100) : 0;
}
?>
