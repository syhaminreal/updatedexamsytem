<?php
session_start();
require 'db_connection.php';

$error = "";

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare(
        "SELECT * FROM admin_users 
         WHERE username = :u AND is_active = 1"
    );

    $stmt->execute([':u' => $username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if($admin && password_verify($password, $admin['password_hash'])) {

        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['username'] = $admin['username'];
        $_SESSION['role']     = $admin['role'];

        $pdo->prepare(
            "UPDATE admin_users 
             SET last_login = NOW() 
             WHERE admin_id = ?"
        )->execute([$admin['admin_id']]);

        header("Location: index.php");
        exit();
    } else {
        $error = "❌ Invalid login credentials";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            background: #0e0e0e;
            font-family: Arial, sans-serif;
            color: #eee;
        }

        .login-container {
            width: 380px;
            margin: 80px auto;
            padding: 30px;
            background: #1a1a1a;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.6);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #4da3ff;
        }

        .error {
            text-align: center;
            color: #ff5252;
            margin-bottom: 15px;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0 20px;
            border: none;
            border-radius: 6px;
            background: #2b2b2b;
            color: #fff;
            font-size: 15px;
        }

        input:focus {
            outline: 2px solid #4da3ff;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #4da3ff;
            color: #000;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            transition: .2s;
        }

        button:hover {
            background: #1c8cff;
        }

        .register-link {
            text-align: center;
            margin-top: 15px;
        }

        .register-link a {
            color: #4da3ff;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }
    </style>

</head>
<body>

<div class="login-container">
    <h2>Admin Login</h2>

    <?php if($error): ?>
    <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <div class="register-link">
        <a href="admin_reg.php">Create an account</a>
    </div>
</div>

</body>
</html>
