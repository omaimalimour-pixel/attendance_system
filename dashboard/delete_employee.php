<?php
session_start();
include "../db.php";

if (!isset($_GET['id'])) {
    header("Location: employees.php");
    exit;
}

$id = (int)$_GET['id'];

// Check if employee exists
$result = mysqli_query($conn, "SELECT * FROM employees WHERE id=$id");
if (mysqli_num_rows($result) == 0) {
    header("Location: employees.php");
    exit;
}

$employee = mysqli_fetch_assoc($result);

// Delete attendance records first
mysqli_query($conn, "DELETE FROM attendance WHERE user_id='" . $employee['user_id'] . "'");

// Delete employee
mysqli_query($conn, "DELETE FROM employees WHERE id=$id");

header("Location: employees.php");
exit;
?>
