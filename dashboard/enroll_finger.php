<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../core/device.php';
require_perm('employees.manage');

$pageTitle   = "Enroll Fingerprint";
$currentPage = "enrollment";
$enrollResult = null;
$message = ''; $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = inp($_POST, 'action');

    // Push user to device + show manual steps
    if ($action === 'enroll') {
        $empId  = (int) inp($_POST, 'employee_id');
        $devId  = (int) inp($_POST, 'device_id');
        $finger = (int) inp($_POST, 'finger');
        $emp    = db_one("SELECT * FROM employees WHERE id=?", [$empId]);
        $device = db_one("SELECT * FROM devices WHERE id=?", [$devId]);

        if (!$emp || !$device) {
            $message = "Employee or device not found."; $messageType = "danger";
        } else {
            [$ok, $msg] = device_enroll_finger($device, $emp, $finger);
            $enrollResult = ['ok'=>$ok,'msg'=>$msg,'emp'=>$emp,'device'=>$device,'finger'=>$finger];
            if (db_table_exists('enrollment_requests')) {
                $reqId = @db_val("SELECT id FROM enrollment_requests WHERE employee_id=? AND status NOT IN ('enrolled')", [$empId]);
                $status = $ok ? 'sent' : 'pending';
                if ($reqId) {
                    db_exec("UPDATE enrollment_requests SET device_id=?,status=?,message=?,updated_at=NOW() WHERE id=?",[$devId,$status,$msg,(int)$reqId]);
                } else {
                    db_exec("INSERT INTO enrollment_requests (employee_id,user_id,device_id,department_id,status,message,requested_by,updated_at) VALUES (?,?,?,?,?,?,?,NOW())",
                        [$empId,(int)$emp['user_id'],$devId,$emp['department_id']??null,$status,$msg,current_user()['username']??'']);
                }
            }
            audit('enrollment.push','employees',$empId);
        }
    }

    // Confirm the employee has scanned — mark enrolled in DB
    if ($action === 'confirm_enrolled') {
        $empId = (int) inp($_POST, 'employee_id');
        $reqId = @db_val("SELECT id FROM enrollment_requests WHERE employee_id=? ORDER BY id DESC LIMIT 1",[$empId]);
        if ($reqId) enrollment_mark_enrolled((int)$reqId);
        audit('enrollment.confirmed','enrollment_requests',$reqId??0);
        $emp = db_one("SELECT * FROM employees WHERE id=?",[$empId]);
        $message = "✓ ".e($emp['first_name']??'')." ".e($emp['last_name']??'')." marked as enrolled.";
        $messageType = "success";
    }
}

$employees = db_all("SELECT e.*,dep.name AS dept_name FROM employees e LEFT JOIN departments dep ON dep.id=e.department_id ORDER BY e.first_name");
$devices   = db_all("SELECT d.*,dep.name AS dept_name FROM devices d LEFT JOIN departments dep ON dep.id=d.department_id WHERE d.status='active' ORDER BY d.name");
$preEmpId  = (int) inp($_GET, 'emp');
$preDevId  = $devices ? (int)$devices[0]['id'] : 0;
foreach ($devices as $d) { if ($d['ip_address']==='192.168.100.201'){ $preDevId=(int)$d['id']; break; } }

$FN = [0=>'Right Pinky',1=>'Right Ring',2=>'Right Middle',3=>'Right Index',4=>'Right Thumb',
       5=>'Left Thumb', 6=>'Left Index', 7=>'Left Middle', 8=>'Left Ring',  9=>'Left Pinky'];

include "includes/header.php";
?>

<?php if ($message): ?>
<div class="alert alert-<?=$messageType?>" style="margin-bottom:18px"><?=$message?></div>
<?php endif; ?>

