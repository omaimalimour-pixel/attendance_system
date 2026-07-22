<?php
require __DIR__ . '/bootstrap.php';

$pageTitle = "Device Sync";
$currentPage = "sync";

$results = [];      // per-device outcome for this run
$ranSync = false;

/**
 * Sync a single device row. Returns [imported, skipped, status, message].
 * Isolated so one offline device never blocks the others.
 */
function sync_one_device(array $dev): array
{
    $imported = 0; $skipped = 0;

    if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
        return [0, 0, 'error', 'ZKTeco library not installed (run composer install).'];
    }
    require_once __DIR__ . '/../vendor/autoload.php';

    try {
        $zk = new \Rats\Zkteco\Lib\ZKTeco($dev['ip_address'], (int)$dev['port']);
        if (!$zk->connect()) {
            return [0, 0, 'error', "Could not connect to {$dev['ip_address']}:{$dev['port']}"];
        }
        $rows = $zk->getAttendance();
        foreach ($rows as $a) {
            if (!isset($a['timestamp'], $a['id'])) { continue; }
            $ts   = strtotime($a['timestamp']);
            $date = date('Y-m-d', $ts);
            $time = date('H:i:s', $ts);
            $uid  = (int) $a['id'];
            $type = (isset($a['type']) && (int)$a['type'] === 1) ? 'OUT' : 'IN';

            // Only import punches for employees that exist in this system
            $emp = db_val("SELECT id FROM employees WHERE user_id=?", [$uid]);
            if (!$emp) { $skipped++; continue; }

            // Deduplicate on the unique punch key
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
        $zk->disconnect();
        return [$imported, $skipped, 'success', "Imported $imported, skipped $skipped."];
    } catch (\Throwable $e) {
        return [$imported, $skipped, 'error', 'Sync error: ' . $e->getMessage()];
    }
}

/* ---------------- Handle sync request ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_perm('devices.sync');
    csrf_verify();
    $ranSync = true;

    $scope = inp($_POST, 'scope'); // 'all' or 'one'
    if ($scope === 'one') {
        $devs = db_all("SELECT * FROM devices WHERE id=?", [(int) inp($_POST, 'device_id')]);
    } else {
        $devs = db_all("SELECT * FROM devices WHERE status='active' ORDER BY name");
    }

    foreach ($devs as $dev) {
        [$imp, $skp, $st, $msg] = sync_one_device($dev);
        db_exec(
            "INSERT INTO sync_logs (device_id, imported, skipped, status, message, synced_by) VALUES (?,?,?,?,?,?)",
            [$dev['id'], $imp, $skp, $st, $msg, current_user()['username'] ?? 'system']
        );
        if ($st === 'success') {
            db_exec("UPDATE devices SET last_sync_at=NOW() WHERE id=?", [$dev['id']]);
        }
        $results[] = ['device' => $dev, 'imported' => $imp, 'skipped' => $skp, 'status' => $st, 'message' => $msg];
    }
    audit('devices.sync', 'devices', $scope === 'one' ? (int) inp($_POST, 'device_id') : 'all');
}

/* Preselect a device if arriving from devices.php (?device=ID) */
$preDevice = isset($_GET['device']) ? (int) $_GET['device'] : 0;

$devices  = db_all("SELECT d.*, dep.name AS dept_name FROM devices d LEFT JOIN departments dep ON dep.id=d.department_id ORDER BY d.name");
$recent   = db_all("SELECT s.*, d.name AS device_name FROM sync_logs s LEFT JOIN devices d ON d.id=s.device_id ORDER BY s.created_at DESC LIMIT 10");
$activeCount = count(array_filter($devices, fn($d) => $d['status'] === 'active'));

include "includes/header.php";
?>

<?php if ($ranSync): ?>
  <?php $ok = count(array_filter($results, fn($r)=>$r['status']==='success')); $bad = count($results)-$ok; ?>
  <div class="alert alert-<?= $bad === 0 ? 'success' : 'danger' ?>">
    Sync finished · <?= $ok ?> device(s) succeeded<?= $bad ? ", $bad failed" : '' ?>.
    <?= array_sum(array_column($results,'imported')) ?> new punches imported.
  </div>
