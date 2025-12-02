<?php
// staff/logout.php
session_start();

// Store user info before destroying session
$username = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'User';

// Unset all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear remember me cookie
if (isset($_COOKIE['admin_remember'])) {
    setcookie('admin_remember', '', time() - 3600, "/");
}

// Destroy the session
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logout-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }
        
        .logout-box {
            background: white;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        
        .logout-icon {
            font-size: 64px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .logout-box h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 28px;
        }
        
        .logout-box p {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 14px;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
        }
        
        .countdown {
            margin-top: 20px;
            font-size: 14px;
            color: #777;
        }
        
        .security-note {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #888;
        }
    </style>
    <script>
        // Auto redirect after 5 seconds
        let countdown = 5;
        
        function updateCountdown() {
            document.getElementById('countdown').textContent = countdown;
            if (countdown <= 0) {
                window.location.href = 'login.php'; // Changed to login.php
            } else {
                countdown--;
                setTimeout(updateCountdown, 1000);
            }
        }
        
        // Start countdown when page loads
        window.onload = function() {
            updateCountdown();
        };
    </script>
</head>
<body>
    <div class="logout-container">
        <div class="logout-box">
            <div class="logout-icon">👋</div>
            <h1>Logged Out Successfully</h1>
            <p>You have been securely logged out of the admin panel.</p>
            
            <div class="user-info">
                <p><strong>User:</strong> <?php echo htmlspecialchars($username); ?></p>
                <p><strong>Time:</strong> <?php echo date('H:i:s'); ?></p>
                <p><strong>Date:</strong> <?php echo date('F j, Y'); ?></p>
            </div>
            
            <p>For security reasons, please close your browser if you're on a shared computer.</p>
            
            <div class="btn-group">
                <a href="login.php" class="btn btn-primary">Login Again</a>
                <!-- Removed the "Go to Homepage" button since we want to redirect to login -->
            </div>
            
            <div class="countdown">
                Redirecting to login page in <span id="countdown">5</span> seconds...
            </div>
            
            <div class="security-note">
                <p>✅ Session destroyed<br>
                   ✅ Cookies cleared<br>
                   ✅ Secure logout complete</p>
            </div>
        </div>
    </div>
</body>
</html>
<?php
// Make sure no code executes after HTML
exit();
?>