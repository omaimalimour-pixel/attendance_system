<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../core/device.php';
require_perm('*');

$pageTitle   = "Device Test";
$currentPage = "devices";

$result  = null;
$users   = [];
$selId   = (int)inp($_GET, 'device_id');

// POST: push a single user for quick test
$pushMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action   = inp($_POST, 'action');
    $deviceId = (int)inp($_POST, 'device_id');
    $device   = db_one("SELECT * FROM devices WHERE id=?", [$deviceId]);

    if ($action === 'test' && $device) {
        $result = device_test_connection($device);
        $users  = device_get_users($device);
        $selId  = $deviceId;
    } elseif ($action === 'push_user' && $device) {
        $empId = (int)inp($_POST, 'employee_id');
        $emp   = db_one("SELECT * FROM employees WHERE id=?", [$empId]);
        if ($emp) {
            [$ok, $msg] = device_push_user($device, $emp);
            if ($ok) {
                enrollment_request_create($emp, current_user()['username'] ?? null);
            }
            $pushMsg = ['ok' => $ok, 'msg' => $msg];
        }
        $result = device_test_connection($device);
        $users  = device_get_users($device);
        $selId  = $deviceId;
    }
}

$devices     = db_all("SELECT d.*, dep.name AS dept_name FROM devices d LEFT JOIN departments dep ON dep.id=d.department_id ORDER BY d.name");
$libOk       = device_lib_available();
$employees   = db_all("SELECT e.id, e.user_id, e.first_name, e.last_name, dep.name AS dept_name FROM employees e LEFT JOIN departments dep ON dep.id=e.department_id ORDER BY e.first_name");

include "includes/header.php";
?>

<?php if (!$libOk): ?>
<div class="alert alert-danger" style="margin-bottom:20px">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    ZKTeco library not found. Run <code style="background:rgba(251,113,133,.15);padding:2px 7px;border-radius:5px">composer install</code> in the project root, then refresh.
</div>
<?php endif; ?>

<div class="row" style="gap:0">
<div class="col-4">
<!-- Device list -->
<div class="panel" style="height:auto">
    <div class="panel-hd"><div><h3>Devices</h3><p class="sub">Select one to test</p></div></div>
    <?php foreach ($devices as $d):
        $isActive = $d['id'] == $selId;
        $dotCol   = $d['status']==='active' ? 'var(--green)' : 'var(--rose)';
    ?>
    <form method="POST" style="border-bottom:1px solid var(--border)">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="test">
        <input type="hidden" name="device_id" value="<?= (int)$d['id'] ?>">
        <button type="submit" style="width:100%;text-align:left;background:<?= $isActive ? 'rgba(129,140,248,.08)' : 'transparent' ?>;border:none;padding:14px 20px;cursor:pointer;display:flex;align-items:center;gap:12px">
            <span style="width:9px;height:9px;border-radius:50%;background:<?= $dotCol ?>;flex-shrink:0"></span>
            <div style="min-width:0">
                <div style="font-weight:650;font-size:13px;color:var(--text)"><?= e($d['name']) ?></div>
                <div style="font-size:11px;color:var(--muted-2)"><?= e($d['ip_address']) ?>:<?= (int)$d['port'] ?> · <?= e($d['dept_name'] ?? '—') ?></div>
            </div>
            <?php if ($isActive): ?>
            <div style="margin-left:auto;font-size:11px;color:var(--accent);font-weight:600">Testing</div>
            <?php endif; ?>
        </button>
    </form>
    <?php endforeach; ?>
</div>
</div>

<div class="col-8">

<?php if ($pushMsg !== null): ?>
<div class="alert alert-<?= $pushMsg['ok'] ? 'success' : 'danger' ?>" style="margin-bottom:16px">
    <?= $pushMsg['ok'] ? '✓' : '✗' ?> <?= e($pushMsg['msg']) ?>
</div>
<?php endif; ?>

