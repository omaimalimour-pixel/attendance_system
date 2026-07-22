<?php
require __DIR__ . '/bootstrap.php';

$pageTitle   = "Device Sync";
$currentPage = "sync";

$devices     = db_all("SELECT d.*, dep.name AS dept_name FROM devices d LEFT JOIN departments dep ON dep.id=d.department_id ORDER BY d.name");
$recent      = db_all("SELECT s.*, d.name AS device_name FROM sync_logs s LEFT JOIN devices d ON d.id=s.device_id ORDER BY s.created_at DESC LIMIT 15");
$activeCount = count(array_filter($devices, fn($d) => $d['status'] === 'active'));

include "includes/header.php";
?>

<div class="page-top">
    <div class="greet">
        <h2 style="font-size:18px">Device Synchronization</h2>
        <p><?= $activeCount ?> active device(s) · pulls punches from the ZKTeco machine into the database</p>
    </div>
    <?php if (can('devices.sync')): ?>
    <button id="syncAllBtn" class="btn btn-primary" onclick="syncDevices('all')" style="font-size:15px;padding:12px 22px;gap:10px">
        <svg id="syncIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
            <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>
            <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/>
        </svg>
        <span id="syncAllLabel">Sync All Active Devices</span>
    </button>
    <?php endif; ?>
</div>

<!-- Progress / result banner -->
<div id="syncBanner" style="display:none;margin-bottom:20px">
    <div id="syncBannerInner" class="alert"></div>
</div>

<!-- Devices table -->
<div class="row">
<div class="col-8">
    <div class="panel">
        <div class="panel-head"><div><h3>Clocking Machines</h3><p class="sub">Click the sync icon on any row to sync one device</p></div><a href="devices.php" class="btn">Manage</a></div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Device</th><th>Department</th><th>IP · Port</th><th>Status</th><th>Last Sync</th><th></th></tr></thead>
                <tbody id="devTable">
                <?php foreach ($devices as $d):
                    $badge = $d['status']==='active' ? 'badge-present' : ($d['status']==='maintenance' ? 'badge-late' : 'badge-absent');
                ?>
                <tr id="devRow-<?= (int)$d['id'] ?>">
                    <td>
                        <div class="emp-cell">
                            <div class="emp-avatar" style="background:linear-gradient(135deg,#6366F1,#8B5CF6)">
                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="#fff" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><circle cx="12" cy="12" r="3"/></svg>
                            </div>
                            <div class="emp-name"><?= e($d['name']) ?></div>
                        </div>
                    </td>
                    <td><?= $d['dept_name'] ? '<span class="badge badge-dept">'.e($d['dept_name']).'</span>' : '—' ?></td>
                    <td class="mono"><?= e($d['ip_address']) ?> · <?= (int)$d['port'] ?></td>
                    <td><span class="badge <?= $badge ?>"><?= ucfirst(e($d['status'])) ?></span></td>
                    <td class="mono" id="lastSync-<?= (int)$d['id'] ?>"><?= $d['last_sync_at'] ? e(date('d/m/Y H:i', strtotime($d['last_sync_at']))) : '—' ?></td>
                    <td>
                        <?php if (can('devices.sync') && $d['status'] === 'active'): ?>
                        <button class="row-act" id="btn-<?= (int)$d['id'] ?>" title="Sync this device" onclick="syncDevices('one', <?= (int)$d['id'] ?>)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$devices): ?>
                <tr><td colspan="6"><div class="empty-state"><h4>No devices</h4><p><a href="devices.php" style="color:var(--accent)">Add a device</a> to begin.</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent sync history -->
