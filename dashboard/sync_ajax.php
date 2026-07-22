<?php
/**
 * ChronoX — AJAX Sync
 *
 * ROOT CAUSE of "Fidae shows absent":
 * When enrolled at the terminal (Menu → Enroll FP → scan finger),
 * the ID field is left blank → $a['id'] = "" in attendance records.
 * Previous code: (int)"" = 0 → no employee match → silently skipped.
 *
 * FIX: call getUser() first to build a map of
 *   device_uid (int, always set) → employee user_id (int)
 * then resolve each punch via that map.
 */
set_time_limit(0);
ignore_user_abort(false);

require __DIR__ . '/bootstrap.php';
require_perm('devices.sync');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
}

csrf_verify();

$scope    = inp($_POST, 'scope', 'all');
$deviceId = (int) inp($_POST, 'device_id');

$devs = $scope === 'one'
    ? db_all("SELECT * FROM devices WHERE id=? AND status='active'", [$deviceId])
    : db_all("SELECT * FROM devices WHERE status='active' ORDER BY name");

if (!$devs) { echo json_encode(['ok' => false, 'error' => 'No active devices.']); exit; }
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo json_encode(['ok' => false, 'error' => 'Run composer install first.']); exit;
}
require_once __DIR__ . '/../vendor/autoload.php';

$results = []; $totalImp = 0; $totalSkp = 0;

