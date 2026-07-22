<?php
/**
 * ChronoX — Setup Fixer
 * Run this ONCE in your browser to fix the device→department link
 * and re-push any stuck fingerprint enrolment requests.
 * URL: http://localhost/attendance_system/fix_setup.php
 */
require_once __DIR__ . '/core/security.php';

$steps = [];
function step(bool $ok, string $msg): void { global $steps; $steps[] = ['ok'=>$ok,'msg'=>$msg]; }

/* 1. Ensure DEV department exists */
$dev = db_one("SELECT id FROM departments WHERE code='DEV'");
if (!$dev) {
    db_exec("INSERT INTO departments (name,code,description) VALUES (?,?,?)",
        ['DEV','DEV','Development department — ZKTeco IN01 installed']);
    $dev = db_one("SELECT id FROM departments WHERE code='DEV'");
    step(true,'Created DEV department');
} else {
    step(true,'DEV department already exists (id='.$dev['id'].')');
}

/* 2. Ensure ZKTeco IN01 exists and is linked to DEV */
$devId  = (int)$dev['id'];
$device = db_one("SELECT * FROM devices WHERE ip_address='192.168.100.201'");
if (!$device) {
    db_exec("INSERT INTO devices (name,ip_address,port,location,department_id,status) VALUES (?,?,?,?,?,'active')",
        ['ZKTeco IN01 – DEV','192.168.100.201',4370,'DEV Department',$devId]);
    step(true,'Created ZKTeco IN01 device and linked to DEV');
} else {
    $fixed = false;
    if ((int)$device['department_id'] !== (int)$devId) {
        db_exec("UPDATE devices SET department_id=? WHERE id=?", [$devId, (int)$device['id']]);
        $fixed = true;
    }
    if ($device['status'] !== 'active') {
        db_exec("UPDATE devices SET status='active' WHERE id=?", [(int)$device['id']]);
        $fixed = true;
    }
    // Rename from old "Engineering Entrance" → "ZKTeco IN01 – DEV"
    if ($device['name'] !== 'ZKTeco IN01 – DEV') {
        db_exec("UPDATE devices SET name='ZKTeco IN01 \xe2\x80\x93 DEV' WHERE id=?", [(int)$device['id']]);
        $fixed = true;
    }
    step(true, $fixed
        ? 'Fixed: device renamed "ZKTeco IN01 – DEV" and linked to DEV department ✓'
        : 'ZKTeco IN01 already correctly configured ✓');
}

/* 3. Re-push stuck pending enrollments */
require_once __DIR__ . '/core/device.php';
$stuck = db_all(
    "SELECT r.*,e.first_name,e.last_name,e.user_id AS emp_uid
     FROM enrollment_requests r JOIN employees e ON e.id=r.employee_id
     WHERE r.status IN ('pending','failed')"
);
$machine = db_one("SELECT * FROM devices WHERE ip_address='192.168.100.201' AND status='active'");
if ($stuck && $machine) {
    $n = 0;
    foreach ($stuck as $req) {
        $emp = db_one("SELECT * FROM employees WHERE id=?",[(int)$req['employee_id']]);
        if (!$emp) continue;
        [$ok,$msg] = device_push_user($machine,$emp);
        db_exec("UPDATE enrollment_requests SET device_id=?,status=?,message=?,updated_at=NOW() WHERE id=?",
            [(int)$machine['id'],$ok?'sent':'pending',$msg,(int)$req['id']]);
        $n++;
    }
    step(true,"Re-pushed $n pending enrolment(s) to ZKTeco IN01");
} elseif (!$stuck) {
    step(true,'No stuck enrolments found');
} else {
    step(false,'Device not reachable — start the machine then re-run this page');
}

/* 4. enrollment_requests table guard */
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
    step(true,'Created enrollment_requests table');
} else {
    step(true,'enrollment_requests table OK');
}

$allOk = !in_array(false,array_column($steps,'ok'));
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Setup Fixer — ChronoX</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#05060D;color:#F0F2FA;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:30px}
.card{width:100%;max-width:600px;background:#0A0C18;border:1px solid rgba(255,255,255,.13);border-radius:20px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.6)}
.hd{padding:22px 28px;border-bottom:1px solid rgba(255,255,255,.09);display:flex;align-items:center;gap:14px}
.logo{width:42px;height:42px;border-radius:12px;background:linear-gradient(120deg,#818CF8,#22D3EE,#A78BFA);display:grid;place-items:center;flex-shrink:0}
.logo svg{width:21px;height:21px}
h1{font-family:'Sora',sans-serif;font-size:19px;font-weight:800}
h1+p{font-size:13px;color:#9AA2C0;margin-top:3px}
.bd{padding:20px 28px}
.banner{padding:13px 16px;border-radius:11px;font-size:14px;font-weight:600;margin-bottom:18px}
.ok-b{background:rgba(52,211,153,.12);color:#34D399;border:1px solid rgba(52,211,153,.2)}
.bad-b{background:rgba(251,113,133,.12);color:#FB7185;border:1px solid rgba(251,113,133,.2)}
.row{display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.06)}
.row:last-child{border-bottom:none}
.ic{width:24px;height:24px;border-radius:7px;display:grid;place-items:center;flex-shrink:0;margin-top:1px}
.ic-ok{background:rgba(52,211,153,.18);color:#34D399}
.ic-bad{background:rgba(251,113,133,.18);color:#FB7185}
.ic svg{width:13px;height:13px}
.lbl{font-size:14.5px;color:#F0F2FA}
.ft{padding:18px 28px;background:rgba(255,255,255,.03);border-top:1px solid rgba(255,255,255,.09);display:flex;gap:10px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:9px;font-weight:600;font-size:14px;text-decoration:none;transition:.15s}
.btn-p{background:linear-gradient(120deg,#818CF8,#22D3EE,#A78BFA);color:#05060D}
.btn-s{background:rgba(255,255,255,.07);color:#F0F2FA;border:1px solid rgba(255,255,255,.12)}
.btn-s:hover{background:rgba(255,255,255,.12)}
</style>
</head>
<body>
<div class="card">
  <div class="hd">
    <div class="logo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 6a6 6 0 1 0 6 6"/><circle cx="12" cy="12" r="2"/></svg></div>
    <div><h1>ChronoX Setup Fixer</h1><p>Links ZKTeco IN01 (192.168.100.201) to DEV department</p></div>
  </div>
  <div class="bd">
    <div class="banner <?= $allOk?'ok-b':'bad-b' ?>">
      <?= $allOk ? '✓ All done — employees can now enrol their fingerprint on the IN01.' : '✗ Some steps failed — see below.' ?>
    </div>
    <?php foreach ($steps as $s): ?>
    <div class="row">
      <div class="ic <?= $s['ok']?'ic-ok':'ic-bad' ?>">
        <?php if($s['ok']): ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
        <?php else: ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg><?php endif; ?>
      </div>
      <div class="lbl"><?= htmlspecialchars($s['msg']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="ft">
    <a href="dashboard/enrollment.php" class="btn btn-p">Fingerprint Enrolment →</a>
    <a href="dashboard/devices.php" class="btn btn-s">Devices</a>
    <a href="dashboard/dashboard.php" class="btn btn-s">Dashboard</a>
  </div>
</div>
</body></html>
