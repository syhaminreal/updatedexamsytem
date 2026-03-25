<?php
// login.php
session_start();
require_once 'db_connection.php';   // MUST create $pdo

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

$error = '';

// Redirect if already logged in
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header("Location: home.php");
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = sanitize($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email/username and password!";
    } else {

        // ✅ Use PDO only
        $stmt = $pdo->prepare("
            SELECT 
                user_id,
                username,
                email,
                password_hash,
                full_name,
                user_type,
                is_active,
                profile_image
            FROM users
            WHERE email = :email OR username = :email
            LIMIT 1
        ");

        $stmt->execute([
            ':email' => $email
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = "Invalid email/username or password!";
        }

        // Account inactive
        elseif ($user['is_active'] != 1) {
            $error = "Your account is inactive. Please contact administrator.";
        }

        // Password verify
        elseif (!password_verify($password, $user['password_hash'])) {
            $error = "Invalid email/username or password!";
        }

        else {

            // ✅ Update last login
            $update = $pdo->prepare("
                UPDATE users SET last_login = NOW()
                WHERE user_id = ?
            ");
            $update->execute([$user['user_id']]);

            // ✅ Sessions
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id']        = $user['user_id'];
            $_SESSION['username']       = $user['username'];
            $_SESSION['user_email']     = $user['email'];
            $_SESSION['full_name']      = $user['full_name'];
            $_SESSION['user_type']      = $user['user_type'];
            $_SESSION['profile_image'] = $user['profile_image'];

            // ✅ Remember-me cookie
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                
                // Store token in database
                $token_stmt = $pdo->prepare("UPDATE users SET remember_token = ?, remember_expires = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE user_id = ?");
                $token_stmt->execute([$token, $user['user_id']]);
                
                // Set secure cookie
                setcookie('user_remember', $token, time() + (86400 * 30), "/", "", true, true);
            }

            // ✅ Redirect by role
            if ($user['user_type'] === 'admin' || $user['user_type'] === 'teacher') {
                header("Location: staff/dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="dark-theme">
<head>
    <meta charset="UTF-8">
    <title>Login - ExamPro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

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

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Inter',sans-serif;
    background:var(--bg-primary);
    color:var(--text-primary);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
}

.login-container{
    width:100%;
    max-width:440px;
}

.login-header{
    text-align:center;
    margin-bottom:2.5rem;
}

.logo{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:12px;
    margin-bottom:1rem;
}

.logo-icon{
    width:44px;
    height:44px;
    background:linear-gradient(135deg,var(--accent),var(--accent-hover));
    border-radius:12px;
    display:flex;
    justify-content:center;
    align-items:center;
    color:#fff;
    font-size:1.2rem;
}

.logo-text{
    font-size:1.8rem;
    font-weight:700;
    background:linear-gradient(135deg,var(--text-primary),var(--accent));
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

.login-header h1{
    font-size:1.8rem;
}

.login-header p{
    color:var(--text-secondary);
}

.login-card{
    background:var(--bg-secondary);
    border-radius:20px;
    padding:2.5rem;
    border:1px solid var(--border);
    box-shadow:0 20px 60px rgba(0,0,0,0.4);
}

/* Alerts */
.alert{
    padding:1rem;
    border-radius:10px;
    margin-bottom:1.5rem;
    display:flex;
    align-items:center;
    gap:10px;
}

.alert-error{
    background:rgba(239,68,68,0.1);
    border:1px solid rgba(239,68,68,0.3);
    color:#fca5a5;
}

.form-group{
    margin-bottom:1.5rem;
}

label{
    display:block;
    margin-bottom:0.5rem;
}

.input-group{
    position:relative;
}

input{
    width:100%;
    padding:1rem;
    background:rgba(15,23,42,0.6);
    border:1px solid var(--border);
    border-radius:10px;
    color:var(--text-primary);
}

input:focus{
    outline:none;
    border-color:var(--accent);
    box-shadow:0 0 0 3px rgba(59,130,246,0.2);
}

.input-icon{
    position:absolute;
    right:15px;
    top:50%;
    transform:translateY(-50%);
    color:var(--text-secondary);
}

.form-options{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:1.5rem;
}

.remember-me{
    display:flex;
    gap:8px;
    font-size:0.9rem;
}

.forgot-password{
    color:var(--accent);
    text-decoration:none;
}

.btn-login{
    width:100%;
    padding:1rem;
    background:linear-gradient(135deg,var(--accent),var(--accent-hover));
    color:#fff;
    font-weight:600;
    border:none;
    border-radius:10px;
    cursor:pointer;
}

.btn-login:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 25px rgba(59,130,246,0.4);
}

.login-footer{
    margin-top:1.5rem;
    text-align:center;
    font-size:0.9rem;
    color:var(--text-secondary);
}

.login-footer a{
    color:var(--accent);
}
</style>
</head>

<body>

<div class="login-container">

    <div class="login-header">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
            <span class="logo-text">ExamPro</span>
        </div>
        <h1>Welcome Back</h1>
        <p>Sign in to continue to your dashboard</p>
    </div>

    <div class="login-card">

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email or Username</label>
                <div class="input-group">
                    <input type="text" name="email" required>
                    <div class="input-icon"><i class="fas fa-user"></i></div>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-group">
                    <input type="password" name="password" required>
                    <div class="input-icon"><i class="fas fa-lock"></i></div>
                </div>
            </div>

            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    Remember me
                </label>
                <a href="forgot_password.php" class="forgot-password">
                    Forgot password?
                </a>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>
        </form>

        <div class="login-footer">
            Don’t have an account?
            <a href="register.php">Create one now</a>
        </div>

    </div>
</div>

</body>
</html>