foreach ($devs as $dev) {
    $imported = 0; $skipped = 0; $status = 'success'; $message = '';

    try {
        $zk = new \Rats\Zkteco\Lib\ZKTeco($dev['ip_address'], (int)$dev['port']);
        @socket_set_option($zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);

        if (!$zk->connect()) {
            $msg = "Cannot connect to {$dev['ip_address']}:{$dev['port']}";
            db_exec("INSERT INTO sync_logs (device_id,imported,skipped,status,message,synced_by) VALUES (?,0,0,'error',?,?)",
                [$dev['id'], $msg, current_user()['username'] ?? 'system']);
            $results[] = ['device'=>$dev['name'],'ip'=>$dev['ip_address'],'imported'=>0,'skipped'=>0,'status'=>'error','message'=>$msg];
            continue;
        }

        // ── Step 1: build device_uid → employee_user_id map ───────────
        // getUser() returns array keyed by userid string, each value has:
        //   'uid'    => internal device slot (int, always set, e.g. 3)
        //   'userid' => the employee ID string (e.g. "990", or "" if enrolled without ID)
        //
        // IMPORTANT: use $u['userid'] directly — NOT the array key.
        // The array key is also the userid string but can be unreliable for empty strings
        // (PHP merges duplicate '' keys, losing all but the last one).
        $deviceUserList = $zk->getUser();
        $uidMap = []; // device internal slot (int) → employee user_id (int)
        foreach ((array)$deviceUserList as $u) {
            $internalSlot = (int)($u['uid']    ?? 0);
            $employeeId   = (int)trim((string)($u['userid'] ?? ''));
            if ($internalSlot > 0 && $employeeId > 0) {
                $uidMap[$internalSlot] = $employeeId;
            }
        }

        // ── Step 2: get attendance punches ────────────────────────────
        $rows = $zk->getAttendance();
        $zk->disconnect();

        if (!is_array($rows)) {
            $msg = "No attendance data returned.";
            db_exec("INSERT INTO sync_logs (device_id,imported,skipped,status,message,synced_by) VALUES (?,0,0,'error',?,?)",
                [$dev['id'], $msg, current_user()['username'] ?? 'system']);
            $results[] = ['device'=>$dev['name'],'ip'=>$dev['ip_address'],'imported'=>0,'skipped'=>0,'status'=>'error','message'=>$msg];
            continue;
        }

        // ── Step 3: import each punch ─────────────────────────────────
        foreach ($rows as $a) {
            if (!isset($a['timestamp'])) { $skipped++; continue; }
            $ts = strtotime($a['timestamp']);
            if ($ts === false || $ts <= 0) { $skipped++; continue; }

            $date = date('Y-m-d', $ts);
            $time = date('H:i:s', $ts);
            $type = (isset($a['type']) && (int)$a['type'] === 1) ? 'OUT' : 'IN';

            // Resolve employee user_id:
            // 1st: look up device's internal slot in the map from getUser()  ← most reliable
            // 2nd: fall back to $a['id'] string (user typed their ID on enrolment)
            // 3rd: use $a['uid'] directly as last resort
            $internalSlot = (int)($a['uid'] ?? 0);
            if (isset($uidMap[$internalSlot])) {
                $userId = $uidMap[$internalSlot];
            } else {
                $rawId  = trim((string)($a['id'] ?? ''));
                $userId = $rawId !== '' ? (int)$rawId : $internalSlot;
            }

            if ($userId <= 0) { $skipped++; continue; }

            // ── Smart dedup (3 cases) ──────────────────────────────────
            // Case A: correct record already exists → skip (true duplicate)
            $correctDup = db_val(
                "SELECT id FROM attendance WHERE user_id=? AND date=? AND time=? AND type=?",
                [$userId, $date, $time, $type]
            );
            if ($correctDup) { $skipped++; continue; }

            // Case B: record exists under WRONG user_id (old internal slot ≠ employee id)
            // UPDATE it to the correct user_id — no duplicate created.
            if ($internalSlot !== $userId) {
                $wrongRow = db_val(
                    "SELECT id FROM attendance WHERE user_id=? AND date=? AND time=? AND type=?",
                    [$internalSlot, $date, $time, $type]
                );
                if ($wrongRow) {
                    db_exec(
                        "UPDATE attendance SET user_id=?, device_id=? WHERE id=?",
                        [$userId, (int)$dev['id'], (int)$wrongRow]
                    );
                    $imported++;
                    continue;
                }
            }

            // Case C: genuinely new record → insert
            db_exec(
                "INSERT INTO attendance (user_id, device_id, date, time, type, raw) VALUES (?,?,?,?,?,?)",
                [$userId, $dev['id'], $date, $time, $type, json_encode($a)]
            );
            $imported++;

            // Auto-create placeholder employee for unknown user IDs
            if (!db_val("SELECT id FROM employees WHERE user_id=?", [$userId])) {
                $nameOnDevice = trim((string)($a['name'] ?? ''));
                db_exec(
                    "INSERT IGNORE INTO employees (user_id,first_name,last_name,status) VALUES (?,?,?,'active')",
                    [$userId, $nameOnDevice ?: 'Unknown', (string)$userId]
                );
            }
        }

        $message = "Imported $imported punch".($imported===1?'':'es').", skipped $skipped duplicates.";
        db_exec("INSERT INTO sync_logs (device_id,imported,skipped,status,message,synced_by) VALUES (?,?,?,'success',?,?)",
            [$dev['id'],$imported,$skipped,$message,current_user()['username']??'system']);
        db_exec("UPDATE devices SET last_sync_at=NOW() WHERE id=?", [$dev['id']]);

    } catch (\Throwable $e) {
        $status  = 'error';
        $message = 'Error: '.$e->getMessage();
        db_exec("INSERT INTO sync_logs (device_id,imported,skipped,status,message,synced_by) VALUES (?,?,?,'error',?,?)",
            [$dev['id'],$imported,$skipped,$message,current_user()['username']??'system']);
    }

    $results[] = ['device'=>$dev['name'],'ip'=>$dev['ip_address'],
                  'imported'=>$imported,'skipped'=>$skipped,'status'=>$status,'message'=>$message];
    $totalImp += $imported;
    $totalSkp += $skipped;
}

audit('devices.sync', 'devices', $scope==='one' ? $deviceId : 'all');
echo json_encode(['ok'=>true,'total_imported'=>$totalImp,'total_skipped'=>$totalSkp,'devices'=>$results]);
