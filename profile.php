<?php
// profile.php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: logout.php");
    exit();
}

// Fetch user statistics
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_exams,
        AVG(percentage) as avg_score,
        MAX(percentage) as highest_score,
        SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) as passed_exams
    FROM exam_results 
    // Fetch user statistics
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_exams,
        AVG(percentage) as avg_score,
        MAX(percentage) as highest_score,
        SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) as passed_exams
    FROM exam_results 
    WHERE user_id = ?
");

// Add error checking
if ($stats_stmt === false) {
    // Log the error for debugging
    error_log("Prepare failed: " . $conn->error);
    
    // Set default stats
    $stats = [
        'total_exams' => 0,
        'avg_score' => 0,
        'highest_score' => 0,
        'passed_exams' => 0
    ];
} else {
    $stats_stmt->bind_param("i", $user_id);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();

    if (!$stats) {
        $stats = [
            'total_exams' => 0,
            'avg_score' => 0,
            'highest_score' => 0,
            'passed_exams' => 0
        ];
    }
    
    // Close statement
    $stats_stmt->close();
}
        'highest_score' => 0,
        'passed_exams' => 0
    ];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        
        // Validation
        if (empty($full_name) || empty($email)) {
            $error = "Full name and email are required!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format!";
        } else {
            // Check if email is already taken by another user
            $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $check_email->bind_param("si", $email, $user_id);
            $check_email->execute();
            
            if ($check_email->get_result()->num_rows > 0) {
                $error = "Email already registered by another user!";
            } else {
                // Update profile
                $update_stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW() WHERE user_id = ?");
                $update_stmt->bind_param("ssssi", $full_name, $email, $phone, $address, $user_id);
                
                if ($update_stmt->execute()) {
                    // Update session variables
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['user_email'] = $email;
                    
                    $success = "Profile updated successfully!";
                    // Refresh user data
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                } else {
                    $error = "Failed to update profile: " . $update_stmt->error;
                }
            }
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = "All password fields are required!";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match!";
        } elseif (strlen($new_password) < 6) {
            $error = "New password must be at least 6 characters!";
        } else {
            // Verify current password
            if (password_verify($current_password, $user['password_hash'])) {
                // Update password
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_pass_stmt = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?");
                $update_pass_stmt->bind_param("si", $new_password_hash, $user_id);
                
                if ($update_pass_stmt->execute()) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Failed to change password: " . $update_pass_stmt->error;
                }
            } else {
                $error = "Current password is incorrect!";
            }
        }
    }
}

