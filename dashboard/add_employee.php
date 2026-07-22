<?php
require __DIR__ . '/bootstrap.php';
require_perm('employees.manage');

$pageTitle = "Add Employee";
$currentPage = "employees";
$message = ""; $messageType = "";
$old = ['user_id'=>'','first_name'=>'','last_name'=>'','department_id'=>'','position'=>'','email'=>'','phone'=>''];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();
    foreach ($old as $k => $_) { $old[$k] = inp($_POST, $k); }

    $userId = (int) $old['user_id'];
    if ($userId <= 0 || $old['first_name'] === '' || $old['last_name'] === '') {
        $message = "User ID, first name and last name are required."; $messageType = "danger";
    } elseif (db_val("SELECT id FROM employees WHERE user_id=?", [$userId])) {
        $message = "That User ID already exists."; $messageType = "danger";
    } elseif ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address."; $messageType = "danger";
    } else {
        db_exec(
            "INSERT INTO employees (user_id, first_name, last_name, department_id, position, email, phone, status)
             VALUES (?,?,?,?,?,?,?, 'active')",
            [$userId, $old['first_name'], $old['last_name'], ($old['department_id'] ? (int)$old['department_id'] : null),
             $old['position'] ?: null, $old['email'] ?: null, $old['phone'] ?: null]
        );
        audit('employee.create', 'employees', db_insert_id());
        header("Location: employees.php");
        exit;
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
  <div class="panel-head"><div><h3>Add New Employee</h3><p class="sub">Register a person and link them to a department</p></div></div>
  <form method="POST">
    <?= csrf_field() ?>
    <div class="form-panel"><div class="form-grid">
      <div class="form-group"><label>User ID (Device ID)</label><input type="number" name="user_id" required value="<?= e($old['user_id']) ?>" placeholder="Matches the ID enrolled on the device"></div>
      <div class="form-group"><label>Position</label><input type="text" name="position" value="<?= e($old['position']) ?>" placeholder="e.g. Developer"></div>
      <div class="form-group"><label>First Name</label><input type="text" name="first_name" required value="<?= e($old['first_name']) ?>"></div>
      <div class="form-group"><label>Last Name</label><input type="text" name="last_name" required value="<?= e($old['last_name']) ?>"></div>
      <div class="form-group"><label>Department</label>
        <select name="department_id"><option value="">— None —</option>
          <?php foreach ($departments as $d): ?><option value="<?= (int)$d['id'] ?>" <?= ($old['department_id']==$d['id']?'selected':'') ?>><?= e($d['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= e($old['email']) ?>" placeholder="name@company.com"></div>
      <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= e($old['phone']) ?>" placeholder="Optional"></div>
    </div></div>
    <div class="form-actions"><a href="employees.php" class="btn">Cancel</a><button class="btn btn-primary">Add Employee</button></div>
  </form>
</div>

<?php include "includes/footer.php"; ?>
