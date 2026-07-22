<?php
require __DIR__ . '/bootstrap.php';

$pageTitle = "Devices";
$currentPage = "devices";

$message = ""; $messageType = "";

/* ---------------- Handle actions (POST + CSRF protected) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_perm('*'); // only Administrators manage devices
    csrf_verify();
    $action = inp($_POST, 'action');

    $name   = inp($_POST, 'name');
    $ip     = inp($_POST, 'ip_address');
    $port   = (int) (inp($_POST, 'port') ?: 4370);
    $loc    = inp($_POST, 'location');
    $serial = inp($_POST, 'serial');
    $deptId = (int) inp($_POST, 'department_id') ?: null;
    $status = in_array(inp($_POST, 'status'), ['active','inactive','maintenance'], true) ? inp($_POST, 'status') : 'active';

    // basic IP validation
    $ipValid = filter_var($ip, FILTER_VALIDATE_IP) !== false;

    if ($action === 'create' || $action === 'update') {
        if ($name === '' || !$ipValid) {
            $message = "Please provide a device name and a valid IP address.";
            $messageType = "danger";
        } elseif ($action === 'create') {
            db_exec("INSERT INTO devices (name, serial, ip_address, port, location, department_id, status)
                     VALUES (?,?,?,?,?,?,?)", [$name, $serial ?: null, $ip, $port, $loc ?: null, $deptId, $status]);
            audit('device.create', 'devices', db_insert_id());
            $message = "Device added successfully."; $messageType = "success";
        } else {
            $id = (int) inp($_POST, 'id');
            db_exec("UPDATE devices SET name=?, serial=?, ip_address=?, port=?, location=?, department_id=?, status=? WHERE id=?",
                    [$name, $serial ?: null, $ip, $port, $loc ?: null, $deptId, $status, $id]);
            audit('device.update', 'devices', $id);
            $message = "Device updated successfully."; $messageType = "success";
        }
    } elseif ($action === 'delete') {
        $id = (int) inp($_POST, 'id');
        db_exec("DELETE FROM devices WHERE id=?", [$id]);
        audit('device.delete', 'devices', $id);
        $message = "Device removed."; $messageType = "success";
    } elseif ($action === 'toggle') {
        $id = (int) inp($_POST, 'id');
        db_exec("UPDATE devices SET status = IF(status='active','inactive','active') WHERE id=?", [$id]);
        audit('device.toggle', 'devices', $id);
        $message = "Device status updated."; $messageType = "success";
    }
}

/* ---------------- Data ---------------- */
$departments = db_all("SELECT id, name, code FROM departments ORDER BY name");
$devices = db_all(
    "SELECT d.*, dep.name AS dept_name
     FROM devices d
     LEFT JOIN departments dep ON dep.id = d.department_id
     ORDER BY d.name"
);

$total     = count($devices);
$activeCnt = count(array_filter($devices, fn($d) => $d['status'] === 'active'));
$deptCnt   = count($departments);

include "includes/header.php";
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>"><?= e($message) ?></div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-row">
  <div class="stat-mini"><div class="kpi-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9h6v6H9z"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $total ?></div><div class="kpi-sub">Total Devices</div></div></div>
  <div class="stat-mini"><div class="kpi-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12.55a11 11 0 0 1 14 0"/><path d="M8.5 16.5a5 5 0 0 1 7 0"/><path d="M2 8.82a15 15 0 0 1 20 0"/><line x1="12" y1="20" x2="12" y2="20"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $activeCnt ?></div><div class="kpi-sub">Active</div></div></div>
  <div class="stat-mini"><div class="kpi-icon violet"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $deptCnt ?></div><div class="kpi-sub">Departments</div></div></div>
</div>

