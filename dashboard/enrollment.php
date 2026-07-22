<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../core/device.php';
require_perm('employees.manage');

$pageTitle   = "Fingerprint Enrolment";
$currentPage = "enrollment";

$message = ""; $messageType = "";

/* ── POST actions ─────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = inp($_POST, 'action');
    $id     = (int)inp($_POST, 'id');

    if ($action === 'retry') {
        [$ok, $msg] = enrollment_retry($id, current_user()['username'] ?? null);
        $message     = $ok ? "✓ Pushed to device — employee can now scan their finger." : "✗ " . $msg;
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
        $emp = db_one("SELECT * FROM employees WHERE id=?", [(int)inp($_POST, 'employee_id')]);
        if ($emp) {
            $res = enrollment_request_create($emp, current_user()['username'] ?? null);
            $ok  = !empty(array_filter($res, fn($r) => $r['ok']));
            $message     = $ok
                ? "✓ Pushed to device — ask " . e($emp['first_name']) . " to scan their finger."
                : "Request saved as pending — device may be offline.";
            $messageType = $ok ? "success" : "warning";
        }
    }
}

/* ── Stats ──────────────────────────────────────────── */
$counts = [];
foreach (['pending','sent','enrolled','failed'] as $s) {
    $counts[$s] = (int)db_val("SELECT COUNT(*) FROM enrollment_requests WHERE status=?", [$s]);
}

/* ── List ─────────────────────────────────────────── */
$statusFilter = inp($_GET, 'status');
$where = ''; $args = [];
if (in_array($statusFilter, ['pending','sent','enrolled','failed'], true)) {
    $where = "WHERE r.status=?"; $args[] = $statusFilter;
}
$rows = db_all(
    "SELECT r.*, e.first_name, e.last_name, e.user_id AS emp_uid,
            dep.name AS dept_name, d.name AS device_name, d.ip_address, d.port
     FROM enrollment_requests r
     LEFT JOIN employees e   ON e.id  = r.employee_id
     LEFT JOIN departments dep ON dep.id = r.department_id
     LEFT JOIN devices d     ON d.id  = r.device_id
     $where
     ORDER BY FIELD(r.status,'pending','failed','sent','enrolled'), r.created_at DESC
     LIMIT 300",
    $args
);

/* ── Employees without a request ─────────────────── */
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

<!-- Alerts -->
<?php if ($message): ?>
<div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'warning' ? 'warning' : 'danger') ?>" style="margin-bottom:18px">
    <?= e($message) ?>
</div>
<?php endif; ?>

<?php if (!$libOk): ?>
<div class="alert alert-danger" style="margin-bottom:18px">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:17px;height:17px;flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    ZKTeco library missing. Run <code>composer install</code> in the project root. Until then, all requests stay <strong>pending</strong>.
</div>
<?php endif; ?>

<!-- Header row -->
<div class="page-top">
    <div class="greet">
        <h2>Fingerprint Enrolment</h2>
        <p>Push employees to their department's ZKTeco machine so they can register their fingerprint.</p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="device_test.php" class="btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><circle cx="12" cy="12" r="3"/></svg>
            Test Devices
        </a>
        <a href="enrollment.php" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4v16h16M9 16l3-6 3 6M9 10h6"/></svg>
            Refresh
        </a>
    </div>
</div>

<!-- KPI row -->
<div class="stats-row">
    <div class="stat-mini">
        <div class="kpi-icon amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div><div class="kpi-value" style="font-size:22px"><?= $counts['pending'] ?></div><div class="kpi-sub">Pending</div></div>
    </div>
    <div class="stat-mini">
        <div class="kpi-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg></div>
        <div><div class="kpi-value" style="font-size:22px"><?= $counts['sent'] ?></div><div class="kpi-sub">Sent to device</div></div>
    </div>
    <div class="stat-mini">
        <div class="kpi-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        <div><div class="kpi-value" style="font-size:22px"><?= $counts['enrolled'] ?></div><div class="kpi-sub">Enrolled ✓</div></div>
    </div>
    <div class="stat-mini">
        <div class="kpi-icon rose"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
        <div><div class="kpi-value" style="font-size:22px"><?= $counts['failed'] ?></div><div class="kpi-sub">Failed</div></div>
    </div>
</div>

