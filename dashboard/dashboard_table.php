<?php
if(!isset($selectedDate))$selectedDate=date("Y-m-d");
$rT=@mysqli_query($conn,"SELECT employees.user_id,employees.first_name,employees.last_name,employees.department,MIN(attendance.time)AS fi,MAX(attendance.time)AS lo,COUNT(attendance.id)AS p FROM employees LEFT JOIN attendance ON employees.user_id=attendance.user_id AND attendance.date='$selectedDate' GROUP BY employees.user_id ORDER BY employees.first_name");
?>
<div class="panel">
  <div class="panel-hd"><div><h3>Today's Attendance</h3><p class="sub"><?=date("l, F j, Y",strtotime($selectedDate))?></p></div><a href="attendance.php?date=<?=$selectedDate?>" class="btn">View all</a></div>
  <div class="tw"><table class="dt"><thead><tr><th>Employee</th><th>Department</th><th>Check In</th><th>Check Out</th><th>Hours</th><th>Status</th></tr></thead><tbody>
<?php if($rT && mysqli_num_rows($rT)>0):while($r=mysqli_fetch_assoc($rT)):
  $s="Absent";$b="b-no";if($r['fi']){$s="Present";$b="b-ok";if($r['fi']>"09:00:00"){$s="Late";$b="b-lt";}}
  $h="—";if($r['fi']&&$r['lo']){$d=strtotime($r['lo'])-strtotime($r['fi']);if($d>0)$h=gmdate("H:i",$d);}
  $c=['#6366F1','#8B5CF6','#0BA5C7','#0EA372','#E5484D','#D98A0B'];$bg=$c[crc32($r['user_id'])%6];
?>
    <tr>
      <td><div class="emp-c"><div class="emp-av" style="background:<?=$bg?>"><?=strtoupper(substr($r['first_name'],0,1))?></div><div><div class="emp-nm"><?=htmlspecialchars($r['first_name'].' '.$r['last_name'])?></div><div class="emp-id">ID <?=$r['user_id']?></div></div></div></td>
      <td><?=htmlspecialchars($r['department']?:'—')?></td>
      <td class="mono"><?=$r['fi']?:'—'?></td>
      <td class="mono"><?=$r['lo']?:'—'?></td>
      <td class="mono"><?=$h?></td>
      <td><span class="badge <?=$b?>"><?=$s?></span></td>
    </tr>
<?php endwhile;else:?>
    <tr><td colspan="6"><div class="empty"><h4>No records yet</h4><p>Sync your device to import attendance.</p></div></td></tr>
<?php endif;?>
  </tbody></table></div>
</div>