<?php if ($enrollResult !== null): ?>
<!-- Result panel -->
<div class="panel" style="margin-bottom:22px;border-color:<?=$enrollResult['ok']?'rgba(52,211,153,.3)':'rgba(251,113,133,.3)'?>">
    <div class="panel-hd" style="background:<?=$enrollResult['ok']?'rgba(52,211,153,.07)':'rgba(251,113,133,.07)'?>">
        <div style="display:flex;align-items:center;gap:14px">
            <div style="width:44px;height:44px;border-radius:12px;display:grid;place-items:center;flex-shrink:0;background:<?=$enrollResult['ok']?'rgba(52,211,153,.18)':'rgba(251,113,133,.18)'?>">
                <?php if ($enrollResult['ok']): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2.5" width="22" height="22"><polyline points="20 6 9 17 4 12"/></svg>
                <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="#FB7185" stroke-width="2.5" width="22" height="22"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                <?php endif; ?>
            </div>
            <div>
                <h3 style="font-size:17px;color:<?=$enrollResult['ok']?'#34D399':'#FB7185'?>"><?=$enrollResult['ok']?'User registered on device ✓':'Connection failed'?></h3>
                <p class="sub" style="font-size:13.5px;margin-top:4px"><?=e($enrollResult['msg'])?></p>
            </div>
        </div>
    </div>
    <?php if ($enrollResult['ok']):
        $uid   = (int)$enrollResult['emp']['user_id'];
        $fname = $FN[$enrollResult['finger']] ?? 'Finger '.$enrollResult['finger'];
        $steps = [
            ['1', 'On the <strong>ZKTeco IN01</strong> terminal, press <strong>Menu</strong>'],
            ['2', 'Go to <strong>User Mgmt</strong>'],
            ['3', 'Select <strong>Enroll FP</strong>'],
            ['4', 'Enter user ID: <strong style="font-size:20px;color:#818CF8">'.$uid.'</strong>'],
            ['5', 'Select finger: <strong style="color:#34D399">'.$fname.'</strong>'],
            ['6', 'Place the finger on the sensor <strong>3 times</strong> as prompted'],
            ['7', 'Terminal beeps — enrollment complete ✓'],
        ];
    ?>
    <div class="panel-bd">
        <div style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:14px">Steps to complete on the terminal:</div>
        <?php foreach ($steps as [$n,$text]): ?>
        <div style="display:flex;align-items:center;gap:14px;padding:11px 0;border-bottom:1px solid rgba(255,255,255,.06)">
            <div style="width:30px;height:30px;border-radius:50%;background:rgba(129,140,248,.14);border:1px solid rgba(129,140,248,.25);display:grid;place-items:center;flex-shrink:0;font-weight:750;font-size:14px;color:#818CF8"><?=$n?></div>
            <div style="font-size:14.5px;color:var(--text-2)"><?=$text?></div>
        </div>
        <?php endforeach; ?>
        <div style="margin-top:20px;padding:16px 18px;background:rgba(129,140,248,.08);border:1px solid rgba(129,140,248,.2);border-radius:12px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
            <div>
                <div style="font-size:15px;font-weight:700;color:#818CF8">Done scanning?</div>
                <div style="font-size:13.5px;color:var(--muted);margin-top:3px">Click confirm once the employee has successfully scanned their finger.</div>
            </div>
            <form method="POST">
                <?=csrf_field()?>
                <input type="hidden" name="action" value="confirm_enrolled">
                <input type="hidden" name="employee_id" value="<?=(int)$enrollResult['emp']['id']?>">
                <button class="btn btn-primary" style="font-size:15px;padding:12px 22px;gap:9px">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18"><polyline points="20 6 9 17 4 12"/></svg>
                    Confirm Enrolled
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Main layout -->
<div class="row" style="gap:0;align-items:flex-start">
<div class="col-4">
    <div class="panel">
        <div class="panel-hd"><div><h3>Employees</h3><p class="sub">Select who to enroll</p></div></div>
        <?php foreach ($employees as $emp):
            $active = ($emp['id']==$preEmpId);
            $col = ['#6366F1','#8B5CF6','#0BA5C7','#0EA372','#E5484D','#D98A0B'][$emp['id']%6];
            $es = @db_val("SELECT status FROM enrollment_requests WHERE employee_id=? ORDER BY id DESC LIMIT 1",[(int)$emp['id']]);
            $dot = $es==='enrolled'?'#34D399':($es==='sent'?'#818CF8':($es?'#FB7185':'#444'));
        ?>
        <a href="enroll_finger.php?emp=<?=(int)$emp['id']?>"
           style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid var(--border);text-decoration:none;background:<?=$active?'rgba(129,140,248,.09)':'transparent'?>">
            <div style="width:34px;height:34px;border-radius:9px;background:<?=$col?>;color:#fff;display:grid;place-items:center;font-weight:700;font-size:13px;flex-shrink:0"><?=e(strtoupper(substr($emp['first_name'],0,1)))?></div>
            <div style="min-width:0;flex:1">
                <div style="font-weight:600;font-size:14px;color:<?=$active?'#818CF8':'var(--text)'?>"><?=e($emp['first_name'].' '.$emp['last_name'])?></div>
                <div style="font-size:12px;color:var(--muted-2)">UID <?=(int)$emp['user_id']?> · <?=e($emp['dept_name']??'—')?></div>
            </div>
            <div style="width:8px;height:8px;border-radius:50%;background:<?=$dot?>;flex-shrink:0" title="<?=e($es??'not started')?>"></div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="col-8">
