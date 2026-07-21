<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../db.php";

$pageTitle = "Employees";
$currentPage = "employees";

// Search
$search = "";
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
}

// Query
$sql = "SELECT * FROM employees";
if ($search != "") {
    $sql .= " WHERE user_id LIKE '%$search%'
              OR first_name LIKE '%$search%'
              OR last_name LIKE '%$search%'
              OR department LIKE '%$search%'
              OR position LIKE '%$search%'";
}
$sql .= " ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

// Stats
$total = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM employees"));
$departments = mysqli_num_rows(mysqli_query($conn, "SELECT DISTINCT department FROM employees"));
$newThisMonth = mysqli_num_rows(mysqli_query($conn, "
    SELECT * FROM employees
    WHERE MONTH(created_at) = MONTH(CURDATE())
    AND YEAR(created_at) = YEAR(CURDATE())
"));

include "includes/header.php";
?>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-mini">
        <div class="stat-icon ic-blue"><i data-lucide="users"></i></div>
        <div>
            <div class="stat-value"><?= $total ?></div>
            <div class="stat-label">Total Employees</div>
        </div>
    </div>
    <div class="stat-mini">
        <div class="stat-icon ic-green"><i data-lucide="building-2"></i></div>
        <div>
            <div class="stat-value"><?= $departments ?></div>
            <div class="stat-label">Departments</div>
        </div>
    </div>
    <div class="stat-mini">
        <div class="stat-icon ic-amber"><i data-lucide="user-plus"></i></div>
        <div>
            <div class="stat-value"><?= $newThisMonth ?></div>
            <div class="stat-label">New This Month</div>
        </div>
    </div>
</div>

<!-- Table Panel -->
<div class="panel">
    <div class="panel-head">
        <div>
            <h3>All Employees</h3>
            <p class="sub"><?= $total ?> employees registered</p>
        </div>
        <div class="filter-bar">
            <a href="add_employee.php" class="qa-btn qa-primary">
                <i data-lucide="user-plus"></i> Add Employee
            </a>
        </div>
    </div>

    <!-- Search Bar -->
    <form method="GET" class="search-filter-bar">
        <input type="text" name="search" placeholder="Search by name, ID, department..."
               value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="qa-btn qa-primary">
            <i data-lucide="search"></i> Search
        </button>
        <?php if ($search): ?>
            <a href="employees.php" class="qa-btn">
                <i data-lucide="x"></i> Reset
            </a>
        <?php endif; ?>
    </form>

    <!-- Table -->
    <div class="table-scroll">
        <table class="att">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>User ID</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>

<?php if (mysqli_num_rows($result) > 0): ?>
    <?php while ($row = mysqli_fetch_assoc($result)):
        $letter = strtoupper(substr($row['first_name'], 0, 1));
        $colors = ['#2563EB','#7C3AED','#059669','#D97706','#DC2626','#0891B2'];
        $bgColor = $colors[$row['id'] % count($colors)];
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
        <td class="mono"><?= $row['user_id'] ?></td>
        <td>
            <span class="badge-status b-present">
                <?= htmlspecialchars($row['department'] ?? '---') ?>
            </span>
        </td>
        <td><?= htmlspecialchars($row['position'] ?? '---') ?></td>
        <td class="mono"><?= date("d/m/Y", strtotime($row['created_at'])) ?></td>
        <td>
            <a href="edit_employee.php?id=<?= $row['id'] ?>" class="row-action success" title="Edit">
                <i data-lucide="pencil"></i>
            </a>
            <a href="delete_employee.php?id=<?= $row['id'] ?>" class="row-action danger"
               title="Delete" data-confirm="Are you sure you want to delete this employee?">
                <i data-lucide="trash-2"></i>
            </a>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="6">
            <div class="empty-state">
                <i data-lucide="users"></i>
                <h4>No employees found</h4>
                <p>Try adjusting your search or add a new employee.</p>
            </div>
        </td>
    </tr>
<?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "includes/footer.php"; ?>
