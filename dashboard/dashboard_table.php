<?php
if(!isset($selectedDate))$selectedDate=date("Y-m-d");
$rT=@mysqli_query($conn,"SELECT employees.user_id,employees.first_name,employees.last_name,MIN(attendance.time)AS fi,MAX(attendance.time)AS lo,COUNT(attendance.id)AS p FROM employees LEFT JOIN attendance ON employees.user_id=attendance.user_id AND attendance.date='$selectedDate' GROUP BY employees.user_id ORDER BY employees.first_name");
?>
<div class="panel"><div class="panel-hd"><div><h3>Attendance</h3><p class="sub"><?=date("d/m/Y",strtotime($selectedDate))?></p></div><a href="attendance.php?date=<?=$selectedDate?>" class="btn">View All</a></div>
<div class="tw"><table class="dt"><thead><tr><th>Employee</th><th>IN</th><th>OUT</th><th>Hours</th><th>Status</th></tr></thead><tbody>
<?php if($rT):while($r=mysqli_fetch_assoc($rT)):
$s="Absent";$b="b-no";if($r['fi']){$s="Present";$b="b-ok";if($r['fi']>"09:00:00"){$s="Late";$b="b-lt";}}
$h="--";if($r['fi']&&$r['lo']){$d=strtotime($r['lo'])-strtotime($r['fi']);if($d>0)$h=gmdate("H:i",$d);}
$c=['#7c6aff','#38bdf8','#2dd4a8','#fbbf24','#f87171','#e879f9'];$bg=$c[crc32($r['user_id'])%6];
?><tr><td><div class="emp-c"><div class="emp-av" style="background:<?=$bg?>"><?=strtoupper(substr($r['first_name'],0,1))?></div><div><div class="emp-nm"><?=htmlspecialchars($r['first_name'].' '.$r['last_name'])?></div><div class="emp-id">ID: <?=$r['user_id']?></div></div></div></td><td class="mono"><?=$r['fi']?:'--:--'?></td><td class="mono"><?=$r['lo']?:'--:--'?></td><td class="mono"><?=$h?></td><td><span class="badge <?=$b?>"><?=$s?></span></td></tr>
<?php endwhile;endif;?></tbody></table></div></div>