<?php if ($result !== null): $dev = db_one("SELECT d.*, dep.name AS dept_name FROM devices d LEFT JOIN departments dep ON dep.id=d.department_id WHERE d.id=?", [$selId]); ?>
<!-- Connection result -->
<div class="panel">
    <div class="panel-hd">
        <div>
            <h3><?= e($dev['name'] ?? 'Device') ?></h3>
            <p class="sub"><?= e($dev['ip_address'] ?? '') ?> · <?= e($dev['dept_name'] ?? '—') ?></p>
        </div>
        <span class="badge <?= $result['ok'] ? 'badge-present' : 'badge-absent' ?>" style="font-size:13px;padding:7px 14px">
            <?= $result['ok'] ? '● Online' : '● Offline' ?>
        </span>
    </div>
    <div class="panel-bd">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:<?= $result['ok'] ? '20px' : '0' ?>">
            <div style="padding:16px;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:12px;text-align:center">
                <div style="font-size:11px;color:var(--muted-2);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">Status</div>
                <div style="font-size:16px;font-weight:750;color:<?= $result['ok'] ? 'var(--green)' : 'var(--rose)' ?>"><?= $result['ok'] ? 'Connected' : 'Unreachable' ?></div>
            </div>
            <?php if ($result['ok']): ?>
            <div style="padding:16px;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:12px;text-align:center">
                <div style="font-size:11px;color:var(--muted-2);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">Device Time</div>
                <div style="font-size:14px;font-weight:650;color:var(--text)"><?= e($result['time'] ?? 'N/A') ?></div>
            </div>
            <div style="padding:16px;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:12px;text-align:center">
                <div style="font-size:11px;color:var(--muted-2);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">Users on device</div>
                <div style="font-size:22px;font-weight:800;color:var(--accent)"><?= (int)$result['users'] ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php if (!$result['ok']): ?>
        <div style="padding:14px;background:var(--red-s);border:1px solid rgba(251,113,133,.2);border-radius:10px;color:var(--rose);font-size:13px"><?= e($result['message']) ?></div>
        <?php endif; ?>
    </div>
</div>

<?php if ($result['ok'] && !empty($users)): ?>
<!-- Users on device -->
<div class="panel">
    <div class="panel-hd"><div><h3>Users registered on device</h3><p class="sub"><?= count($users) ?> enrolled</p></div></div>
    <div class="tw"><table class="dt">
        <thead><tr><th>UID</th><th>User ID</th><th>Name on device</th><th>Role</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u):
            $c=['#6366F1','#8B5CF6','#0BA5C7','#0EA372','#E5484D','#D98A0B'];
            $bg=$c[(int)($u['uid']??0)%6];
        ?>
        <tr>
            <td class="mono"><?= e($u['uid'] ?? '—') ?></td>
            <td class="mono"><?= e($u['userid'] ?? '—') ?></td>
            <td>
                <div class="emp-c">
                    <div class="emp-av" style="background:<?= $bg ?>"><?= e(strtoupper(substr($u['name']??'?',0,1))) ?></div>
                    <span style="font-weight:600;color:var(--text)"><?= e($u['name'] ?? '—') ?></span>
                </div>
            </td>
            <td><span class="badge <?= ($u['role']??0)==14 ? 'badge-dept' : 'b-ok' ?>"><?= ($u['role']??0)==14?'Admin':'User' ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php elseif ($result['ok']): ?>
<div class="panel"><div class="panel-bd"><div class="empty"><h4>No users on device yet</h4><p>Push an employee below to register them on this machine.</p></div></div></div>
<?php endif; ?>

<?php if ($result['ok'] && !empty($employees)): ?>
<!-- Push employee to device -->
<div class="panel">
    <div class="panel-hd"><div><h3>Push employee to this device</h3><p class="sub">Register them so they can enrol their fingerprint at the machine</p></div></div>
    <form method="POST" class="panel-bd" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="push_user">
        <input type="hidden" name="device_id" value="<?= (int)$selId ?>">
        <div style="flex:1;min-width:220px">
            <label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:6px">Employee</label>
            <select name="employee_id" style="width:100%;height:40px;padding:0 12px;background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;font-family:inherit">
                <?php foreach ($employees as $emp): ?>
                <option value="<?= (int)$emp['id'] ?>">
                    <?= e($emp['first_name'].' '.$emp['last_name']) ?> — ID <?= (int)$emp['user_id'] ?> (<?= e($emp['dept_name']??'No dept') ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" type="submit" style="height:40px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
            Push to device
        </button>
    </form>
</div>
<?php endif; ?>

<?php else: ?>
<!-- No test run yet -->
<div class="panel" style="min-height:260px">
    <div class="panel-bd" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px;padding:48px 20px;text-align:center">
        <div style="width:64px;height:64px;border-radius:16px;background:var(--accent-s);display:grid;place-items:center">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.5" width="32" height="32"><rect x="4" y="4" width="16" height="16" rx="2"/><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2"/></svg>
        </div>
        <div>
            <div style="font-size:16px;font-weight:750;color:var(--text);margin-bottom:6px">Select a device to test</div>
            <div style="font-size:13px;color:var(--muted)">Click any device on the left to ping it, view registered users, and push employees.</div>
        </div>
    </div>
</div>
<?php endif; ?>

</div><!-- col-8 -->
</div><!-- row -->

<?php include "includes/footer.php"; ?>
