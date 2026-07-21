<?php
include "../db.php";

$result = mysqli_query($conn, "SELECT user_id, first_name, last_name, department, position, created_at FROM employees ORDER BY id ASC");

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="employees_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['User ID', 'First Name', 'Last Name', 'Department', 'Position', 'Created At']);

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
