<?php
/**
 * Device User Manager
 * Shows all users stored on the ZKTeco device and lets you
 * link them to employees in the database — no terminal menu needed.
 */
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../core/device.php';
require_perm('*');

$pageTitle   = "Device Users";
$currentPage = "devices";

$message = ''; $messageType = '';
$deviceUsers = [];
$selDeviceId = (int) inp($_GET, 'device');

// Get default device (IN01)
if (!$selDeviceId) {
    $d = db_one("SELECT id FROM devices WHERE ip_address='192.168.100.201' AND status='active' LIMIT 1");
    if ($d) $selDeviceId = (int)$d['id'];
}

$selDevice = $selDeviceId ? db_one("SELECT * FROM devices WHERE id=?", [$selDeviceId]) : null;
$employees = db_all("SELECT id, user_id, first_name, last_name FROM employees ORDER BY first_name");
$devices   = db_all("SELECT * FROM devices WHERE status='active' ORDER BY name");

// ── Load users from device ────────────────────────────────────────
if ($selDevice && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['device'])) {
    if (!device_lib_available()) {
        $message = "ZKTeco library not installed. Run: composer install"; $messageType = 'danger';
    } else {
        try {
            $zk = new \Rats\Zkteco\Lib\ZKTeco($selDevice['ip_address'], (int)$selDevice['port']);
            if ($zk->connect()) {
                $raw = $zk->getUser();
                $zk->disconnect();
                // Convert to sorted array with internal uid
                foreach ((array)$raw as $userIdStr => $u) {
                    $deviceUsers[] = [
                        'device_uid' => (int)($u['uid'] ?? 0),
                        'device_userid' => trim((string)$userIdStr),
                        'name' => trim($u['name'] ?? ''),
                        'role' => (int)($u['role'] ?? 0),
                    ];
                }
                usort($deviceUsers, fn($a,$b) => $a['device_uid'] <=> $b['device_uid']);
            } else {
                $message = "Cannot connect to {$selDevice['ip_address']}:{$selDevice['port']}"; $messageType = 'danger';
            }
        } catch (\Throwable $e) {
            $message = "Device error: ".$e->getMessage(); $messageType = 'danger';
        }
    }
}

// ── POST: link device user to employee ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action       = inp($_POST, 'action');
    $deviceUid    = (int) inp($_POST, 'device_uid');    // internal device index
    $employeeId   = (int) inp($_POST, 'employee_id');   // our DB employee id
    $devId        = (int) inp($_POST, 'device_id');
    $device       = db_one("SELECT * FROM devices WHERE id=?", [$devId]);
    $emp          = db_one("SELECT * FROM employees WHERE id=?", [$employeeId]);

    if ($action === 'link' && $device && $emp) {
        // Update the device: set the userid on the machine to the employee's user_id
        // This means setUser(device_uid, employee_user_id_as_string, name, ...)
        if (!device_lib_available()) {
            $message = "ZKTeco library not installed."; $messageType = 'danger';
        } else {
            try {
                $zk = new \Rats\Zkteco\Lib\ZKTeco($device['ip_address'], (int)$device['port']);
                if (!$zk->connect()) {
                    $message = "Cannot connect to device."; $messageType = 'danger';
                } else {
                    $name = trim($emp['first_name'].' '.$emp['last_name']);
                    if (strlen($name) > 24) $name = substr($name, 0, 24);
                    $zk->disableDevice();
                    // Overwrite the device user: keep same internal uid, set userid = employee's user_id
                    $ok = $zk->setUser($deviceUid, (string)$emp['user_id'], $name, '', 0, 0);
                    $zk->enableDevice();
                    $zk->disconnect();
                    if ($ok !== false) {
                        $message = "✓ Device user (uid=$deviceUid) linked to {$emp['first_name']} {$emp['last_name']} (ID {$emp['user_id']}). Now sync to import their punches.";
                        $messageType = 'success';
                        audit('device.link_user', 'devices', $devId);
                    } else {
                        $message = "setUser() failed. Check device connection."; $messageType = 'danger';
                    }
                }
            } catch (\Throwable $e) {
                $message = "Error: ".$e->getMessage(); $messageType = 'danger';
            }
        }
        // Reload device users after change
        $selDeviceId = $devId;
        $selDevice   = $device;
        if (!$message || $messageType === 'success') {
            try {
                $zk2 = new \Rats\Zkteco\Lib\ZKTeco($device['ip_address'], (int)$device['port']);
                if ($zk2->connect()) {
                    $raw = $zk2->getUser(); $zk2->disconnect();
                    $deviceUsers = [];
                    foreach ((array)$raw as $uid => $u) {
                        $deviceUsers[] = ['device_uid'=>(int)($u['uid']??0),'device_userid'=>trim((string)$uid),'name'=>trim($u['name']??''),'role'=>(int)($u['role']??0)];
                    }
                    usort($deviceUsers, fn($a,$b) => $a['device_uid'] <=> $b['device_uid']);
                }
            } catch (\Throwable $e) {}
        }
    }
}

// Build employee lookup by user_id for display
$empByUserId = [];
foreach ($employees as $e) { $empByUserId[(int)$e['user_id']] = $e; }

include "includes/header.php";
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>" style="margin-bottom:18px">
    <?= e($message) ?>
</div>
<?php endif; ?>

