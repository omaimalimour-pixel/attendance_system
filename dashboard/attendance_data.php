<?php

/* ===========================
   FILTERS
=========================== */

$selectedDate = date("Y-m-d");

if(isset($_GET['date']) && !empty($_GET['date'])){
    $selectedDate = $_GET['date'];
}

$search = "";

if(isset($_GET['search'])){
    $search = mysqli_real_escape_string($conn, trim($_GET['search']));
}

$statusFilter = "";

if(isset($_GET['status'])){
    $statusFilter = $_GET['status'];
}

/* ===========================
   DASHBOARD STATISTICS
=========================== */

$totalEmployees = mysqli_num_rows(
    mysqli_query($conn,"SELECT * FROM employees")
);

$totalPunches = mysqli_num_rows(
    mysqli_query($conn,"
        SELECT *
        FROM attendance
        WHERE date='$selectedDate'
    ")
);

$totalPresent = mysqli_num_rows(
    mysqli_query($conn,"
        SELECT DISTINCT user_id
        FROM attendance
        WHERE date='$selectedDate'
    ")
);

$totalAbsent = $totalEmployees - $totalPresent;

if($totalAbsent < 0){
    $totalAbsent = 0;
}

$attendanceRate = 0;

if($totalEmployees > 0){
    $attendanceRate = round(($totalPresent/$totalEmployees)*100);
}

/* ===========================
   LATE EMPLOYEES
=========================== */

$totalLate = 0;

$lateQuery = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM
(
    SELECT user_id,
    MIN(time) AS first_in
    FROM attendance
    WHERE date='$selectedDate'
    GROUP BY user_id
) t
WHERE first_in > '09:00:00'
");

if($lateQuery){

    $lateRow = mysqli_fetch_assoc($lateQuery);

    $totalLate = $lateRow['total'];

}

/* ===========================
   DEVICE
=========================== */

$device = "ZKTeco";

/* ===========================
   MAIN QUERY
=========================== */

$sql = "

SELECT

employees.id,
employees.user_id,
employees.first_name,
employees.last_name,
employees.department,
employees.position,

MIN(attendance.time) AS first_in,
MAX(attendance.time) AS last_out,

COUNT(attendance.id) AS punches

FROM employees

LEFT JOIN attendance

ON employees.user_id = attendance.user_id

AND attendance.date = '$selectedDate'

WHERE 1=1

";

/* ===========================
   SEARCH
=========================== */

if($search != ""){

    $sql .= "

    AND (

        employees.user_id LIKE '%$search%'

        OR employees.first_name LIKE '%$search%'

        OR employees.last_name LIKE '%$search%'

        OR employees.department LIKE '%$search%'

        OR employees.position LIKE '%$search%'

    )

    ";

}

/* ===========================
   GROUP
=========================== */

$sql .= "

GROUP BY employees.user_id

ORDER BY employees.first_name ASC

";

/* ===========================
   EXECUTE
=========================== */

$resultAttendance = mysqli_query($conn,$sql);

if(!$resultAttendance){

    die(mysqli_error($conn));

}