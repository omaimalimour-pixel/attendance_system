<?php
session_start();
include "../db.php";

if (!isset($_GET['id'])) {
    header("Location: attendance.php");
    exit;
}

$user_id = (int)$_GET['id'];
$date = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : "";

// Verify employee exists
$result = mysqli_query($conn, "SELECT * FROM employees WHERE user_id='$user_id'");
if (mysqli_num_rows($result) == 0) {
    header("Location: attendance.php");
    exit;
}

if ($date != "") {
    // Delete only for specific date
    mysqli_query($conn, "DELETE FROM attendance WHERE user_id='$user_id' AND date='$date'");
    header("Location: attendance.php?date=$date");
} else {
    // Delete all attendance for this employee
    mysqli_query($conn, "DELETE FROM attendance WHERE user_id='$user_id'");
    header("Location: attendance.php");
}

exit;
?>
