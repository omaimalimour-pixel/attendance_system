<?php
include "../db.php";

if (!isset($_GET['id'])) {
    header("Location: attendance.php");
    exit;
}

$user_id = (int)$_GET['id'];

$sqlEmployee = "
SELECT *
FROM employees
WHERE user_id='$user_id'
";

$resultEmployee = mysqli_query($conn,$sqlEmployee);

if(mysqli_num_rows($resultEmployee)==0){
    die("Employee not found.");
}

$employee=mysqli_fetch_assoc($resultEmployee);

$sqlAttendance="
SELECT *
FROM attendance
WHERE user_id='$user_id'
ORDER BY date DESC,time DESC
";

$resultAttendance=mysqli_query($conn,$sqlAttendance);
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Attendance Details</title>

<link rel="stylesheet" href="attendance.css">

</head>

<body>

<div class="container">

<div class="page-header">

<div>

<h1>Attendance Details</h1>

<p>
<?= htmlspecialchars($employee['first_name']." ".$employee['last_name']); ?>
</p>

</div>

<div>

<a href="attendance.php" class="btn btn-secondary">

Back

</a>

</div>

</div>

<div class="card employee-info">

<div class="employee-avatar">

<?= strtoupper(substr($employee['first_name'],0,1)); ?>

</div>

<div>

<h2>

<?= htmlspecialchars($employee['first_name']." ".$employee['last_name']); ?>

</h2>

<p>

Employee ID :
<strong><?= $employee['user_id']; ?></strong>

</p>

<p>

Department :
<strong><?= htmlspecialchars($employee['department']); ?></strong>

</p>

<p>

Position :
<strong><?= htmlspecialchars($employee['position']); ?></strong>

</p>

</div>

</div>

<div class="card">

<div class="table-header">

<h2>Attendance History</h2>

</div>

<div class="table-responsive">

<table>

<thead>

<tr>

<th>Date</th>

<th>Time</th>

<th>Type</th>

</tr>

</thead>

<tbody>
    <?php

if(mysqli_num_rows($resultAttendance)>0){

while($row=mysqli_fetch_assoc($resultAttendance)){

$type = ucfirst($row['type']);

$badge = "badge-success";

if(strtolower($row['type'])=="check out"){

    $badge="badge-danger";

}

?>

<tr>

<td>

<?= date("d/m/Y",strtotime($row['date'])); ?>

</td>

<td>

<?= $row['time']; ?>

</td>

<td>

<span class="<?= $badge; ?>">

<?= htmlspecialchars($type); ?>

</span>

</td>

</tr>

<?php

}

}else{

?>

<tr>

<td colspan="3" class="empty">

No attendance records found.

</td>

</tr>

<?php

}

?>

</tbody>

</table>

</div>

</div>

<div class="card actions-card">

<a href="edit_attendance.php?id=<?= $employee['user_id']; ?>" class="btn btn-primary">

Edit Attendance

</a>

<a
href="delete_attendance.php?id=<?= $employee['user_id']; ?>"
class="btn btn-danger"
onclick="return confirm('Delete all attendance records for this employee?');">

Delete Attendance

</a>

<a href="attendance.php" class="btn btn-secondary">

Back to Attendance

</a>

</div>

</div>

</body>

</html>
