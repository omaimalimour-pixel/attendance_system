<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../core/device.php';
require_perm('employees.manage');

$pageTitle = "Fingerprint Enrolment";
$currentPage = "enrollment";
$message = ""; $messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = inp($_POST, 'action');
    $id = (int) inp($_POST, 'id');

    if ($action === 'retry') {
        [$ok, $msg] = enrollment_retry($id, current_user()['username'] ?? null);
        $message = $ok ? "Re-sent to device: $msg" : "Could not reach device: $msg";
        $messageType = $ok ? "success" : "danger";
        audit('enrollment.retry', 'enrollment_requests', $id);
    } elseif ($action === 'enrolled') {
        enrollment_mark_enrolled($id);
        audit('enrollment.mark_enrolled', 'enrollment_requests', $id);
        $message = "Marked as enrolled."; $messageType = "success";
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM enrollment_requests WHERE id=?", [$id]);
        audit('enrollment.delete', 'enrollment_requests', $id);
        $message = "Request removed."; $messageType = "success";
    } elseif ($action === 'request') {
        // Create a fresh enrolment request for an existing employee
        $emp = db_one("SELECT * FROM employees WHERE id=?", [(int) inp($_POST, 'employee_id')]);
        if ($emp) {
            enrollment_request_create($emp, current_user()['username'] ?? null);
            $message = "Enrolment request created for " . $emp['first_name'] . ' ' . $emp['last_name'] . ".";
            $messageType = "success";
        }
    }
}

// Stats
$counts = [];
foreach (['pending','sent','enrolled','failed'] as $s) {
    $counts[$s] = (int) db_val("SELECT COUNT(*) FROM enrollment_requests WHERE status=?", [$s]);
}

// Filter
$statusFilter = inp($_GET, 'status');
$where = ''; $args = [];
if (in_array($statusFilter, ['pending','sent','enrolled','failed'], true)) {
    $where = "WHERE r.status=?"; $args[] = $statusFilter;
}

$rows = db_all(
    "SELECT r.*, e.first_name, e.last_name, dep.name AS dept_name, d.name AS device_name, d.ip_address
     FROM enrollment_requests r
     LEFT JOIN employees e ON e.id = r.employee_id
     LEFT JOIN departments dep ON dep.id = r.department_id
     LEFT JOIN devices d ON d.id = r.device_id
     $where
     ORDER BY FIELD(r.status,'pending','failed','sent','enrolled'), r.created_at DESC
     LIMIT 300",
    $args
);

// Employees without any enrolment request (so admin can trigger one)
$missing = db_all(
    "SELECT e.id, e.user_id, e.first_name, e.last_name, dep.name AS dept_name
     FROM employees e
     LEFT JOIN departments dep ON dep.id = e.department_id
     WHERE NOT EXISTS (SELECT 1 FROM enrollment_requests r WHERE r.employee_id = e.id)
     ORDER BY e.id DESC LIMIT 50"
);

$libOk = device_lib_available();
include "includes/header.php";
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>"><?= e($message) ?></div>
<?php endif; ?>

<?php if (!$libOk): ?>
<div class="alert alert-danger">Device library not detected on this server. Requests will stay <strong>pending</strong> until you run <code>composer install</code> and the machines are reachable.</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-row">
  <div class="stat-mini"><div class="kpi-icon amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $counts['pending'] ?></div><div class="kpi-sub">Pending</div></div></div>
  <div class="stat-mini"><div class="kpi-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $counts['sent'] ?></div><div class="kpi-sub">Sent to device</div></div></div>
  <div class="stat-mini"><div class="kpi-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $counts['enrolled'] ?></div><div class="kpi-sub">Enrolled</div></div></div>
  <div class="stat-mini"><div class="kpi-icon rose"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $counts['failed'] ?></div><div class="kpi-sub">Failed</div></div></div>
</div>

<div class="panel">
  <div class="panel-head">
    <div><h3>Enrolment Queue</h3><p class="sub">Employees pushed to their department's machine to register a fingerprint</p></div>
    <div class="flt">
      <a href="enrollment.php" class="btn <?= $statusFilter===''?'':'' ?>">All</a>
      <a href="enrollment.php?status=pending" class="btn">Pending</a>
      <a href="enrollment.php?status=enrolled" class="btn">Enrolled</a>
    </div>
  </div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Employee</th><th>Department</th><th>Target Device</th><th>Status</th><th>Note</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if ($rows): foreach ($rows as $r):
        $badge = ['pending'=>'badge-late','sent'=>'badge-dept','enrolled'=>'badge-present','failed'=>'badge-absent'][$r['status']] ?? 'badge-dept';
        $colors=['#6366F1','#8B5CF6','#0BA5C7','#0EA372','#E5484D','#D98A0B']; $bg=$colors[(int)$r['employee_id']%6];
      ?>
        <tr>
          <td><div class="emp-cell"><div class="emp-avatar" style="background:<?= $bg ?>"><?= e(strtoupper(substr($r['first_name'] ?? '?',0,1))) ?></div><div><div class="emp-name"><?= e(trim(($r['first_name']??'').' '.($r['last_name']??'')) ?: 'Employee #'.$r['employee_id']) ?></div><div class="emp-id">ID <?= (int)$r['user_id'] ?></div></div></div></td>
          <td><?= $r['dept_name'] ? '<span class="badge badge-dept">'.e($r['dept_name']).'</span>' : '<span style="color:var(--dim)">—</span>' ?></td>
          <td><?= $r['device_name'] ? e($r['device_name']).' <span style="color:var(--dim);font-size:11px">'.e($r['ip_address']).'</span>' : '<span style="color:var(--dim)">—</span>' ?></td>
          <td><span class="badge <?= $badge ?>"><?= ucfirst(e($r['status'])) ?></span></td>
          <td style="max-width:280px;color:var(--muted);font-size:12.5px"><?= e($r['message'] ?: '—') ?></td>
          <td>
            <div class="row-actions">
              <?php if ($r['status'] !== 'enrolled'): ?>
              <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="retry"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="row-act" title="Re-send to device"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg></button>
              </form>
              <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="enrolled"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="row-act success" title="Mark as enrolled"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></button>
              </form>
              <?php endif; ?>
              <form method="post" onsubmit="return confirm('Remove this request?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="row-act danger" title="Remove"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="6"><div class="empty-state"><h4>No enrolment requests</h4><p>Add an employee (with a department) to create one automatically.</p></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($missing): ?>
<div class="panel">
  <div class="panel-head"><div><h3>Employees without a request</h3><p class="sub">Trigger fingerprint enrolment for existing employees</p></div></div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Employee</th><th>Department</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($missing as $m):
        $colors=['#6366F1','#8B5CF6','#0BA5C7','#0EA372','#E5484D','#D98A0B']; $bg=$colors[(int)$m['id']%6];
      ?>
        <tr>
          <td><div class="emp-cell"><div class="emp-avatar" style="background:<?= $bg ?>"><?= e(strtoupper(substr($m['first_name'],0,1))) ?></div><div><div class="emp-name"><?= e($m['first_name'].' '.$m['last_name']) ?></div><div class="emp-id">ID <?= (int)$m['user_id'] ?></div></div></div></td>
          <td><?= $m['dept_name'] ? '<span class="badge badge-dept">'.e($m['dept_name']).'</span>' : '<span style="color:var(--dim)">No department</span>' ?></td>
          <td>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="request"><input type="hidden" name="employee_id" value="<?= (int)$m['id'] ?>">
              <button class="btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 11c0 3.5-2 6-2 6M7 8a5 5 0 0 1 10 0v2"/></svg> Request enrolment</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
