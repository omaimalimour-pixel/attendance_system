<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Casablanca');

include "../db.php";
$pageTitle = "Employees";
$currentPage = "employees";

$search = "";
if (isset($_GET['search']) && !empty($_GET['search'])) $search = mysqli_real_escape_string($conn, $_GET['search']);
$sql = "SELECT * FROM employees";
if ($search != "") $sql .= " WHERE user_id LIKE '%$search%' OR first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR department LIKE '%$search%' OR position LIKE '%$search%'";
$sql .= " ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

$total = 0; $departments = 0; $newMonth = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM employees"); if ($r) $total = mysqli_fetch_assoc($r)['c'];
$r = mysqli_query($conn, "SELECT COUNT(DISTINCT department) AS c FROM employees"); if ($r) $departments = mysqli_fetch_assoc($r)['c'];
$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM employees WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())"); if ($r) $newMonth = mysqli_fetch_assoc($r)['c'];

include "includes/header.php";
?>

<div class="stats-row">
    <div class="stat-mini"><div class="kpi-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $total ?></div><div class="kpi-sub">Total Employees</div></div></div>
    <div class="stat-mini"><div class="kpi-icon violet"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $departments ?></div><div class="kpi-sub">Departments</div></div></div>
    <div class="stat-mini"><div class="kpi-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></div><div><div class="kpi-value" style="font-size:22px"><?= $newMonth ?></div><div class="kpi-sub">New This Month</div></div></div>
</div>

<div class="panel">
    <div class="panel-head"><div><h3>All Employees</h3><p class="sub"><?= $total ?> registered</p></div><a href="add_employee.php" class="btn btn-primary">+ Add Employee</a></div>
    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search by name, ID, department..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search): ?><a href="employees.php" class="btn">Reset</a><?php endif; ?>
    </form>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Employee</th><th>User ID</th><th>Department</th><th>Position</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
<?php if ($result && mysqli_num_rows($result) > 0): while ($row = mysqli_fetch_assoc($result)):
    $colors = ['#6366F1','#8B5CF6','#22D3EE','#34D399','#FB7185','#FBBF24'];
    $bg = $colors[$row['id'] % count($colors)];
?>
                <tr>
                    <td><div class="emp-cell"><div class="emp-avatar" style="background:<?= $bg ?>"><?= strtoupper(substr($row['first_name'],0,1)) ?></div><div><div class="emp-name"><?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?></div><div class="emp-id">ID: <?= $row['user_id'] ?></div></div></div></td>
                    <td class="mono"><?= $row['user_id'] ?></td>
                    <td><span class="badge badge-dept"><?= htmlspecialchars($row['department'] ?? '—') ?></span></td>
                    <td><?= htmlspecialchars($row['position'] ?? '—') ?></td>
                    <td class="mono"><?= date("d/m/Y", strtotime($row['created_at'])) ?></td>
                    <td><div class="row-actions"><a href="edit_employee.php?id=<?= $row['id'] ?>" class="row-act success"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a><a href="delete_employee.php?id=<?= $row['id'] ?>" class="row-act danger" data-confirm="Delete this employee?"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></a></div></td>
                </tr>
<?php endwhile; else: ?>
                <tr><td colspan="6"><div class="empty-state"><h4>No employees found</h4><p>Try adjusting your search or add a new employee.</p></div></td></tr>
<?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "includes/footer.php"; ?>
