<?php
if (!isset($selectedDate)) {
    $selectedDate = date("Y-m-d");
}

$sqlAttendanceTable = "
    SELECT employees.user_id, employees.first_name, employees.last_name,
           MIN(attendance.time) AS first_in,
           MAX(attendance.time) AS last_out,
           COUNT(attendance.id) AS punches
    FROM employees
    LEFT JOIN attendance ON employees.user_id = attendance.user_id
         AND attendance.date = '$selectedDate'
    GROUP BY employees.user_id
    ORDER BY employees.first_name ASC
";

$resultAttendanceTable = mysqli_query($conn, $sqlAttendanceTable);
?>

<div class="panel">
    <div class="panel-head">
        <div>
            <h3>Today's Attendance</h3>
            <p class="sub"><?= date("d/m/Y", strtotime($selectedDate)) ?></p>
        </div>
        <a href="attendance.php?date=<?= $selectedDate ?>" class="qa-btn">
            <i data-lucide="external-link"></i> View All
        </a>
    </div>
    <div class="table-scroll">
        <table class="att">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>First IN</th>
                    <th>Last OUT</th>
                    <th>Work Hours</th>
                    <th>Punches</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
<?php while ($row = mysqli_fetch_assoc($resultAttendanceTable)):
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

    $hours = "--";
    if ($row['first_in'] && $row['last_out']) {
        $start = strtotime($row['first_in']);
        $end = strtotime($row['last_out']);
        if ($end > $start) {
            $hours = gmdate("H:i", $end - $start);
        }
    }

    $letter = strtoupper(substr($row['first_name'], 0, 1));
    $colors = ['#2563EB','#7C3AED','#059669','#D97706','#DC2626','#0891B2'];
    $bgColor = $colors[crc32($row['user_id']) % count($colors)];
?>
                <tr>
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
                    <td class="mono"><?= $row['first_in'] ?: '<span class="dash">--:--</span>' ?></td>
                    <td class="mono"><?= $row['last_out'] ?: '<span class="dash">--:--</span>' ?></td>
                    <td class="mono"><?= $hours ?></td>
                    <td class="mono"><?= $row['punches'] ?></td>
                    <td>
                        <span class="badge-status <?= $badge ?>">
                            <span class="bdot"></span> <?= $status ?>
                        </span>
                    </td>
                </tr>
<?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
