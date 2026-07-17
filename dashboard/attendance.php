<?php
include "../db.php";
include "attendance_data.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Attendance</title>

<link rel="stylesheet" href="attendance.css">

</head>

<body>

<div class="container">

<div class="page-header">

<div>

<h1>🕒 Attendance</h1>

<p>Manage employee attendance and punches.</p>

</div>

<div class="header-buttons">

<a href="../sync_attendance.php" class="btn btn-primary">

🔄 Sync Device

</a>

<a href="#" class="btn btn-success">

📥 Export Excel

</a>

<a href="#" class="btn btn-danger">

📄 Export PDF

</a>

</div>

</div>

<!-- ================= STATISTICS ================= -->

<div class="stats">

<div class="stat-card">

<div class="icon blue">👥</div>

<div>

<h2><?= $totalEmployees ?></h2>

<p>Total Employees</p>

</div>

</div>

<div class="stat-card">

<div class="icon green">✅</div>

<div>

<h2><?= $totalPresent ?></h2>

<p>Present Today</p>

</div>

</div>

<div class="stat-card">

<div class="icon red">❌</div>

<div>

<h2><?= $totalAbsent ?></h2>

<p>Absent Today</p>

</div>

</div>

<div class="stat-card">

<div class="icon orange">⏰</div>

<div>

<h2><?= $totalLate ?></h2>

<p>Late Employees</p>

</div>

</div>

</div>
<!-- ================= SEARCH & FILTER ================= -->

<div class="card">

<form method="GET" class="search-form">

<div class="search-group">

<input
type="date"
name="date"
value="<?= $selectedDate; ?>">

<input
type="text"
name="search"
placeholder="Search by User ID, Name, Department..."
value="<?= htmlspecialchars($search); ?>">

<select name="status">

<option value="">All Status</option>

<option value="Present"
<?= ($statusFilter=="Present")?"selected":""; ?>>

Present

</option>

<option value="Absent"
<?= ($statusFilter=="Absent")?"selected":""; ?>>

Absent

</option>

<option value="Late"
<?= ($statusFilter=="Late")?"selected":""; ?>>

Late

</option>

</select>

<button type="submit" class="btn btn-primary">
<a href="edit_attendance.php?id=<?= $row['user_id']; ?>" class="btn-action edit"></a>
🔍 Search

</button>

<a href="attendance.php" class="btn btn-secondary">

Reset

</a>

</div>

</form>

</div>

<!-- ================= TABLE ================= -->

<div class="card">

<div class="table-header">

<h2>Attendance Records</h2>

<p>

<?= date("d/m/Y",strtotime($selectedDate)); ?>

</p>

</div>

<div class="table-responsive">

<table>

<thead>

<tr>

<th>#</th>

<th>Employee</th>

<th>Department</th>

<th>First IN</th>

<th>Last OUT</th>

<th>Punches</th>

<th>Working Hours</th>

<th>Status</th>

<th>Actions</th>

</tr>

</thead>

<tbody>
    <?php

$no = 1;

while($row = mysqli_fetch_assoc($resultAttendance)){

    $status = "Absent";
    $badge = "badge-danger";

    if($row['first_in']){

        $status = "Present";
        $badge = "badge-success";

        if($row['first_in'] > "09:00:00"){

            $status = "Late";
            $badge = "badge-warning";

        }

    }

    $workHours = "--";

    if($row['first_in'] && $row['last_out']){

        $start = strtotime($row['first_in']);
        $end   = strtotime($row['last_out']);

        if($end > $start){

            $workHours = gmdate("H:i",$end-$start);

        }

    }

    $avatar = strtoupper(substr($row['first_name'],0,1));

?>

<tr>

<td><?= $no++; ?></td>

<td>

<div class="employee">

<div class="avatar">

<?= $avatar; ?>

</div>

<div>

<strong>

<?= htmlspecialchars($row['first_name']." ".$row['last_name']); ?>

</strong>

<br>

<small>

ID : <?= $row['user_id']; ?>

</small>

</div>

</div>

</td>

<td>

<?= htmlspecialchars($row['department']); ?>

</td>

<td>

<?= $row['first_in'] ? $row['first_in'] : "--"; ?>

</td>

<td>

<?= $row['last_out'] ? $row['last_out'] : "--"; ?>

</td>

<td>

<?= $row['punches']; ?>

</td>

<td>

<?= $workHours; ?>

</td>

<td>

<span class="<?= $badge; ?>">

<?= $status; ?>

</span>

</td>

<td>

<a href="view_attendance.php?id=<?= $row['user_id']; ?>" class="btn-action view">

👁

</a>

<a href="edit_attendance.php?id=<?= $row['user_id']; ?>" class="btn-action edit">

✏

</a>

<a
href="delete_attendance.php?id=<?= $row['user_id']; ?>"
class="btn-action delete"
onclick="return confirm('Delete this attendance record?');">

🗑

</a>

</td>

</tr>

<?php

}

?>

</tbody>

</table>

</div>

</div>

</div>


</body>

</html>