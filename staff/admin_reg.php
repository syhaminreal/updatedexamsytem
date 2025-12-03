<?php
require 'db_connection.php';

$msg = "";

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $fullname = trim($_POST['fullname']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $sql = "INSERT INTO admin_users
                (username, email, full_name, password_hash, role)
                VALUES (:u, :e, :f, :p, :r)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':u' => $username,
            ':e' => $email,
            ':f' => $fullname,
            ':p' => $password_hash,
            ':r' => $role
        ]);

        $msg = "✅ Admin registered successfully!";
        
    } catch(PDOException $e) {
        $msg = "❌ " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Registration</title>

    <style>
        body{
            margin:0;
            padding:0;
            background:#0e0e0e;
            font-family:Arial, sans-serif;
            color:#eee;
        }

        .register-container{
            width:420px;
            margin:70px auto;
            padding:30px;
            background:#1a1a1a;
            border-radius:12px;
            box-shadow:0px 0px 20px rgba(0,0,0,0.6);
        }

        h2{
            text-align:center;
            margin-bottom:25px;
            color:#4da3ff;
        }

        .msg{
            text-align:center;
            margin-bottom:15px;
            color:#4cff72;
        }

        .error{
            color:#ff5252;
        }

        label{
            display:block;
            margin-top:10px;
            margin-bottom:5px;
            color:#aaa;
        }

        input, select{
            width:100%;
            padding:12px;
            border:none;
            border-radius:6px;
            background:#2b2b2b;
            color:#fff;
            font-size:15px;
        }

        input:focus, select:focus{
            outline:2px solid #4da3ff;
        }

        select{
            cursor:pointer;
        }

        button{
            width:100%;
            padding:12px;
            margin-top:20px;
            background:#4da3ff;
            color:#000;
            border:none;
            border-radius:6px;
            font-size:16px;
            font-weight:bold;
            cursor:pointer;
            transition:.2s;
        }

        button:hover{
            background:#1c8cff;
        }

        .login-link{
            text-align:center;
            margin-top:15px;
        }

        .login-link a{
            color:#4da3ff;
            text-decoration:none;
        }

        .login-link a:hover{
            text-decoration:underline;
        }
    </style>
</head>
<body>

<div class="register-container">

    <h2>Admin Registration</h2>

    <?php if($msg): ?>
        <p class="<?= str_contains($msg,'✅') ? 'msg' : 'msg error' ?>">
            <?= $msg ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label>Full Name</label>
        <input type="text" name="fullname" required>

        <label>Username</label>
        <input type="text" name="username" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Role</label>
        <select name="role">
            <option value="admin">Admin</option>
            <option value="moderator">Moderator</option>
            <option value="superadmin">Super Admin</option>
        </select>

        <button type="submit">Register</button>

    </form>

    <div class="login-link">
        <a href="login.php">Already have an account? Login</a>
    </div>

</div>

</body>
</html>
