<?php
/**
 * ChronoX — ZKTeco device layer
 *
 * Wraps the rats/zkteco library for:
 *   - Connection testing
 *   - Listing users on the device
 *   - Pushing a new user (so they can enrol their fingerprint at the terminal)
 *   - Reading attendance punches
 *   - Full fingerprint-enrolment request lifecycle
 */
require_once __DIR__ . '/db.php';

/* ─── Library availability ─────────────────────────────────────── */

function device_lib_available(): bool
{
    static $ok = null;
    if ($ok !== null) return $ok;
    if (!is_file(__DIR__ . '/../vendor/autoload.php')) return $ok = false;
    require_once __DIR__ . '/../vendor/autoload.php';
    return $ok = class_exists('\Rats\Zkteco\Lib\ZKTeco');
}

function _zk(string $ip, int $port = 4370): \Rats\Zkteco\Lib\ZKTeco
{
    require_once __DIR__ . '/../vendor/autoload.php';
    return new \Rats\Zkteco\Lib\ZKTeco($ip, $port);
}

/* ─── Connection helpers ────────────────────────────────────────── */

/**
 * Test connectivity to a device.
 * Returns ['ok'=>bool, 'message'=>string, 'time'=>string|null]
 */
function device_test_connection(array $device): array
{
    if (!device_lib_available()) {
        return ['ok' => false, 'message' => 'ZKTeco library not installed — run: composer install',
                'time' => null, 'users' => 0];
    }
    try {
        $zk = _zk($device['ip_address'], (int)$device['port']);
        if (!$zk->connect()) {
            return ['ok' => false, 'message' => "Cannot connect to {$device['ip_address']}:{$device['port']}. Check the device is online and on the same network.",
                    'time' => null, 'users' => 0];
        }
        $t = $zk->getTime();
        $users = $zk->getUser();
        $zk->disconnect();
        return [
            'ok'      => true,
            'message' => "Connected successfully to {$device['name']}.",
            'time'    => $t ?: null,
            'users'   => is_array($users) ? count($users) : 0,
        ];
    } catch (\Throwable $e) {
        return ['ok' => false, 'message' => 'Error: ' . $e->getMessage(), 'time' => null, 'users' => 0];
    }
}

/**
 * Get all users currently registered on a device.
 * Returns array of ['uid', 'userid', 'name', 'role'] or empty array.
 */
function device_get_users(array $device): array
{
    if (!device_lib_available()) return [];
    try {
        $zk   = _zk($device['ip_address'], (int)$device['port']);
        if (!$zk->connect()) return [];
        $list = $zk->getUser();
        $zk->disconnect();
        return is_array($list) ? $list : [];
    } catch (\Throwable $e) {
        return [];
    }
}

/* ─── Push user to device (enables fingerprint enrolment) ────────── */

/**
 * Push ONE employee to ONE device so they can register their fingerprint.
 *
 * The ZKTeco IN01 flow:
 *   1. App calls setUser() → device knows the user ID & name.
 *   2. Employee walks to the terminal and places finger → device stores the template.
 *   3. From that point on, every punch is recognised and sent back on sync.
 *
 * Returns [bool $ok, string $message]
 */
function device_push_user(array $device, array $employee): array
{
    if (!device_lib_available()) {
        return [false, 'ZKTeco library not installed. Run: cd ' . dirname(__DIR__) . ' && composer install'];
    }
    try {
        $zk = _zk($device['ip_address'], (int)$device['port']);
        if (!$zk->connect()) {
            return [false, "Device offline at {$device['ip_address']}:{$device['port']} — request saved as pending."];
        }

        $uid  = (int)$employee['user_id'];   // numeric UID (max 65535)
        $name = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
        if ($name === '') $name = 'User ' . $uid;
        if (strlen($name) > 24) $name = substr($name, 0, 24); // device limit

        $zk->disableDevice();
        $ok = $zk->setUser($uid, (string)$uid, $name, '', 0, 0);
        $zk->enableDevice();
        $zk->disconnect();

        if ($ok === false) {
            return [false, "setUser() returned false for UID {$uid} on {$device['name']}."];
        }

        return [true, "User '{$name}' (UID {$uid}) pushed to {$device['name']} ({$device['ip_address']}). Employee can now scan their finger on that machine."];
    } catch (\Throwable $e) {
        return [false, 'Device exception: ' . $e->getMessage()];
    }
}

