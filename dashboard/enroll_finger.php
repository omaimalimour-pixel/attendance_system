<?php
/**
 * Fingerprint Enrollment — sends CMD_ENROLL_FP to the ZKTeco IN01 terminal.
 * The terminal screen shows "Place finger" and captures the biometric.
 */
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../core/device.php';
require_perm('employees.manage');

$pageTitle   = "Enroll Fingerprint";
$currentPage = "enrollment";

$enrollResult = null;
$message = ''; $messageType = '';

/* ── POST: send CMD_ENROLL_FP to the terminal ───────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $empId  = (int) inp($_POST, 'employee_id');
    $devId  = (int) inp($_POST, 'device_id');
    $finger = (int) inp($_POST, 'finger');
    $emp    = db_one("SELECT * FROM employees WHERE id=?", [$empId]);
    $device = db_one("SELECT * FROM devices WHERE id=?", [$devId]);

    if (!$emp)    { $message = "Employee not found."; $messageType = "danger"; }
    elseif (!$device) { $message = "Device not found."; $messageType = "danger"; }
    else {
        [$ok, $msg] = device_enroll_finger($device, $emp, $finger);
        $enrollResult = ['ok'=>$ok, 'msg'=>$msg, 'emp'=>$emp, 'device'=>$device, 'finger'=>$finger];

        /* Update / create an enrollment_requests record */
        if (db_table_exists('enrollment_requests')) {
            $reqExists = db_val("SELECT id FROM enrollment_requests WHERE employee_id=? AND status NOT IN ('enrolled')", [$empId]);
            if ($reqExists) {
                db_exec("UPDATE enrollment_requests SET device_id=?, status=?, message=?, updated_at=NOW() WHERE id=?",
                    [$devId, $ok ? 'sent' : 'failed', $msg, (int)$reqExists]);
            } else {
                db_exec(
                    "INSERT INTO enrollment_requests (employee_id, user_id, device_id, department_id, status, message, requested_by, updated_at)
                     VALUES (?,?,?,?,'".($ok?'sent':'failed')."',?,?,NOW())",
                    [$empId, (int)$emp['user_id'], $devId, $emp['department_id'] ?? null, $msg, current_user()['username'] ?? '']
                );
            }
        }
        audit('enrollment.finger_cmd', 'employees', $empId);
    }
}

/* ── Data ─────────────────────────────────────────────────────── */
$employees = db_all(
    "SELECT e.*, dep.name AS dept_name FROM employees e
     LEFT JOIN departments dep ON dep.id=e.department_id
     ORDER BY e.first_name"
);
$devices = db_all(
    "SELECT d.*, dep.name AS dept_name FROM devices d
     LEFT JOIN departments dep ON dep.id=d.department_id
     WHERE d.status='active' ORDER BY d.name"
);

/* Pre-select: employee from URL, device = IN01 if available */
$preEmpId = (int) inp($_GET, 'emp');
$preDevId = $devices ? (int)$devices[0]['id'] : 0;
foreach ($devices as $d) {
    if ($d['ip_address'] === '192.168.100.201') { $preDevId = (int)$d['id']; break; }
}

$libOk = device_lib_available();
$fingerNames = [
    0=>'Right Pinky', 1=>'Right Ring', 2=>'Right Middle', 3=>'Right Index', 4=>'Right Thumb',
    5=>'Left Thumb',  6=>'Left Index',  7=>'Left Middle',  8=>'Left Ring',   9=>'Left Pinky',
];

include "includes/header.php";
?>

<?php if (!$libOk): ?>
<div class="alert alert-danger" style="margin-bottom:18px">
    <strong>ZKTeco library not installed.</strong> Run <code>composer install</code> in the project root, then refresh.
</div>
<?php endif; ?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>" style="margin-bottom:18px"><?= e($message) ?></div>
<?php endif; ?>

