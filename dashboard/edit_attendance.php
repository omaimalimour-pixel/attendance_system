<?php
include "../db.php";

if(!isset($_GET['id'])){
    header("Location: attendance.php");
    exit;
}

$id = (int)$_GET['id'];

$sql = "
SELECT attendance.*,
employees.first_name,
employees.last_name,
employees.department,
employees.position
FROM attendance

INNER JOIN employees

ON attendance.user_id = employees.user_id

WHERE attendance.id='$id'
";

$result = mysqli_query($conn,$sql);

if(mysqli_num_rows($result)==0){
    die("Attendance record not found.");
}

$row = mysqli_fetch_assoc($result);

if(isset($_POST['update'])){

    $date = $_POST['date'];
    $time = $_POST['time'];
    $type = $_POST['type'];

    mysqli_query($conn,"
    UPDATE attendance
    SET
        date='$date',
        time='$time',
        type='$type'
    WHERE id='$id'
    ");

    header("Location:view_attendance.php?id=".$row['user_id']);
    exit;
}
?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Attendance</title>

<link rel="stylesheet" href="attendance.css">

</head>

<body>

<div class="container">

<div class="page-header">

<div>

<h1>Edit Attendance</h1>

<p>

<?= htmlspecialchars($row['first_name']." ".$row['last_name']); ?>

</p>

</div>

<a href="attendance.php" class="btn btn-secondary">

Back

</a>

</div>

<div class="card">

<form method="POST">

<div class="form-grid"></div>
<!-- Employee -->

<div class="form-group">

<label>Employee</label>

<input
type="text"
value="<?= htmlspecialchars($row['first_name']." ".$row['last_name']); ?>"
readonly>

</div>

<!-- Department -->

<div class="form-group">

<label>Department</label>

<input
type="text"
value="<?= htmlspecialchars($row['department']); ?>"
readonly>

</div>

<!-- Date -->

<div class="form-group">

<label>Date</label>

<input
type="date"
name="date"
value="<?= $row['date']; ?>"
required>

</div>

<!-- Time -->

<div class="form-group">

<label>Time</label>

<input
type="time"
name="time"
value="<?= $row['time']; ?>"
required>

</div>

<!-- Type -->

<div class="form-group">

<label>Punch Type</label>

<select name="type" required>

<option
value="Check In"
<?= ($row['type']=="Check In") ? "selected" : ""; ?>>

Check In

</option>

<option
value="Check Out"
<?= ($row['type']=="Check Out") ? "selected" : ""; ?>>

Check Out

</option>

</select>

</div>

</div>

<div class="form-actions">

<button
type="submit"
name="update"
class="btn btn-primary">

Save Changes

</button>

<a
href="view_attendance.php?id=<?= $row['user_id']; ?>"
class="btn btn-secondary">

Cancel

</a>

</div>

</form>

</div>
</div>

</div>

</body>

</html>