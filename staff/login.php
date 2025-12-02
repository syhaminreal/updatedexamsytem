<?php
session_start();


// Add this at the top of login.php after session_start()

// Check for logout message
if (isset($_GET['msg']) && $_GET['msg'] == 'logged_out') {
    $success = "You have been successfully logged out!";
}

// Then in your HTML, show the message:
if (isset($success) && $success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif;

include 'db_connection.php';

$username  = 'admin';
$password  = 'admin123';
$email     = 'admin@example.com';
$full_name = 'Administrator';
$role      = 'superadmin';



// Generate password hash
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Check if admin already exists
$stmt = $pdo->prepare("SELECT admin_id, password_hash FROM admin_users WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);

if ($stmt->rowCount() > 0) {
    // Admin exists, log in automatically
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['admin_id']   = $admin['admin_id'];
    $_SESSION['username']   = $username;
    $_SESSION['full_name']  = $full_name;
    $_SESSION['role']       = $role;

    // Redirect to index.php
    header('Location: index.php');
    exit();
}

// Insert admin user if not exists
$stmt = $pdo->prepare("INSERT INTO admin_users (username, email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
if ($stmt->execute([$username, $email, $password_hash, $full_name, $role])) {
    // Get inserted admin ID
    $admin_id = $pdo->lastInsertId();

    // Set session
    $_SESSION['admin_id']   = $admin_id;
    $_SESSION['username']   = $username;
    $_SESSION['full_name']  = $full_name;
    $_SESSION['role']       = $role;

    // Redirect to index.php
    header('Location: index.php');
    exit();
} else {
    echo "❌ Error inserting admin user.";
}
?>
