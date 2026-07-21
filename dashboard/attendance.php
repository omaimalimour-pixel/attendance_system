<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Casablanca');

include "../db.php";
$pageTitle = "Attendance";
$currentPage = "attendance";
include "attendance_data.php";
include "includes/header.php";
?>

<div class="stats-row">
    <div class="stat-mini"><div class="kpi-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $totalEmployees ?></div><div class="kpi-sub">Total</div></div></div>
    <div class="stat-mini"><div class="kpi-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $totalPresent ?></div><div class="kpi-sub">Present</div></div></div>
    <div class="stat-mini"><div class="kpi-icon rose"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $totalAbsent ?></div><div class="kpi-sub">Absent</div></div></div>
    <div class="stat-mini"><div class="kpi-icon amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $totalLate ?></div><div class="kpi-sub">Late</div></div></div>
</div>

<div class="panel">
    <div class="panel-head"><div><h3>Attendance Records</h3><p class="sub"><?= date("d/m/Y", strtotime($selectedDate)) ?></p></div><a href="sync_attendance.php" class="btn btn-primary">Sync Device</a></div>
    <form method="GET" class="search-bar">
        <input type="date" name="date" value="<?= $selectedDate ?>" style="min-width:150px;flex:0;">
        <input type="text" name="search" placeholder="Search name, ID, department..." value="<?= htmlspecialchars($search) ?>">
        <select name="status"><option value="">All</option><option value="Present" <?= $statusFilter=='Present'?'selected':'' ?>>Present</option><option value="Absent" <?= $statusFilter=='Absent'?'selected':'' ?>>Absent</option><option value="Late" <?= $statusFilter=='Late'?'selected':'' ?>>Late</option></select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="attendance.php" class="btn">Reset</a>
    </form>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>#</th><th>Employee</th><th>Department</th><th>First IN</th><th>Last OUT</th><th>Punches</th><th>Hours</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
<?php $no=1; while ($row = mysqli_fetch_assoc($resultAttendance)):
    $st="Absent"; $bc="badge-absent";
    if ($row['first_in']) { $st="Present"; $bc="badge-present"; if ($row['first_in']>"09:00:00") { $st="Late"; $bc="badge-late"; } }
    if ($statusFilter != "" && $statusFilter != $st) continue;
    $hrs="--"; if ($row['first_in']&&$row['last_out']){$s=strtotime($row['first_in']);$e=strtotime($row['last_out']);if($e>$s)$hrs=gmdate("H:i",$e-$s);}
    $colors=['#6366F1','#8B5CF6','#22D3EE','#34D399','#FB7185','#FBBF24']; $bg=$colors[$row['id']%count($colors)];
?>
                <tr>
                    <td class="mono"><?= $no++ ?></td>
                    <td><div class="emp-cell"><div class="emp-avatar" style="background:<?= $bg ?>"><?= strtoupper(substr($row['first_name'],0,1)) ?></div><div><div class="emp-name"><?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?></div><div class="emp-id">ID: <?= $row['user_id'] ?></div></div></div></td>
                    <td><?= htmlspecialchars($row['department'] ?? '--') ?></td>
                    <td class="mono"><?= $row['first_in'] ?: '--:--' ?></td>
                    <td class="mono"><?= $row['last_out'] ?: '--:--' ?></td>
                    <td class="mono"><?= $row['punches'] ?></td>
                    <td class="mono"><?= $hrs ?></td>
                    <td><span class="badge <?= $bc ?>"><?= $st ?></span></td>
                    <td><div class="row-actions"><a href="view_attandence.php?id=<?= $row['user_id'] ?>" class="row-act"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a><a href="delete_attandence.php?id=<?= $row['user_id'] ?>&date=<?= $selectedDate ?>" class="row-act danger" data-confirm="Delete?"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></a></div></td>
                </tr>
<?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "includes/footer.php"; ?>
