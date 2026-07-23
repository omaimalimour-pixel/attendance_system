<?php
/**
 * ChronoX — One-click Setup & Repair
 * Run this ONCE after every git pull.
 * URL: http://localhost/clocking/setup.php
 *
 * Does everything in order:
 *   1. Fix DEV department
 *   2. Fix ZKTeco IN01 device (port, name, department link)
 *   3. Remove fake placeholder devices (192.168.100.202-205)
 *   4. Fix ALL attendance records stored under wrong user_id
 *   5. Re-push any stuck enrollment requests
 *   6. Verify enrollment_requests table exists
 */
require_once __DIR__ . '/core/security.php';
require_once __DIR__ . '/core/device.php';

$steps = [];
function step(bool $ok, string $msg): void { global $steps; $steps[] = ['ok'=>$ok,'msg'=>$msg]; }

/* ═══════════════════════════════════════════════════════
   1. DEV department
═══════════════════════════════════════════════════════ */
$dev = db_one("SELECT id FROM departments WHERE code='DEV'");
if (!$dev) {
    db_exec("INSERT INTO departments (name,code,description) VALUES (?,?,?)",
        ['DEV','DEV','Development — ZKTeco IN01 installed here']);
    $dev = db_one("SELECT id FROM departments WHERE code='DEV'");
    step(true, 'Created DEV department');
} else {
    step(true, 'DEV department OK (id='.$dev['id'].')');
}

/* ═══════════════════════════════════════════════════════
   2. ZKTeco IN01 device — fix port / name / dept link
═══════════════════════════════════════════════════════ */
$devId  = (int)$dev['id'];
$device = db_one("SELECT * FROM devices WHERE ip_address='192.168.100.201'");
if (!$device) {
    db_exec("INSERT INTO devices (name,ip_address,port,location,department_id,status) VALUES (?,?,?,?,?,'active')",
        ['ZKTeco IN01 – DEV','192.168.100.201',4370,'DEV Department',$devId]);
    step(true,'Created ZKTeco IN01 device');
} else {
    $notes = [];
    if ((int)$device['port'] !== 4370) {
        db_exec("UPDATE devices SET port=4370 WHERE id=?",[(int)$device['id']]);
        $notes[] = 'port corrected to 4370';
    }
    if ((int)$device['department_id'] !== $devId) {
        db_exec("UPDATE devices SET department_id=? WHERE id=?",[$devId,(int)$device['id']]);
        $notes[] = 'linked to DEV dept';
    }
    if ($device['status'] !== 'active') {
        db_exec("UPDATE devices SET status=\'active\' WHERE id=?",[(int)$device['id']]);
        $notes[] = 'set active';
    }
    if ($device['name'] !== 'ZKTeco IN01 – DEV') {
        db_exec("UPDATE devices SET name=? WHERE id=?",['ZKTeco IN01 – DEV',(int)$device['id']]);
        $notes[] = 'renamed';
    }
    step(true, $notes ? 'Fixed IN01: '.implode(', ',$notes).' ✓' : 'ZKTeco IN01 already correct ✓');
}

// Also fix any other device with wrong port
$badPort = db_all("SELECT id,name FROM devices WHERE port != 4370");
if ($badPort) {
    foreach ($badPort as $bp) db_exec("UPDATE devices SET port=4370 WHERE id=?",[(int)$bp['id']]);
    step(true,'Fixed port on '.count($badPort).' other device(s) → 4370 ✓');
}

/* ═══════════════════════════════════════════════════════
   3. Remove fake placeholder devices
═══════════════════════════════════════════════════════ */
$fakeIPs = ['192.168.100.202','192.168.100.203','192.168.100.204','192.168.100.205'];
$deleted = 0;
foreach ($fakeIPs as $ip) {
    $fake = db_one("SELECT id FROM devices WHERE ip_address=?",[$ip]);
    if ($fake) {
        db_exec("DELETE FROM sync_logs WHERE device_id=?",[(int)$fake['id']]);
        db_exec("DELETE FROM devices WHERE id=?",[(int)$fake['id']]);
        $deleted++;
    }
}
step(true, $deleted ? "Removed $deleted fake device(s) ✓" : 'No fake devices found ✓');