/**
 * Remove a user from a device (e.g. when an employee is deleted).
 */
function device_remove_user(array $device, int $uid): array
{
    if (!device_lib_available()) return [false, 'Library not installed.'];
    try {
        $zk = _zk($device['ip_address'], (int)$device['port']);
        if (!$zk->connect()) return [false, 'Device offline.'];
        $zk->disableDevice();
        $zk->deleteUser($uid);
        $zk->enableDevice();
        $zk->disconnect();
        return [true, "User {$uid} removed from {$device['name']}."];
    } catch (\Throwable $e) {
        return [false, $e->getMessage()];
    }
}

/* ─── Live fingerprint enrollment ──────────────────────────────── */

/**
 * Send CMD_ENROLL_FP (0x3D = 61) to the ZKTeco terminal.
 *
 * This is EXACTLY what ZK Bio Time.Net does when you click "Enroll Fingerprint"
 * and select a finger number. The terminal screen shows "Place finger" and the
 * employee scans their biometric directly on the device.
 *
 * Protocol:
 *   - Command:  61  (0x3D — standard ZKTeco fingerprint enroll command)
 *   - Payload:  uid_lo + uid_hi + finger_id + flag(3)
 *   - flag=1 → overwrite if exists, flag=3 → normal new enroll
 *
 * Finger index (ZK standard numbering, same as ZK Bio Time.Net hand diagram):
 *   Right hand: 0=Pinky  1=Ring  2=Middle  3=Index  4=Thumb
 *   Left  hand: 5=Thumb  6=Index 7=Middle  8=Ring   9=Pinky
 *
 * @return array [bool $ok, string $message]
 */
function device_enroll_finger(array $device, array $employee, int $finger = 1): array
{
    if (!device_lib_available()) {
        return [false, 'ZKTeco library not installed. Run: composer install in project root.'];
    }
    if ($finger < 0 || $finger > 9) {
        return [false, "Finger index must be 0–9 (got $finger)."];
    }

    try {
        $zk = _zk($device['ip_address'], (int)$device['port']);
        if (!$zk->connect()) {
            return [false, "Cannot connect to {$device['name']} at {$device['ip_address']}:{$device['port']}. Make sure the device is on and reachable."];
        }

        $uid  = (int)$employee['user_id'];
        $name = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
        if (strlen($name) > 24) $name = substr($name, 0, 24);
        if ($name === '') $name = 'User ' . $uid;

        // Step 1 — register the user on the device so it recognises the UID
        $zk->disableDevice();
        $zk->setUser($uid, (string)$uid, $name, '', 0, 0);
        $zk->enableDevice();

        // Step 2 — send CMD_ENROLL_FP = 61 (0x3D)
        // Payload format (ZKTeco SDK spec):
        //   byte 0: uid low byte
        //   byte 1: uid high byte
        //   byte 2: finger index (0-9)
        //   byte 3: flag — 1=overwrite existing, 3=new enroll
        $payload = pack('vCC', $uid, $finger, 3);  // 'v' = little-endian uint16

        $response = $zk->_command(61, $payload);
        $zk->disconnect();

        if ($response === false) {
            // Some firmware versions require disabling the device first
            // Try alternative approach: just push user (device will prompt on next touch)
            return [false,
                "CMD_ENROLL_FP rejected. Your firmware may not support remote enroll trigger. " .
                "Instead: go to the IN01 terminal → Menu → User Mgmt → Enroll FP → enter UID $uid → scan finger."
            ];
        }

        $fingerNames = [
            0=>'Right Pinky', 1=>'Right Ring', 2=>'Right Middle', 3=>'Right Index', 4=>'Right Thumb',
            5=>'Left Thumb',  6=>'Left Index',  7=>'Left Middle',  8=>'Left Ring',   9=>'Left Pinky',
        ];
        $fname = $fingerNames[$finger] ?? "Finger $finger";

        return [true,
            "✓ Enrollment started on {$device['name']} for {$name} — {$fname}. " .
            "The terminal screen shows 'Place finger'. Ask the employee to scan their {$fname} on the IN01 NOW."
        ];

    } catch (\Throwable $e) {
        return [false, 'Device error: ' . $e->getMessage()];
    }
}