<?php if ($preEmpId): $emp=db_one("SELECT e.*,dep.name AS dept_name FROM employees e LEFT JOIN departments dep ON dep.id=e.department_id WHERE e.id=?",[$preEmpId]); ?>
<?php if ($emp):
    $curReq = @db_one("SELECT * FROM enrollment_requests WHERE employee_id=? ORDER BY id DESC LIMIT 1",[(int)$emp['id']]);
    $col2   = ['#6366F1','#8B5CF6','#0BA5C7','#0EA372','#E5484D','#D98A0B'][$emp['id']%6];
?>

<?php if ($curReq && $curReq['status']==='enrolled'): ?>
<div class="panel" style="margin-bottom:16px;border-color:rgba(52,211,153,.3)">
    <div class="panel-hd" style="background:rgba(52,211,153,.07)">
        <div style="display:flex;align-items:center;gap:12px">
            <div style="width:40px;height:40px;border-radius:10px;background:rgba(52,211,153,.18);display:grid;place-items:center"><svg viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="2.5" width="20" height="20"><polyline points="20 6 9 17 4 12"/></svg></div>
            <div><h3 style="color:#34D399">Fingerprint enrolled ✓</h3><p class="sub"><?=e($emp['first_name'].' '.$emp['last_name'])?> can now use the terminal. Enroll another finger below if needed.</p></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="panel">
    <div class="panel-hd">
        <div style="display:flex;align-items:center;gap:12px">
            <div style="width:42px;height:42px;border-radius:11px;background:<?=$col2?>;color:#fff;display:grid;place-items:center;font-weight:700;font-size:17px"><?=e(strtoupper(substr($emp['first_name'],0,1)))?></div>
            <div><h3><?=e($emp['first_name'].' '.$emp['last_name'])?></h3><p class="sub">UID <?=(int)$emp['user_id']?> · <?=e($emp['dept_name']??'—')?></p></div>
        </div>
        <select id="devSel" style="height:38px;padding:0 12px;background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px;font-family:inherit">
            <?php foreach ($devices as $d): ?><option value="<?=(int)$d['id']?>" <?=$d['id']==$preDevId?'selected':''?>><?=e($d['name'])?> (<?=e($d['ip_address'])?>)</option><?php endforeach; ?>
        </select>
    </div>
    <div class="panel-bd">
        <p style="font-size:14.5px;color:var(--muted);text-align:center;margin-bottom:18px">Select a finger, then click <strong style="color:var(--text)">Register on Device</strong>.</p>
        <div id="selLabel" style="text-align:center;font-size:16px;font-weight:750;color:#818CF8;min-height:24px;margin-bottom:18px"></div>
        <div style="display:flex;justify-content:center;gap:40px;flex-wrap:wrap">
            <!-- RIGHT HAND -->
            <div style="text-align:center">
                <div style="font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;font-weight:600;margin-bottom:10px">Right Hand</div>
                <svg viewBox="0 0 200 260" width="160" height="208" style="overflow:visible">
                    <ellipse cx="100" cy="188" rx="68" ry="56" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.10)" stroke-width="1.5"/>
                    <g class="fp" data-fi="0" onclick="pick(0)" style="cursor:pointer"><ellipse cx="34" cy="115" rx="17" ry="44" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.10)" stroke-width="1.5" class="fe"/><text x="34" y="119" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.5)" font-family="Inter" class="ft">0</text></g>
                    <g class="fp" data-fi="1" onclick="pick(1)" style="cursor:pointer"><ellipse cx="64" cy="92" rx="18" ry="54" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.10)" stroke-width="1.5" class="fe"/><text x="64" y="96" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.5)" font-family="Inter" class="ft">1</text></g>
                    <g class="fp" data-fi="2" onclick="pick(2)" style="cursor:pointer"><ellipse cx="97" cy="79" rx="18" ry="60" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.10)" stroke-width="1.5" class="fe"/><text x="97" y="83" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.5)" font-family="Inter" class="ft">2</text></g>
                    <g class="fp" data-fi="3" onclick="pick(3)" style="cursor:pointer"><ellipse cx="130" cy="90" rx="18" ry="54" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.10)" stroke-width="1.5" class="fe"/><text x="130" y="94" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.5)" font-family="Inter" class="ft">3</text></g>
                    <g class="fp" data-fi="4" onclick="pick(4)" style="cursor:pointer"><ellipse cx="167" cy="158" rx="22" ry="42" transform="rotate(-35 167 158)" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.10)" stroke-width="1.5" class="fe"/><text x="167" y="161" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.5)" font-family="Inter" class="ft">4</text></g>
                </svg>
            </div>
            <!-- LEFT HAND -->
            <div style="text-align:center">
                <div style="font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;font-weight:600;margin-bottom:10px">Left Hand</div>
                <svg viewBox="0 0 200 260" width="160" height="208" style="overflow:visible">
                    <ellipse cx="100" cy="188" rx="68" ry="56" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.10)" stroke-width="1.5"/>
                    <g class="fp" data-fi="5" onclick="pick(5)" style="cursor:pointer"><ellipse cx="33" cy="158" rx="22" ry="42" transform="rotate(35 33 158)" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.10)" stroke-width="1.5" class="fe"/><text x="33" y="161" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.5)" font-family="Inter" class="ft">5</text></g>
                    <g class="fp" data-fi="6" onclick="pick(6)" style="cursor:pointer"><ellipse cx="70" cy="90" rx="18" ry="54" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.10)" stroke-width="1.5" class="fe"/><text x="70" y="94" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.5)" font-family="Inter" class="ft">6</text></g>
                    <g class="fp" data-fi="7" onclick="pick(7)" style="cursor:pointer"><ellipse cx="103" cy="79" rx="18" ry="60" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.10)" stroke-width="1.5" class="fe"/><text x="103" y="83" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.5)" font-family="Inter" class="ft">7</text></g>
                    <g class="fp" data-fi="8" onclick="pick(8)" style="cursor:pointer"><ellipse cx="136" cy="92" rx="18" ry="54" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.10)" stroke-width="1.5" class="fe"/><text x="136" y="96" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.5)" font-family="Inter" class="ft">8</text></g>
                    <g class="fp" data-fi="9" onclick="pick(9)" style="cursor:pointer"><ellipse cx="166" cy="115" rx="17" ry="44" fill="rgba(255,255,255,.05)" stroke="rgba(255,255,255,.10)" stroke-width="1.5" class="fe"/><text x="166" y="119" text-anchor="middle" font-size="15" fill="rgba(255,255,255,.5)" font-family="Inter" class="ft">9</text></g>
                </svg>
            </div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-top:16px">
            <?php foreach ($FN as $fi=>$fn): ?>
            <button type="button" class="fq" data-fi="<?=$fi?>" onclick="pick(<?=$fi?>)"
                style="padding:7px 12px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--muted);font-size:13.5px;cursor:pointer;transition:.12s;font-family:inherit">
                <span style="font-weight:750;color:var(--accent);margin-right:4px"><?=$fi?></span><?=$fn?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
    <form method="POST" id="ef">
        <?=csrf_field()?>
        <input type="hidden" name="action" value="enroll">
        <input type="hidden" name="employee_id" value="<?=(int)$preEmpId?>">
        <input type="hidden" name="finger" id="fi" value="3">
        <input type="hidden" name="device_id" id="di" value="<?=$preDevId?>">
    </form>
    <div class="form-actions">
        <a href="enrollment.php" class="btn">Enrollment Queue</a>
        <button id="enrollBtn" class="btn btn-primary" onclick="send()" style="font-size:15px;padding:12px 24px;gap:9px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M12 11c0 3.5-2 6-2 6M7 8a5 5 0 0 1 10 0v2"/></svg>
            <span id="bt">Register on Device</span>
        </button>
    </div>
