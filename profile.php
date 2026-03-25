<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

$error = '';
$success = '';

// Fetch user profile
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("User not found");
    }
} catch (PDOException $e) {
    die("Error fetching profile: " . $e->getMessage());
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    
    if (empty($full_name)) {
        $error = "Full name is required!";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?");
            $stmt->execute([$full_name, $phone, $user_id]);
            $success = "Profile updated successfully!";
            $user['full_name'] = $full_name;
            $user['phone'] = $phone;
        } catch (PDOException $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
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
                <span class="profile-value"><?php echo htmlspecialchars($user['full_name']); ?></span>
            </div>
            
            <div class="profile-item">
                <span class="profile-label">Username:</span>
                <span class="profile-value"><?php echo htmlspecialchars($user['username']); ?></span>
            </div>
            
            <div class="profile-item">
                <span class="profile-label">Email:</span>
                <span class="profile-value"><?php echo htmlspecialchars($user['email']); ?></span>
            </div>
            
            <div class="profile-item">
                <span class="profile-label">Role:</span>
                <span class="profile-value"><?php echo htmlspecialchars($user['user_type']); ?></span>
            </div>
            
            <div class="profile-item">
                <span class="profile-label">Phone:</span>
                <span class="profile-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></span>
            </div>
            
            <div class="profile-item">
                <span class="profile-label">Last Login:</span>
                <span class="profile-value">
                    <?php echo !empty($user['last_login']) ? date('F j, Y, g:i a', strtotime($user['last_login'])) : 'Never logged in'; ?>
                </span>
            </div>
            
            <div class="profile-item">
                <span class="profile-label">Account Created:</span>
                <span class="profile-value">
                    <?php echo date('F j, Y, g:i a', strtotime($user['created_at'])); ?>
                </span>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="edit_profile.php" class="btn btn-edit">Edit Profile</a>
            <a href="change_password.php" class="btn btn-password">Change Password</a>
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</body>
</html>