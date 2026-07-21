<?php
/**
 * Database Configuration
 * Update these credentials to match your environment.
 */

date_default_timezone_set('Africa/Casablanca');

$host = "localhost";
$user = "root";
$password = "";
$dbname = "clocking";

// Disable mysqli exception mode so we can handle errors gracefully
mysqli_report(MYSQLI_REPORT_ERROR);

$conn = @mysqli_connect($host, $user, $password, $dbname);

if (!$conn) {
    die("<div style='font-family:Inter,sans-serif;max-width:600px;margin:60px auto;padding:30px;border-radius:16px;background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;'>
        <h2 style='margin:0 0 12px;'>Database Connection Failed</h2>
        <p style='margin:0 0 10px;'>Cannot connect to MySQL. Please check:</p>
        <ul style='margin:0;padding-left:20px;'>
            <li>MySQL/XAMPP is running</li>
            <li>Database <strong>\"$dbname\"</strong> exists</li>
            <li>Credentials in <code>db.php</code> are correct</li>
        </ul>
        <p style='margin:16px 0 0;font-size:13px;color:#7F1D1D;'>Error: " . mysqli_connect_error() . "</p>
    </div>");
}
?>