<?php endif; ?>

<div class="page-top">
  <div class="greet"><h2 style="font-size:18px">Device Synchronization</h2><p><?= $activeCount ?> active device(s) ready to pull attendance</p></div>
  <?php if (can('devices.sync')): ?>
  <form method="post" class="flt">
    <?= csrf_field() ?><input type="hidden" name="scope" value="all">
    <button class="btn btn-primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg> Sync All Active Devices</button>
  </form>
  <?php endif; ?>
</div>

<div class="row">
  <!-- Devices to sync -->
  <div class="col-8">
    <div class="panel">
      <div class="panel-head"><div><h3>Devices</h3><p class="sub">Sync individually or all at once</p></div><a href="devices.php" class="btn">Manage Devices</a></div>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Device</th><th>Department</th><th>IP · Port</th><th>Status</th><th>Last Sync</th><th></th></tr></thead>
          <tbody>
          <?php if ($devices): foreach ($devices as $d):
            $badge=$d['status']==='active'?'badge-present':($d['status']==='maintenance'?'badge-late':'badge-absent');
            $runRes = null; foreach ($results as $r) { if ($r['device']['id']==$d['id']) { $runRes=$r; break; } }
          ?>
            <tr>
              <td><div class="emp-cell"><div class="emp-avatar" style="background:linear-gradient(135deg,#6366F1,#8B5CF6)"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="#fff" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><circle cx="12" cy="12" r="3"/></svg></div><div class="emp-name"><?= e($d['name']) ?></div></div></td>
              <td><?= $d['dept_name'] ? '<span class="badge badge-dept">'.e($d['dept_name']).'</span>' : '—' ?></td>
              <td class="mono"><?= e($d['ip_address']) ?> · <?= (int)$d['port'] ?></td>
              <td><span class="badge <?= $badge ?>"><?= ucfirst(e($d['status'])) ?></span></td>
              <td class="mono">
                <?php if ($runRes): ?>
                  <span class="badge <?= $runRes['status']==='success'?'badge-present':'badge-absent' ?>"><?= e($runRes['message']) ?></span>
                <?php else: ?>
                  <?= $d['last_sync_at'] ? e(date('d/m/Y H:i', strtotime($d['last_sync_at']))) : '—' ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if (can('devices.sync')): ?>
                <form method="post" style="display:inline">
                  <?= csrf_field() ?><input type="hidden" name="scope" value="one"><input type="hidden" name="device_id" value="<?= (int)$d['id'] ?>">
                  <button class="row-act" title="Sync this device"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg></button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="6"><div class="empty-state"><h4>No devices</h4><p><a href="devices.php" style="color:#5B54E8">Add a device</a> to begin.</p></div></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Recent sync activity -->
  <div class="col-4">
    <div class="panel h-100">
      <div class="panel-head"><div><h3>Recent Syncs</h3><p class="sub">Last 10 runs</p></div></div>
      <div class="panel-bd" style="display:flex;flex-direction:column;gap:12px">
        <?php if ($recent): foreach ($recent as $s): ?>
          <div style="display:flex;gap:11px;align-items:flex-start">
            <div class="kpi-icon <?= $s['status']==='success'?'green':'rose' ?>" style="width:32px;height:32px;flex-shrink:0">
              <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><?php if($s['status']==='success'):?><polyline points="20 6 9 17 4 12"/><?php else:?><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/><?php endif;?></svg>
            </div>
            <div style="min-width:0">
              <div style="font-size:13px;font-weight:600"><?= e($s['device_name'] ?: 'Device #'.$s['device_id']) ?></div>
              <div style="font-size:11.5px;color:#98A0B3"><?= e($s['message']) ?></div>
              <div style="font-size:11px;color:#98A0B3;margin-top:2px"><?= e(date('d/m H:i', strtotime($s['created_at']))) ?> · <?= e($s['synced_by']) ?></div>
            </div>
          </div>
        <?php endforeach; else: ?>
          <div class="empty-state" style="padding:24px 8px"><p>No sync history yet.</p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include "includes/footer.php"; ?>
