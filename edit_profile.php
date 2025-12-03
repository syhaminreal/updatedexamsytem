<?php
session_start();
require_once 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];
$error = '';
$success = '';

// Fetch current admin data
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    
    // Validation
    if (empty($full_name) || empty($email)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        try {
            // Check if email already exists (excluding current admin)
            $stmt = $pdo->prepare("SELECT admin_id FROM admin_users WHERE email = ? AND admin_id != ?");
            $stmt->execute([$email, $admin_id]);
            if ($stmt->fetch()) {
                $error = 'Email already exists';
            } else {
                // Update profile
                $stmt = $pdo->prepare("UPDATE admin_users SET full_name = ?, email = ? WHERE admin_id = ?");
                if ($stmt->execute([$full_name, $email, $admin_id])) {
                    $success = 'Profile updated successfully!';
                    // Refresh admin data
                    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE admin_id = ?");
                    $stmt->execute([$admin_id]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = 'Failed to update profile';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
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
        .form-group {
            margin: 20px 0;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .btn {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-cancel {
            background: #6c757d;
            text-decoration: none;
            display: inline-block;
            padding: 10px 20px;
            margin-left: 10px;
        }
        .btn-cancel:hover {
            background: #545b62;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .readonly-field {
            background: #e9ecef;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Profile</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" value="<?php echo htmlspecialchars($admin['username']); ?>" class="readonly-field" readonly>
                <small style="color: #666;">Username cannot be changed</small>
            </div>
            
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Role:</label>
                <input type="text" value="<?php echo htmlspecialchars($admin['role']); ?>" class="readonly-field" readonly>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn">Update Profile</button>
                <a href="profile.php" class="btn btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>