<div class="page-top">
    <div class="greet">
        <h2 style="font-size:18px">Device User Manager</h2>
        <p>View and link device users to employees — no terminal menu needed</p>
    </div>
    <div class="flt">
        <?php foreach ($devices as $d): ?>
        <a href="device_users.php?device=<?= (int)$d['id'] ?>"
           class="btn <?= $d['id']==$selDeviceId ? 'btn-primary' : '' ?>"
           style="font-size:14px">
            <?= e($d['name']) ?> (<?= e($d['ip_address']) ?>)
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!$selDevice): ?>
<div class="panel"><div class="panel-body" style="text-align:center;padding:48px;color:var(--muted)">
    <h4>Select a device above to view its users</h4>
</div></div>
<?php elseif (empty($deviceUsers) && !$message): ?>
<div class="panel"><div class="panel-body">
    <p style="color:var(--muted);margin-bottom:14px;font-size:14.5px">Connecting to <strong><?= e($selDevice['name']) ?></strong> at <strong><?= e($selDevice['ip_address']) ?></strong>…</p>
    <a href="device_users.php?device=<?= (int)$selDeviceId ?>" class="btn btn-primary">Load Device Users</a>
</div></div>
<?php else: ?>

<!-- Explanation banner -->
<div style="padding:16px 20px;background:rgba(129,140,248,.08);border:1px solid rgba(129,140,248,.2);border-radius:12px;margin-bottom:20px;font-size:14px;color:var(--text-2)">
    <strong style="color:var(--accent)">How to fix "Absent" for terminal-enrolled users:</strong>
    Find the user in the table below, check if their <em>Device ID</em> is empty or doesn't match their employee number,
    then click <strong>Link to Employee</strong> to fix it. After linking, go to Sync Devices.
</div>

<div class="panel">
    <div class="panel-head">
        <div>
            <h3><?= e($selDevice['name']) ?></h3>
            <p class="sub"><?= count($deviceUsers) ?> users enrolled on device · <?= e($selDevice['ip_address']) ?></p>
        </div>
        <a href="sync_attendance.php" class="btn btn-primary">Sync Devices →</a>
    </div>
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Internal UID</th>
                    <th>Device ID (userid)</th>
                    <th>Name on device</th>
                    <th>Linked employee</th>
                    <th>Status</th>
                    <th>Fix</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($deviceUsers as $du):
                $devUid    = $du['device_uid'];
                $devUserId = $du['device_userid'];
                $matchEmp  = isset($empByUserId[(int)$devUserId]) ? $empByUserId[(int)$devUserId] : null;
                $hasId     = $devUserId !== '' && (int)$devUserId > 0;
                $colors    = ['#6366F1','#8B5CF6','#0BA5C7','#0EA372','#E5484D','#D98A0B'];
                $bg        = $colors[$devUid % 6];
            ?>
            <tr>
                <td class="mono" style="font-size:16px;font-weight:750;color:var(--accent)"><?= $devUid ?></td>
                <td class="mono">
                    <?php if ($hasId): ?>
                        <span style="font-size:15px;font-weight:700;color:var(--text)"><?= e($devUserId) ?></span>
                    <?php else: ?>
                        <span style="color:var(--rose);font-weight:600;font-size:14px">⚠ EMPTY — punch will be lost!</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="emp-cell">
                        <div class="emp-avatar" style="background:<?= $bg ?>"><?= e(strtoupper(substr($du['name']?:' ',0,1))) ?></div>
                        <span style="font-weight:600"><?= e($du['name'] ?: '(no name)') ?></span>
                    </div>
                </td>
                <td>
                    <?php if ($matchEmp): ?>
                        <div class="emp-cell">
                            <div class="emp-avatar" style="background:var(--green-s);color:var(--green);font-size:16px">✓</div>
                            <div>
                                <div style="font-weight:600;color:var(--text)"><?= e($matchEmp['first_name'].' '.$matchEmp['last_name']) ?></div>
                                <div style="font-size:12px;color:var(--muted-2)">user_id=<?= (int)$matchEmp['user_id'] ?></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <span style="color:var(--rose);font-size:13px">No employee linked</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($matchEmp): ?>
                        <span class="badge badge-present">Linked ✓</span>
                    <?php elseif (!$hasId): ?>
                        <span class="badge badge-absent">ID Missing</span>
                    <?php else: ?>
                        <span class="badge badge-late">Mismatch</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!$matchEmp): ?>
                    <form method="POST" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="link">
                        <input type="hidden" name="device_uid" value="<?= $devUid ?>">
                        <input type="hidden" name="device_id" value="<?= (int)$selDeviceId ?>">
                        <select name="employee_id" required
                            style="height:34px;padding:0 10px;background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13.5px;font-family:inherit;min-width:180px">
                            <option value="">— select employee —</option>
                            <?php foreach ($employees as $emp): ?>
                            <option value="<?= (int)$emp['id'] ?>">
                                <?= e($emp['first_name'].' '.$emp['last_name']) ?> (ID <?= (int)$emp['user_id'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary" style="font-size:13px;padding:7px 14px">Link</button>
                    </form>
                    <?php else: ?>
                    <span style="color:var(--muted);font-size:13px">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($deviceUsers)): ?>
            <tr><td colspan="6"><div class="empty-state"><h4>No users on device</h4><p>Enroll employees first via Enroll Fingerprint.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
