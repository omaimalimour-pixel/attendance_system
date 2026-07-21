<?php
/**
 * Database Configuration — update credentials to match your environment.
 */
date_default_timezone_set('Africa/Casablanca');

$host = "localhost";
$user = "root";
$password = "";
$dbname = "clocking";

mysqli_report(MYSQLI_REPORT_ERROR);
$conn = @mysqli_connect($host, $user, $password, $dbname);

if (!$conn) {
    die("<div style='font-family:Inter,system-ui,sans-serif;max-width:560px;margin:80px auto;padding:32px;border-radius:16px;background:#fff;border:1px solid #FECACA;box-shadow:0 10px 40px rgba(0,0,0,.08);color:#991B1B;'>
        <h2 style='margin:0 0 12px;font-size:20px;'>Database Connection Failed</h2>
        <p style='margin:0 0 10px;color:#7F1D1D;'>Please make sure:</p>
        <ul style='margin:0;padding-left:20px;color:#7F1D1D;line-height:1.8;'>
            <li>MySQL / XAMPP is running</li>
            <li>The <strong>\"$dbname\"</strong> database exists</li>
            <li>Credentials in <code>db.php</code> are correct</li>
        </ul>
        <p style='margin:16px 0 0;font-size:13px;color:#B91C1C;'>" . mysqli_connect_error() . "</p>
    </div>");
}
?>
