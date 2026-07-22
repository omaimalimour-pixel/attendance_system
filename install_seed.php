<?php
/**
 * ChronoX — Seed starter data (idempotent).
 * Included by install.php after the schema is created.
 */

/* Departments (5) — one per clocking machine, as in a real company */
$departments = [
    ['Engineering', 'ENG', 'Product & software engineering'],
    ['Human Resources', 'HR', 'People operations & recruiting'],
    ['Sales', 'SALES', 'Revenue & account management'],
    ['Operations', 'OPS', 'Logistics & facilities'],
    ['Finance', 'FIN', 'Accounting & payroll'],
];
foreach ($departments as [$name, $code, $desc]) {
    $exists = db_val("SELECT id FROM departments WHERE code=?", [$code]);
    if (!$exists) {
        db_exec("INSERT INTO departments (name, code, description) VALUES (?,?,?)", [$name, $code, $desc]);
    }
}
step(true, 'Seed 5 departments');

/* ---- Migrate legacy free-text employee departments (preserves your data) ----
   If the old employees.department text column still exists, turn each distinct
   value into a real department and link the employee via department_id. */
if (column_exists('employees', 'department')) {
    $legacy = db_all(
        "SELECT DISTINCT department FROM employees
         WHERE department IS NOT NULL AND department <> ''
           AND (department_id IS NULL OR department_id = 0)"
    );
    foreach ($legacy as $l) {
        $name = trim($l['department']);
        if ($name === '') continue;
        $depId = db_val("SELECT id FROM departments WHERE name=?", [$name]);
        if (!$depId) {
            $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 8)) ?: 'DEPT';
            $code = $base; $n = 1;
            while (db_val("SELECT id FROM departments WHERE code=?", [$code])) { $code = $base . $n; $n++; }
            db_exec("INSERT INTO departments (name, code) VALUES (?,?)", [$name, $code]);
            $depId = db_insert_id();
        }
        db_exec("UPDATE employees SET department_id=? WHERE department=? AND (department_id IS NULL OR department_id=0)", [$depId, $name]);
    }
    step(true, 'Migrate existing employee departments (' . count($legacy) . ' found)');
}

/* Devices (5) — one biometric machine installed per department */
$deviceSeed = [
    ['ENG',   'Engineering Entrance',  '192.168.100.201', 'Building A · Floor 2'],
    ['HR',    'HR Office Terminal',    '192.168.100.202', 'Building A · Floor 1'],
    ['SALES', 'Sales Floor Reader',    '192.168.100.203', 'Building B · Floor 1'],
    ['OPS',   'Warehouse Gate',        '192.168.100.204', 'Warehouse · Dock 1'],
    ['FIN',   'Finance Wing',          '192.168.100.205', 'Building A · Floor 3'],
];
foreach ($deviceSeed as [$code, $name, $ip, $loc]) {
    $deptId = db_val("SELECT id FROM departments WHERE code=?", [$code]);
    $exists = db_val("SELECT id FROM devices WHERE ip_address=?", [$ip]);
    if (!$exists) {
        db_exec(
            "INSERT INTO devices (name, ip_address, port, location, department_id, status)
             VALUES (?,?,?,?,?, 'active')",
            [$name, $ip, 4370, $loc, $deptId]
        );
    }
}
step(true, 'Seed 5 clocking devices (one per department)');

/* Default settings */
$settings = [
    'work_start'   => '09:00:00',
    'company_name' => 'Your Company',
    'app_name'     => 'ChronoX',
];
foreach ($settings as $k => $v) {
    db_exec("INSERT INTO settings (`key`,`value`) VALUES (?,?)
             ON DUPLICATE KEY UPDATE `value`=`value`", [$k, $v]);
}
step(true, 'Seed default settings');

/* Default admin (only if none exists). Password must be changed after login. */
$hasAdmin = (int) db_val("SELECT COUNT(*) FROM admin_users");
if ($hasAdmin === 0) {
    db_exec(
        "INSERT INTO admin_users (name, username, email, password, role, status)
         VALUES (?,?,?,?,?, 'active')",
        ['Administrator', 'admin', 'admin@chronox.local', password_hash('admin123', PASSWORD_DEFAULT), 'Administrator']
    );
    step(true, 'Create default admin (admin / admin123 — change it!)');
} else {
    step(true, 'Admin account already exists — skipped');
}
