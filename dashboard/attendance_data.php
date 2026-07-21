<?php
date_default_timezone_set('Africa/Casablanca');

$selectedDate = date("Y-m-d");
$search = "";
$statusFilter = "";
$totalEmployees = 0;
$totalPunches = 0;
$totalPresent = 0;
$totalAbsent = 0;
$attendanceRate = 0;
$totalLate = 0;

if (!empty($_GET['date'])) $selectedDate = $_GET['date'];
if (!empty($_GET['search'])) $search = mysqli_real_escape_string($conn, trim($_GET['search']));
if (!empty($_GET['status'])) $statusFilter = $_GET['status'];

$r = @mysqli_query($conn, "SELECT COUNT(*) c FROM employees"); if ($r) $totalEmployees = (int)mysqli_fetch_assoc($r)['c'];
$r = @mysqli_query($conn, "SELECT COUNT(*) c FROM attendance WHERE date='$selectedDate'"); if ($r) $totalPunches = (int)mysqli_fetch_assoc($r)['c'];
$r = @mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) c FROM attendance WHERE date='$selectedDate'"); if ($r) $totalPresent = (int)mysqli_fetch_assoc($r)['c'];
$totalAbsent = max(0, $totalEmployees - $totalPresent);
if ($totalEmployees > 0) $attendanceRate = round(($totalPresent / $totalEmployees) * 100);
$r = @mysqli_query($conn, "SELECT COUNT(*) c FROM (SELECT user_id,MIN(time) t FROM attendance WHERE date='$selectedDate' GROUP BY user_id) x WHERE t>'09:00:00'"); if ($r) $totalLate = (int)mysqli_fetch_assoc($r)['c'];

$sql = "SELECT employees.id,employees.user_id,employees.first_name,employees.last_name,employees.department,employees.position,MIN(attendance.time) AS first_in,MAX(attendance.time) AS last_out,COUNT(attendance.id) AS punches FROM employees LEFT JOIN attendance ON employees.user_id=attendance.user_id AND attendance.date='$selectedDate' WHERE 1=1";
if ($search !== '') $sql .= " AND (employees.user_id LIKE '%$search%' OR employees.first_name LIKE '%$search%' OR employees.last_name LIKE '%$search%' OR employees.department LIKE '%$search%')";
$sql .= " GROUP BY employees.user_id ORDER BY employees.first_name";
$resultAttendance = @mysqli_query($conn, $sql);
?>