<!-- Result after sending command -->
<?php if ($enrollResult !== null): ?>
<div class="panel" style="margin-bottom:22px;border-color:<?= $enrollResult['ok'] ? 'rgba(52,211,153,.35)' : 'rgba(251,113,133,.35)' ?>">
    <div class="panel-hd" style="background:<?= $enrollResult['ok'] ? 'rgba(52,211,153,.07)' : 'rgba(251,113,133,.07)' ?>">
        <div style="display:flex;align-items:center;gap:14px">
            <div style="width:42px;height:42px;border-radius:12px;display:grid;place-items:center;flex-shrink:0;background:<?= $enrollResult['ok'] ? 'rgba(52,211,153,.18)' : 'rgba(251,113,133,.18)' ?>">
                <?php if ($enrollResult['ok']): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2.5" width="22" height="22"><polyline points="20 6 9 17 4 12"/></svg>
                <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="#FB7185" stroke-width="2.5" width="22" height="22"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                <?php endif; ?>
            </div>
            <div>
                <h3 style="font-size:16px;color:<?= $enrollResult['ok'] ? '#34D399' : '#FB7185' ?>"><?= $enrollResult['ok'] ? 'Command sent — terminal is ready' : 'Command failed' ?></h3>
                <p class="sub" style="margin-top:4px"><?= e($enrollResult['msg']) ?></p>
            </div>
        </div>
    </div>
    <?php if ($enrollResult['ok']): ?>
    <div class="panel-bd">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:18px">
            <div style="padding:15px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:11px">
                <div style="font-size:11.5px;color:var(--muted-2);text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px">Employee</div>
                <div style="font-size:15px;font-weight:650;color:var(--text)"><?= e($enrollResult['emp']['first_name'].' '.$enrollResult['emp']['last_name']) ?></div>
                <div style="font-size:12.5px;color:var(--muted)">UID <?= (int)$enrollResult['emp']['user_id'] ?></div>
            </div>
            <div style="padding:15px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:11px">
                <div style="font-size:11.5px;color:var(--muted-2);text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px">Device</div>
                <div style="font-size:15px;font-weight:650;color:var(--text)"><?= e($enrollResult['device']['name']) ?></div>
                <div style="font-size:12.5px;color:var(--muted)"><?= e($enrollResult['device']['ip_address']) ?></div>
            </div>
            <div style="padding:15px;background:rgba(52,211,153,.07);border:1px solid rgba(52,211,153,.25);border-radius:11px">
                <div style="font-size:11.5px;color:var(--muted-2);text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px">Finger</div>
                <div style="font-size:15px;font-weight:650;color:#34D399"><?= $fingerNames[$enrollResult['finger']] ?? 'Finger '.$enrollResult['finger'] ?></div>
                <div style="font-size:12.5px;color:var(--muted)">Index #<?= $enrollResult['finger'] ?></div>
            </div>
        </div>

        <!-- Big prominent instruction -->
        <div style="padding:18px 20px;background:rgba(52,211,153,.08);border:1px solid rgba(52,211,153,.25);border-radius:12px;display:flex;align-items:center;gap:14px">
            <div style="font-size:34px;animation:blink 1s infinite">👆</div>
            <div>
                <div style="font-size:16px;font-weight:750;color:#34D399">The IN01 screen shows "Place finger" — scan NOW</div>
                <div style="font-size:14px;color:var(--muted);margin-top:4px">
                    Employee places their <strong style="color:#34D399"><?= $fingerNames[$enrollResult['finger']] ?? 'selected finger' ?></strong> on the ZKTeco sensor.
                    The device will beep when enrollment is complete.
                </div>
            </div>
        </div>

        <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap">
            <a href="enrollment.php" class="btn btn-primary">Go to Enrollment Queue →</a>
            <a href="enroll_finger.php?emp=<?= (int)$enrollResult['emp']['id'] ?>" class="btn">Enroll Another Finger</a>
        </div>
    </div>
    <?php endif; ?>
</div>
<style>@keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}</style>
<?php endif; ?>

<!-- Main layout: employee list + finger selector -->
<div class="row" style="gap:0;align-items:flex-start">

