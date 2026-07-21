<?php
/**
 * Database Setup Script
 * Run this once to create necessary tables and default admin user.
 */

include "db.php";

echo "<h2>Setting up database...</h2>";

// Create admin_users table
$sql = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'Administrator',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "<p>✅ admin_users table created.</p>";
} else {
    echo "<p>❌ Error: " . mysqli_error($conn) . "</p>";
}

// Create employees table (if not exists)
$sql = "CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    position VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "<p>✅ employees table created.</p>";
} else {
    echo "<p>❌ Error: " . mysqli_error($conn) . "</p>";
}

// Create attendance table (if not exists)
$sql = "CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    type VARCHAR(20) DEFAULT 'IN',
    device_id VARCHAR(100) DEFAULT 'ZKTeco',
    data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "<p>✅ attendance table created.</p>";
} else {
    echo "<p>❌ Error: " . mysqli_error($conn) . "</p>";
}

// Create default admin user (admin / admin123)
$defaultUsername = "admin";
$defaultPassword = password_hash("admin123", PASSWORD_DEFAULT);

$check = mysqli_query($conn, "SELECT * FROM admin_users WHERE username='$defaultUsername'");
if (mysqli_num_rows($check) == 0) {
    $sql = "INSERT INTO admin_users (username, password, role) VALUES ('$defaultUsername', '$defaultPassword', 'Administrator')";
    if (mysqli_query($conn, $sql)) {
        echo "<p>✅ Default admin user created (username: <strong>admin</strong>, password: <strong>admin123</strong>)</p>";
    } else {
        echo "<p>❌ Error: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>ℹ️ Admin user already exists.</p>";
}

echo "<br><p><strong>Setup complete!</strong></p>";
echo "<p><a href='login.php'>Go to Login Page →</a></p>";
?>
