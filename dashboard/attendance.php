<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../db.php";

$pageTitle = "Attendance";
$currentPage = "attendance";

include "attendance_data.php";
include "includes/header.php";
?>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-mini">
        <div class="stat-icon ic-blue"><i data-lucide="users"></i></div>
        <div>
            <div class="stat-value"><?= $totalEmployees ?></div>
            <div class="stat-label">Total Employees</div>
        </div>
    </div>
    <div class="stat-mini">
        <div class="stat-icon ic-green"><i data-lucide="check-circle-2"></i></div>
        <div>
            <div class="stat-value"><?= $totalPresent ?></div>
            <div class="stat-label">Present Today</div>
        </div>
    </div>
    <div class="stat-mini">
        <div class="stat-icon ic-red"><i data-lucide="user-x"></i></div>
        <div>
            <div class="stat-value"><?= $totalAbsent ?></div>
            <div class="stat-label">Absent Today</div>
        </div>
    </div>
    <div class="stat-mini">
        <div class="stat-icon ic-amber"><i data-lucide="clock"></i></div>
        <div>
            <div class="stat-value"><?= $totalLate ?></div>
            <div class="stat-label">Late Employees</div>
        </div>
    </div>
</div>

<!-- Attendance Table Panel -->
<div class="panel">
    <div class="panel-head">
        <div>
            <h3>Attendance Records</h3>
            <p class="sub"><?= date("d/m/Y", strtotime($selectedDate)) ?></p>
        </div>
        <div class="filter-bar">
            <a href="sync_attendance.php" class="qa-btn qa-primary">
                <i data-lucide="refresh-cw"></i> Sync Device
            </a>
        </div>
    </div>

    <!-- Search & Filter -->
    <form method="GET" class="search-filter-bar">
        <input type="date" name="date" value="<?= $selectedDate ?>" style="min-width:160px;">
        <input type="text" name="search" placeholder="Search by name, ID, department..."
               value="<?= htmlspecialchars($search) ?>">
        <select name="status">
            <option value="">All Status</option>
            <option value="Present" <?= ($statusFilter == "Present") ? "selected" : "" ?>>Present</option>
            <option value="Absent" <?= ($statusFilter == "Absent") ? "selected" : "" ?>>Absent</option>
            <option value="Late" <?= ($statusFilter == "Late") ? "selected" : "" ?>>Late</option>
        </select>
        <button type="submit" class="qa-btn qa-primary">
            <i data-lucide="search"></i> Filter
        </button>
        <a href="attendance.php" class="qa-btn">
            <i data-lucide="rotate-cw"></i> Reset
        </a>
    </form>

    <!-- Table -->
    <div class="table-scroll">
        <table class="att">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>First IN</th>
                    <th>Last OUT</th>
                    <th>Punches</th>
                    <th>Work Hours</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>

<?php
$no = 1;
while ($row = mysqli_fetch_assoc($resultAttendance)):
    $status = "Absent";
    $badge = "b-absent";

    if ($row['first_in']) {
        $status = "Present";
        $badge = "b-present";
        if ($row['first_in'] > "09:00:00") {
            $status = "Late";
            $badge = "b-late";
        }
    }

    // Apply status filter
    if ($statusFilter != "" && $statusFilter != $status) {
        continue;
    }

    $workHours = "--";
    if ($row['first_in'] && $row['last_out']) {
        $start = strtotime($row['first_in']);
        $end = strtotime($row['last_out']);
        if ($end > $start) {
            $workHours = gmdate("H:i", $end - $start);
        }
    }

    $letter = strtoupper(substr($row['first_name'], 0, 1));
    $colors = ['#2563EB','#7C3AED','#059669','#D97706','#DC2626','#0891B2'];
    $bgColor = $colors[$row['id'] % count($colors)];
?>
<tr>
    <td class="mono"><?= $no++ ?></td>
    <td>
        <div class="emp">
            <div class="emp-av" style="background:<?= $bgColor ?>">
                <?= $letter ?>
            </div>
            <div>
                <div class="emp-name"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                <div class="emp-id">ID: <?= $row['user_id'] ?></div>
            </div>
        </div>
    </td>
    <td><?= htmlspecialchars($row['department'] ?? '--') ?></td>
    <td class="mono"><?= $row['first_in'] ?: '<span class="dash">--:--</span>' ?></td>
    <td class="mono"><?= $row['last_out'] ?: '<span class="dash">--:--</span>' ?></td>
    <td class="mono"><?= $row['punches'] ?></td>
    <td class="mono"><?= $workHours ?></td>
    <td>
        <span class="badge-status <?= $badge ?>">
            <span class="bdot"></span> <?= $status ?>
        </span>
    </td>
    <td>
        <a href="view_attandence.php?id=<?= $row['user_id'] ?>" class="row-action" title="View">
            <i data-lucide="eye"></i>
        </a>
        <a href="edit_attendance.php?id=<?= $row['user_id'] ?>" class="row-action success" title="Edit">
            <i data-lucide="pencil"></i>
        </a>
        <a href="delete_attandence.php?id=<?= $row['user_id'] ?>&date=<?= $selectedDate ?>"
           class="row-action danger" title="Delete"
           data-confirm="Delete attendance records for this employee?">
            <i data-lucide="trash-2"></i>
        </a>
    </td>
</tr>
<?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "includes/footer.php"; ?>
