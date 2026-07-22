<?php
require __DIR__ . '/bootstrap.php';
$pageTitle = "Attendance";
$currentPage = "attendance";
include "attendance_data.php";
$canManage = can('attendance.manage');
include "includes/header.php";

// Show sync result banner if redirected back from sync
$synced   = isset($_GET['synced']);
$imported = (int)($_GET['imported'] ?? 0);
?>

<?php if ($synced): ?>
<div class="alert alert-<?= $imported > 0 ? 'success' : 'warning' ?>" style="margin-bottom:18px">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:17px;height:17px;flex-shrink:0"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
  <?php if ($imported > 0): ?>
    Sync complete — <strong><?= $imported ?> new punch<?= $imported === 1 ? '' : 'es' ?></strong> imported from the device. Attendance updated.
  <?php else: ?>
    Sync complete — no new punches found (device may already be up to date).
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="stats-row">
  <div class="stat-mini"><div class="kpi-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $totalEmployees ?></div><div class="kpi-sub">Total</div></div></div>
  <div class="stat-mini"><div class="kpi-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $totalPresent ?></div><div class="kpi-sub">Present</div></div></div>
  <div class="stat-mini"><div class="kpi-icon rose"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $totalAbsent ?></div><div class="kpi-sub">Absent</div></div></div>
  <div class="stat-mini"><div class="kpi-icon amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $totalLate ?></div><div class="kpi-sub">Late</div></div></div>
</div>

<div class="panel">
  <div class="panel-head"><div><h3>Attendance Records</h3><p class="sub"><?= e(date("l, F j, Y", strtotime($selectedDate))) ?></p></div>
    <div style="display:flex;gap:8px">
      <form method="post" action="sync_attendance.php" style="display:inline">
        <?= csrf_field() ?><input type="hidden" name="scope" value="all">
        <button class="btn btn-primary" title="Pull punches from the ZKTeco device right now">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>
          Sync Device Now
        </button>
      </form>
    </div>
  </div>
  <form method="GET" class="search-bar">
    <input type="date" name="date" value="<?= e($selectedDate) ?>" style="min-width:150px;flex:0;">
    <input type="text" name="search" placeholder="Search name, ID, department..." value="<?= e($search) ?>">
    <select name="status"><option value="">All</option><option value="Present" <?= $statusFilter=='Present'?'selected':'' ?>>Present</option><option value="Absent" <?= $statusFilter=='Absent'?'selected':'' ?>>Absent</option><option value="Late" <?= $statusFilter=='Late'?'selected':'' ?>>Late</option></select>
    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="attendance.php" class="btn">Reset</a>
  </form>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>#</th><th>Employee</th><th>Department</th><th>First IN</th><th>Last OUT</th><th>Punches</th><th>Hours</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php $no=1; foreach ($attendanceRows as $row):
        $st="Absent"; $bc="badge-absent";
        if ($row['first_in']) { $st="Present"; $bc="badge-present"; if ($row['first_in'] > $workStart) { $st="Late"; $bc="badge-late"; } }
        if ($statusFilter !== "" && $statusFilter !== $st) continue;
        $hrs="—"; if ($row['first_in'] && $row['last_out']) { $d=strtotime($row['last_out'])-strtotime($row['first_in']); if ($d>0) $hrs=gmdate("H:i",$d); }
        $colors=['#6366F1','#8B5CF6','#0BA5C7','#0EA372','#E5484D','#D98A0B']; $bg=$colors[$row['id']%6];
      ?>
        <tr>
          <td class="mono"><?= $no++ ?></td>
          <td><div class="emp-cell"><div class="emp-avatar" style="background:<?= $bg ?>"><?= e(strtoupper(substr($row['first_name'],0,1))) ?></div><div><div class="emp-name"><?= e($row['first_name'].' '.$row['last_name']) ?></div><div class="emp-id">ID <?= (int)$row['user_id'] ?></div></div></div></td>
          <td><?= e($row['department'] ?: '—') ?></td>
          <td class="mono"><?= e($row['first_in'] ?: '—') ?></td>
          <td class="mono"><?= e($row['last_out'] ?: '—') ?></td>
          <td class="mono"><?= (int)$row['punches'] ?></td>
          <td class="mono"><?= e($hrs) ?></td>
          <td><span class="badge <?= $bc ?>"><?= $st ?></span></td>
          <td>
            <div class="row-actions">
              <a href="view_attandence.php?id=<?= (int)$row['user_id'] ?>" class="row-act" title="View"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a>
              <?php if ($canManage): ?>
              <form method="post" action="delete_attandence.php" style="display:inline" onsubmit="return confirm('Delete this employee\'s punches for <?= e($selectedDate) ?>?')">
                <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$row['user_id'] ?>"><input type="hidden" name="date" value="<?= e($selectedDate) ?>">
                <button class="row-act danger" title="Delete day"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg></button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include "includes/footer.php"; ?>
