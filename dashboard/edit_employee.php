<?php
require __DIR__ . '/bootstrap.php';
require_perm('employees.manage');

$pageTitle = "Edit Employee";
$currentPage = "employees";
$message = ""; $messageType = "";

$id = (int) ($_GET['id'] ?? 0);
$employee = db_one("SELECT * FROM employees WHERE id=?", [$id]);
if (!$employee) { header("Location: employees.php"); exit; }

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();
    $first = inp($_POST, 'first_name');
    $last  = inp($_POST, 'last_name');
    $deptId = inp($_POST, 'department_id');
    $position = inp($_POST, 'position');
    $email = inp($_POST, 'email');
    $phone = inp($_POST, 'phone');
    $status = in_array(inp($_POST, 'status'), ['active','inactive'], true) ? inp($_POST, 'status') : 'active';

    if ($first === '' || $last === '') {
        $message = "First and last name are required."; $messageType = "danger";
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address."; $messageType = "danger";
    } else {
        db_exec("UPDATE employees SET first_name=?, last_name=?, department_id=?, position=?, email=?, phone=?, status=? WHERE id=?",
            [$first, $last, ($deptId ? (int)$deptId : null), $position ?: null, $email ?: null, $phone ?: null, $status, $id]);
        audit('employee.update', 'employees', $id);
        $message = "Employee updated successfully."; $messageType = "success";
        $employee = db_one("SELECT * FROM employees WHERE id=?", [$id]);
    }
}

$departments = db_all("SELECT id, name FROM departments ORDER BY name");
include "includes/header.php";
?>

<div class="page-top">
  <a href="employees.php" class="btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg> Back to Employees</a>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>"><?= e($message) ?></div>
<?php endif; ?>

<div class="panel">
  <div class="panel-head"><div><h3>Edit Employee</h3><p class="sub"><?= e($employee['first_name'].' '.$employee['last_name']) ?></p></div></div>
  <form method="POST">
    <?= csrf_field() ?>
    <div class="form-panel"><div class="form-grid">
      <div class="form-group"><label>User ID (Device ID)</label><input type="number" value="<?= e($employee['user_id']) ?>" readonly></div>
      <div class="form-group"><label>Status</label>
        <select name="status"><option value="active" <?= $employee['status']==='active'?'selected':'' ?>>Active</option><option value="inactive" <?= ($employee['status']??'')==='inactive'?'selected':'' ?>>Inactive</option></select>
      </div>
      <div class="form-group"><label>First Name</label><input type="text" name="first_name" required value="<?= e($employee['first_name']) ?>"></div>
      <div class="form-group"><label>Last Name</label><input type="text" name="last_name" required value="<?= e($employee['last_name']) ?>"></div>
      <div class="form-group"><label>Department</label>
        <select name="department_id"><option value="">— None —</option>
          <?php foreach ($departments as $d): ?><option value="<?= (int)$d['id'] ?>" <?= ($employee['department_id']==$d['id']?'selected':'') ?>><?= e($d['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Position</label><input type="text" name="position" value="<?= e($employee['position']) ?>"></div>
      <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= e($employee['email'] ?? '') ?>"></div>
      <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= e($employee['phone'] ?? '') ?>"></div>
    </div></div>
    <div class="form-actions"><a href="employees.php" class="btn">Cancel</a><button class="btn btn-primary">Save Changes</button></div>
  </form>
</div>

<?php include "includes/footer.php"; ?>
