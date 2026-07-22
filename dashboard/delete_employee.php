<?php
require __DIR__ . '/bootstrap.php';
require_perm('employees.manage');

// Accept POST (with CSRF) for state-changing deletes.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: employees.php"); exit; }
csrf_verify();

$id = (int) inp($_POST, 'id');
$emp = db_one("SELECT * FROM employees WHERE id=?", [$id]);
if ($emp) {
    // Remove the person's punches, then the employee.
    db_exec("DELETE FROM attendance WHERE user_id=?", [(int)$emp['user_id']]);
    db_exec("DELETE FROM employees WHERE id=?", [$id]);
    audit('employee.delete', 'employees', $id);
}
header("Location: employees.php");
exit;
