<?php
/**
 * ChronoX — Installer / Migrator
 * Creates the full normalized schema (idempotent) and seeds starter data.
 * Safe to re-run: uses IF NOT EXISTS and presence checks.
 */
require_once __DIR__ . '/core/security.php'; // pulls in db.php and defines e() used by install_view.php

$steps = [];
function step($ok, $label, $err = '') { global $steps; $steps[] = [$ok, $label, $err]; }
function run_ddl($sql, $label) {
    $res = db_run($sql);
    step($res !== false, $label, $res === false ? mysqli_error(db()) : '');
}
function column_exists($table, $col) {
    $c = cx_config();
    return (int) db_val(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?",
        [$c['db_name'], $table, $col]
    ) > 0;
}
function add_column($table, $col, $ddl) {
    if (!column_exists($table, $col)) {
        run_ddl("ALTER TABLE `$table` ADD COLUMN $ddl", "Add $table.$col");
    }
}

/* ---------------- departments ---------------- */
run_ddl("CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(30) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Create departments");

/* ---------------- devices (multi-machine) ---------------- */
run_ddl("CREATE TABLE IF NOT EXISTS devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    serial VARCHAR(120) NULL,
    ip_address VARCHAR(45) NOT NULL,
    port INT NOT NULL DEFAULT 4370,
    location VARCHAR(150) NULL,
    department_id INT NULL,
    status ENUM('active','inactive','maintenance') NOT NULL DEFAULT 'active',
    last_sync_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dev_dept (department_id),
    CONSTRAINT fk_dev_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Create devices");

/* ---------------- employees ---------------- */
run_ddl("CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    department_id INT NULL,
    position VARCHAR(100) NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(40) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_emp_dept (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Create employees");
// Upgrade legacy employees table
add_column('employees', 'department_id', 'department_id INT NULL');
add_column('employees', 'email', 'email VARCHAR(150) NULL');
add_column('employees', 'phone', 'phone VARCHAR(40) NULL');
add_column('employees', 'status', "status ENUM('active','inactive') NOT NULL DEFAULT 'active'");

/* ---------------- attendance ---------------- */
run_ddl("CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_id INT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    type ENUM('IN','OUT') NOT NULL DEFAULT 'IN',
    raw TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_att_date (date),
    INDEX idx_att_user_date (user_id, date),
    INDEX idx_att_device (device_id),
    UNIQUE KEY uniq_punch (user_id, date, time, type, device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Create attendance");
add_column('attendance', 'device_id', 'device_id INT NULL');
add_column('attendance', 'raw', 'raw TEXT NULL');

/* ---------------- admin_users ---------------- */
run_ddl("CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Administrator','Manager','Viewer') NOT NULL DEFAULT 'Viewer',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Create admin_users");
add_column('admin_users', 'name', 'name VARCHAR(120) NULL');
add_column('admin_users', 'email', 'email VARCHAR(150) NULL');
add_column('admin_users', 'status', "status ENUM('active','inactive') NOT NULL DEFAULT 'active'");
add_column('admin_users', 'last_login_at', 'last_login_at DATETIME NULL');

/* ---------------- sync_logs ---------------- */
run_ddl("CREATE TABLE IF NOT EXISTS sync_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NULL,
    imported INT DEFAULT 0,
    skipped INT DEFAULT 0,
    status ENUM('success','error') NOT NULL DEFAULT 'success',
    message VARCHAR(255) NULL,
    synced_by VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sync_device (device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Create sync_logs");

/* ---------------- audit_logs ---------------- */
run_ddl("CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(100) NULL,
    action VARCHAR(120) NOT NULL,
    entity VARCHAR(80) NULL,
    entity_id VARCHAR(80) NULL,
    ip VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Create audit_logs");

/* ---------------- settings ---------------- */
run_ddl("CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(80) PRIMARY KEY,
    `value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Create settings");

/* ---------------- enrollment_requests (fingerprint enrolment) ----------------
   When an employee is added, we create a request that pushes them to their
   department's device so they can register their fingerprint on that machine. */
run_ddl("CREATE TABLE IF NOT EXISTS enrollment_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    user_id INT NOT NULL,
    device_id INT NULL,
    department_id INT NULL,
    status ENUM('pending','sent','enrolled','failed') NOT NULL DEFAULT 'pending',
    message VARCHAR(255) NULL,
    requested_by VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    enrolled_at DATETIME NULL,
    INDEX idx_enr_emp (employee_id),
    INDEX idx_enr_device (device_id),
    INDEX idx_enr_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Create enrollment_requests");

require __DIR__ . '/install_seed.php';
require __DIR__ . '/install_view.php';
