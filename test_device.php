<?php
/**
 * ChronoX — ZKTeco Device Diagnostic & Fix Tool
 *
 * This page:
 * 1. Connects to IN01 and reads ALL current users (shows raw hex)
 * 2. Lets you REMOVE corrupted/garbage users by slot number
 * 3. Lets you PUSH a single clean user with exact params you specify
 * 4. Shows exactly what bytes are sent — so you can verify
 *
 * URL: http://localhost/clocking/test_device.php
 */
require_once __DIR__ . '/core/security.php';
require_once __DIR__ . '/vendor/autoload.php';

$ip   = '192.168.100.201';
$port = 4370;
$results = [];
$users   = [];
$error   = '';
$action  = inp($_POST, 'action');

// Connect
$zk = null;
try {
    $zk = new \Rats\Zkteco\Lib\ZKTeco($ip, $port);
    @socket_set_option($zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 8, 'usec' => 0]);
    if (!$zk->connect()) {
        $error = "Cannot connect to $ip:$port — is the machine on?";
        $zk = null;
    }
} catch (\Throwable $e) {
    $error = "Connection error: " . $e->getMessage();
    $zk = null;
}

// ── Actions ─────────────────────────────────────────────────────
if ($zk && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($action === 'push_user') {
        $slot   = (int) inp($_POST, 'slot');
        $userid = trim(inp($_POST, 'userid'));
        $name   = trim(inp($_POST, 'name'));

        // Force pure ASCII
        $name = preg_replace('/[^\x20-\x7E]/', '', $name);
        if (strlen($name) > 24) $name = substr($name, 0, 24);
        if ($name === '') $name = 'U' . $userid;

        if ($slot < 1 || $slot > 65535 || $userid === '' || !ctype_digit($userid) || strlen($userid) > 9) {
            $results[] = ['type'=>'error', 'msg'=>"Invalid params: slot=$slot, userid='$userid', name='$name'. Userid must be 1-9 digit number."];
        } else {
            $zk->disableDevice();
            $ok = $zk->setUser($slot, $userid, $name, '', 0, 0);
            $zk->enableDevice();
            $results[] = [
                'type' => $ok !== false ? 'ok' : 'error',
                'msg'  => $ok !== false
                    ? "setUser(slot=$slot, userid='$userid', name='$name') → SUCCESS"
                    : "setUser(slot=$slot, userid='$userid', name='$name') → FAILED (returned false)"
            ];
        }
    }

    if ($action === 'remove_user') {
        $slot = (int) inp($_POST, 'slot');
        if ($slot > 0) {
            $zk->disableDevice();
            $zk->removeUser($slot);
            $zk->enableDevice();
            $results[] = ['type'=>'ok', 'msg'=>"removeUser(slot=$slot) — done"];
        }
    }

    if ($action === 'clear_all') {
        $zk->disableDevice();
        $zk->clearUsers();
        $zk->enableDevice();
        $results[] = ['type'=>'ok', 'msg'=>"clearUsers() — ALL users removed from device"];
    }
}

// ── Read users ──────────────────────────────────────────────────
if ($zk) {
    $raw = $zk->getUser();
    if (is_array($raw)) {
        foreach ($raw as $key => $u) {
            $users[] = [
                'uid'      => (int)($u['uid'] ?? 0),
                'userid'   => (string)($u['userid'] ?? ''),
                'name'     => (string)($u['name'] ?? ''),
                'role'     => (int)($u['role'] ?? 0),
                'key'      => (string)$key,
                'userid_hex' => bin2hex((string)($u['userid'] ?? '')),
                'name_hex'   => bin2hex((string)($u['name'] ?? '')),
            ];
        }
        usort($users, fn($a,$b) => $a['uid'] <=> $b['uid']);
    }
    $zk->disconnect();
}

