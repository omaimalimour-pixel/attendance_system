<?php
require __DIR__ . '/bootstrap.php';
$pageTitle = "Employees";
$currentPage = "employees";

// --- Filters & pagination ---
$search = inp($_GET, 'search');
$deptFilter = (int) inp($_GET, 'dept');
$perPage = 12;
$page = max(1, (int) inp($_GET, 'p', '1'));
$offset = ($page - 1) * $perPage;

$where = "WHERE 1=1";
$args = [];
if ($search !== '') {
    $where .= " AND (e.user_id LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ? OR e.position LIKE ?)";
    $like = "%$search%";
    array_push($args, $like, $like, $like, $like);
}
if ($deptFilter > 0) { $where .= " AND e.department_id = ?"; $args[] = $deptFilter; }

$totalRows = (int) db_val("SELECT COUNT(*) FROM employees e $where", $args);
$totalPages = max(1, (int) ceil($totalRows / $perPage));

// Include the latest fingerprint-enrolment status per employee (if the table exists)
$hasEnroll = db_table_exists('enrollment_requests');
$enrollSelect = $hasEnroll
    ? ", (SELECT r.status FROM enrollment_requests r WHERE r.employee_id = e.id
          ORDER BY FIELD(r.status,'enrolled','sent','pending','failed') LIMIT 1) AS enroll_status"
    : ", NULL AS enroll_status";

$rows = db_all(
    "SELECT e.*, dep.name AS dept_name $enrollSelect
     FROM employees e LEFT JOIN departments dep ON dep.id = e.department_id
     $where ORDER BY e.id DESC LIMIT $perPage OFFSET $offset",
    $args
);

$total     = (int) db_val("SELECT COUNT(*) FROM employees");
$deptCount = (int) db_val("SELECT COUNT(*) FROM departments");
$newMonth  = (int) db_val("SELECT COUNT(*) FROM employees WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())");
$departments = db_all("SELECT id, name FROM departments ORDER BY name");
$canManage = can('employees.manage');

include "includes/header.php";
?>

<div class="stats-row">
  <div class="stat-mini"><div class="kpi-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $total ?></div><div class="kpi-sub">Total Employees</div></div></div>
  <div class="stat-mini"><div class="kpi-icon violet"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $deptCount ?></div><div class="kpi-sub">Departments</div></div></div>
  <div class="stat-mini"><div class="kpi-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $newMonth ?></div><div class="kpi-sub">New This Month</div></div></div>
</div>

<div class="panel">
  <div class="panel-head"><div><h3>All Employees</h3><p class="sub"><?= $totalRows ?> matching · <?= $total ?> total</p></div>
    <?php if ($canManage): ?><a href="add_employee.php" class="btn btn-primary">+ Add Employee</a><?php endif; ?>
  </div>
  <form method="GET" class="search-bar">
    <input type="text" name="search" placeholder="Search name, ID, position..." value="<?= e($search) ?>">
    <select name="dept"><option value="0">All departments</option>
      <?php foreach ($departments as $d): ?><option value="<?= (int)$d['id'] ?>" <?= $deptFilter==$d['id']?'selected':'' ?>><?= e($d['name']) ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if ($search || $deptFilter): ?><a href="employees.php" class="btn">Reset</a><?php endif; ?>
  </form>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Employee</th><th>User ID</th><th>Department</th><th>Position</th><th>Fingerprint</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if ($rows): foreach ($rows as $row):
        $colors=['#6366F1','#8B5CF6','#0BA5C7','#0EA372','#E5484D','#D98A0B']; $bg=$colors[$row['id']%6];
        $st = ($row['status'] ?? 'active');
        $fp = $row['enroll_status'] ?? null;
        $fpMap = ['enrolled'=>['badge-present','Enrolled'],'sent'=>['badge-dept','Sent'],'pending'=>['badge-late','Pending'],'failed'=>['badge-absent','Failed']];
      ?>
        <tr>
          <td><div class="emp-cell"><div class="emp-avatar" style="background:<?= $bg ?>"><?= e(strtoupper(substr($row['first_name'],0,1))) ?></div><div><div class="emp-name"><?= e($row['first_name'].' '.$row['last_name']) ?></div><div class="emp-id"><?= e($row['email'] ?: 'ID '.$row['user_id']) ?></div></div></div></td>
          <td class="mono"><?= (int)$row['user_id'] ?></td>
          <td><?= $row['dept_name'] ? '<span class="badge badge-dept">'.e($row['dept_name']).'</span>' : '<span style="color:#98A0B3">—</span>' ?></td>
          <td><?= e($row['position'] ?: '—') ?></td>
          <td><?php if ($fp && isset($fpMap[$fp])): ?><span class="badge <?= $fpMap[$fp][0] ?>"><?= $fpMap[$fp][1] ?></span><?php else: ?><span style="color:#98A0B3">—</span><?php endif; ?></td>
          <td><span class="badge <?= $st==='active'?'badge-present':'badge-absent' ?>"><?= ucfirst(e($st)) ?></span></td>
          <td>
            <div class="row-actions">
              <a href="view_attandence.php?id=<?= (int)$row['user_id'] ?>" class="row-act" title="View"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a>
              <?php if ($canManage): ?>
              <a href="edit_employee.php?id=<?= (int)$row['id'] ?>" class="row-act success" title="Edit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a>
              <form method="post" action="delete_employee.php" style="display:inline" onsubmit="return confirm('Delete this employee and all their punches?')">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                <button class="row-act danger" title="Delete"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg></button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="7"><div class="empty-state"><h4>No employees found</h4><p>Try adjusting your search or add a new employee.</p></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
  <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--border)">
    <span style="font-size:12.5px;color:#98A0B3">Page <?= $page ?> of <?= $totalPages ?></span>
    <div class="flt">
      <?php $qs = fn($n) => '?' . http_build_query(array_filter(['search'=>$search,'dept'=>$deptFilter?:null,'p'=>$n])); ?>
      <?php if ($page>1): ?><a class="btn" href="<?= e($qs($page-1)) ?>">← Prev</a><?php endif; ?>
      <?php if ($page<$totalPages): ?><a class="btn" href="<?= e($qs($page+1)) ?>">Next →</a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include "includes/footer.php"; ?>
