<?php
/**
 * ChronoX — AJAX Sync endpoint
 * Returns JSON. Never gets killed by PHP timeout.
 *
 * ZKTeco attendance record structure:
 *   $a['uid']  = internal device index (auto-assigned: 1, 2, 3...)
 *   $a['id']   = userid STRING you entered when enrolling (e.g. "990")
 *   $a['type'] = 0=IN, 1=OUT
 *
 * CRITICAL: We use $a['id'] (userid string) to match employees.user_id.
 * We import ALL punches regardless of employee existence.
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

if ($scope === 'one') {
    $devs = db_all("SELECT * FROM devices WHERE id=? AND status='active'", [$deviceId]);
} else {
    $devs = db_all("SELECT * FROM devices WHERE status='active' ORDER BY name");
}

if (!$devs) { echo json_encode(['ok' => false, 'error' => 'No active devices found.']); exit; }
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo json_encode(['ok' => false, 'error' => 'ZKTeco library not installed. Run: composer install']);
    exit;
}
require_once __DIR__ . '/../vendor/autoload.php';

$results = []; $totalImp = 0; $totalSkp = 0;

foreach ($devs as $dev) {
    $imported = 0; $skipped = 0; $status = 'success'; $message = '';

    try {
        $zk = new \Rats\Zkteco\Lib\ZKTeco($dev['ip_address'], (int)$dev['port']);
        @socket_set_option($zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);

        if (!$zk->connect()) {
            $msg = "Cannot connect to {$dev['ip_address']}:{$dev['port']} — device offline";
            db_exec("INSERT INTO sync_logs (device_id,imported,skipped,status,message,synced_by) VALUES (?,0,0,'error',?,?)",
                [$dev['id'], $msg, current_user()['username'] ?? 'system']);
            $results[] = ['device'=>$dev['name'],'ip'=>$dev['ip_address'],'imported'=>0,'skipped'=>0,'status'=>'error','message'=>$msg];
            continue;
        }

        $rows = $zk->getAttendance();
        $zk->disconnect();

        if (!is_array($rows)) {
            $msg = "Device returned no data.";
            db_exec("INSERT INTO sync_logs (device_id,imported,skipped,status,message,synced_by) VALUES (?,0,0,'error',?,?)",
                [$dev['id'], $msg, current_user()['username'] ?? 'system']);
            $results[] = ['device'=>$dev['name'],'ip'=>$dev['ip_address'],'imported'=>0,'skipped'=>0,'status'=>'error','message'=>$msg];
            continue;
        }

        foreach ($rows as $a) {
            if (!isset($a['timestamp'])) { $skipped++; continue; }

            $ts = strtotime($a['timestamp']);
            if ($ts === false || $ts <= 0) { $skipped++; continue; }

            $date = date('Y-m-d', $ts);
            $time = date('H:i:s', $ts);
            $type = (isset($a['type']) && (int)$a['type'] === 1) ? 'OUT' : 'IN';

            // Use $a['id'] (the userid the employee registered with, e.g. "990")
            // Fall back to $a['uid'] (internal device index) if id is empty
            $rawId = trim((string)($a['id'] ?? ''));
            $uid   = $rawId !== '' ? (int)$rawId : (int)($a['uid'] ?? 0);

            if ($uid <= 0) { $skipped++; continue; }

            // Deduplicate by (user_id + date + time + type) ONLY
            // Do NOT include device_id — old records have device_id='ZKTeco' string
            // and new ones have device_id=INT, causing incorrect re-imports
            $dup = db_val(
                "SELECT id FROM attendance WHERE user_id=? AND date=? AND time=? AND type=?",
                [$uid, $date, $time, $type]
            );
            if ($dup) { $skipped++; continue; }

            // Import this punch — always, even if employee not in employees table
            db_exec(
                "INSERT INTO attendance (user_id, device_id, date, time, type, raw) VALUES (?,?,?,?,?,?)",
                [$uid, $dev['id'], $date, $time, $type, json_encode($a)]
            );
            $imported++;

            // Auto-create placeholder employee if UID unknown (no punch is ever lost)
            $empExists = db_val("SELECT id FROM employees WHERE user_id=?", [$uid]);
            if (!$empExists) {
                $nameOnDevice = trim((string)($a['name'] ?? ''));
                db_exec("INSERT IGNORE INTO employees (user_id, first_name, last_name, status) VALUES (?,?,?,'active')",
                    [$uid, $nameOnDevice ?: 'Employee', (string)$uid]);
            }
        }

        $message = "Imported $imported new punch".($imported===1?'':'es').", skipped $skipped duplicates.";
        db_exec("INSERT INTO sync_logs (device_id,imported,skipped,status,message,synced_by) VALUES (?,?,?,'success',?,?)",
            [$dev['id'],$imported,$skipped,$message,current_user()['username']??'system']);
        db_exec("UPDATE devices SET last_sync_at=NOW() WHERE id=?", [$dev['id']]);

    } catch (\Throwable $e) {
        $status  = 'error';
        $message = 'Error: ' . $e->getMessage();
        db_exec("INSERT INTO sync_logs (device_id,imported,skipped,status,message,synced_by) VALUES (?,?,?,'error',?,?)",
            [$dev['id'],$imported,$skipped,$message,current_user()['username']??'system']);
    }

    $results[] = ['device'=>$dev['name'],'ip'=>$dev['ip_address'],'imported'=>$imported,'skipped'=>$skipped,'status'=>$status,'message'=>$message];
    $totalImp += $imported;
    $totalSkp += $skipped;
}

audit('devices.sync','devices',$scope==='one'?$deviceId:'all');

echo json_encode(['ok'=>true,'total_imported'=>$totalImp,'total_skipped'=>$totalSkp,'devices'=>$results]);
