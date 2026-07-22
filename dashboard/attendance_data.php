<?php

date_default_timezone_set('Africa/Casablanca');

/**
 * Attendance data layer — uses core db helpers (prepared statements).
 * Assumes bootstrap.php (core) is already loaded by the including page.
 */


/* ===========================
   FILTERS
=========================== */

$selectedDate = date("Y-m-d");
$search = "";
$statusFilter = "";


if (
    !empty($_GET['selectedDate']) &&
    preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['selectedDate'])
) {
    $selectedDate = $_GET['selectedDate'];
}


if (!empty($_GET['search'])) {
    $search = trim($_GET['search']);
}


if (!empty($_GET['status'])) {
    $statusFilter = $_GET['status'];
}



/* ===========================
   DEFAULT VALUES
=========================== */

$totalEmployees = 0;
$totalPunches = 0;
$totalPresent = 0;
$totalAbsent = 0;
$attendanceRate = 0;
$totalLate = 0;



/* ===========================
   STATISTICS
=========================== */

$workStart = work_start();


$totalEmployees = (int) db_val(
    "SELECT COUNT(*) FROM employees"
);


$totalPunches = (int) db_val(
    "SELECT COUNT(*) FROM attendance WHERE date=?",
    [$selectedDate]
);


$totalPresent = (int) db_val(
    "SELECT COUNT(DISTINCT user_id) FROM attendance WHERE date=?",
    [$selectedDate]
);


$totalAbsent = max(
    0,
    $totalEmployees - $totalPresent
);


$attendanceRate = $totalEmployees > 0
    ? round(($totalPresent / $totalEmployees) * 100)
    : 0;



$totalLate = (int) db_val(
    "SELECT COUNT(*)
     FROM (
        SELECT user_id, MIN(time) t
        FROM attendance
        WHERE date=?
        GROUP BY user_id
     ) x
     WHERE x.t > ?",
    [
        $selectedDate,
        $workStart
    ]
);



/* ===========================
   ATTENDANCE LIST
=========================== */


$sql = "
SELECT 
    e.id,
    e.user_id,
    e.first_name,
    e.last_name,
    e.position,
    dep.name AS department,
    MIN(a.time) AS first_in,
    MAX(a.time) AS last_out,
    COUNT(a.id) AS punches

FROM employees e

LEFT JOIN departments dep
ON dep.id = e.department_id

LEFT JOIN attendance a
ON a.user_id = e.user_id
AND a.date = ?

WHERE 1=1
";


$args = [
    $selectedDate
];



/* SEARCH */

if ($search !== '') {

    $sql .= "
    AND (
        e.user_id LIKE ?
        OR e.first_name LIKE ?
        OR e.last_name LIKE ?
        OR dep.name LIKE ?
    )
    ";


    $like = "%".$search."%";


    array_push(
        $args,
        $like,
        $like,
        $like,
        $like
    );
}



/* STATUS FILTER */

if ($statusFilter !== '') {

    if ($statusFilter === "present") {

        $sql .= "
        AND EXISTS (
            SELECT 1
            FROM attendance a2
            WHERE a2.user_id=e.user_id
            AND a2.date=?
        )
        ";

        $args[] = $selectedDate;

    }


    if ($statusFilter === "absent") {

        $sql .= "
        AND NOT EXISTS (
            SELECT 1
            FROM attendance a3
            WHERE a3.user_id=e.user_id
            AND a3.date=?
        )
        ";

        $args[] = $selectedDate;

    }
}



/* ORDER */

$sql .= "
GROUP BY e.user_id
ORDER BY e.first_name
";



$attendanceRows = db_all(
    $sql,
    $args
);