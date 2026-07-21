<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../db.php";

$pageTitle = "Edit Employee";
$currentPage = "employees";

if (!isset($_GET['id'])) {
    header("Location: employees.php");
    exit;
}

$id = (int)$_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM employees WHERE id=$id");

if (mysqli_num_rows($result) == 0) {
    header("Location: employees.php");
    exit;
}

$employee = mysqli_fetch_assoc($result);

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $position = mysqli_real_escape_string($conn, $_POST['position']);

    $sql = "UPDATE employees SET
            first_name='$first_name',
            last_name='$last_name',
            department='$department',
            position='$position'
            WHERE id=$id";

    if (mysqli_query($conn, $sql)) {
        $message = "Employee updated successfully.";
        $messageType = "success";
        // Refresh data
        $result = mysqli_query($conn, "SELECT * FROM employees WHERE id=$id");
        $employee = mysqli_fetch_assoc($result);
    } else {
        $message = "Error: " . mysqli_error($conn);
        $messageType = "danger";
    }
}

include "includes/header.php";
?>

<div class="section-head">
    <div>
        <a href="employees.php" class="qa-btn">
            <i data-lucide="arrow-left"></i> Back to Employees
        </a>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?>">
    <i data-lucide="<?= $messageType == 'success' ? 'check-circle-2' : 'alert-circle' ?>"></i>
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<div class="panel">
    <div class="panel-head">
        <div>
            <h3>Edit Employee</h3>
            <p class="sub">Update information for <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></p>
        </div>
    </div>

    <form method="POST">
        <div class="form-panel">
            <div class="form-grid">
                <div class="form-group">
                    <label>User ID (Device ID)</label>
                    <input type="number" value="<?= $employee['user_id'] ?>" readonly>
                </div>
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name"
                           value="<?= htmlspecialchars($employee['first_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name"
                           value="<?= htmlspecialchars($employee['last_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department"
                           value="<?= htmlspecialchars($employee['department']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Position</label>
                    <input type="text" name="position"
                           value="<?= htmlspecialchars($employee['position']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Created At</label>
                    <input type="text" value="<?= date('d/m/Y H:i', strtotime($employee['created_at'])) ?>" readonly>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <a href="employees.php" class="qa-btn">Cancel</a>
            <button type="submit" class="qa-btn qa-primary">
                <i data-lucide="save"></i> Save Changes
            </button>
        </div>
    </form>
</div>

<?php include "includes/footer.php"; ?>
