<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../db.php";

$pageTitle = "Attendance Details";
$currentPage = "attendance";

if (!isset($_GET['id'])) {
    header("Location: attendance.php");
    exit;
}

$user_id = (int)$_GET['id'];

// Get employee info
$resultEmployee = mysqli_query($conn, "SELECT * FROM employees WHERE user_id='$user_id'");
if (mysqli_num_rows($resultEmployee) == 0) {
    header("Location: attendance.php");
    exit;
}
$employee = mysqli_fetch_assoc($resultEmployee);

// Get attendance records
$resultAttendance = mysqli_query($conn, "
    SELECT * FROM attendance
    WHERE user_id='$user_id'
    ORDER BY date DESC, time DESC
");

// Stats for this employee
$totalDays = mysqli_num_rows(mysqli_query($conn, "
    SELECT DISTINCT date FROM attendance WHERE user_id='$user_id'
"));
$totalPunches = mysqli_num_rows(mysqli_query($conn, "
    SELECT * FROM attendance WHERE user_id='$user_id'
"));

include "includes/header.php";
?>

<div class="section-head">
    <div>
        <a href="attendance.php" class="qa-btn">
            <i data-lucide="arrow-left"></i> Back to Attendance
        </a>
    </div>
    <div class="filter-bar">
        <a href="edit_attendance.php?id=<?= $user_id ?>" class="qa-btn qa-primary">
            <i data-lucide="pencil"></i> Edit Records
        </a>
        <a href="delete_attandence.php?id=<?= $user_id ?>"
           class="qa-btn qa-danger"
           data-confirm="Delete all attendance records for this employee?">
            <i data-lucide="trash-2"></i> Delete All
        </a>
    </div>
</div>

<!-- Employee Info Card -->
<div class="panel" style="margin-bottom:20px;">
    <div class="panel-body">
        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
            <div class="avatar" style="width:60px;height:60px;font-size:22px;border-radius:16px;">
                <?= strtoupper(substr($employee['first_name'], 0, 1)) ?>
            </div>
            <div>
                <h3 style="margin:0;font-size:20px;font-weight:700;">
                    <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?>
                </h3>
                <p style="margin:4px 0 0;color:var(--muted);font-size:13px;">
                    User ID: <strong><?= $employee['user_id'] ?></strong> &middot;
                    Department: <strong><?= htmlspecialchars($employee['department']) ?></strong> &middot;
                    Position: <strong><?= htmlspecialchars($employee['position']) ?></strong>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-mini">
        <div class="stat-icon ic-blue"><i data-lucide="calendar"></i></div>
        <div>
            <div class="stat-value"><?= $totalDays ?></div>
            <div class="stat-label">Days Attended</div>
        </div>
    </div>
    <div class="stat-mini">
        <div class="stat-icon ic-green"><i data-lucide="clock"></i></div>
        <div>
            <div class="stat-value"><?= $totalPunches ?></div>
            <div class="stat-label">Total Punches</div>
        </div>
    </div>
</div>

<!-- Attendance History -->
<div class="panel">
    <div class="panel-head">
        <div>
            <h3>Attendance History</h3>
            <p class="sub">All recorded punches</p>
        </div>
    </div>
    <div class="table-scroll">
        <table class="att">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Type</th>
                    <th>Device</th>
                </tr>
            </thead>
            <tbody>
<?php
$no = 1;
if (mysqli_num_rows($resultAttendance) > 0):
    while ($row = mysqli_fetch_assoc($resultAttendance)):
        $typeClass = (strtolower($row['type']) == 'in' || strtolower($row['type']) == 'check in')
            ? 'b-present' : 'b-absent';
?>
                <tr>
                    <td class="mono"><?= $no++ ?></td>
                    <td class="mono"><?= date("d/m/Y", strtotime($row['date'])) ?></td>
                    <td class="mono"><?= $row['time'] ?></td>
                    <td>
                        <span class="badge-status <?= $typeClass ?>">
                            <span class="bdot"></span>
                            <?= htmlspecialchars(ucfirst($row['type'])) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($row['device_id'] ?? 'ZKTeco') ?></td>
                </tr>
<?php
    endwhile;
else:
?>
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i data-lucide="calendar-x"></i>
                            <h4>No attendance records</h4>
                            <p>No punches have been recorded for this employee.</p>
                        </div>
                    </td>
                </tr>
<?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "includes/footer.php"; ?>
