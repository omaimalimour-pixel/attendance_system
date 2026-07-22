<?php
/**
 * ChronoX — Device integration (ZKTeco fingerprint terminals).
 *
 * Fingerprint enrolment flow:
 *   1. An employee is added and assigned to a department.
 *   2. Each department has one (or more) biometric device(s).
 *   3. We push the employee's user record to that department's device(s) so the
 *      terminal recognises the user_id — the employee can then walk up to that
 *      machine and register their fingerprint.
 *   4. We track the request in `enrollment_requests` (pending → sent → enrolled).
 *
 * Every device call is wrapped so an offline terminal never breaks the app.
 */

require_once __DIR__ . '/db.php';

/** True when the ZKTeco PHP library is installed (composer). */
function device_lib_available(): bool
{
    return is_file(__DIR__ . '/../vendor/autoload.php')
        && (class_exists('\Rats\Zkteco\Lib\ZKTeco') || _device_try_autoload());
}

function _device_try_autoload(): bool
{
    require_once __DIR__ . '/../vendor/autoload.php';
    return class_exists('\Rats\Zkteco\Lib\ZKTeco');
}

/**
 * Push an employee onto a single device so they can enrol a fingerprint.
 * Returns [bool ok, string message].
 */
function device_push_user(array $device, array $employee): array
{
    if (!device_lib_available()) {
        return [false, 'ZKTeco library not installed (run composer install on the server).'];
    }
    try {
        $zk = new \Rats\Zkteco\Lib\ZKTeco($device['ip_address'], (int) $device['port']);
        if (!$zk->connect()) {
            return [false, "Device offline at {$device['ip_address']}:{$device['port']}"];
        }
        $zk->disableDevice();

        $uid  = (int) $employee['user_id'];             // internal index
        $name = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
        // setUser(uid, userid, name, password, role, cardno)
        $zk->setUser($uid, (string) $employee['user_id'], $name !== '' ? $name : ('User ' . $uid), '', 0, 0);

        $zk->enableDevice();
        $zk->disconnect();
        return [true, "User pushed to {$device['name']} — employee can now enrol their fingerprint on that machine."];
    } catch (\Throwable $ex) {
        return [false, 'Device error: ' . $ex->getMessage()];
    }
}

/**
 * Create fingerprint-enrolment request(s) for an employee, targeting every
 * active device in their department, and attempt to push them immediately.
 * Falls back to a "pending" request when the device is unreachable.
 */
function enrollment_request_create(array $employee, ?string $by = null): array
{
    $results = [];
    $deptId  = $employee['department_id'] ?? null;

    $devices = $deptId
        ? db_all("SELECT * FROM devices WHERE department_id=? AND status='active'", [(int) $deptId])
        : [];

    // No department device? Still record a pending request (unassigned).
    if (!$devices) {
        db_exec(
            "INSERT INTO enrollment_requests (employee_id, user_id, device_id, department_id, status, message, requested_by, updated_at)
             VALUES (?,?,?,?, 'pending', ?, ?, NOW())",
            [
                (int) $employee['id'], (int) $employee['user_id'], null, $deptId ? (int) $deptId : null,
                $deptId ? 'No active device in this department yet.' : 'Employee has no department assigned.',
                $by,
            ]
        );
        $results[] = ['device' => null, 'ok' => false, 'message' => 'No active device — request left pending.'];
        return $results;
    }

    foreach ($devices as $device) {
        [$ok, $msg] = device_push_user($device, $employee);
        $status = $ok ? 'sent' : 'pending';
        db_exec(
            "INSERT INTO enrollment_requests (employee_id, user_id, device_id, department_id, status, message, requested_by, updated_at)
             VALUES (?,?,?,?,?,?,?, NOW())",
            [(int) $employee['id'], (int) $employee['user_id'], (int) $device['id'], $device['department_id'], $status, $msg, $by]
        );
        $results[] = ['device' => $device, 'ok' => $ok, 'message' => $msg];
    }
    return $results;
}

/** Retry a single enrolment request (re-push to its device). */
function enrollment_retry(int $requestId, ?string $by = null): array
{
    $req = db_one("SELECT * FROM enrollment_requests WHERE id=?", [$requestId]);
    if (!$req) return [false, 'Request not found.'];

    $employee = db_one("SELECT * FROM employees WHERE id=?", [(int) $req['employee_id']]);
    if (!$employee) return [false, 'Employee no longer exists.'];

    // Resolve a device: the stored one, else an active device in the department.
    $device = $req['device_id'] ? db_one("SELECT * FROM devices WHERE id=?", [(int) $req['device_id']]) : null;
    if (!$device && !empty($employee['department_id'])) {
        $device = db_one("SELECT * FROM devices WHERE department_id=? AND status='active' LIMIT 1", [(int) $employee['department_id']]);
    }
    if (!$device) {
        db_exec("UPDATE enrollment_requests SET status='pending', message=?, updated_at=NOW() WHERE id=?",
            ['No active device available for this department.', $requestId]);
        return [false, 'No active device available.'];
    }

    [$ok, $msg] = device_push_user($device, $employee);
    db_exec("UPDATE enrollment_requests SET device_id=?, status=?, message=?, updated_at=NOW() WHERE id=?",
        [(int) $device['id'], $ok ? 'sent' : 'failed', $msg, $requestId]);
    return [$ok, $msg];
}

/** Mark a request as completed (fingerprint successfully registered). */
function enrollment_mark_enrolled(int $requestId): void
{
    db_exec("UPDATE enrollment_requests SET status='enrolled', enrolled_at=NOW(), updated_at=NOW() WHERE id=?", [$requestId]);
}
