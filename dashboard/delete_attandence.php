<?php
require __DIR__ . '/bootstrap.php';
require_perm('attendance.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: attendance.php"); exit; }
csrf_verify();

$userId = (int) inp($_POST, 'user_id');
$date   = inp($_POST, 'date');

if ($userId > 0) {
    if ($date !== '') {
        db_exec("DELETE FROM attendance WHERE user_id=? AND date=?", [$userId, $date]);
        audit('attendance.delete_day', 'attendance', $userId);
        header("Location: attendance.php?date=" . urlencode($date));
        exit;
    }
    db_exec("DELETE FROM attendance WHERE user_id=?", [$userId]);
    audit('attendance.delete_all', 'attendance', $userId);
}
header("Location: attendance.php");
exit;