// Employees for the push form
$employees = [];
if (function_exists('db_all')) {
    $employees = db_all("SELECT id, user_id, first_name, last_name FROM employees WHERE status='active' ORDER BY first_name");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Device Diagnostic — ChronoX</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Courier New',monospace;background:#0a0a0a;color:#e0e0e0;padding:20px;line-height:1.6}
h1{font-size:20px;margin-bottom:5px;color:#818CF8}
h2{font-size:16px;margin:20px 0 10px;color:#22D3EE;border-bottom:1px solid #333;padding-bottom:5px}
.err{background:#2d1111;border:1px solid #f44;color:#f88;padding:12px;border-radius:6px;margin:10px 0}
.ok{background:#112d11;border:1px solid #4f4;color:#8f8;padding:12px;border-radius:6px;margin:10px 0}
.warn{background:#2d2d11;border:1px solid #ff4;color:#ff8;padding:12px;border-radius:6px;margin:10px 0}
table{width:100%;border-collapse:collapse;margin:10px 0;font-size:13px}
th,td{padding:8px 10px;border:1px solid #333;text-align:left}
th{background:#1a1a2e;color:#818CF8}
tr:hover{background:#111122}
.bad{color:#f88;font-weight:bold}
.good{color:#8f8;font-weight:bold}
.hex{color:#888;font-size:11px}
form{display:inline}
input,select{background:#1a1a1a;border:1px solid #444;color:#eee;padding:6px 10px;border-radius:4px;font-family:inherit;font-size:13px}
button{background:#818CF8;color:#000;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;font-weight:bold;font-size:13px}
button:hover{background:#6366F1}
button.danger{background:#E5484D}
button.danger:hover{background:#c33}
.section{background:#111;border:1px solid #333;border-radius:8px;padding:16px;margin:15px 0}
</style>
</head>
<body>
<h1>ZKTeco IN01 — Device Diagnostic</h1>
<p style="color:#888">IP: <?= $ip ?>:<?= $port ?> | <?= date('Y-m-d H:i:s') ?></p>

<?php if ($error): ?>
<div class="err"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php foreach ($results as $r): ?>
<div class="<?= $r['type'] === 'ok' ? 'ok' : 'err' ?>"><?= htmlspecialchars($r['msg']) ?></div>
<?php endforeach; ?>

<!-- ════════════════════════════════════════════════════════════════ -->
<h2>1. Current Users on Device (<?= count($users) ?> found)</h2>
<?php if ($users): ?>
<table>
<thead><tr><th>Slot (uid)</th><th>UserID</th><th>UserID (hex)</th><th>Name</th><th>Name (hex)</th><th>Valid?</th><th>Action</th></tr></thead>
<tbody>
<?php foreach ($users as $u):
    $validId = $u['userid'] !== '' && ctype_digit($u['userid']);
    $isGarbage = !$validId && $u['userid'] !== '';
?>
<tr>
    <td><strong><?= $u['uid'] ?></strong></td>
    <td class="<?= $validId ? 'good' : ($isGarbage ? 'bad' : '') ?>"><?= htmlspecialchars($u['userid'] ?: '(empty)') ?></td>
    <td class="hex"><?= $u['userid_hex'] ?: '—' ?></td>
    <td><?= htmlspecialchars($u['name'] ?: '(empty)') ?></td>
    <td class="hex"><?= substr($u['name_hex'], 0, 30) ?><?= strlen($u['name_hex']) > 30 ? '…' : '' ?></td>
    <td><?= $validId ? '<span class="good">OK</span>' : ($isGarbage ? '<span class="bad">CORRUPTED</span>' : '<span class="bad">EMPTY</span>') ?></td>
    <td>
        <form method="POST"><input type="hidden" name="action" value="remove_user"><input type="hidden" name="slot" value="<?= $u['uid'] ?>">
        <button class="danger" onclick="return confirm('Remove slot <?= $u['uid'] ?>?')">Remove</button></form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p style="color:#888">No users on device (or couldn't connect).</p>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════════ -->
<h2>2. Push a User (Manual Test)</h2>
<div class="section">
<p style="margin-bottom:10px;color:#aaa">Set exactly what to send to the device. Use this to test one user.</p>
<form method="POST">
<input type="hidden" name="action" value="push_user">
<label>Slot: <input name="slot" type="number" value="<?= count($users) + 1 ?>" min="1" max="65535" style="width:80px"></label>
<label>UserID: <input name="userid" value="" placeholder="e.g. 990" style="width:100px"></label>
<label>Name: <input name="name" value="" placeholder="e.g. FIDAE" style="width:150px"></label>
<button>Push to Device</button>
</form>

<?php if ($employees): ?>
<p style="margin-top:12px;color:#aaa">Or select from employees:</p>
<form method="POST">
<input type="hidden" name="action" value="push_user">
<input type="hidden" name="slot" id="autoSlot" value="<?= count($users) + 1 ?>">
<select name="userid" id="empSelect" onchange="document.getElementById('autoName').value=this.options[this.selectedIndex].dataset.name||''">
    <option value="">— select —</option>
    <?php foreach ($employees as $emp): ?>
    <option value="<?= (int)$emp['user_id'] ?>" data-name="<?= htmlspecialchars(preg_replace('/[^\x20-\x7E]/', '', trim($emp['first_name'].' '.$emp['last_name']))) ?>">
        <?= htmlspecialchars($emp['first_name'].' '.$emp['last_name']) ?> (ID=<?= (int)$emp['user_id'] ?>)
    </option>
    <?php endforeach; ?>
</select>
<input name="name" id="autoName" value="" placeholder="name (auto-filled)" style="width:150px">
<button>Push Selected</button>
</form>
<?php endif; ?>
</div>

<!-- ════════════════════════════════════════════════════════════════ -->
<h2>3. Nuclear Option: Clear ALL Users</h2>
<div class="section">
<p style="color:#f88;margin-bottom:10px">⚠ This removes ALL user registrations. Fingerprint templates may also be lost. Use only if device is full of garbage.</p>
<form method="POST" onsubmit="return confirm('CLEAR ALL USERS from the device? This cannot be undone!')">
<input type="hidden" name="action" value="clear_all">
<button class="danger">Clear All Users from Device</button>
</form>
</div>

<!-- ════════════════════════════════════════════════════════════════ -->
<h2>4. After Fixing: Re-register All Employees</h2>
<div class="section">
<p style="color:#aaa;margin-bottom:10px">After clearing, push all employees back with correct sequential slots:</p>
<form method="POST">
<input type="hidden" name="action" value="push_all">
<?php if ($action === 'push_all' && $zk === null): ?>
<p class="err">Need fresh connection — reload the page and try again.</p>
<?php endif; ?>
<button>Push All Employees (slots 1,2,3...)</button>
</form>
</div>

<?php
// Handle push_all (needs to reconnect)
if ($action === 'push_all' && $employees) {
    try {
        $zk2 = new \Rats\Zkteco\Lib\ZKTeco($ip, $port);
        @socket_set_option($zk2->_zkclient, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 8, 'usec' => 0]);
        if ($zk2->connect()) {
            $zk2->disableDevice();
            $slot = 1;
            $pushed = 0;
            echo '<div class="section"><h3 style="color:#22D3EE">Push All Results:</h3>';
            foreach ($employees as $emp) {
                $uid = (string)(int)$emp['user_id'];
                if ((int)$uid <= 0) continue;
                $nm = preg_replace('/[^\x20-\x7E]/', '', trim($emp['first_name'].' '.$emp['last_name']));
                if ($nm === '') $nm = 'U'.$uid;
                if (strlen($nm) > 24) $nm = substr($nm, 0, 24);
                $ok = $zk2->setUser($slot, $uid, $nm, '', 0, 0);
                $class = $ok !== false ? 'good' : 'bad';
                echo "<p class=\"$class\">Slot $slot → userid='$uid', name='$nm' — " . ($ok !== false ? 'OK' : 'FAILED') . "</p>";
                if ($ok !== false) $pushed++;
                $slot++;
            }
            $zk2->enableDevice();
            $zk2->disconnect();
            echo "<p class=\"good\" style=\"margin-top:10px\"><strong>Done! Pushed $pushed employees.</strong></p>";
            echo "<p style=\"color:#aaa\">Now go to Enroll Fingerprint for each person to re-scan their finger.</p></div>";
        } else {
            echo '<div class="err">Cannot reconnect to device for push_all.</div>';
        }
    } catch (\Throwable $e) {
        echo '<div class="err">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<div style="margin-top:30px;padding:15px;background:#111;border:1px solid #333;border-radius:8px">
<h3 style="color:#FBBF24;font-size:14px">How to fix your problem:</h3>
<ol style="margin:10px 0 0 20px;color:#ccc;font-size:13px">
<li>Look at section 1 above — any row with "CORRUPTED" status means bad data on device</li>
<li>Click <strong>Remove</strong> on each corrupted row, OR use "Clear All Users" to wipe everything</li>
<li>Then use section 4 "Push All Employees" to re-register everyone with clean data</li>
<li>After that: go to <strong>Enroll Fingerprint</strong> page, select each employee, click Register → they scan finger once</li>
<li>Then <strong>Sync</strong> — punches will import correctly with the right employee ID</li>
</ol>
</div>

</body>
</html>