/* ─── Enrolment request lifecycle ──────────────────────────────── */

/**
 * Create enrolment requests for an employee, targeting every active device
 * in their department, and attempt to push immediately.
 * Falls back to 'pending' when the device is unreachable.
 */
function enrollment_request_create(array $employee, ?string $by = null): array
{
    $results = [];
    $deptId  = $employee['department_id'] ?? null;

    $devices = $deptId
        ? db_all("SELECT * FROM devices WHERE department_id=? AND status='active'", [(int)$deptId])
        : [];

    if (!$devices) {
        // FALLBACK: if no device is linked to this specific department,
        // use any active device in the database (e.g. ZKTeco IN01 at 192.168.100.201)
        $devices = db_all("SELECT * FROM devices WHERE status='active' ORDER BY id ASC LIMIT 1");
    }

    if (!$devices) {
        $msg = $deptId
            ? 'No active device found. Open Devices page, add your ZKTeco IN01 (192.168.100.201) and assign it to this department.'
            : 'Employee has no department. Assign a department and retry.';
        db_exec(
            "INSERT INTO enrollment_requests
               (employee_id, user_id, device_id, department_id, status, message, requested_by, updated_at)
             VALUES (?,?,NULL,?,'pending',?,?,NOW())",
            [(int)$employee['id'], (int)$employee['user_id'], $deptId ? (int)$deptId : null, $msg, $by]
        );
        $results[] = ['device' => null, 'ok' => false, 'message' => $msg];
        return $results;
    }

    foreach ($devices as $device) {
        [$ok, $msg] = device_push_user($device, $employee);
        $status = $ok ? 'sent' : 'pending';
        // Avoid duplicate requests
        $exists = db_val(
            "SELECT id FROM enrollment_requests WHERE employee_id=? AND device_id=? AND status NOT IN ('enrolled','failed')",
            [(int)$employee['id'], (int)$device['id']]
        );
        if ($exists) {
            // Update existing
            db_exec("UPDATE enrollment_requests SET status=?, message=?, updated_at=NOW() WHERE id=?",
                [$status, $msg, (int)$exists]);
        } else {
            db_exec(
                "INSERT INTO enrollment_requests
                   (employee_id, user_id, device_id, department_id, status, message, requested_by, updated_at)
                 VALUES (?,?,?,?,?,?,?,NOW())",
                [(int)$employee['id'], (int)$employee['user_id'],
                 (int)$device['id'], $device['department_id'], $status, $msg, $by]
            );
        }
        $results[] = ['device' => $device, 'ok' => $ok, 'message' => $msg];
    }
    return $results;
}

/** Retry a pending/failed enrolment request. */
function enrollment_retry(int $requestId, ?string $by = null): array
{
    $req = db_one("SELECT * FROM enrollment_requests WHERE id=?", [$requestId]);
    if (!$req) return [false, 'Request not found.'];

    $employee = db_one("SELECT * FROM employees WHERE id=?", [(int)$req['employee_id']]);
    if (!$employee) return [false, 'Employee no longer exists.'];

    $device = $req['device_id']
        ? db_one("SELECT * FROM devices WHERE id=?", [(int)$req['device_id']])
        : null;
    if (!$device && !empty($employee['department_id'])) {
        $device = db_one(
            "SELECT * FROM devices WHERE department_id=? AND status='active' LIMIT 1",
            [(int)$employee['department_id']]
        );
    }
    if (!$device) {
        db_exec("UPDATE enrollment_requests SET status='pending', message=?, updated_at=NOW() WHERE id=?",
            ['No active device available — assign a device to this department first.', $requestId]);
        return [false, 'No active device available.'];
    }

    [$ok, $msg] = device_push_user($device, $employee);
    db_exec(
        "UPDATE enrollment_requests SET device_id=?, status=?, message=?, requested_by=?, updated_at=NOW() WHERE id=?",
        [(int)$device['id'], $ok ? 'sent' : 'failed', $msg, $by, $requestId]
    );
    return [$ok, $msg];
}

/** Mark a request as completed (fingerprint confirmed on device). */
function enrollment_mark_enrolled(int $requestId): void
{
    db_exec(
        "UPDATE enrollment_requests SET status='enrolled', enrolled_at=NOW(), updated_at=NOW() WHERE id=?",
        [$requestId]
    );
}