<div class="panel">
  <div class="panel-head">
    <div><h3>Clocking Devices</h3><p class="sub">Biometric machines installed across departments</p></div>
    <?php if (can('*')): ?>
    <div class="flt">
      <a href="sync_attendance.php" class="btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>Sync All</a>
      <button class="btn btn-primary" onclick="openDevice()">+ Add Device</button>
    </div>
    <?php endif; ?>
  </div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Device</th><th>Department</th><th>IP · Port</th><th>Location</th><th>Status</th><th>Last Sync</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if ($devices): foreach ($devices as $d):
        $badge = $d['status']==='active' ? 'badge-present' : ($d['status']==='maintenance' ? 'badge-late' : 'badge-absent');
      ?>
        <tr>
          <td>
            <div class="emp-cell">
              <div class="emp-avatar" style="background:linear-gradient(135deg,#6366F1,#8B5CF6)"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#fff" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><circle cx="12" cy="12" r="3"/></svg></div>
              <div><div class="emp-name"><?= e($d['name']) ?></div><div class="emp-id"><?= e($d['serial'] ?: 'ZKTeco') ?></div></div>
            </div>
          </td>
          <td><?php if($d['dept_name']): ?><span class="badge badge-dept"><?= e($d['dept_name']) ?></span><?php else: ?><span style="color:#98A0B3">—</span><?php endif; ?></td>
          <td class="mono"><?= e($d['ip_address']) ?> · <?= (int)$d['port'] ?></td>
          <td><?= e($d['location'] ?: '—') ?></td>
          <td><span class="badge <?= $badge ?>"><?= ucfirst(e($d['status'])) ?></span></td>
          <td class="mono"><?= $d['last_sync_at'] ? e(date('d/m/Y H:i', strtotime($d['last_sync_at']))) : '—' ?></td>
          <td>
            <div class="row-actions">
              <a class="row-act" href="sync_attendance.php?device=<?= (int)$d['id'] ?>" title="Sync this device"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg></a>
              <?php if (can('*')): ?>
              <button class="row-act success" title="Edit"
                onclick='openDevice(<?= json_encode($d, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </button>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete this device?')">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                <button class="row-act danger" title="Delete"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="7"><div class="empty-state"><h4>No devices yet</h4><p>Add your first clocking machine to start syncing attendance.</p></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (can('*')): ?>
<!-- Add/Edit modal -->
<div id="deviceModal" class="cx-modal" style="display:none">
  <div class="cx-modal-card">
    <div class="cx-modal-hd"><h3 id="dmTitle">Add Device</h3><button class="cx-x" onclick="closeDevice()">&times;</button></div>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" id="dmAction" value="create">
      <input type="hidden" name="id" id="dmId" value="">
      <div class="form-panel"><div class="form-grid">
        <div class="form-group"><label>Device Name</label><input name="name" id="dmName" required placeholder="e.g. Engineering Entrance"></div>
        <div class="form-group"><label>Serial (optional)</label><input name="serial" id="dmSerial" placeholder="Device serial"></div>
        <div class="form-group"><label>IP Address</label><input name="ip_address" id="dmIp" required placeholder="192.168.100.201"></div>
        <div class="form-group"><label>Port</label><input name="port" id="dmPort" type="number" value="4370" required></div>
        <div class="form-group"><label>Location</label><input name="location" id="dmLoc" placeholder="Building A · Floor 2"></div>
        <div class="form-group"><label>Department</label>
          <select name="department_id" id="dmDept"><option value="">— None —</option>
            <?php foreach ($departments as $dep): ?><option value="<?= (int)$dep['id'] ?>"><?= e($dep['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Status</label>
          <select name="status" id="dmStatus"><option value="active">Active</option><option value="inactive">Inactive</option><option value="maintenance">Maintenance</option></select>
        </div>
      </div></div>
      <div class="form-actions"><button type="button" class="btn" onclick="closeDevice()">Cancel</button><button class="btn btn-primary">Save Device</button></div>
    </form>
  </div>
</div>
<style>
.cx-modal{position:fixed;inset:0;background:rgba(11,16,32,.5);backdrop-filter:blur(3px);z-index:2000;display:flex;align-items:center;justify-content:center;padding:20px}
.cx-modal-card{width:100%;max-width:640px;background:#fff;border-radius:18px;box-shadow:0 24px 60px rgba(0,0,0,.28);overflow:hidden}
.cx-modal-hd{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #F1F2F6}
.cx-modal-hd h3{font-size:16px;font-weight:750}
.cx-x{border:none;background:none;font-size:26px;line-height:1;color:#98A0B3;cursor:pointer}
</style>
<script>
function openDevice(d){
  document.getElementById('deviceModal').style.display='flex';
  var isEdit=!!d;
  document.getElementById('dmTitle').textContent=isEdit?'Edit Device':'Add Device';
  document.getElementById('dmAction').value=isEdit?'update':'create';
  document.getElementById('dmId').value=isEdit?d.id:'';
  document.getElementById('dmName').value=isEdit?d.name:'';
  document.getElementById('dmSerial').value=isEdit&&d.serial?d.serial:'';
  document.getElementById('dmIp').value=isEdit?d.ip_address:'';
  document.getElementById('dmPort').value=isEdit?d.port:4370;
  document.getElementById('dmLoc').value=isEdit&&d.location?d.location:'';
  document.getElementById('dmDept').value=isEdit&&d.department_id?d.department_id:'';
  document.getElementById('dmStatus').value=isEdit?d.status:'active';
}
function closeDevice(){document.getElementById('deviceModal').style.display='none';}
document.getElementById('deviceModal').addEventListener('click',function(e){if(e.target===this)closeDevice();});
</script>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
