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
 * Resolve the correct internal device slot (uid) for an employee.
 *
 * ZKTeco has TWO separate concepts:
 *   - uid      = internal slot number (1, 2, 3 …) assigned by the device
 *   - userid   = the employee ID string we want ("990", "2007" …)
 *
 * setUser($uid, $userid, ...) MUST use the RIGHT internal slot.
 * If we blindly use employee->user_id as the slot, we create a SECOND
 * entry on the device (slot #990) while the fingerprint stays on slot #3
 * with userid="" → punches never match.
 *
 * This helper:
 *   1. Calls getUser() to read all current device users
 *   2. If the employee's userid already exists on the device → returns that slot
 *   3. If not → returns the next free slot (max_uid + 1, capped at 65535)
 *
 * Returns int slot number, or 0 on failure.
 */
function device_resolve_uid(\Rats\Zkteco\Lib\ZKTeco $zk, string $employeeUserId): int
{
    $existing = $zk->getUser();
    if (!is_array($existing)) return 1;

    $maxUid  = 0;
    foreach ($existing as $u) {
        $devUid    = (int)($u['uid']    ?? 0);
        $devUserId = trim((string)($u['userid'] ?? ''));
        if ($devUserId === $employeeUserId) {
            return $devUid; // already on device — reuse same slot
        }
        if ($devUid > $maxUid) $maxUid = $devUid;
    }

    // Not found → next free slot
    $next = $maxUid + 1;
    return ($next > 0 && $next <= 65535) ? $next : 1;
}

/**
 * Push ONE employee to ONE device so they can register their fingerprint.
 *
 * The ZKTeco IN01 flow:
 *   1. App calls setUser(slot, employeeId, name) → device stores user.
 *   2. Employee walks to terminal, places finger → device stores template.
 *   3. Every punch is now recognised and synced with the correct employee ID.
 *
 * KEY FIX: uid (param 1) = internal device slot (resolved via device_resolve_uid)
 *          userid (param 2) = employee's user_id string ("990")
 *          These are TWO different things — must not use the same value for both.
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

        $employeeUserId = (string)(int)$employee['user_id']; // e.g. "990"
        $name = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
        if ($name === '') $name = 'User ' . $employeeUserId;
        if (strlen($name) > 24) $name = substr($name, 0, 24);

        // Get the correct internal slot for this employee
        $slot = device_resolve_uid($zk, $employeeUserId);

        $zk->disableDevice();
        // setUser(internalSlot, employeeIdString, name, password, role, cardno)
        $ok = $zk->setUser($slot, $employeeUserId, $name, '', 0, 0);
        $zk->enableDevice();
        $zk->disconnect();

        if ($ok === false) {
            return [false, "setUser() failed for slot={$slot}, userid={$employeeUserId} on {$device['name']}."];
        }

        return [true, "User '{$name}' (slot={$slot}, ID={$employeeUserId}) registered on {$device['name']} ({$device['ip_address']}). Employee can now scan their finger."];
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
 * Register an employee on the ZKTeco terminal and return instructions
 * for the manual fingerprint enrollment steps on the device.
 *
 * The IN01 firmware does NOT support CMD_ENROLL_FP (61) remotely.
 * We push the user via setUser() — then the employee follows these
 * steps on the terminal: Menu → User Mgmt → Enroll FP → UID → scan.
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
            return [false, "Cannot connect to {$device['name']} ({$device['ip_address']}:{$device['port']}). Check the device is on."];
        }

        $employeeUserId = (string)(int)$employee['user_id']; // e.g. "990"
        $name = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
        if (strlen($name) > 24) $name = substr($name, 0, 24);
        if ($name === '') $name = 'User ' . $employeeUserId;

        // KEY FIX: resolve the correct internal device slot for this employee.
        // Do NOT use employee user_id as the slot — the device manages its own slot numbers.
        $slot = device_resolve_uid($zk, $employeeUserId);

        $zk->disableDevice();
        // setUser(internalSlot, employeeIdString, name, password, role, cardno)
        $zk->setUser($slot, $employeeUserId, $name, '', 0, 0);
        $zk->enableDevice();

        // CMD_ENROLL_FP = 61: triggers the fingerprint screen on the terminal
        $response = $zk->_command(61, pack('vCC', $slot, $finger, 3));
        $zk->disconnect();

        $fingerNames = [
            0=>'Right Pinky', 1=>'Right Ring', 2=>'Right Middle', 3=>'Right Index', 4=>'Right Thumb',
            5=>'Left Thumb',  6=>'Left Index',  7=>'Left Middle',  8=>'Left Ring',   9=>'Left Pinky',
        ];
        $fname = $fingerNames[$finger] ?? "Finger $finger";

        if ($response === false) {
            // CMD 61 not supported — user is still registered via setUser above
            return [true,
                "User \"{$name}\" (slot={$slot}, ID={$employeeUserId}) registered on {$device['name']}. " .
                "Go to IN01: Menu → User Mgmt → Enroll FP → UID {$slot} → scan {$fname}."
            ];
        }

        return [true,
            "✓ Fingerprint screen activated on {$device['name']} — {$name} ({$fname}). " .
            "Terminal shows 'Place finger'. Ask the employee to scan NOW."
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
