<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Casablanca');

include "../db.php";

$pageTitle = "Edit Attendance";
$currentPage = "attendance";

if (!isset($_GET['id'])) {
    header("Location: attendance.php");
    exit;
}

$id = (int)$_GET['id'];

// Check if this is a user_id (from attendance page) or attendance record id
$result = mysqli_query($conn, "
    SELECT attendance.*, employees.first_name, employees.last_name,
           employees.department, employees.position
    FROM attendance
    INNER JOIN employees ON attendance.user_id = employees.user_id
    WHERE attendance.id='$id'
");

if (mysqli_num_rows($result) == 0) {
    // Try as user_id - show latest record
    $result = mysqli_query($conn, "
        SELECT attendance.*, employees.first_name, employees.last_name,
               employees.department, employees.position
        FROM attendance
        INNER JOIN employees ON attendance.user_id = employees.user_id
        WHERE attendance.user_id='$id'
        ORDER BY attendance.date DESC, attendance.time DESC
        LIMIT 1
    ");
    if (mysqli_num_rows($result) == 0) {
        header("Location: attendance.php");
        exit;
    }
}

$row = mysqli_fetch_assoc($result);

$message = "";
$messageType = "";

if (isset($_POST['update'])) {
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $time = mysqli_real_escape_string($conn, $_POST['time']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);

    $updateId = (int)$_POST['record_id'];

    $sql = "UPDATE attendance SET date='$date', time='$time', type='$type' WHERE id=$updateId";

    if (mysqli_query($conn, $sql)) {
        $message = "Attendance record updated successfully.";
        $messageType = "success";
        // Refresh
        $result = mysqli_query($conn, "
            SELECT attendance.*, employees.first_name, employees.last_name,
                   employees.department, employees.position
            FROM attendance
            INNER JOIN employees ON attendance.user_id = employees.user_id
            WHERE attendance.id=$updateId
        ");
        $row = mysqli_fetch_assoc($result);
    } else {
        $message = "Error: " . mysqli_error($conn);
        $messageType = "danger";
    }
}

include "includes/header.php";
?>

<div class="section-head">
    <div>
        <a href="view_attandence.php?id=<?= $row['user_id'] ?>" class="qa-btn">
            <i data-lucide="arrow-left"></i> Back to Details
        </a>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?>">
    <i data-lucide="<?= $messageType == 'success' ? 'check-circle-2' : 'alert-circle' ?>"></i>
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<div class="panel">
    <div class="panel-head">
        <div>
            <h3>Edit Attendance Record</h3>
            <p class="sub"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></p>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="record_id" value="<?= $row['id'] ?>">
        <div class="form-panel">
            <div class="form-grid">
                <div class="form-group">
                    <label>Employee</label>
                    <input type="text" value="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" value="<?= htmlspecialchars($row['department']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?= $row['date'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Time</label>
                    <input type="time" name="time" value="<?= $row['time'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Punch Type</label>
                    <select name="type" required>
                        <option value="IN" <?= ($row['type'] == "IN") ? "selected" : "" ?>>Check In</option>
                        <option value="OUT" <?= ($row['type'] == "OUT") ? "selected" : "" ?>>Check Out</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <a href="view_attandence.php?id=<?= $row['user_id'] ?>" class="qa-btn">Cancel</a>
            <button type="submit" name="update" class="qa-btn qa-primary">
                <i data-lucide="save"></i> Save Changes
            </button>
        </div>
    </form>
</div>

<?php include "includes/footer.php"; ?>
