<?php
require __DIR__ . '/bootstrap.php';
$pageTitle = "Analytics";
$currentPage = "analytics";

// Monthly presence (last 6 months) — prepared statements
$monthlyData = [];
$monthlyLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date("Y-m", strtotime("-$i months"));
    $monthlyLabels[] = date("M Y", strtotime("-$i months"));
    $monthlyData[] = (int) db_val(
        "SELECT COUNT(DISTINCT user_id) FROM attendance WHERE DATE_FORMAT(date,'%Y-%m') = ?",
        [$month]
    );
}

// Department breakdown via normalized departments table
$deptLabels = [];
$deptData = [];
foreach (db_all(
    "SELECT COALESCE(dep.name,'Unassigned') AS name, COUNT(*) AS total
     FROM employees e LEFT JOIN departments dep ON dep.id = e.department_id
     GROUP BY dep.id ORDER BY total DESC"
) as $row) {
    $deptLabels[] = $row['name'];
    $deptData[]   = (int) $row['total'];
}

// Top attendees
$topAttendees = db_all(
    "SELECT e.first_name, e.last_name, dep.name AS department,
            COUNT(DISTINCT a.date) AS days_present
     FROM employees e
     LEFT JOIN departments dep ON dep.id = e.department_id
     LEFT JOIN attendance a ON a.user_id = e.user_id
     GROUP BY e.user_id ORDER BY days_present DESC LIMIT 10"
);

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
<?php $no = 1; foreach ($topAttendees as $row): ?>
                <tr>
                    <td class="mono"><?= $no++ ?></td>
                    <td>
                        <div class="emp">
                            <div class="emp-av" style="background:linear-gradient(135deg,#7c6aff,#38bdf8)">
                                <?= e(strtoupper(substr($row['first_name'], 0, 1))) ?>
                            </div>
                            <div>
                                <div class="emp-name"><?= e($row['first_name'] . ' ' . $row['last_name']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= e($row['department'] ?: '—') ?></td>
                    <td class="mono"><?= (int)$row['days_present'] ?> days</td>
                </tr>
<?php endforeach; ?>
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
                backgroundColor: "rgba(124,106,255,0.18)",
                borderColor: "#7c6aff",
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: "rgba(255,255,255,.05)" }, ticks:{color:"#8891ad"} },
                x: { grid: { display: false }, ticks:{color:"#8891ad"} }
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
                backgroundColor: ["#7c6aff","#38bdf8","#2dd4a8","#fbbf24","#f87171","#e879f9","#a78bfa"],
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