<!-- Queue -->
<div class="panel">
    <div class="panel-hd">
        <div><h3>Enrolment Queue</h3><p class="sub">Employees awaiting or completed fingerprint registration</p></div>
        <div style="display:flex;gap:8px">
            <a href="enrollment.php" class="btn <?= $statusFilter==='' ? 'btn-primary' : '' ?>">All (<?= array_sum($counts) ?>)</a>
            <a href="enrollment.php?status=pending" class="btn <?= $statusFilter==='pending' ? 'btn-primary' : '' ?>">Pending</a>
            <a href="enrollment.php?status=sent" class="btn <?= $statusFilter==='sent' ? 'btn-primary' : '' ?>">Sent</a>
            <a href="enrollment.php?status=enrolled" class="btn <?= $statusFilter==='enrolled' ? 'btn-primary' : '' ?>">Enrolled</a>
        </div>
    </div>
    <div class="tw">
        <table class="dt">
            <thead>
                <tr>
                    <th>Employee</th><th>Department</th><th>Target Device (IP)</th>
                    <th>Status</th><th>Message</th><th>Updated</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $r):
                $badge  = ['pending'=>'badge-late','sent'=>'badge-dept','enrolled'=>'badge-present','failed'=>'badge-absent'][$r['status']] ?? 'badge-dept';
                $colors = ['#6366F1','#8B5CF6','#0BA5C7','#0EA372','#E5484D','#D98A0B'];
                $bg     = $colors[(int)$r['employee_id'] % 6];
                $name   = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?: 'Employee #' . $r['employee_id'];
            ?>
                <tr>
                    <td>
                        <div class="emp-c">
                            <div class="emp-av" style="background:<?= $bg ?>"><?= e(strtoupper(substr($name, 0, 1))) ?></div>
                            <div>
                                <div class="emp-nm"><?= e($name) ?></div>
                                <div class="emp-id">ID <?= (int)$r['emp_uid'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= $r['dept_name'] ? '<span class="badge b-dp">'.e($r['dept_name']).'</span>' : '<span style="color:var(--muted-2)">—</span>' ?></td>
                    <td>
                        <?php if ($r['device_name']): ?>
                        <div style="font-weight:600;font-size:13px;color:var(--text)"><?= e($r['device_name']) ?></div>
                        <div style="font-size:11px;color:var(--muted-2)"><?= e($r['ip_address']) ?>:<?= (int)$r['port'] ?></div>
                        <?php else: ?>
                        <span style="color:var(--rose);font-size:12px">No device assigned</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $badge ?>"><?= ucfirst(e($r['status'])) ?></span></td>
                    <td style="max-width:240px;font-size:12px;color:var(--muted)"><?= e($r['message'] ?: '—') ?></td>
                    <td style="font-size:11.5px;color:var(--muted-2)" class="mono"><?= $r['updated_at'] ? date('d/m H:i', strtotime($r['updated_at'])) : '—' ?></td>
                    <td>
                        <div class="acts">
                            <?php if ($r['status'] !== 'enrolled'): ?>
                            <!-- Retry: re-push to device -->
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="retry">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button class="act" title="Re-push to device">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                </button>
                            </form>
                            <!-- Mark enrolled -->
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="enrolled">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button class="act success" title="Confirm fingerprint enrolled at machine">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                </button>
                            </form>
                            <?php endif; ?>
                            <!-- Remove -->
                            <form method="post" onsubmit="return confirm('Remove this request?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button class="act dg" title="Remove">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="7">
                    <div class="empty">
                        <h4>No enrolment requests yet</h4>
                        <p>Add an employee with a department — an enrolment request is created automatically.</p>
                    </div>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Employees without request -->
<?php if ($missing): ?>
<div class="panel">
    <div class="panel-hd">
        <div><h3>Employees not yet enrolled</h3><p class="sub">Trigger enrolment for existing employees</p></div>
    </div>
    <div class="tw">
        <table class="dt">
            <thead><tr><th>Employee</th><th>Department</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($missing as $m):
                $colors = ['#6366F1','#8B5CF6','#0BA5C7','#0EA372','#E5484D','#D98A0B'];
                $bg = $colors[(int)$m['id'] % 6];
            ?>
            <tr>
                <td>
                    <div class="emp-c">
                        <div class="emp-av" style="background:<?= $bg ?>"><?= e(strtoupper(substr($m['first_name'], 0, 1))) ?></div>
                        <div><div class="emp-nm"><?= e($m['first_name'].' '.$m['last_name']) ?></div><div class="emp-id">ID <?= (int)$m['user_id'] ?></div></div>
                    </div>
                </td>
                <td><?= $m['dept_name'] ? '<span class="badge b-dp">'.e($m['dept_name']).'</span>' : '<span style="color:var(--rose);font-size:12px">No department</span>' ?></td>
                <td>
                    <form method="post" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="request">
                        <input type="hidden" name="employee_id" value="<?= (int)$m['id'] ?>">
                        <button class="btn btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                            Push to device
                        </button>
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
