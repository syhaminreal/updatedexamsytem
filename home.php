<?php
// login.php
session_start();
require_once 'db_connection.php';

$error = '';
$success = '';

// Redirect if already logged in
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password!";
    } else {
        // Use database authentication
        $stmt = $pdo->prepare("SELECT user_id, username, email, password_hash, full_name, user_type, is_active FROM users WHERE email = :email OR username = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $error = "Invalid email or password!";
        } elseif ($user['is_active'] != 1) {
            $error = "Your account is inactive. Please contact administrator.";
        } elseif (!password_verify($password, $user['password_hash'])) {
            $error = "Invalid email or password!";
        } else {
            // Set session variables
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['username'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['user_role'] = $user['user_type']; // Backward compatibility
            $_SESSION['full_name'] = $user['full_name'];
            
            // Update last login
            $update = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $update->execute([$user['user_id']]);
            
            header("Location: dashboard.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark-theme">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ExamPro</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 440px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 1rem;
        }
        
        .logo-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }
        
        .logo-text {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--text-primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }
        
        .login-card {
            background: var(--bg-secondary);
            border-radius: 20px;
            padding: 2.5rem;
            border: 1px solid var(--border);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
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
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .input-group {
            position: relative;
        }
        
        input {
            width: 100%;
            padding: 1rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .forgot-password {
            color: var(--accent);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1.5rem;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
        }
        
        .divider {
            text-align: center;
            position: relative;
            margin: 1.5rem 0;
            color: var(--text-secondary);
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 45%;
            height: 1px;
            background: var(--border);
        }
        
        .divider::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            width: 45%;
            height: 1px;
            background: var(--border);
        }
        
        .demo-credentials {
            background: rgba(15, 23, 42, 0.6);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
        }
        
        .demo-credentials h4 {
            margin-bottom: 0.5rem;
            color: var(--accent);
            font-size: 0.9rem;
        }
        
        .demo-credentials p {
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .login-footer a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 1.5rem;
            }
            
            .form-options {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <span class="logo-text">ExamPro</span>
            </div>
            <h1>Welcome Back</h1>
            <p>Sign in to continue to your dashboard</p>
        </div>
        
        <div class="login-card">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>