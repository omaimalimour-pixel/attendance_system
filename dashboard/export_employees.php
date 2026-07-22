<?php
require __DIR__ . '/bootstrap.php';
require_perm('export');

$rows = db_all(
    "SELECT e.user_id, e.first_name, e.last_name,
            COALESCE(dep.name,'') AS department, COALESCE(e.position,'') AS position,
            COALESCE(e.email,'') AS email, COALESCE(e.phone,'') AS phone,
            e.status, e.created_at
     FROM employees e
     LEFT JOIN departments dep ON dep.id = e.department_id
     ORDER BY e.id ASC"
);

audit('export.employees');
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="employees_' . date('Y-m-d') . '.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['User ID','First Name','Last Name','Department','Position','Email','Phone','Status','Created']);
foreach ($rows as $r) { fputcsv($out, $r); }
fclose($out);
exit;
