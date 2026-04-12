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
$edit_mode = isset($_GET['edit']) && $_GET['edit'] === 'true';

// Fetch current user data
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation
    if (empty($full_name) || empty($email)) {
        $error = 'Full name and email are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        try {
            // Check if email already exists (excluding current user)
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = 'Email already exists';
            } else {
                // Update profile
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?");
                if ($stmt->execute([$full_name, $email, $phone, $user_id])) {
                    $success = 'Profile updated successfully!';
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    $_SESSION['full_name'] = $user['full_name'];
                    $edit_mode = false;
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
            top: -2rem;
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
            max-width: 700px;
            margin: 0 auto;
        }
        
        .header-section {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            font-weight: 700;
        }
        
        .profile-info h1 {
            font-size: 1.8rem;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }
        
        .profile-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .header-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
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
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .info-group {
            display: flex;
            flex-direction: column;
        }
        
        .info-group.full {
            grid-column: 1 / -1;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .info-value {
            font-size: 1rem;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group.full {
            grid-column: 1 / -1;
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
        
        input {
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
        
        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
            text-decoration: none;
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
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        .header-btn-group {
            display: flex;
            gap: 0.5rem;
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
        
        .hide {
            display: none;
        }
        
        @media (max-width: 640px) {
            .header-section {
                flex-direction: column;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .header-btn-group {
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
            <span>My Profile</span>
        </div>
    </div>
    
    <div class="container">
        <div class="header-section">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
            <div class="header-actions">
                <?php if (!$edit_mode): ?>
                    <a href="?edit=true" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                <?php else: ?>
                    <a href="?" class="btn btn-secondary btn-sm">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                <?php endif; ?>
            </div>
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
        
        <?php if (!$edit_mode): ?>
            <!-- View Mode -->
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-user-circle"></i>
                    Personal Information
                </div>
                
                <div class="info-grid">
                    <div class="info-group">
                        <span class="info-label">Full Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Username</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="info-group full">
                        <span class="info-label">Email Address</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Phone Number</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">User Type</span>
                        <span class="info-value"><?php echo htmlspecialchars(ucfirst($user['user_type'] ?? 'student')); ?></span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Edit Mode -->
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-edit"></i>
                    Edit Personal Information
                </div>
                
                <form method="POST" action="">
                    <div class="info-grid">
                        <div class="form-group">
                            <label for="full_name">Full Name <span class="required">*</span></label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required placeholder="Enter your full name">
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled placeholder="Username cannot be changed">
                        </div>
                        <div class="form-group full">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required placeholder="Enter your email address">
                        </div>
                        <div class="form-group full">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Enter your phone number (optional)">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="save_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="?" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-title">
                <i class="fas fa-lock"></i>
                Account Settings
            </div>
            <div class="links-group">
                <a href="change_password.php">
                    <i class="fas fa-key"></i> Change Password
                </a>
                <a href="dashboard.php">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - ExamPro</title>
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
            max-width: 700px;
            margin: 0 auto;
        }
        
        .header-section {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1.5rem;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            font-weight: 700;
        }
        
        .header-info h1 {
            font-size: 1.8rem;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }
        
        .header-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
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
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-row.full {
            grid-template-columns: 1fr;
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
        
        .form-input,
        input[type="text"],
        input[type="email"],
        input[type="phone"] {
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
        
        .form-input:focus,
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="phone"]:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .readonly-field {
            background: rgba(148, 163, 184, 0.05);
            cursor: not-allowed;
            opacity: 0.8;
        }
        
        .readonly-field:focus {
            border-color: var(--border);
            box-shadow: none;
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
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .header-section {
                flex-direction: column;
                text-align: center;
            }
            
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
            <span>Edit Profile</span>
        </div>
    </div>
    
    <div class="container">
        <div class="header-section">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
            <div class="header-info">
                <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
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
            <div class="card-title">
                <i class="fas fa-user-circle"></i>
                Personal Information
            </div>
            
            <form method="POST" action="">
                <div class="form-group form-row">
                    <div>
                        <label for="username">Username</label>
                        <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="readonly-field" readonly>
                        <div class="helper-text"><i class="fas fa-lock"></i> Cannot be changed</div>
                    </div>
                    <div>
                        <label for="user_type">User Type</label>
                        <input type="text" id="user_type" value="<?php echo htmlspecialchars(ucfirst($user['user_type'] ?? 'student')); ?>" class="readonly-field" readonly>
                        <div class="helper-text">Fixed account type</div>
                    </div>
                </div>
                
                <div class="form-group form-row full">
                    <div>
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="form-input" value="<?php echo htmlspecialchars($user['full_name']); ?>" required placeholder="Enter your full name">
                    </div>
                </div>
                
                <div class="form-group form-row full">
                    <div>
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" required placeholder="Enter your email address">
                    </div>
                </div>
                
                <div class="form-group form-row full">
                    <div>
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Enter your phone number (optional)">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="card-title">
                <i class="fas fa-lock"></i>
                Account Settings
            </div>
            <div class="links-group">
                <a href="change_password.php">
                    <i class="fas fa-key"></i> Change Password
                </a>
                <a href="edit_profile.php">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
            </div>
        </div>
    </div>
</body>
</html>