/* ═══════════════════════════════════════════════════════
   4. Repair attendance records with wrong user_id
      (terminal-enrolled users whose punches were stored
       under the device internal slot number instead of
       their real employee ID)
═══════════════════════════════════════════════════════ */
$repaired   = 0;
$repairNote = 'Device offline — skipped attendance repair (run sync after machine is on)';
$machine    = db_one("SELECT * FROM devices WHERE ip_address='192.168.100.201' AND status='active'");

if ($machine && device_lib_available()) {
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        $zk = new \Rats\Zkteco\Lib\ZKTeco($machine['ip_address'], (int)$machine['port']);
        @socket_set_option($zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, ['sec'=>6,'usec'=>0]);

        if ($zk->connect()) {
            $rawUsers = $zk->getUser();
            $zk->disconnect();

            // Build slot → employee_user_id map using $u['userid'] field directly
            $slotMap = []; // internal_slot → correct_employee_user_id
            foreach ((array)$rawUsers as $u) {
                $slot   = (int)($u['uid']    ?? 0);
                $empId  = (int)trim((string)($u['userid'] ?? ''));
                if ($slot > 0 && $empId > 0 && $slot !== $empId) {
                    $slotMap[$slot] = $empId;
                }
            }

            foreach ($slotMap as $wrongId => $correctId) {
                // Find attendance rows stored under the wrong user_id
                $rows = db_all(
                    "SELECT id,date,time,type FROM attendance WHERE user_id=?",
                    [$wrongId]
                );
                foreach ($rows as $row) {
                    // Only update if correct version doesn't exist yet
                    $exists = db_val(
                        "SELECT id FROM attendance WHERE user_id=? AND date=? AND time=? AND type=?",
                        [$correctId,$row['date'],$row['time'],$row['type']]
                    );
                    if ($exists) {
                        db_exec("DELETE FROM attendance WHERE id=?",[(int)$row['id']]);
                    } else {
                        db_exec("UPDATE attendance SET user_id=?,device_id=? WHERE id=?",
                            [$correctId,(int)$machine['id'],(int)$row['id']]);
                        $repaired++;
                    }
                }
            }
            $repairNote = $repaired
                ? "Repaired $repaired attendance record(s) — user_id corrected ✓"
                : 'Attendance already clean — no wrong user_id records found ✓';
        } else {
            $repairNote = 'Machine offline during repair — sync when it\'s on to auto-fix ✓';
        }
    } catch (\Throwable $e) {
        $repairNote = 'Repair error: '.$e->getMessage();
    }
}
step(true, $repairNote);

/* ═══════════════════════════════════════════════════════
   5. Re-push stuck enrollment requests
═══════════════════════════════════════════════════════ */
$stuck = db_all(
    "SELECT r.*,e.first_name,e.last_name FROM enrollment_requests r
     JOIN employees e ON e.id=r.employee_id
     WHERE r.status IN ('pending','failed')"
);
if ($stuck && $machine) {
    $pushed = 0;
    foreach ($stuck as $req) {
        $emp = db_one("SELECT * FROM employees WHERE id=?",[(int)$req['employee_id']]);
        if (!$emp) continue;
        [$ok,$msg] = device_push_user($machine,$emp);
        db_exec("UPDATE enrollment_requests SET device_id=?,status=?,message=?,updated_at=NOW() WHERE id=?",
            [(int)$machine['id'],$ok?'sent':'pending',$msg,(int)$req['id']]);
        $pushed++;
    }
    step(true, "Re-pushed $pushed pending enrollment(s) ✓");
} else {
    step(true, $stuck ? 'Machine offline — enrollments remain pending' : 'No pending enrollments ✓');
}

