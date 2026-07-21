<?php
if (!isset($selectedDate)) $selectedDate = date("Y-m-d");
$sqlT = "SELECT employees.user_id, employees.first_name, employees.last_name,
         MIN(attendance.time) AS first_in, MAX(attendance.time) AS last_out,
         COUNT(attendance.id) AS punches
         FROM employees LEFT JOIN attendance ON employees.user_id = attendance.user_id AND attendance.date='$selectedDate'
         GROUP BY employees.user_id ORDER BY employees.first_name ASC";
$resT = mysqli_query($conn, $sqlT);
?>
<div class="panel">
    <div class="panel-head">
        <div><h3>Today's Attendance</h3><p class="sub"><?= date("d/m/Y", strtotime($selectedDate)) ?></p></div>
        <a href="attendance.php?date=<?= $selectedDate ?>" class="btn">View All</a>
    </div>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Employee</th><th>First IN</th><th>Last OUT</th><th>Hours</th><th>Punches</th><th>Status</th></tr></thead>
            <tbody>
<?php if ($resT): while ($r = mysqli_fetch_assoc($resT)):
    $st = "Absent"; $bc = "badge-absent";
    if ($r['first_in']) { $st = "Present"; $bc = "badge-present"; if ($r['first_in'] > "09:00:00") { $st = "Late"; $bc = "badge-late"; } }
    $hrs = "--";
    if ($r['first_in'] && $r['last_out']) { $s = strtotime($r['first_in']); $e = strtotime($r['last_out']); if ($e > $s) $hrs = gmdate("H:i", $e-$s); }
    $colors = ['#6366F1','#8B5CF6','#22D3EE','#34D399','#FB7185','#FBBF24'];
    $bg = $colors[crc32($r['user_id']) % count($colors)];
?>
                <tr>
                    <td><div class="emp-cell"><div class="emp-avatar" style="background:<?= $bg ?>"><?= strtoupper(substr($r['first_name'],0,1)) ?></div><div><div class="emp-name"><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></div><div class="emp-id">ID: <?= $r['user_id'] ?></div></div></div></td>
                    <td class="mono"><?= $r['first_in'] ?: '--:--' ?></td>
                    <td class="mono"><?= $r['last_out'] ?: '--:--' ?></td>
                    <td class="mono"><?= $hrs ?></td>
                    <td class="mono"><?= $r['punches'] ?></td>
                    <td><span class="badge <?= $bc ?>"><?= $st ?></span></td>
                </tr>
<?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>
