<?php
date_default_timezone_set('Africa/Casablanca');
include "../db.php";

$selectedDate = date("Y-m-d");
if (isset($_GET['date']) && $_GET['date'] !== '') { $selectedDate = $_GET['date']; }

$totalEmployees = 0; $totalPresent = 0; $totalPunches = 0; $totalAbsent = 0; $attendanceRate = 0; $totalLate = 0;
$device = "ZKTeco"; $weeklyLabels = []; $weeklyData = [];

$res = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM employees");
if ($res) $totalEmployees = (int)mysqli_fetch_assoc($res)['c'];

$res = @mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) AS c FROM attendance WHERE date='$selectedDate'");
if ($res) $totalPresent = (int)mysqli_fetch_assoc($res)['c'];

$res = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM attendance WHERE date='$selectedDate'");
if ($res) $totalPunches = (int)mysqli_fetch_assoc($res)['c'];

$totalAbsent = max(0, $totalEmployees - $totalPresent);
if ($totalEmployees > 0) $attendanceRate = round(($totalPresent / $totalEmployees) * 100);

$res = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM (SELECT user_id, MIN(time) AS t FROM attendance WHERE date='$selectedDate' GROUP BY user_id) x WHERE x.t > '09:00:00'");
if ($res) $totalLate = (int)mysqli_fetch_assoc($res)['c'];

for ($i = 6; $i >= 0; $i--) {
    $d = date("Y-m-d", strtotime("-$i days"));
    $weeklyLabels[] = date("D", strtotime($d));
    $p = 0;
    $res = @mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) AS c FROM attendance WHERE date='$d'");
    if ($res) $p = (int)mysqli_fetch_assoc($res)['c'];
    $weeklyData[] = $totalEmployees > 0 ? round(($p / $totalEmployees) * 100) : 0;
}
?>
