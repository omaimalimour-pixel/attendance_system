<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../core/device.php';
require_perm('employees.manage');

$pageTitle = "Add Employee";
$currentPage = "employees";
$message = ""; $messageType = "";
$created = false;          // becomes true after a successful insert
$enrollResults = [];       // per-device enrolment outcome
$newEmployee = null;
$old = ['user_id'=>'','first_name'=>'','last_name'=>'','department_id'=>'','position'=>'','email'=>'','phone'=>''];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();
    foreach ($old as $k => $_) { $old[$k] = inp($_POST, $k); }
    $wantEnroll = isset($_POST['enroll']); // checkbox (checked by default in the form)

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
        $newId = db_insert_id();
        audit('employee.create', 'employees', $newId);
        $newEmployee = db_one("SELECT * FROM employees WHERE id=?", [$newId]);

        // Trigger fingerprint enrolment on the department's device(s).
        if ($wantEnroll && $newEmployee) {
            $enrollResults = enrollment_request_create($newEmployee, current_user()['username'] ?? null);
        }
        $created = true;
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

<?php if ($created): ?>
  <!-- SUCCESS + fingerprint enrolment result -->
  <div class="panel">
    <div class="panel-head"><div><h3>Employee added ✓</h3><p class="sub"><?= e($newEmployee['first_name'].' '.$newEmployee['last_name']) ?> · User ID <?= (int)$newEmployee['user_id'] ?></p></div></div>
    <div class="panel-body">
      <?php if (!empty($enrollResults)): ?>
        <div style="display:flex;flex-direction:column;gap:12px">
        <?php foreach ($enrollResults as $r):
          $ok = $r['ok']; $devName = $r['device']['name'] ?? 'department device';
        ?>
          <div style="display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border:1px solid var(--border);border-radius:12px;background:<?= $ok ? 'var(--green-soft)' : 'var(--amber-soft)' ?>">
            <div class="kpi-icon <?= $ok ? 'green' : 'amber' ?>" style="width:38px;height:38px;flex-shrink:0">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 11c0 3.5-2 6-2 6M7 8a5 5 0 0 1 10 0v2M5 12a7 7 0 0 1 .5-2.6M12 8v4a8 8 0 0 0 2 5"/><path d="M9 20a12 12 0 0 1-1.5-6"/></svg>
            </div>
            <div>
              <div style="font-weight:650;font-size:13.5px"><?= $ok ? 'Enrolment request sent to '.e($devName) : 'Enrolment pending' ?></div>
              <div style="font-size:12.5px;color:var(--muted);margin-top:2px"><?= e($r['message']) ?></div>
              <?php if ($ok): ?><div style="font-size:12.5px;color:var(--green);margin-top:4px;font-weight:600">→ Ask the employee to scan their finger on <strong><?= e($devName) ?></strong> to complete enrolment.</div><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="color:var(--muted);font-size:13.5px">No fingerprint enrolment request was created. You can trigger one anytime from the Fingerprint Enrolment page.</p>
      <?php endif; ?>
    </div>
    <div class="form-actions">
      <a href="add_employee.php" class="btn">Add Another</a>
      <a href="enroll_finger.php?emp=<?= (int)$newEmployee['id'] ?>" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M12 11c0 3.5-2 6-2 6M7 8a5 5 0 0 1 10 0v2M5 12a7 7 0 0 1 .5-2.6M12 8v5a8 8 0 0 0 2 5M9 20a12 12 0 0 1-1.5-6"/></svg>
        Enroll Fingerprint →
      </a>
      <a href="enrollment.php" class="btn">Enrolment Queue</a>
      <a href="employees.php" class="btn">Done</a>
    </div>
  </div>
<?php else: ?>

<div class="panel">
  <div class="panel-head"><div><h3>Add New Employee</h3><p class="sub">Register a person, link them to a department, and enrol their fingerprint</p></div></div>
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
    </div>
    <!-- Fingerprint enrolment option -->
    <label style="display:flex;align-items:center;gap:11px;margin-top:6px;padding:14px 16px;border:1px solid var(--border);border-radius:12px;background:var(--accent-soft);cursor:pointer">
      <input type="checkbox" name="enroll" value="1" checked style="width:18px;height:18px;accent-color:var(--accent)">
      <span>
        <span style="font-weight:650;font-size:13.5px;color:var(--text);display:block">Send fingerprint enrolment request to the department's device</span>
        <span style="font-size:12px;color:var(--muted)">The employee will be pushed to their department's biometric machine so they can register their fingerprint there.</span>
      </span>
    </label>
    </div>
    <div class="form-actions"><a href="employees.php" class="btn">Cancel</a><button class="btn btn-primary">Add Employee</button></div>
  </form>
</div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
