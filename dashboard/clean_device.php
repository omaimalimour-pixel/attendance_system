<?php
/**
 * ChronoX — Device User Cleanup & Re-register
 *
 * THE PROBLEM: Previous enrollment code pushed users with the wrong
 * internal slot (uid=990 instead of next available slot like uid=4).
 * This created garbled/corrupted entries on the ZKTeco device —
 * visible as emojis or unreadable text in the ID field on the machine screen.
 *
 * THIS FIX:
 * 1. Connects to the ZKTeco IN01
 * 2. Removes ALL existing user registrations (NOT fingerprint templates!)
 * 3. Re-pushes every employee from the database with correct slot numbers
 *    (slot 1, 2, 3... sequentially) and correct userid strings ("990", "2007"...)
 * 4. After this, existing fingerprint templates remain BUT may need re-enrollment
 *    if the old slot numbers don't match.
 *
 * IMPORTANT: This does NOT delete fingerprint templates stored on the device.
 * However, fingerprints are linked to slot numbers. If slot numbers change,
 * employees may need to re-scan their fingers once.
 *
 * URL: http://localhost/clocking/dashboard/clean_device.php
 */
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../core/device.php';
require_perm('*');

$pageTitle   = "Clean Device Users";
$currentPage = "devices";

$message = ''; $messageType = '';
$log = [];
$deviceUsers  = [];
$reregistered = 0;

$device = db_one("SELECT * FROM devices WHERE ip_address='192.168.100.201' AND status='active'");

// ── POST: perform the cleanup ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $device) {
    csrf_verify();
    $action = inp($_POST, 'action');

    if (!device_lib_available()) {
        $message = "ZKTeco library not installed. Run: composer install"; $messageType = 'danger';
    } else {
        try {
            $zk = new \Rats\Zkteco\Lib\ZKTeco($device['ip_address'], (int)$device['port']);
            @socket_set_option($zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 8, 'usec' => 0]);

            if (!$zk->connect()) {
                $message = "Cannot connect to device. Is it powered on?"; $messageType = 'danger';
            } else {
                if ($action === 'clean_and_reregister') {
                    // Step 1: Read current users (for logging)
                    $before = $zk->getUser();
                    $log[] = "Found " . count((array)$before) . " user(s) on device before cleanup.";

                    // Step 2: Clear ALL users from device
                    $zk->disableDevice();
                    $zk->clearUsers();
                    $log[] = "Cleared all user registrations from device.";

                    // Step 3: Re-push all employees with correct sequential slots
                    $employees = db_all("SELECT * FROM employees WHERE status='active' ORDER BY user_id ASC");
                    $slot = 1;
                    foreach ($employees as $emp) {
                        $empUserId = (string)(int)$emp['user_id'];
                        if ((int)$empUserId <= 0) continue; // skip invalid

                        // Build clean ASCII-only name (device firmware can't handle UTF-8)
                        $name = trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
                        $name = preg_replace('/[^\x20-\x7E]/', '', $name); // strip non-ASCII
                        if ($name === '') $name = 'User' . $empUserId;
                        if (strlen($name) > 24) $name = substr($name, 0, 24);

                        // setUser(slot, userid_string, name, password, role, cardno)
                        $ok = $zk->setUser($slot, $empUserId, $name, '', 0, 0);

                        if ($ok !== false) {
                            $log[] = "  slot {$slot} → ID={$empUserId}, name=\"{$name}\" ✓";
                            $reregistered++;
                        } else {
                            $log[] = "  slot {$slot} → ID={$empUserId} FAILED ✗";
                        }
                        $slot++;
                    }

                    $zk->enableDevice();
                    $zk->disconnect();

                    $message = "Done! Cleaned device and re-registered {$reregistered} employee(s) with correct IDs.";
                    $messageType = 'success';
                    $log[] = "";
                    $log[] = "✅ NEXT STEPS:";
                    $log[] = "1. Each employee needs to re-scan their finger on the device";
                    $log[] = "   (go to Enroll Fingerprint page for each person)";
                    $log[] = "2. After scanning, go to Sync Devices — punches will appear correctly.";

                } elseif ($action === 'view_only') {
                    // Just read and display current device users
                    $raw = $zk->getUser();
                    $zk->disconnect();
                    foreach ((array)$raw as $u) {
                        $deviceUsers[] = [
                            'uid'    => (int)($u['uid'] ?? 0),
                            'userid' => trim((string)($u['userid'] ?? '')),
                            'name'   => trim((string)($u['name'] ?? '')),
                        ];
                    }
                    usort($deviceUsers, fn($a,$b) => $a['uid'] <=> $b['uid']);
                    $message = "Loaded " . count($deviceUsers) . " user(s) from device.";
                    $messageType = 'success';
                }
            }
        } catch (\Throwable $e) {
            $message = "Error: " . $e->getMessage(); $messageType = 'danger';
        }
    }
}

