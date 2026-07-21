<?php
include "../db.php";

/*====================================================
    DATE SELECTED
====================================================*/

$selectedDate = isset($_GET['date']) && $_GET['date'] != ""
    ? $_GET['date']
    : date("Y-m-d");

/*====================================================
    TOTAL EMPLOYEES
====================================================*/

$sql = "SELECT COUNT(*) AS total FROM employees";
$res = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($res);
$totalEmployees = (int)$row['total'];

/*====================================================
    PRESENT EMPLOYEES
====================================================*/

$sql = "SELECT COUNT(DISTINCT user_id) AS total FROM attendance WHERE date='$selectedDate'";
$res = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($res);
$totalPresent = (int)$row['total'];

/*====================================================
    ABSENT EMPLOYEES
====================================================*/

$totalAbsent = $totalEmployees - $totalPresent;
if ($totalAbsent < 0) $totalAbsent = 0;

/*====================================================
    TOTAL PUNCHES
====================================================*/

$sql = "SELECT COUNT(*) AS total FROM attendance WHERE date='$selectedDate'";
$res = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($res);
$totalPunches = (int)$row['total'];

/*====================================================
    ATTENDANCE RATE
====================================================*/

$attendanceRate = 0;
if ($totalEmployees > 0) {
    $attendanceRate = round(($totalPresent / $totalEmployees) * 100);
}

/*====================================================
    LATE EMPLOYEES
====================================================*/

$totalLate = 0;
$lateQuery = mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM (
        SELECT user_id, MIN(time) AS first_in
        FROM attendance
        WHERE date='$selectedDate'
        GROUP BY user_id
    ) t WHERE first_in > '09:00:00'
");
if ($lateQuery) {
    $lateRow = mysqli_fetch_assoc($lateQuery);
    $totalLate = (int)$lateRow['total'];
}

/*====================================================
    DEVICE
====================================================*/

$device = "ZKTeco";
$res = mysqli_query($conn, "SELECT device_id FROM attendance ORDER BY id DESC LIMIT 1");
if (mysqli_num_rows($res) > 0) {
    $device = mysqli_fetch_assoc($res)['device_id'];
}

/*====================================================
    WEEKLY DATA FOR CHART
====================================================*/

$weeklyData = [];
$weeklyLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date("Y-m-d", strtotime("-$i days", strtotime($selectedDate)));
    $weeklyLabels[] = date("D", strtotime($d));

    $res = mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) AS present FROM attendance WHERE date='$d'");
    $r = mysqli_fetch_assoc($res);
    $present = (int)$r['present'];

    if ($totalEmployees > 0) {
        $weeklyData[] = round(($present / $totalEmployees) * 100);
    } else {
        $weeklyData[] = 0;
    }
}

?>
