<?php
if (!isset($selectedDate)) $selectedDate = date("Y-m-d");
$workStart = function_exists('work_start') ? work_start() : '09:00:00';
$rows = db_all(
    "SELECT e.user_id, e.first_name, e.last_name, dep.name AS department,
            MIN(a.time) AS fi, MAX(a.time) AS lo, COUNT(a.id) AS p
     FROM employees e
     LEFT JOIN departments dep ON dep.id = e.department_id
     LEFT JOIN attendance a ON a.user_id = e.user_id AND a.date = ?
     GROUP BY e.user_id ORDER BY e.first_name",
    [$selectedDate]
);
?>
<div class="panel"><div class="panel-hd"><div><h3>Attendance</h3><p class="sub"><?= e(date("d/m/Y", strtotime($selectedDate))) ?></p></div><a href="attendance.php?date=<?= e($selectedDate) ?>" class="btn">View All</a></div>
<div class="tw"><table class="dt"><thead><tr><th>Employee</th><th>Department</th><th>IN</th><th>OUT</th><th>Hours</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rows as $r):
  $s="Absent";$b="b-no"; if($r['fi']){$s="Present";$b="b-ok"; if($r['fi']>$workStart){$s="Late";$b="b-lt";}}
  $h="—"; if($r['fi']&&$r['lo']){$d=strtotime($r['lo'])-strtotime($r['fi']); if($d>0)$h=gmdate("H:i",$d);}
  $c=['#7c6aff','#38bdf8','#2dd4a8','#fbbf24','#f87171','#e879f9'];$bg=$c[crc32($r['user_id'])%6];
?>
  <tr>
    <td><div class="emp-c"><div class="emp-av" style="background:<?= $bg ?>"><?= e(strtoupper(substr($r['first_name'],0,1))) ?></div><div><div class="emp-nm"><?= e($r['first_name'].' '.$r['last_name']) ?></div><div class="emp-id">ID <?= (int)$r['user_id'] ?></div></div></div></td>
    <td><?= e($r['department'] ?: '—') ?></td>
    <td class="mono"><?= e($r['fi'] ?: '—') ?></td>
    <td class="mono"><?= e($r['lo'] ?: '—') ?></td>
    <td class="mono"><?= e($h) ?></td>
    <td><span class="badge <?= $b ?>"><?= $s ?></span></td>
  </tr>
<?php endforeach; ?>
</tbody></table></div></div>