<div class="col-4">
    <div class="panel">
        <div class="panel-head"><div><h3>Sync History</h3><p class="sub">Last 15 runs</p></div></div>
        <div id="syncHistory" style="display:flex;flex-direction:column;gap:0">
        <?php foreach ($recent as $s): ?>
            <div style="display:flex;align-items:flex-start;gap:11px;padding:12px 18px;border-bottom:1px solid var(--border)">
                <div class="kpi-icon <?= $s['status']==='success'?'green':'rose' ?>" style="width:32px;height:32px;flex-shrink:0">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                        <?php if($s['status']==='success'): ?><polyline points="20 6 9 17 4 12"/>
                        <?php else: ?><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/><?php endif; ?>
                    </svg>
                </div>
                <div style="min-width:0">
                    <div style="font-weight:600;font-size:13.5px"><?= e($s['device_name'] ?: 'Device #'.$s['device_id']) ?></div>
                    <div style="font-size:12px;color:var(--muted-2)"><?= e($s['message']) ?></div>
                    <div style="font-size:11px;color:var(--muted-2);margin-top:2px"><?= e(date('d/m H:i', strtotime($s['created_at']))) ?> · <?= e($s['synced_by']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$recent): ?>
            <div style="padding:28px 18px;text-align:center;color:var(--muted)">No sync history yet.</div>
        <?php endif; ?>
        </div>
    </div>
</div>
</div>

<script>
const CSRF = <?= json_encode(csrf_token()) ?>;

async function syncDevices(scope, deviceId = null) {
    const btn     = scope === 'all' ? document.getElementById('syncAllBtn') : document.getElementById('btn-' + deviceId);
    const label   = document.getElementById('syncAllLabel');
    const banner  = document.getElementById('syncBanner');
    const inner   = document.getElementById('syncBannerInner');

    // Disable button + show spinner
    if (btn) btn.disabled = true;
    if (label && scope === 'all') label.textContent = 'Syncing… please wait';
    if (scope === 'one' && btn) btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>';

    banner.style.display = 'none';

    // Spinning animation
    const iconEl = scope === 'all' ? document.getElementById('syncIcon') : null;
    if (iconEl) iconEl.style.animation = 'spin 1s linear infinite';

    const body = new URLSearchParams({_csrf: CSRF, scope: scope});
    if (deviceId) body.append('device_id', deviceId);

    let data;
    try {
        // No timeout on the fetch — let the server take as long as needed
        const res = await fetch('sync_ajax.php', {method: 'POST', body});
        data = await res.json();
    } catch (e) {
        inner.className = 'alert alert-danger';
        inner.textContent = 'Network error: ' + e.message;
        banner.style.display = 'block';
        resetBtn(btn, label, iconEl, scope);
        return;
    }

    // Show result
    if (data.ok) {
        const imp = data.total_imported;
        inner.className = 'alert alert-' + (imp > 0 ? 'success' : 'warning');
        inner.textContent = imp > 0
            ? '✓ Sync complete — ' + imp + ' new punch' + (imp === 1 ? '' : 'es') + ' imported.'
            : 'Sync complete — no new punches found (device is already up to date).';

        // Update last-sync timestamps in the table
        if (data.devices) {
            data.devices.forEach(d => {
                const idMatch = document.querySelectorAll('[id^="lastSync-"]');
                idMatch.forEach(el => el.textContent = 'just now');
            });
        }
        // Reload page after 1.5s to show fresh data
        setTimeout(() => location.reload(), 1500);
    } else {
        inner.className = 'alert alert-danger';
        inner.textContent = '✗ ' + (data.error || 'Sync failed');
    }

    banner.style.display = 'block';
    resetBtn(btn, label, iconEl, scope);
}

function resetBtn(btn, label, iconEl, scope) {
    if (btn) btn.disabled = false;
    if (label && scope === 'all') label.textContent = 'Sync All Active Devices';
    if (iconEl) iconEl.style.animation = '';
    if (scope === 'one' && btn) btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>';
}
</script>

<style>
@keyframes spin {from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
.alert-warning{background:rgba(251,191,36,.1);color:var(--amber);border:1px solid rgba(251,191,36,.2)}
</style>

<?php include "includes/footer.php"; ?>
