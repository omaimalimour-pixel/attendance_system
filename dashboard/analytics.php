<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../db.php";

$pageTitle = "Analytics";
$currentPage = "analytics";

// Get monthly data
$monthlyData = [];
$monthlyLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date("Y-m", strtotime("-$i months"));
    $monthlyLabels[] = date("M Y", strtotime("-$i months"));

    $res = mysqli_query($conn, "
        SELECT COUNT(DISTINCT user_id) AS present,
               COUNT(DISTINCT date) AS days
        FROM attendance
        WHERE DATE_FORMAT(date, '%Y-%m') = '$month'
    ");
    $r = mysqli_fetch_assoc($res);
    $monthlyData[] = (int)$r['present'];
}

// Department breakdown
$deptResult = mysqli_query($conn, "
    SELECT department, COUNT(*) AS total
    FROM employees
    GROUP BY department
    ORDER BY total DESC
");
$deptLabels = [];
$deptData = [];
while ($row = mysqli_fetch_assoc($deptResult)) {
    $deptLabels[] = $row['department'] ?: 'Unknown';
    $deptData[] = (int)$row['total'];
}

// Top attendees
$topAttendees = mysqli_query($conn, "
    SELECT employees.first_name, employees.last_name, employees.department,
           COUNT(DISTINCT attendance.date) AS days_present
    FROM employees
    LEFT JOIN attendance ON employees.user_id = attendance.user_id
    GROUP BY employees.user_id
    ORDER BY days_present DESC
    LIMIT 10
");

include "includes/header.php";
?>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="panel h-100">
            <div class="panel-head">
                <div>
                    <h3>Monthly Attendance Overview</h3>
                    <p class="sub">Unique employees present per month (last 6 months)</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="chart-box">
                    <canvas id="analyticsMonthly"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel h-100">
            <div class="panel-head">
                <div>
                    <h3>Department Distribution</h3>
                    <p class="sub">Employees per department</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="chart-box-sm">
                    <canvas id="analyticsDept"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Attendees Table -->
<div class="panel">
    <div class="panel-head">
        <div>
            <h3>Top Attendees</h3>
            <p class="sub">Employees with most attendance days</p>
        </div>
    </div>
    <div class="table-scroll">
        <table class="att">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Days Present</th>
                </tr>
            </thead>
            <tbody>
<?php $no = 1; while ($row = mysqli_fetch_assoc($topAttendees)): ?>
                <tr>
                    <td class="mono"><?= $no++ ?></td>
                    <td>
                        <div class="emp">
                            <div class="emp-av" style="background:#2563EB">
                                <?= strtoupper(substr($row['first_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="emp-name"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($row['department'] ?? '--') ?></td>
                    <td class="mono"><?= $row['days_present'] ?> days</td>
                </tr>
<?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
window.analyticsMonthlyLabels = <?= json_encode($monthlyLabels) ?>;
window.analyticsMonthlyData = <?= json_encode($monthlyData) ?>;
window.deptLabels = <?= json_encode($deptLabels) ?>;
window.deptData = <?= json_encode($deptData) ?>;
</script>

<script>
// Monthly Chart
const mc = document.getElementById("analyticsMonthly");
if (mc) {
    new Chart(mc, {
        type: "bar",
        data: {
            labels: window.analyticsMonthlyLabels,
            datasets: [{
                label: "Employees Present",
                data: window.analyticsMonthlyData,
                backgroundColor: "rgba(37,99,235,0.15)",
                borderColor: "#2563EB",
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: "#F1F5F9" } },
                x: { grid: { display: false } }
            }
        }
    });
}
// Dept Chart
const dc = document.getElementById("analyticsDept");
if (dc) {
    new Chart(dc, {
        type: "doughnut",
        data: {
            labels: window.deptLabels,
            datasets: [{
                data: window.deptData,
                backgroundColor: ["#2563EB","#7C3AED","#059669","#D97706","#DC2626","#0891B2","#EC4899"],
                borderWidth: 0, borderRadius: 4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: "60%",
            plugins: { legend: { position: "bottom", labels: { boxWidth: 12, padding: 12 } } }
        }
    });
}
</script>

<?php include "includes/footer.php"; ?>
