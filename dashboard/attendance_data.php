<?php
/**
 * Attendance Data Query Layer
 * Requires $conn to be available (include db.php before this file).
 */

date_default_timezone_set('Africa/Casablanca');

/* ===========================
   FILTERS
=========================== */

$selectedDate = date("Y-m-d");

if (isset($_GET['date']) && !empty($_GET['date'])) {
    $selectedDate = $_GET['date'];
}

$search = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, trim($_GET['search']));
}

$statusFilter = "";
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $statusFilter = $_GET['status'];
}

/* ===========================
   DASHBOARD STATISTICS
=========================== */

$totalEmployees = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM employees");
if ($res) {
    $row = mysqli_fetch_assoc($res);
    $totalEmployees = (int)$row['total'];
}

$totalPunches = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance WHERE date='$selectedDate'");
if ($res) {
    $row = mysqli_fetch_assoc($res);
    $totalPunches = (int)$row['total'];
}

$totalPresent = 0;
$res = mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) AS total FROM attendance WHERE date='$selectedDate'");
if ($res) {
    $row = mysqli_fetch_assoc($res);
    $totalPresent = (int)$row['total'];
}

$totalAbsent = $totalEmployees - $totalPresent;
if ($totalAbsent < 0) {
    $totalAbsent = 0;
}

$attendanceRate = 0;
if ($totalEmployees > 0) {
    $attendanceRate = round(($totalPresent / $totalEmployees) * 100);
}

/* ===========================
   LATE EMPLOYEES
=========================== */

$totalLate = 0;
$lateQuery = @mysqli_query($conn, "
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

if ($search != "") {
    $sql .= " AND (
        employees.user_id LIKE '%$search%'
        OR employees.first_name LIKE '%$search%'
        OR employees.last_name LIKE '%$search%'
        OR employees.department LIKE '%$search%'
        OR employees.position LIKE '%$search%'
    )";
}

/* ===========================
   GROUP & ORDER
=========================== */

$sql .= " GROUP BY employees.user_id ORDER BY employees.first_name ASC";

/* ===========================
   EXECUTE
=========================== */

$resultAttendance = mysqli_query($conn, $sql);

if (!$resultAttendance) {
    die("Query error: " . mysqli_error($conn));
}
?>