</div>

<?php else: ?><div class="panel"><div class="panel-bd" style="text-align:center;padding:40px;color:var(--muted)">Employee not found.</div></div><?php endif; ?>
<?php else: ?>
<div class="panel"><div class="panel-bd" style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:64px 20px;text-align:center;gap:18px">
    <div style="width:72px;height:72px;border-radius:20px;background:var(--accent-s);display:grid;place-items:center"><svg viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.5" width="36" height="36"><path d="M12 11c0 3.5-2 6-2 6M7 8a5 5 0 0 1 10 0v2M5 12a7 7 0 0 1 .5-2.6M12 8v5a8 8 0 0 0 2 5M9 20a12 12 0 0 1-1.5-6"/></svg></div>
    <div><div style="font-size:19px;font-weight:750;margin-bottom:8px">Select an employee</div><div style="font-size:14.5px;color:var(--muted)">Choose from the left list, select a finger, and click Register on Device.</div></div>
</div></div>
<?php endif; ?>
</div></div>

<script>
const FN=<?=json_encode(array_values($FN))?>;
function pick(i){
    document.getElementById('fi').value=i;
    document.getElementById('selLabel').textContent='Selected: '+FN[i]+' (index '+i+')';
    document.getElementById('bt').textContent='Register '+FN[i]+' on Device';
    document.querySelectorAll('.fp .fe').forEach(e=>{e.setAttribute('fill','rgba(255,255,255,.05)');e.setAttribute('stroke','rgba(255,255,255,.10)');});
    document.querySelectorAll('.fp .ft').forEach(e=>e.setAttribute('fill','rgba(255,255,255,.5)'));
    const g=document.querySelector('.fp[data-fi="'+i+'"]');
    if(g){g.querySelector('.fe').setAttribute('fill','rgba(129,140,248,.22)');g.querySelector('.fe').setAttribute('stroke','#818CF8');g.querySelector('.ft').setAttribute('fill','#818CF8');}
    document.querySelectorAll('.fq').forEach(b=>{const a=parseInt(b.dataset.fi)===i;b.style.background=a?'rgba(129,140,248,.16)':'var(--surface)';b.style.borderColor=a?'#818CF8':'var(--border)';b.style.color=a?'#818CF8':'var(--muted)';});
}
function send(){
    document.getElementById('di').value=document.getElementById('devSel')?.value||'';
    document.getElementById('bt').textContent='Registering…';
    document.getElementById('enrollBtn').disabled=true;
    document.getElementById('ef').submit();
}
document.getElementById('devSel')?.addEventListener('change',e=>document.getElementById('di').value=e.target.value);
pick(3);
</script>
<?php include "includes/footer.php"; ?>