/* ═══════════════════════════════════════════════════════
   6. enrollment_requests table guard
═══════════════════════════════════════════════════════ */
if (!db_table_exists('enrollment_requests')) {
    db_exec("CREATE TABLE enrollment_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL, user_id INT NOT NULL,
        device_id INT NULL, department_id INT NULL,
        status ENUM('pending','sent','enrolled','failed') NOT NULL DEFAULT 'pending',
        message VARCHAR(255) NULL, requested_by VARCHAR(100) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL, enrolled_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    step(true,'Created enrollment_requests table ✓');
} else {
    step(true,'enrollment_requests table OK ✓');
}

$allOk = !in_array(false, array_column($steps,'ok'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ChronoX Setup</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#05060D;color:#F0F2FA;min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:40px 20px}
.card{width:100%;max-width:640px;background:#0A0C18;border:1px solid rgba(255,255,255,.12);border-radius:20px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.6)}
.hd{padding:22px 28px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:14px}
.logo{width:44px;height:44px;border-radius:12px;background:linear-gradient(120deg,#818CF8,#22D3EE);display:grid;place-items:center;flex-shrink:0}
.logo svg{width:22px;height:22px}
h1{font-family:'Sora',sans-serif;font-size:19px;font-weight:800}
h1+p{font-size:13px;color:#9AA2C0;margin-top:3px}
.banner{padding:14px 18px;border-radius:11px;font-size:14px;font-weight:600;margin:20px 28px 0}
.ok-b{background:rgba(52,211,153,.1);color:#34D399;border:1px solid rgba(52,211,153,.2)}
.bad-b{background:rgba(251,113,133,.1);color:#FB7185;border:1px solid rgba(251,113,133,.2)}
.steps{padding:18px 28px;display:flex;flex-direction:column;gap:4px}
.s{display:flex;align-items:flex-start;gap:12px;padding:10px 14px;border-radius:10px}
.s.ok {background:rgba(52,211,153,.06)}
.s.bad{background:rgba(251,113,133,.07)}
.ic{width:26px;height:26px;border-radius:7px;display:grid;place-items:center;flex-shrink:0}
.ic.ok {background:rgba(52,211,153,.18);color:#34D399}
.ic.bad{background:rgba(251,113,133,.18);color:#FB7185}
.ic svg{width:13px;height:13px}
.lbl{font-size:14px;line-height:1.5;color:#E2E8F0;padding-top:3px}
.ft{padding:18px 28px;background:rgba(255,255,255,.03);border-top:1px solid rgba(255,255,255,.08);display:flex;gap:10px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:7px;padding:11px 20px;border-radius:9px;font-weight:600;font-size:14px;text-decoration:none;font-family:inherit;transition:.15s;cursor:pointer;border:none}
.btn-p{background:linear-gradient(120deg,#818CF8,#22D3EE);color:#05060D}
.btn-s{background:rgba(255,255,255,.07);color:#F0F2FA;border:1px solid rgba(255,255,255,.12)}
.btn-s:hover{background:rgba(255,255,255,.13)}
.next{padding:16px 28px;border-top:1px solid rgba(255,255,255,.08)}
.next h3{font-size:13px;font-weight:700;color:#9AA2C0;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px}
.next ol{padding-left:18px;display:flex;flex-direction:column;gap:7px}
.next li{font-size:14px;color:#CBD5E1;line-height:1.5}
.next strong{color:#F0F2FA}
</style>
</head>
<body>
<div class="card">
  <div class="hd">
    <div class="logo">
      <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
    </div>
    <div>
      <h1>ChronoX Setup & Repair</h1>
      <p>Run once after every git pull to keep everything in sync</p>
    </div>
  </div>

  <div class="banner <?= $allOk ? 'ok-b' : 'bad-b' ?>">
    <?= $allOk
      ? '✓ All steps completed — the system is ready.'
      : '✗ Some steps had issues — see below.' ?>
  </div>

  <div class="steps">
    <?php foreach ($steps as $s): ?>
    <div class="s <?= $s['ok'] ? 'ok' : 'bad' ?>">
      <div class="ic <?= $s['ok'] ? 'ok' : 'bad' ?>">
        <?php if ($s['ok']): ?>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
        <?php else: ?>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        <?php endif; ?>
      </div>
      <div class="lbl"><?= htmlspecialchars($s['msg']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="next">
    <h3>What to do next</h3>
    <ol>
      <li><strong>Enroll Fingerprint</strong> — go to Enroll Fingerprint page, select the employee, click <strong>Register on Device</strong>. The app now correctly assigns the employee ID on the machine slot.</li>
      <li>Ask the employee to scan their finger on the IN01 terminal (3 times as prompted).</li>
      <li>Go to <strong>Sync Devices → Sync All</strong>. Punches will appear under the correct name.</li>
    </ol>
  </div>

  <div class="ft">
    <a href="dashboard/enroll_finger.php" class="btn btn-p">Enroll Fingerprint →</a>
    <a href="dashboard/sync_attendance.php" class="btn btn-s">Sync Devices</a>
    <a href="dashboard/device_users.php" class="btn btn-s">Device Users</a>
    <a href="dashboard/attendance.php" class="btn btn-s">Attendance</a>
  </div>
</div>
</body>
</html>
