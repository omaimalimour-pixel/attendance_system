<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Casablanca');

include "../db.php";

$pageTitle = "Sync Device";
$currentPage = "sync";

$message = "";
$messageType = "";
$syncResults = [];

if (isset($_POST['sync'])) {
    // Check if vendor autoload exists
    if (file_exists("../vendor/autoload.php")) {
        require "../vendor/autoload.php";

        $ip = isset($_POST['ip']) ? $_POST['ip'] : "192.168.100.201";
        $port = isset($_POST['port']) ? (int)$_POST['port'] : 4370;

        try {
            $zk = new \Rats\Zkteco\Lib\ZKTeco($ip, $port);

            if (!$zk->connect()) {
                $message = "Cannot connect to device at $ip:$port";
                $messageType = "danger";
            } else {
                $today = date("Y-m-d");
                $attendances = $zk->getAttendance();
                $imported = 0;
                $skipped = 0;

                foreach ($attendances as $a) {
                    if (!isset($a['timestamp'])) continue;

                    $datetime = strtotime($a['timestamp']);
                    $date = date("Y-m-d", $datetime);
                    $time = date("H:i:s", $datetime);

                    $user_id = (int)$a['id'];

                    // Verify employee exists
                    $emp = mysqli_query($conn, "SELECT id FROM employees WHERE user_id = $user_id");
                    if (mysqli_num_rows($emp) == 0) {
                        $skipped++;
                        continue;
                    }

                    $type = ($a['type'] == 0) ? "IN" : "OUT";
                    $device_id = "ZKTeco";
                    $data = mysqli_real_escape_string($conn, json_encode($a));

                    // Check duplicate
                    $check = mysqli_query($conn, "
                        SELECT id FROM attendance
                        WHERE user_id = $user_id AND date = '$date' AND time = '$time' AND type = '$type'
                    ");
                    if (mysqli_num_rows($check) > 0) {
                        $skipped++;
                        continue;
                    }

                    mysqli_query($conn, "
                        INSERT INTO attendance (user_id, date, time, type, device_id, data)
                        VALUES ($user_id, '$date', '$time', '$type', '$device_id', '$data')
                    ");
                    $imported++;
                }

                $zk->disconnect();
                $message = "Sync complete! $imported records imported, $skipped skipped.";
                $messageType = "success";
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
        }
    } else {
        $message = "ZKTeco library not installed. Run: composer install";
        $messageType = "warning";
    }
}

include "includes/header.php";
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?>">
    <i data-lucide="<?= $messageType == 'success' ? 'check-circle-2' : 'alert-circle' ?>"></i>
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h3>Device Sync</h3>
                    <p class="sub">Connect to ZKTeco device and import attendance records</p>
                </div>
            </div>
            <form method="POST">
                <div class="form-panel">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Device IP Address</label>
                            <input type="text" name="ip" value="192.168.100.201" placeholder="192.168.1.100" required>
                        </div>
                        <div class="form-group">
                            <label>Port</label>
                            <input type="number" name="port" value="4370" required>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="dashboard.php" class="qa-btn">Cancel</a>
                    <button type="submit" name="sync" class="qa-btn qa-primary">
                        <i data-lucide="refresh-cw"></i> Start Sync
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="panel h-100">
            <div class="panel-head">
                <div>
                    <h3>Sync Information</h3>
                    <p class="sub">How device synchronization works</p>
                </div>
            </div>
            <div class="panel-body">
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <div style="display:flex;gap:12px;align-items:flex-start;">
                        <div class="kpi-icon ic-blue" style="width:36px;height:36px;flex-shrink:0;">
                            <i data-lucide="wifi"></i>
                        </div>
                        <div>
                            <strong style="font-size:13px;">Connection</strong>
                            <p style="font-size:12px;color:var(--muted);margin:2px 0 0;">
                                Connects to your ZKTeco device via TCP/IP on the specified port.
                            </p>
                        </div>
                    </div>
                    <div style="display:flex;gap:12px;align-items:flex-start;">
                        <div class="kpi-icon ic-green" style="width:36px;height:36px;flex-shrink:0;">
                            <i data-lucide="download"></i>
                        </div>
                        <div>
                            <strong style="font-size:13px;">Data Retrieval</strong>
                            <p style="font-size:12px;color:var(--muted);margin:2px 0 0;">
                                Fetches all attendance logs from the biometric device.
                            </p>
                        </div>
                    </div>
                    <div style="display:flex;gap:12px;align-items:flex-start;">
                        <div class="kpi-icon ic-amber" style="width:36px;height:36px;flex-shrink:0;">
                            <i data-lucide="filter"></i>
                        </div>
                        <div>
                            <strong style="font-size:13px;">Deduplication</strong>
                            <p style="font-size:12px;color:var(--muted);margin:2px 0 0;">
                                Skips already imported records to avoid duplicates.
                            </p>
                        </div>
                    </div>
                    <div style="display:flex;gap:12px;align-items:flex-start;">
                        <div class="kpi-icon ic-green" style="width:36px;height:36px;flex-shrink:0;">
                            <i data-lucide="database"></i>
                        </div>
                        <div>
                            <strong style="font-size:13px;">Storage</strong>
                            <p style="font-size:12px;color:var(--muted);margin:2px 0 0;">
                                New records are saved to the database with date, time, and type.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