include "includes/header.php";
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>" style="margin-bottom:18px">
    <?= e($message) ?>
</div>
<?php endif; ?>

<div class="panel">
    <div class="panel-head">
        <div>
            <h3>Device User Cleanup</h3>
            <p class="sub">Fix corrupted user registrations on the ZKTeco IN01 (<?= e($device['ip_address'] ?? '?') ?>)</p>
        </div>
    </div>
    <div class="panel-body" style="padding:24px">

        <?php if (!$device): ?>
        <div class="alert alert-danger">No active ZKTeco IN01 device found. Run setup.php first.</div>
        <?php else: ?>

        <!-- Explanation -->
        <div style="padding:18px 22px;background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.2);border-radius:12px;margin-bottom:24px">
            <h4 style="color:#FBBF24;font-size:15px;margin-bottom:8px">Why you see emojis/garbage in the ID field</h4>
            <p style="font-size:14px;color:var(--text-2);line-height:1.7;margin:0">
                Previous enrollment code pushed users to the device with wrong internal slot numbers (using employee ID like "990" as the slot instead of sequential numbers 1, 2, 3...).
                This created corrupted entries in the device memory.<br><br>
                <strong>The fix:</strong> Clear all user data on the device and re-push every employee with correct slot numbers and clean ASCII names.
                After this, each employee will need to re-scan their finger ONE TIME.
            </p>
        </div>

        <!-- Action buttons -->
        <div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:24px">
            <form method="POST" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="view_only">
                <button class="btn" style="font-size:14px;padding:11px 20px">View Current Device Users</button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('This will CLEAR all user registrations on the device and re-push them with correct IDs.\n\nEmployees will need to re-scan their fingerprints after this.\n\nContinue?')">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clean_and_reregister">
                <button class="btn btn-primary" style="font-size:14px;padding:11px 20px;background:linear-gradient(135deg,#E5484D,#DC2626)">
                    🧹 Clean Device & Re-Register All Employees
                </button>
            </form>
        </div>

        <?php endif; ?>

        <!-- Device users table -->
        <?php if ($deviceUsers): ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Slot (uid)</th><th>ID (userid)</th><th>Name</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($deviceUsers as $du):
                    $hasValidId = $du['userid'] !== '' && preg_match('/^\d+$/', $du['userid']);
                    $hasGarbage = !$hasValidId && $du['userid'] !== '';
                ?>
                <tr>
                    <td class="mono" style="font-weight:700"><?= (int)$du['uid'] ?></td>
                    <td class="mono">
                        <?php if ($hasValidId): ?>
                            <span style="color:var(--green);font-weight:600"><?= e($du['userid']) ?></span>
                        <?php elseif ($hasGarbage): ?>
                            <span style="color:var(--rose);font-weight:600">CORRUPTED: <?= e(substr($du['userid'], 0, 20)) ?></span>
                        <?php else: ?>
                            <span style="color:var(--rose)">EMPTY</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($du['name'] ?: '(no name)') ?></td>
                    <td>
                        <?php if ($hasValidId): ?>
                            <span class="badge badge-present">OK</span>
                        <?php elseif ($hasGarbage): ?>
                            <span class="badge badge-absent">Corrupted</span>
                        <?php else: ?>
                            <span class="badge badge-late">Missing ID</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Log output -->
        <?php if ($log): ?>
        <div style="margin-top:20px;padding:18px;background:rgba(0,0,0,.3);border-radius:10px;border:1px solid var(--border)">
            <h4 style="font-size:13px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">Operation Log</h4>
            <?php foreach ($log as $line): ?>
            <div style="font-family:monospace;font-size:13px;color:<?= str_contains($line, '✗') ? 'var(--rose)' : (str_contains($line, '✓') || str_contains($line, '✅') ? '#34D399' : 'var(--text-2)') ?>;padding:3px 0"><?= e($line) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php include "includes/footer.php"; ?>
