<?php
/**
 * ChronoX — AJAX Sync endpoint
 * Called by JavaScript — returns JSON, no HTML.
 * Sets unlimited execution time so the sync never gets killed.
 */
set_time_limit(0);          // never timeout — sync can take a while
ignore_user_abort(false);   // stop if the browser disconnects

require __DIR__ . '/bootstrap.php';
require_perm('devices.sync');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
}

csrf_verify();

$scope    = inp($_POST, 'scope', 'all');   // 'all' or 'one'
$deviceId = (int) inp($_POST, 'device_id');

if ($scope === 'one') {
    $devs = db_all("SELECT * FROM devices WHERE id=? AND status='active'", [$deviceId]);
} else {
    $devs = db_all("SELECT * FROM devices WHERE status='active' ORDER BY name");
}

if (!$devs) {
    echo json_encode(['ok' => false, 'error' => 'No active devices found.']);
    exit;
}

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo json_encode(['ok' => false, 'error' => 'ZKTeco library not installed. Run: composer install']);
    exit;
}
require_once __DIR__ . '/../vendor/autoload.php';

$results  = [];
$totalImp = 0;
$totalSkp = 0;

foreach ($devs as $dev) {
    $imported = 0;
    $skipped  = 0;
    $status   = 'success';
    $message  = '';

    try {
        $zk = new \Rats\Zkteco\Lib\ZKTeco($dev['ip_address'], (int)$dev['port']);

        // Shorter socket timeout: 5 seconds per recv instead of 60
        // This prevents hanging on unresponsive devices
        socket_set_option(
            $zk->_zkclient ?? null,
            SOL_SOCKET, SO_RCVTIMEO,
            ['sec' => 5, 'usec' => 0]
        );

        if (!$zk->connect()) {
            $results[] = [
                'device'   => $dev['name'],
                'ip'       => $dev['ip_address'],
                'imported' => 0, 'skipped' => 0,
                'status'   => 'error',
                'message'  => "Cannot connect to {$dev['ip_address']}:{$dev['port']} — device offline or unreachable",
            ];
            db_exec("INSERT INTO sync_logs (device_id,imported,skipped,status,message,synced_by) VALUES (?,0,0,'error',?,?)",
                [$dev['id'], "Cannot connect to {$dev['ip_address']}", current_user()['username'] ?? 'system']);
            continue;
        }

        $rows = $zk->getAttendance();
        $zk->disconnect();

        if (!is_array($rows)) {
            $results[] = [
                'device'   => $dev['name'],
                'ip'       => $dev['ip_address'],
                'imported' => 0, 'skipped' => 0,
                'status'   => 'error',
                'message'  => "Device returned no data. Try again.",
            ];
            db_exec("INSERT INTO sync_logs (device_id,imported,skipped,status,message,synced_by) VALUES (?,0,0,'error','No data returned',?)",
                [$dev['id'], current_user()['username'] ?? 'system']);
            continue;
        }

        foreach ($rows as $a) {
            if (!isset($a['timestamp'], $a['id'])) { continue; }

            $ts   = strtotime($a['timestamp']);
            if ($ts === false || $ts <= 0) { $skipped++; continue; }

            $date = date('Y-m-d', $ts);
            $time = date('H:i:s', $ts);
            $uid  = (int)$a['id'];
            $type = (isset($a['type']) && (int)$a['type'] === 1) ? 'OUT' : 'IN';

            // Must be a known employee
            $empId = db_val("SELECT id FROM employees WHERE user_id=?", [$uid]);
            if (!$empId) { $skipped++; continue; }

            // Deduplicate
            $dup = db_val(
                "SELECT id FROM attendance WHERE user_id=? AND date=? AND time=? AND type=? AND device_id=?",
                [$uid, $date, $time, $type, $dev['id']]
            );
            if ($dup) { $skipped++; continue; }

            db_exec(
                "INSERT INTO attendance (user_id, device_id, date, time, type, raw) VALUES (?,?,?,?,?,?)",
                [$uid, $dev['id'], $date, $time, $type, json_encode($a)]
            );
            $imported++;
        }

        $message = "Imported $imported new punch" . ($imported === 1 ? '' : 'es') . ", skipped $skipped.";
        db_exec("INSERT INTO sync_logs (device_id,imported,skipped,status,message,synced_by) VALUES (?,?,?,'success',?,?)",
            [$dev['id'], $imported, $skipped, $message, current_user()['username'] ?? 'system']);
        db_exec("UPDATE devices SET last_sync_at=NOW() WHERE id=?", [$dev['id']]);

    } catch (\Throwable $e) {
        $status  = 'error';
        $message = 'Error: ' . $e->getMessage();
        db_exec("INSERT INTO sync_logs (device_id,imported,skipped,status,message,synced_by) VALUES (?,?,?,'error',?,?)",
            [$dev['id'], $imported, $skipped, $message, current_user()['username'] ?? 'system']);
    }

    $results[]  = [
        'device'   => $dev['name'],
        'ip'       => $dev['ip_address'],
        'imported' => $imported,
        'skipped'  => $skipped,
        'status'   => $status,
        'message'  => $message,
    ];
    $totalImp += $imported;
    $totalSkp += $skipped;
}

audit('devices.sync', 'devices', $scope === 'one' ? $deviceId : 'all');

echo json_encode([
    'ok'       => true,
    'total_imported' => $totalImp,
    'total_skipped'  => $totalSkp,
    'devices'  => $results,
]);
