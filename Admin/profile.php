<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
if ($user_id <= 0) {
    header('Location: ../login.php');
    exit();
}

$error = '';
$success = '';

try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die('User not found.');
    }
} catch (PDOException $e) {
    die('Error fetching profile: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($full_name === '' || $email === '') {
        $error = 'Full name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        try {
            $emailCheck = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1');
            $emailCheck->execute([$email, $user_id]);

            if ($emailCheck->fetch()) {
                $error = 'Email is already in use by another account.';
            } else {
                $update = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?');
                $update->execute([$full_name, $email, $phone, $user_id]);

                $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                $_SESSION['full_name'] = $user['full_name'];
                $success = 'Profile updated successfully.';
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
    <title>Admin Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4 mb-5" style="max-width: 800px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Admin Profile</h2>
            <a href="../staff/index.php" class="btn btn-outline-secondary btn-sm">Back to Admin Panel</a>
        </div>

        <?php if ($success !== ''): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <strong>Account Information</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">User Type</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst($user['user_type'] ?? 'admin')); ?>" readonly>
                        </div>

                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>

                        <div class="col-12">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Account Created</label>
                            <input type="text" class="form-control" value="<?php echo !empty($user['created_at']) ? htmlspecialchars(date('Y-m-d H:i', strtotime($user['created_at']))) : 'N/A'; ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Login</label>
                            <input type="text" class="form-control" value="<?php echo !empty($user['last_login']) ? htmlspecialchars(date('Y-m-d H:i', strtotime($user['last_login']))) : 'N/A'; ?>" readonly>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" name="save_profile" class="btn btn-primary">Save Profile</button>
                        <a href="../change_password.php" class="btn btn-warning">Change Password</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