<!-- LEFT: Employee list -->
<div class="col-4">
    <div class="panel">
        <div class="panel-hd"><div><h3>Employees</h3><p class="sub">Select who to enroll</p></div></div>
        <?php foreach ($employees as $emp):
            $active = ($emp['id'] == $preEmpId);
            $c = ['#6366F1','#8B5CF6','#0BA5C7','#0EA372','#E5484D','#D98A0B'][$emp['id']%6];
        ?>
        <a href="enroll_finger.php?emp=<?= (int)$emp['id'] ?>"
           style="display:flex;align-items:center;gap:12px;padding:13px 18px;border-bottom:1px solid var(--border);text-decoration:none;background:<?= $active ? 'rgba(129,140,248,.09)' : 'transparent' ?>">
            <div style="width:34px;height:34px;border-radius:9px;background:<?= $c ?>;color:#fff;display:grid;place-items:center;font-weight:700;font-size:13px;flex-shrink:0"><?= e(strtoupper(substr($emp['first_name'],0,1))) ?></div>
            <div style="min-width:0">
                <div style="font-weight:600;font-size:14px;color:<?= $active ? '#818CF8' : 'var(--text)' ?>"><?= e($emp['first_name'].' '.$emp['last_name']) ?></div>
                <div style="font-size:12px;color:var(--muted-2)">UID <?= (int)$emp['user_id'] ?> · <?= e($emp['dept_name'] ?? '—') ?></div>
            </div>
            <?php if ($active): ?><div style="width:8px;height:8px;border-radius:50%;background:#818CF8;flex-shrink:0;margin-left:auto"></div><?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- RIGHT: Finger selector + device -->