// Calculate time spent on exams
$time_stmt = $conn->prepare("
    SELECT COALESCE(SUM(time_taken), 0) as total_time 
    FROM exam_results 
    WHERE user_id = ?
");
$time_stmt->bind_param("i", $user_id);
$time_stmt->execute();
$time_result = $time_stmt->get_result();
$time_data = $time_result->fetch_assoc();
$total_time_minutes = $time_data['total_time'] ?? 0;

// Format total time
$total_time_hours = floor($total_time_minutes / 60);
$total_time_mins = $total_time_minutes % 60;
$total_time_formatted = $total_time_hours > 0 ? 
    $total_time_hours . "h " . $total_time_mins . "m" : 
    $total_time_mins . "m";

// Get global rank (simplified - you might want a more complex ranking system)
$rank_stmt = $conn->prepare("
    SELECT COUNT(DISTINCT user_id) + 1 as rank
    FROM (
        SELECT user_id, AVG(percentage) as avg_score
        FROM exam_results 
        GROUP BY user_id
        HAVING AVG(percentage) > (
            SELECT AVG(percentage) 
            FROM exam_results 
            WHERE user_id = ?
        )
    ) as higher_scores
");
$rank_stmt->bind_param("i", $user_id);
$rank_stmt->execute();
$rank_result = $rank_stmt->get_result();
$rank_data = $rank_result->fetch_assoc();
$global_rank = $rank_data['rank'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en" class="dark-theme">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ExamPro</title>
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
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--accent);
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .nav-link {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .nav-link:hover {
            color: var(--accent);
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            color: white;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        
        .btn-secondary:hover {
            background: var(--bg-secondary);
            border-color: var(--accent);
            color: var(--accent);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .profile-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--text-primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .profile-header p {
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .profile-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }
        
        .profile-sidebar {
            background: var(--bg-secondary);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid var(--border);
            height: fit-content;
        }
        
        .avatar-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            font-weight: 700;
            border: 4px solid var(--border);
        }
        
        .avatar-change {
            color: var(--accent);
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .user-info-sidebar {
            margin-top: 1.5rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
            color: var(--text-secondary);
        }
        
        .info-item i {
            width: 20px;
            color: var(--accent);
        }
        
        .profile-content {
            background: var(--bg-secondary);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid var(--border);
        }
        
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        input, textarea, select {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        .readonly-field {
            background: rgba(15, 23, 42, 0.3);
            border: 1px solid var(--border);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            color: var(--text-secondary);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .stat-card {
            background: rgba(15, 23, 42, 0.6);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            border: 1px solid var(--border);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .password-form {
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
        }
        
        .password-input-group {
            position: relative;
        }
        
        @media (max-width: 992px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .navbar {
                padding: 1rem;
            }
            
            .nav-links {
                gap: 1rem;
            }
            
            .profile-header h1 {
                font-size: 2rem;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
        
        .tab-navigation {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.5rem;
        }
        
        .tab-btn {
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            background: var(--accent);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <a href="dashboard.php" class="logo">
            <i class="fas fa-graduation-cap"></i>
            <span>ExamPro</span>
        </a>
        
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="results.php" class="nav-link">
                <i class="fas fa-chart-bar"></i> Results
            </a>
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user"></i> Profile
            </a>
            <a href="logout.php" class="btn btn-secondary">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container">
        <div class="profile-header">
            <h1>My Profile</h1>
            <p>Manage your personal information and account settings</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-container">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <div class="avatar-container">
                    <div class="avatar">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                    <p style="color: var(--accent); margin-bottom: 0.5rem;">
                        <i class="fas fa-user-tag"></i> 
                        <?php echo ucfirst($user['user_type']); ?>
                    </p>
                </div>
                
                <div class="user-info-sidebar">
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>
                            Last Login: 
                            <?php 
                            if ($user['last_login']) {
                                echo date('M d, Y H:i', strtotime($user['last_login']));
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </span>
                    </div>
                    <?php if ($user['phone']): ?>
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <span><?php echo htmlspecialchars($user['phone']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                    <a href="results.php" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                        <i class="fas fa-chart-bar"></i> View Results
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary" style="width: 100%;">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="profile-content">
                <!-- Tab Navigation -->
                <div class="tab-navigation">
                    <button class="tab-btn active" onclick="showTab('personal')">
                        <i class="fas fa-user-edit"></i> Personal Info
                    </button>
                    <button class="tab-btn" onclick="showTab('password')">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                    <button class="tab-btn" onclick="showTab('stats')">
                        <i class="fas fa-chart-line"></i> Statistics
                    </button>
                </div>
                
                <!-- Personal Information Tab -->
                <div id="personal-tab" class="tab-content active">
                    <h2 class="section-title">
                        <i class="fas fa-user-edit"></i> Personal Information
                    </h2>
                    
                    <form method="POST" action="">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="full_name">Full Name *</label>
                                <input type="text" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="username">Username</label>
                                <div class="readonly-field">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </div>
                                <small style="color: var(--text-secondary); margin-top: 0.5rem; display: block;">
                                    Username cannot be changed
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label for="user_type">Account Type</label>
                                <div class="readonly-field">
                                    <?php echo ucfirst($user['user_type']); ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="address">Address</label>
                                <textarea id="address" name="address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Change Password Tab -->
                <div id="password-tab" class="tab-content">
                    <h2 class="section-title">
                        <i class="fas fa-key"></i> Change Password
                    </h2>
                    
                    <form method="POST" action="" class="password-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="current_password">Current Password *</label>
                                <div class="password-input-group">
                                    <input type="password" id="current_password" name="current_password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password *</label>
                                <div class="password-input-group">
                                    <input type="password" id="new_password" name="new_password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small style="color: var(--text-secondary); margin-top: 0.5rem; display: block;">
                                    Minimum 6 characters
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password *</label>
                                <div class="password-input-group">
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="showTab('personal')">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Statistics Tab -->
                <div id="stats-tab" class="tab-content">
                    <h2 class="section-title">
                        <i class="fas fa-chart-line"></i> Account Statistics
                    </h2>
                    
                    <div class="stats-cards">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['total_exams']; ?></div>
                            <div class="stat-label">Exams Taken</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($stats['avg_score'] ?? 0, 1); ?>%</div>
                            <div class="stat-label">Average Score</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $total_time_formatted; ?></div>
                            <div class="stat-label">Total Time Spent</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">#<?php echo $global_rank; ?></div>
                            <div class="stat-label">Global Rank</div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 3rem;">
                        <h3 style="margin-bottom: 1rem; color: var(--text-primary);">
                            <i class="fas fa-trophy"></i> Performance Summary
                        </h3>
                        <div style="background: rgba(15, 23, 42, 0.3); padding: 1.5rem; border-radius: 10px; border: 1px solid var(--border);">
                            <div style="display: flex; gap: 2rem; margin-bottom: 1.5rem;">
                                <div>
                                    <div style="font-size: 2rem; font-weight: 700; color: var(--success);">
                                        <?php echo $stats['passed_exams']; ?>
                                    </div>
                                    <div style="color: var(--text-secondary);">Exams Passed</div>
                                </div>
                                <div>
                                    <div style="font-size: 2rem; font-weight: 700; color: var(--accent);">
                                        <?php echo number_format($stats['highest_score'] ?? 0, 1); ?>%
                                    </div>
                                    <div style="color: var(--text-secondary);">Highest Score</div>
                                </div>
                            </div>
                            
                            <?php if ($stats['total_exams'] > 0): ?>
                            <div style="margin-top: 1rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="color: var(--text-secondary);">Success Rate</span>
                                    <span style="color: var(--text-primary); font-weight: 600;">
                                        <?php echo number_format(($stats['passed_exams'] / $stats['total_exams']) * 100, 1); ?>%
                                    </span>
                                </div>
                                <div style="height: 8px; background: var(--border); border-radius: 4px; overflow: hidden;">
                                    <div style="height: 100%; width: <?php echo ($stats['passed_exams'] / $stats['total_exams']) * 100; ?>%; background: linear-gradient(90deg, var(--success), #0d9c6f);"></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        // Password toggle functionality
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = event.currentTarget;
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
        // Form validation
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                // Email validation for profile form
                if (form.querySelector('#email')) {
                    const email = form.querySelector('#email').value;
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    
                    if (!emailRegex.test(email)) {
                        e.preventDefault();
                        alert('Please enter a valid email address!');
                        form.querySelector('#email').focus();
                        return false;
                    }
                }
                
                // Password validation for password form
                if (form.querySelector('#new_password')) {
                    const newPass = form.querySelector('#new_password').value;
                    const confirmPass = form.querySelector('#confirm_password').value;
                    
                    if (newPass !== confirmPass) {
                        e.preventDefault();
                        alert('New passwords do not match!');
                        return false;
                    }
                    
                    if (newPass.length < 6) {
                        e.preventDefault();
                        alert('New password must be at least 6 characters!');
                        return false;
                    }
                }
                
                return true;
            });
        });
        
        // Show confirmation before leaving if changes were made
        let formChanged = false;
        const formInputs = document.querySelectorAll('input, textarea, select');
        
        formInputs.forEach(input => {
            input.addEventListener('input', () => {
                formChanged = true;
            });
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        
        // Save button click handler
        const saveButtons = document.querySelectorAll('button[type="submit"]');
        saveButtons.forEach(button => {
            button.addEventListener('click', function() {
                formChanged = false;
            });
        });
        
        // Initialize password strength indicator
        const newPasswordInput = document.getElementById('new_password');
        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', function() {
                const password = this.value;
                const strengthBar = document.getElementById('password-strength');
                const strengthText = document.getElementById('strength-text');
                
                if (!strengthBar) {
                    // Create strength indicator if it doesn't exist
                    const indicator = document.createElement('div');
                    indicator.innerHTML = `
                        <div style="margin-top: 0.5rem;">
                            <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.25rem;">Password strength: <span id="strength-text">Weak</span></div>
                            <div id="password-strength" style="height: 4px; background: var(--border); border-radius: 2px; overflow: hidden;">
                                <div style="height: 100%; width: 20%; background: var(--danger); transition: width 0.3s;"></div>
                            </div>
                        </div>
                    `;
                    this.parentNode.parentNode.appendChild(indicator);
                }
                
                let strength = 0;
                if (password.length >= 8) strength += 25;
                if (/[a-z]/.test(password)) strength += 25;
                if (/[A-Z]/.test(password)) strength += 25;
                if (/[0-9]/.test(password) || /[^A-Za-z0-9]/.test(password)) strength += 25;
                
                const strengthFill = document.querySelector('#password-strength div');
                strengthFill.style.width = strength + '%';
                
                if (strength <= 25) {
                    strengthFill.style.background = 'var(--danger)';
                    strengthText.textContent = 'Weak';
                } else if (strength <= 50) {
                    strengthFill.style.background = '#f59e0b';
                    strengthText.textContent = 'Medium';
                } else if (strength <= 75) {
                    strengthFill.style.background = '#3b82f6';
                    strengthText.textContent = 'Strong';
                } else {
                    strengthFill.style.background = 'var(--success)';
                    strengthText.textContent = 'Very Strong';
                }
            });
        }
    </script>
</body>
</html>