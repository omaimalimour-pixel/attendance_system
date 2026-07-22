<?php
require __DIR__ . '/bootstrap.php';
require_perm('attendance.manage');

$pageTitle = "Edit Attendance";
$currentPage = "attendance";
$message = ""; $messageType = "";

$id = (int) ($_GET['id'] ?? 0);

// Resolve a record: try by attendance id, then by user_id (latest punch)
$row = db_one(
    "SELECT a.*, e.first_name, e.last_name, e.department_id
     FROM attendance a INNER JOIN employees e ON a.user_id = e.user_id
     WHERE a.id=? LIMIT 1", [$id]
);
if (!$row) {
    $row = db_one(
        "SELECT a.*, e.first_name, e.last_name, e.department_id
         FROM attendance a INNER JOIN employees e ON a.user_id = e.user_id
         WHERE a.user_id=? ORDER BY a.date DESC, a.time DESC LIMIT 1", [$id]
    );
}
if (!$row) { header("Location: attendance.php"); exit; }

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();
    $recordId = (int) inp($_POST, 'record_id');
    $date = inp($_POST, 'date');
    $time = inp($_POST, 'time');
    $type = in_array(inp($_POST, 'type'), ['IN','OUT'], true) ? inp($_POST, 'type') : 'IN';

    if ($date === '' || $time === '') {
        $message = "Date and time are required."; $messageType = "danger";
    } else {
        db_exec("UPDATE attendance SET date=?, time=?, type=? WHERE id=?", [$date, $time, $type, $recordId]);
        audit('attendance.update', 'attendance', $recordId);
        $message = "Attendance record updated."; $messageType = "success";
        $row = db_one("SELECT a.*, e.first_name, e.last_name, e.department_id FROM attendance a INNER JOIN employees e ON a.user_id=e.user_id WHERE a.id=?", [$recordId]);
    }
}

$deptName = $row['department_id'] ? db_val("SELECT name FROM departments WHERE id=?", [(int)$row['department_id']]) : '';
include "includes/header.php";
?>

<div class="page-top">
  <a href="view_attandence.php?id=<?= (int)$row['user_id'] ?>" class="btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg> Back to Details</a>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>"><?= e($message) ?></div>
<?php endif; ?>

<div class="panel">
  <div class="panel-head"><div><h3>Edit Attendance Record</h3><p class="sub"><?= e($row['first_name'].' '.$row['last_name']) ?></p></div></div>
  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="record_id" value="<?= (int)$row['id'] ?>">
    <div class="form-panel"><div class="form-grid">
      <div class="form-group"><label>Employee</label><input type="text" value="<?= e($row['first_name'].' '.$row['last_name']) ?>" readonly></div>
      <div class="form-group"><label>Department</label><input type="text" value="<?= e($deptName ?: '—') ?>" readonly></div>
      <div class="form-group"><label>Date</label><input type="date" name="date" value="<?= e($row['date']) ?>" required></div>
      <div class="form-group"><label>Time</label><input type="time" name="time" value="<?= e($row['time']) ?>" required></div>
      <div class="form-group"><label>Type</label><select name="type"><option value="IN" <?= $row['type']==='IN'?'selected':'' ?>>Check In</option><option value="OUT" <?= $row['type']==='OUT'?'selected':'' ?>>Check Out</option></select></div>
    </div></div>
    <div class="form-actions"><a href="view_attandence.php?id=<?= (int)$row['user_id'] ?>" class="btn">Cancel</a><button class="btn btn-primary">Save Changes</button></div>
  </form>
</div>

<?php include "includes/footer.php"; ?>
