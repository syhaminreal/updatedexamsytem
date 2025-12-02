<?php


// Or if db_connection.php is in the staff folder:
include 'db_connection.php';

$username = 'admin';
$password = 'admin123';
$email = 'admin@example.com';
$full_name = 'Administrator';
$role = 'superadmin';

// Generate password hash
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Check if admin already exists
$check = $conn->query("SELECT admin_id FROM admin_users WHERE username = '$username' OR email = '$email'");
if ($check->num_rows > 0) {
    die("Admin user already exists!");
}

$stmt = $conn->prepare("INSERT INTO admin_users (username, email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
$stmt->bind_param("sssss", $username, $email, $password_hash, $full_name, $role);

if ($stmt->execute()) {
    echo "✅ Admin user created successfully!<br>";
    echo "<strong>Username:</strong> $username<br>";
    echo "<strong>Email:</strong> $email<br>";
    echo "<strong>Password:</strong> $password<br>";
    echo "<strong>Role:</strong> $role<br><br>";
    echo "✅ You can now login at: <a href='login.php'>login.php</a>";
} else {
    echo "❌ Error: " . $stmt->error;
}

// Close connection
$conn->close();
?>