<?php
session_start();
require_once 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Fetch admin profile
try {
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        die("Admin not found");
    }
} catch (PDOException $e) {
    die("Error fetching profile: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .profile-info {
            margin: 20px 0;
        }
        .profile-item {
            margin: 15px 0;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .profile-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 150px;
        }
        .profile-value {
            color: #333;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-edit {
            background: #28a745;
        }
        .btn-edit:hover {
            background: #1e7e34;
        }
        .btn-password {
            background: #ffc107;
            color: #333;
        }
        .btn-password:hover {
            background: #e0a800;
        }
        .action-buttons {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .status-active {
            color: #28a745;
            font-weight: bold;
        }
        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>User Profile</h1>
        
        <div class="profile-info">
            <div class="profile-item">
                <span class="profile-label">Full Name:</span>
                <span class="profile-value"><?php echo htmlspecialchars($admin['full_name']); ?></span>
            </div>
            
            <div class="profile-item">
                <span class="profile-label">Username:</span>
                <span class="profile-value"><?php echo htmlspecialchars($admin['username']); ?></span>
            </div>
            
            <div class="profile-item">
                <span class="profile-label">Email:</span>
                <span class="profile-value"><?php echo htmlspecialchars($admin['email']); ?></span>
            </div>
            
            <div class="profile-item">
                <span class="profile-label">Role:</span>
                <span class="profile-value"><?php echo htmlspecialchars($admin['role']); ?></span>
            </div>
            
            <div class="profile-item">
                <span class="profile-label">Status:</span>
                <span class="profile-value <?php echo $admin['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
            </div>
            
            <div class="profile-item">
                <span class="profile-label">Last Login:</span>
                <span class="profile-value">
                    <?php echo $admin['last_login'] ? date('F j, Y, g:i a', strtotime($admin['last_login'])) : 'Never logged in'; ?>
                </span>
            </div>
            
            <div class="profile-item">
                <span class="profile-label">Account Created:</span>
                <span class="profile-value">
                    <?php echo date('F j, Y, g:i a', strtotime($admin['created_at'])); ?>
                </span>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="edit_profile.php" class="btn btn-edit">Edit Profile</a>
            <a href="change_password.php" class="btn btn-password">Change Password</a>
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>