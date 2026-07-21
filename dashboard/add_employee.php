<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../db.php";

$pageTitle = "Add Employee";
$currentPage = "employees";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $position = mysqli_real_escape_string($conn, $_POST['position']);

    $check = mysqli_query($conn, "SELECT * FROM employees WHERE user_id='$user_id'");

    if (mysqli_num_rows($check) > 0) {
        $message = "This User ID already exists.";
        $messageType = "danger";
    } else {
        $sql = "INSERT INTO employees (user_id, first_name, last_name, department, position)
                VALUES ('$user_id', '$first_name', '$last_name', '$department', '$position')";

        if (mysqli_query($conn, $sql)) {
            header("Location: employees.php");
            exit();
        } else {
            $message = "Error: " . mysqli_error($conn);
            $messageType = "danger";
        }
    }
}

include "includes/header.php";
?>

<!-- Breadcrumb -->
<div class="section-head">
    <div>
        <a href="employees.php" class="qa-btn">
            <i data-lucide="arrow-left"></i> Back to Employees
        </a>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?>">
    <i data-lucide="alert-circle"></i>
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<div class="panel">
    <div class="panel-head">
        <div>
            <h3>Add New Employee</h3>
            <p class="sub">Fill in the employee information below</p>
        </div>
    </div>

    <form method="POST">
        <div class="form-panel">
            <div class="form-grid">
                <div class="form-group">
                    <label>User ID (Device ID)</label>
                    <input type="number" name="user_id" placeholder="Enter device user ID"
                           value="<?= isset($_POST['user_id']) ? htmlspecialchars($_POST['user_id']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" placeholder="Enter first name"
                           value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="Enter last name"
                           value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" placeholder="e.g. Engineering, HR, Sales"
                           value="<?= isset($_POST['department']) ? htmlspecialchars($_POST['department']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Position</label>
                    <input type="text" name="position" placeholder="e.g. Developer, Manager"
                           value="<?= isset($_POST['position']) ? htmlspecialchars($_POST['position']) : '' ?>" required>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <a href="employees.php" class="qa-btn">Cancel</a>
            <button type="submit" class="qa-btn qa-primary">
                <i data-lucide="user-plus"></i> Add Employee
            </button>
        </div>
    </form>
</div>

<?php include "includes/footer.php"; ?>
