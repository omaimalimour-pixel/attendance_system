<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Casablanca');

include "../db.php";

$pageTitle = "Exports";
$currentPage = "exports";

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $date_from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-01");
    $date_to = isset($_GET['to']) ? $_GET['to'] : date("Y-m-d");

    $sql = "SELECT employees.user_id, employees.first_name, employees.last_name,
                   employees.department, employees.position,
                   attendance.date, attendance.time, attendance.type
            FROM attendance
            INNER JOIN employees ON attendance.user_id = employees.user_id
            WHERE attendance.date BETWEEN '$date_from' AND '$date_to'
            ORDER BY attendance.date ASC, attendance.time ASC";

    $result = mysqli_query($conn, $sql);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_' . $date_from . '_to_' . $date_to . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['User ID', 'First Name', 'Last Name', 'Department', 'Position', 'Date', 'Time', 'Type']);

    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

include "includes/header.php";
?>

<div class="row g-3 mb-4">
    <!-- Export Attendance -->
    <div class="col-lg-6">
        <div class="panel h-100">
            <div class="panel-head">
                <div>
                    <h3>Export Attendance</h3>
                    <p class="sub">Download attendance records as CSV</p>
                </div>
            </div>
            <form method="GET" class="panel-body">
                <input type="hidden" name="export" value="csv">
                <div class="form-grid">
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="from" value="<?= date('Y-m-01') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="to" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <button type="submit" class="qa-btn qa-primary" style="margin-top:20px;">
                    <i data-lucide="download"></i> Export CSV
                </button>
            </form>
        </div>
    </div>

    <!-- Export Employees -->
    <div class="col-lg-6">
        <div class="panel h-100">
            <div class="panel-head">
                <div>
                    <h3>Export Employees</h3>
                    <p class="sub">Download employee list as CSV</p>
                </div>
            </div>
            <div class="panel-body">
                <p style="color:var(--muted);margin-bottom:20px;font-size:13px;">
                    Export all registered employees with their department and position information.
                </p>
                <a href="export_employees.php" class="qa-btn qa-success">
                    <i data-lucide="download"></i> Export Employees CSV
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Export History Info -->
<div class="panel">
    <div class="panel-head">
        <div>
            <h3>Export Information</h3>
            <p class="sub">Details about available exports</p>
        </div>
    </div>
    <div class="panel-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;">
            <div style="padding:20px;background:var(--primary-soft);border-radius:12px;">
                <h4 style="font-size:14px;font-weight:700;color:var(--primary);margin-bottom:8px;">Attendance CSV</h4>
                <p style="font-size:12px;color:var(--muted);">Contains: User ID, Name, Department, Position, Date, Time, Punch Type</p>
            </div>
            <div style="padding:20px;background:var(--success-soft);border-radius:12px;">
                <h4 style="font-size:14px;font-weight:700;color:#15803D;margin-bottom:8px;">Employees CSV</h4>
                <p style="font-size:12px;color:var(--muted);">Contains: User ID, First Name, Last Name, Department, Position, Created Date</p>
            </div>
            <div style="padding:20px;background:var(--warning-soft);border-radius:12px;">
                <h4 style="font-size:14px;font-weight:700;color:#B45309;margin-bottom:8px;">Format</h4>
                <p style="font-size:12px;color:var(--muted);">All exports are in CSV format, compatible with Excel, Google Sheets, etc.</p>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