<div class="col-8">
    <?php if ($preEmpId):
        $emp = db_one("SELECT e.*, dep.name AS dept_name FROM employees e LEFT JOIN departments dep ON dep.id=e.department_id WHERE e.id=?", [$preEmpId]);
    ?>
    <?php if ($emp): ?>
    <div class="panel">
        <div class="panel-hd">
            <?php $c2 = ['#6366F1','#8B5CF6','#0BA5C7','#0EA372','#E5484D','#D98A0B'][$emp['id']%6]; ?>
            <div style="display:flex;align-items:center;gap:12px">
                <div style="width:42px;height:42px;border-radius:11px;background:<?= $c2 ?>;color:#fff;display:grid;place-items:center;font-weight:700;font-size:17px"><?= e(strtoupper(substr($emp['first_name'],0,1))) ?></div>
                <div>
                    <h3><?= e($emp['first_name'].' '.$emp['last_name']) ?></h3>
                    <p class="sub">UID <?= (int)$emp['user_id'] ?> · <?= e($emp['dept_name'] ?? '—') ?></p>
                </div>
            </div>
            <!-- Device selector -->
            <div>
                <select id="deviceSelect" style="height:38px;padding:0 12px;background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px;font-family:inherit">
                    <?php foreach ($devices as $d): ?>
                    <option value="<?= (int)$d['id'] ?>" <?= $d['id']==$preDevId?'selected':'' ?>><?= e($d['name']) ?> (<?= e($d['ip_address']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="panel-bd">
            <div style="text-align:center">
                <p style="font-size:14.5px;color:var(--muted);margin-bottom:16px">Click a finger to select it, then click <strong>Enroll on Device</strong></p>
                <div id="selectedFingerLabel" style="font-size:16px;font-weight:750;color:#818CF8;min-height:24px;margin-bottom:20px"></div>

                <!-- TWO HANDS SVG DIAGRAM -->
                <div style="display:flex;justify-content:center;gap:48px;flex-wrap:wrap">

                    <!-- RIGHT HAND -->
                    <div>
                        <div style="font-size:12.5px;color:var(--muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:.07em;font-weight:600">Right Hand</div>
                        <svg id="rh" viewBox="0 0 200 260" width="170" height="221" style="overflow:visible">
                            <!-- Palm -->
                            <ellipse cx="100" cy="185" rx="70" ry="58" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.12)" stroke-width="1.5"/>
                            <!-- Finger 0: Right Pinky -->
                            <g class="fp" data-fi="0" onclick="pick(0)" style="cursor:pointer">
                                <ellipse cx="34" cy="112" rx="17" ry="44" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.12)" stroke-width="1.5" class="fe"/>
                                <text x="34" y="116" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.55)" font-family="Inter,sans-serif" class="ft">0</text>
                            </g>
                            <!-- Finger 1: Right Ring -->
                            <g class="fp" data-fi="1" onclick="pick(1)" style="cursor:pointer">
                                <ellipse cx="64" cy="90" rx="18" ry="54" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.12)" stroke-width="1.5" class="fe"/>
                                <text x="64" y="94" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.55)" font-family="Inter,sans-serif" class="ft">1</text>
                            </g>
                            <!-- Finger 2: Right Middle -->
                            <g class="fp" data-fi="2" onclick="pick(2)" style="cursor:pointer">
                                <ellipse cx="97" cy="78" rx="18" ry="60" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.12)" stroke-width="1.5" class="fe"/>
                                <text x="97" y="82" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.55)" font-family="Inter,sans-serif" class="ft">2</text>
                            </g>
                            <!-- Finger 3: Right Index -->
                            <g class="fp" data-fi="3" onclick="pick(3)" style="cursor:pointer">
                                <ellipse cx="130" cy="88" rx="18" ry="54" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.12)" stroke-width="1.5" class="fe"/>
                                <text x="130" y="92" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.55)" font-family="Inter,sans-serif" class="ft">3</text>
                            </g>
                            <!-- Finger 4: Right Thumb -->
                            <g class="fp" data-fi="4" onclick="pick(4)" style="cursor:pointer">
                                <ellipse cx="167" cy="155" rx="22" ry="42" transform="rotate(-35 167 155)" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.12)" stroke-width="1.5" class="fe"/>
                                <text x="167" y="158" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.55)" font-family="Inter,sans-serif" class="ft">4</text>
                            </g>
                        </svg>
                    </div>

                    <!-- LEFT HAND -->
                    <div>
                        <div style="font-size:12.5px;color:var(--muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:.07em;font-weight:600">Left Hand</div>
                        <svg id="lh" viewBox="0 0 200 260" width="170" height="221" style="overflow:visible">
                            <ellipse cx="100" cy="185" rx="70" ry="58" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.12)" stroke-width="1.5"/>
                            <!-- Finger 5: Left Thumb -->
                            <g class="fp" data-fi="5" onclick="pick(5)" style="cursor:pointer">
                                <ellipse cx="33" cy="155" rx="22" ry="42" transform="rotate(35 33 155)" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.12)" stroke-width="1.5" class="fe"/>
                                <text x="33" y="158" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.55)" font-family="Inter,sans-serif" class="ft">5</text>
                            </g>
                            <!-- Finger 6: Left Index -->
                            <g class="fp" data-fi="6" onclick="pick(6)" style="cursor:pointer">
                                <ellipse cx="70" cy="88" rx="18" ry="54" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.12)" stroke-width="1.5" class="fe"/>
                                <text x="70" y="92" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.55)" font-family="Inter,sans-serif" class="ft">6</text>
                            </g>
                            <!-- Finger 7: Left Middle -->
                            <g class="fp" data-fi="7" onclick="pick(7)" style="cursor:pointer">
                                <ellipse cx="103" cy="78" rx="18" ry="60" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.12)" stroke-width="1.5" class="fe"/>
                                <text x="103" y="82" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.55)" font-family="Inter,sans-serif" class="ft">7</text>
                            </g>
                            <!-- Finger 8: Left Ring -->
                            <g class="fp" data-fi="8" onclick="pick(8)" style="cursor:pointer">
                                <ellipse cx="136" cy="90" rx="18" ry="54" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.12)" stroke-width="1.5" class="fe"/>
                                <text x="136" y="94" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.55)" font-family="Inter,sans-serif" class="ft">8</text>
                            </g>
                            <!-- Finger 9: Left Pinky -->
                            <g class="fp" data-fi="9" onclick="pick(9)" style="cursor:pointer">
                                <ellipse cx="166" cy="112" rx="17" ry="44" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.12)" stroke-width="1.5" class="fe"/>
                                <text x="166" y="116" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.55)" font-family="Inter,sans-serif" class="ft">9</text>
                            </g>
                        </svg>
                    </div>

                </div><!-- /hands -->

                <!-- Quick pick chips -->
                <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-top:18px">
                    <?php foreach ($fingerNames as $fi => $fn): ?>
                    <button type="button" class="fq" data-fi="<?= $fi ?>" onclick="pick(<?= $fi ?>)"
                        style="padding:7px 12px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--muted);font-size:13px;cursor:pointer;transition:.12s;font-family:inherit">
                        <span style="font-weight:750;color:var(--accent);margin-right:4px"><?= $fi ?></span><?= $fn ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div><!-- /text-center -->
        </div><!-- /panel-bd -->

        <!-- Hidden form -->
        <form method="POST" id="ef">
            <?= csrf_field() ?>
            <input type="hidden" name="employee_id" value="<?= (int)$preEmpId ?>">
            <input type="hidden" name="finger"      id="fi"  value="1">
            <input type="hidden" name="device_id"   id="di"  value="<?= $preDevId ?>">
        </form>

        <div class="form-actions">
            <a href="enrollment.php" class="btn">Cancel</a>
            <button id="enrollBtn" class="btn btn-primary" disabled onclick="send(event)"
                    style="font-size:15px;padding:12px 24px;gap:10px;opacity:.5;transition:.2s">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <path d="M12 11c0 3.5-2 6-2 6M7 8a5 5 0 0 1 10 0v2M5 12a7 7 0 0 1 .5-2.6M12 8v5a8 8 0 0 0 2 5M9 20a12 12 0 0 1-1.5-6"/>
                </svg>
                <span id="bt">Select a finger first</span>
            </button>
        </div>
    </div>

    <script>
    let sel = -1;
    const FN = <?= json_encode(array_values($fingerNames)) ?>;

    function pick(i) {
        sel = i;
        document.getElementById('fi').value = i;
        document.getElementById('selectedFingerLabel').textContent = 'Selected: ' + FN[i] + ' (index ' + i + ')';
        const b = document.getElementById('enrollBtn');
        b.disabled = false; b.style.opacity = '1';
        document.getElementById('bt').textContent = 'Enroll ' + FN[i] + ' on Device';

        // SVG highlight
        document.querySelectorAll('.fp .fe').forEach(e => {
            e.setAttribute('fill','rgba(255,255,255,.05)');
            e.setAttribute('stroke','rgba(255,255,255,.12)');
        });
        document.querySelectorAll('.fp .ft').forEach(e => e.setAttribute('fill','rgba(255,255,255,.55)'));
        const g = document.querySelector('.fp[data-fi="'+i+'"]');
        if (g) {
            g.querySelector('.fe').setAttribute('fill','rgba(129,140,248,.22)');
            g.querySelector('.fe').setAttribute('stroke','#818CF8');
            g.querySelector('.ft').setAttribute('fill','#818CF8');
        }
        // Chip highlight
        document.querySelectorAll('.fq').forEach(b2 => {
            const a = parseInt(b2.dataset.fi) === i;
            b2.style.background   = a ? 'rgba(129,140,248,.16)' : 'var(--surface)';
            b2.style.borderColor  = a ? '#818CF8' : 'var(--border)';
            b2.style.color        = a ? '#818CF8' : 'var(--muted)';
        });
    }

    function send(e) {
        if (sel < 0) return;
        document.getElementById('di').value = document.getElementById('deviceSelect').value;
        document.getElementById('bt').textContent = 'Sending to terminal…';
        document.getElementById('enrollBtn').disabled = true;
        document.getElementById('ef').submit();
    }

    document.getElementById('deviceSelect')?.addEventListener('change', function(){
        document.getElementById('di').value = this.value;
    });

    // Auto-select index finger (most common)
    pick(3);
    </script>

    <?php else: ?>
    <div class="panel"><div class="panel-bd" style="text-align:center;padding:48px;color:var(--muted)">Employee not found.</div></div>
    <?php endif; ?>

    <?php else: ?>
    <!-- No employee chosen yet -->
    <div class="panel" style="min-height:340px">
        <div class="panel-bd" style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:64px 20px;text-align:center;gap:18px">
            <div style="width:80px;height:80px;border-radius:22px;background:var(--accent-s);display:grid;place-items:center">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.4" width="40" height="40">
                    <path d="M12 11c0 3.5-2 6-2 6M7 8a5 5 0 0 1 10 0v2M5 12a7 7 0 0 1 .5-2.6M12 8v5a8 8 0 0 0 2 5M9 20a12 12 0 0 1-1.5-6"/>
                </svg>
            </div>
            <div>
                <div style="font-size:19px;font-weight:750;margin-bottom:10px">Select an employee</div>
                <div style="font-size:14.5px;color:var(--muted);max-width:360px">Pick a person from the left list, then choose which finger to enroll on the ZKTeco IN01.</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div><!-- /col-8 -->

</div><!-- /row -->

<?php include "includes/footer.php"; ?>
