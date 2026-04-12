<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        try {
            // Fetch current password hash
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $error = 'User not found';
            } elseif (!password_verify($current_password, $user['password_hash'])) {
                $error = 'Current password is incorrect';
            } else {
                // Hash new password
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                if ($stmt->execute([$new_password_hash, $user_id])) {
                    $success = 'Password changed successfully!';
                    
                    // Clear form
                    $_POST = array();
                } else {
                    $error = 'Failed to change password';
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
    <title>Change Password - ExamPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border: #334155;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            margin: -2rem -2rem 2rem -2rem;
            border-radius: 0 0 12px 12px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--accent);
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .breadcrumb a {
            color: var(--accent);
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            color: var(--accent-hover);
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .header-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .header-section h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .header-section p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }
        
        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            border-color: var(--accent);
        }
        
        .card-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }
        
        .password-requirements {
            background: rgba(59, 130, 246, 0.05);
            border: 1px solid rgba(59, 130, 246, 0.2);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .password-requirements strong {
            color: var(--accent);
            display: block;
            margin-bottom: 0.75rem;
        }
        
        .password-requirements ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .password-requirements li {
            font-size: 0.9rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .password-requirements li:before {
            content: "✓";
            color: var(--success);
            font-weight: 700;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.95rem;
        }
        
        label .required {
            color: var(--danger);
        }
        
        input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        
        input[type="password"]:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .helper-text {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }
        
        .btn {
            flex: 1;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: 'Inter', sans-serif;
        }
        
        .btn-primary {
            background: var(--accent);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
            text-decoration: none;
        }
        
        .btn-secondary:hover {
            background: rgba(59, 130, 246, 0.05);
            border-color: var(--accent);
            color: var(--accent);
        }
        
        .divider {
            text-align: center;
            margin: 2rem 0;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        
        .links-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }
        
        .links-group a {
            flex: 1;
            text-align: center;
            padding: 0.75rem;
            color: var(--accent);
            text-decoration: none;
            font-size: 0.9rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .links-group a:hover {
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-hover);
        }
        
        @media (max-width: 640px) {
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo">
            <i class="fas fa-book-open"></i> ExamPro
        </div>
        <div class="breadcrumb">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <span>/</span>
            <span>Change Password</span>
        </div>
    </div>
    
    <div class="container">
        <div class="header-section">
            <h1><i class="fas fa-lock"></i> Change Password</h1>
            <p>Update your account password to keep your account secure</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="password-requirements">
                <strong><i class="fas fa-shield-alt"></i> Password Requirements</strong>
                <ul>
                    <li>At least 6 characters long</li>
                    <li>Should be different from your current password</li>
                    <li>Use a mix of letters and numbers for better security</li>
                </ul>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password">Current Password <span class="required">*</span></label>
                    <input type="password" id="current_password" name="current_password" required placeholder="Enter your current password">
                    <div class="helper-text">We need your current password to verify your identity</div>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password <span class="required">*</span></label>
                    <input type="password" id="new_password" name="new_password" required placeholder="Enter your new password">
                    <div class="helper-text">Make it strong and unique</div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your new password">
                    <div class="helper-text">Must match the password above</div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Password
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
            
            <div class="links-group">
                <a href="edit_profile.php">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </a>
                <a href="edit_profile.php">
                    <i class="fas fa-user-edit"></i> View Profile
                </a>
            </div>
        </div>
    </div>
</body>
</html>