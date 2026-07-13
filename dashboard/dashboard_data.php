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

$sqlAttendanceTable = "

SELECT

e.user_id,

e.first_name,

e.last_name,

MIN(
CASE
WHEN a.type='IN'
THEN a.time
END
) first_in,

MAX(
CASE
WHEN a.type='OUT'
THEN a.time
END
) last_out,

COUNT(a.id) punches

FROM employees e

LEFT JOIN attendance a

ON e.user_id=a.user_id

AND a.date='$selectedDate'

GROUP BY

e.user_id,

e.first_name,

e.last_name

ORDER BY

e.first_name

";

$resultAttendanceTable = mysqli_query($conn,$sqlAttendanceTable);

?>