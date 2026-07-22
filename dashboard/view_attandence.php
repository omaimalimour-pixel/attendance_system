<?php
require __DIR__ . '/bootstrap.php';
$pageTitle = "Attendance Details";
$currentPage = "attendance";

$userId = (int) ($_GET['id'] ?? 0);
$employee = db_one(
    "SELECT e.*, dep.name AS department FROM employees e
     LEFT JOIN departments dep ON dep.id = e.department_id WHERE e.user_id=?",
    [$userId]
);
if (!$employee) { header("Location: employees.php"); exit; }

$records = db_all(
    "SELECT a.*, d.name AS device_name FROM attendance a
     LEFT JOIN devices d ON d.id = a.device_id
     WHERE a.user_id=? ORDER BY a.date DESC, a.time DESC LIMIT 200",
    [$userId]
);
$totalDays   = (int) db_val("SELECT COUNT(DISTINCT date) FROM attendance WHERE user_id=?", [$userId]);
$totalPunches= (int) db_val("SELECT COUNT(*) FROM attendance WHERE user_id=?", [$userId]);
$canManage   = can('attendance.manage');

include "includes/header.php";
?>

<div class="page-top">
  <a href="attendance.php" class="btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg> Back</a>
  <?php if ($canManage): ?>
  <div class="flt">
    <a href="edit_attendance.php?id=<?= $userId ?>" class="btn">Edit Records</a>
    <form method="post" action="delete_attandence.php" onsubmit="return confirm('Delete ALL attendance for this employee?')">
      <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $userId ?>">
      <button class="btn btn-danger">Delete All</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<div class="panel"><div class="panel-body">
  <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
    <div class="emp-avatar" style="width:56px;height:56px;font-size:20px;border-radius:14px;background:linear-gradient(135deg,#7c6aff,#38bdf8)"><?= e(strtoupper(substr($employee['first_name'],0,1))) ?></div>
    <div>
      <h3 style="font-size:19px;font-weight:750"><?= e($employee['first_name'].' '.$employee['last_name']) ?></h3>
      <p style="color:var(--muted);font-size:12.5px;margin-top:3px">
        User ID <strong><?= (int)$employee['user_id'] ?></strong> ·
        <?= e($employee['department'] ?: 'No department') ?> ·
        <?= e($employee['position'] ?: '—') ?>
      </p>
    </div>
  </div>
</div></div>

<div class="stats-row">
  <div class="stat-mini"><div class="kpi-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $totalDays ?></div><div class="kpi-sub">Days Attended</div></div></div>
  <div class="stat-mini"><div class="kpi-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $totalPunches ?></div><div class="kpi-sub">Total Punches</div></div></div>
</div>

<div class="panel">
  <div class="panel-head"><div><h3>Punch History</h3><p class="sub">Most recent 200 records</p></div></div>
  <div class="table-wrap"><table class="data">
    <thead><tr><th>#</th><th>Date</th><th>Time</th><th>Type</th><th>Device</th></tr></thead>
    <tbody>
    <?php if ($records): $n=1; foreach ($records as $r):
      $tc = strtoupper($r['type'])==='IN' ? 'badge-present' : 'badge-absent';
    ?>
      <tr>
        <td class="mono"><?= $n++ ?></td>
        <td class="mono"><?= e(date("d/m/Y", strtotime($r['date']))) ?></td>
        <td class="mono"><?= e($r['time']) ?></td>
        <td><span class="badge <?= $tc ?>"><?= e($r['type']) ?></span></td>
        <td><?= e($r['device_name'] ?: 'ZKTeco') ?></td>
      </tr>
    <?php endforeach; else: ?>
      <tr><td colspan="5"><div class="empty-state"><h4>No records</h4><p>No punches recorded for this employee yet.</p></div></td></tr>
    <?php endif; ?>
    </tbody>
  </table></div>
</div>

<?php include "includes/footer.php"; ?>
