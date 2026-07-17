<?php

include "../db.php";

/*====================================================
    CONFIGURATION
====================================================*/

$startDate = "2026-07-13";

/*====================================================
    DATE SELECTIONNEE
====================================================*/

$selectedDate = isset($_GET['date']) && $_GET['date'] != ""
    ? $_GET['date']
    : date("Y-m-d");

if($selectedDate < $startDate){
    $selectedDate = $startDate;
}

/*====================================================
    TOTAL EMPLOYES
====================================================*/

$sql = "SELECT COUNT(*) total FROM employees";

$res = mysqli_query($conn,$sql);

$row = mysqli_fetch_assoc($res);

$totalEmployees = (int)$row['total'];


/*====================================================
    EMPLOYES PRESENTS
====================================================*/

$sql = "

SELECT COUNT(DISTINCT user_id) total

FROM attendance

WHERE date='$selectedDate'

";

$res = mysqli_query($conn,$sql);

$row = mysqli_fetch_assoc($res);

$totalPresent = (int)$row['total'];


/*====================================================
    EMPLOYES ABSENTS
====================================================*/

$totalAbsent = $totalEmployees - $totalPresent;

if($totalAbsent < 0){

    $totalAbsent = 0;

}


/*====================================================
    TOTAL DES POINTAGES
====================================================*/

$sql = "

SELECT COUNT(*) total

FROM attendance

WHERE date='$selectedDate'

";

$res = mysqli_query($conn,$sql);

$row = mysqli_fetch_assoc($res);

$totalPunches = (int)$row['total'];


/*====================================================
    POURCENTAGE
====================================================*/

$attendanceRate = 0;

if($totalEmployees > 0){

    $attendanceRate = round(($totalPresent/$totalEmployees)*100);

}


/*====================================================
    MACHINE
====================================================*/

$sql = "

SELECT device_id

FROM attendance

ORDER BY id DESC

LIMIT 1

";

$res = mysqli_query($conn,$sql);

if(mysqli_num_rows($res)>0){

    $device = mysqli_fetch_assoc($res)['device_id'];

}else{

    $device = "ZKTeco";

}


/*====================================================
    TABLEAU ATTENDANCE
====================================================*/

$sql = "
SELECT

employees.id,
employees.user_id,
employees.first_name,
employees.last_name,
employees.department,
employees.position,

MIN(attendance.id) AS attendance_id,

attendance.date,

MIN(attendance.time) AS first_in,
MAX(attendance.time) AS last_out,

COUNT(attendance.id) AS punches

FROM employees

LEFT JOIN attendance

ON employees.user_id = attendance.user_id

AND attendance.date='$selectedDate'
";