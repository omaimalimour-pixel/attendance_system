<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../db.php";

$pageTitle = "User Management";
$currentPage = "users";

$message = "";
$messageType = "";

// Handle add user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    $check = mysqli_query($conn, "SELECT * FROM admin_users WHERE username='$username'");
    if (mysqli_num_rows($check) > 0) {
        $message = "Username already exists.";
        $messageType = "danger";
    } else {
        $sql = "INSERT INTO admin_users (username, password, role) VALUES ('$username', '$password', '$role')";
        if (mysqli_query($conn, $sql)) {
            $message = "User created successfully.";
            $messageType = "success";
        } else {
            $message = "Error: " . mysqli_error($conn);
            $messageType = "danger";
        }
    }
}

// Handle delete user
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM admin_users WHERE id=$delId");
    header("Location: users.php");
    exit;
}

// Get all users
$users = mysqli_query($conn, "SELECT * FROM admin_users ORDER BY id DESC");

include "includes/header.php";
?>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?>">
    <i data-lucide="<?= $messageType == 'success' ? 'check-circle-2' : 'alert-circle' ?>"></i>
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <!-- Add User Form -->
    <div class="col-lg-5">
        <div class="panel h-100">
            <div class="panel-head">
                <div>
                    <h3>Add New User</h3>
                    <p class="sub">Create a system administrator account</p>
                </div>
            </div>
            <form method="POST">
                <div class="panel-body">
                    <div class="form-group" style="margin-bottom:16px;">
                        <label>Username</label>
                        <input type="text" name="username" placeholder="Enter username" required>
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Enter password" required>
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="Administrator">Administrator</option>
                            <option value="Manager">Manager</option>
                            <option value="Viewer">Viewer</option>
                        </select>
                    </div>
                    <button type="submit" name="add_user" class="qa-btn qa-primary" style="width:100%;justify-content:center;">
                        <i data-lucide="user-plus"></i> Create User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Users List -->
    <div class="col-lg-7">
        <div class="panel h-100">
            <div class="panel-head">
                <div>
                    <h3>System Users</h3>
                    <p class="sub">Manage admin accounts</p>
                </div>
            </div>
            <div class="table-scroll">
                <table class="att">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>

<?php if ($users && mysqli_num_rows($users) > 0): ?>
    <?php while ($user = mysqli_fetch_assoc($users)): ?>
    <tr>
        <td>
            <div class="emp">
                <div class="emp-av" style="background:#7C3AED">
                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                </div>
                <div>
                    <div class="emp-name"><?= htmlspecialchars($user['username']) ?></div>
                    <div class="emp-id">ID: <?= $user['id'] ?></div>
                </div>
            </div>
        </td>
        <td>
            <span class="badge-status b-present">
                <?= htmlspecialchars($user['role']) ?>
            </span>
        </td>
        <td class="mono"><?= isset($user['created_at']) ? date("d/m/Y", strtotime($user['created_at'])) : '--' ?></td>
        <td>
            <a href="users.php?delete=<?= $user['id'] ?>" class="row-action danger"
               data-confirm="Are you sure you want to delete this user?">
                <i data-lucide="trash-2"></i>
            </a>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="4">
            <div class="empty-state">
                <i data-lucide="shield"></i>
                <h4>No users found</h4>
                <p>Create the first administrator account.</p>
            </div>
        </td>
    </tr>
<?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
