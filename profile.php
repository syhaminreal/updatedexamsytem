<?php
// profile.php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id    = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

$success = '';
$error   = '';

function sanitize($d) {
    return htmlspecialchars(strip_tags(trim($d)));
}

/* ===========================
   FETCH USER INFORMATION
=========================== */
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: logout.php");
    exit();
}

/* ===========================
   FETCH USER STATISTICS
   (Using student_email as per your structure)
=========================== */
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(result_id) AS total_exams,
        AVG(percentage) AS avg_score,
        MAX(percentage) AS highest_score,
        SUM(status = 'PASS') AS passed_exams
    FROM exam_results
    WHERE student_email = ?
");
$stats_stmt->execute([$user_email]);
$stats = $stats_stmt->fetch();

if (!$stats) {
    $stats = [
        'total_exams'   => 0,
        'avg_score'    => 0,
        'highest_score'=> 0,
        'passed_exams' => 0,
    ];
}

/* ===========================
   RECENT EXAM HISTORY
   CORRECTED: Join with exams table to get exam_name
   Using completed_at as exam date
=========================== */
$recent_stmt = $pdo->prepare("
    SELECT 
        e.exam_name,
        er.percentage,
        er.status,
        DATE_FORMAT(er.completed_at, '%b %d, %Y') as exam_date
    FROM exam_results er
    LEFT JOIN exams e ON er.exam_id = e.exam_id
    WHERE er.student_email = ? 
    ORDER BY er.completed_at DESC 
    LIMIT 5
");
$recent_stmt->execute([$user_email]);
$recent_exams = $recent_stmt->fetchAll();

/* ===========================
   SIMPLE GLOBAL RANK
=========================== */
$rank_stmt = $pdo->prepare("
    SELECT COUNT(*) + 1 AS rank
    FROM (
        SELECT student_email, AVG(percentage) AS avg_score
        FROM exam_results
        GROUP BY student_email
        HAVING AVG(percentage) > (
            SELECT AVG(percentage)
            FROM exam_results
            WHERE student_email = ?
        )
    ) AS higher_scores
");
$rank_stmt->execute([$user_email]);
$rank_data  = $rank_stmt->fetch();
$global_rank = $rank_data['rank'] ?? 1;

/* ===========================
   UPDATE PROFILE
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']);
    $email     = sanitize($_POST['email']);
    $phone     = sanitize($_POST['phone']);
    $address   = sanitize($_POST['address']);

    if (!$full_name || !$email) {
        $error = "Full name and email required.";
    } else {
        // Check email duplicate
        $check = $pdo->prepare("
            SELECT user_id FROM users 
            WHERE email = ? AND user_id != ?
        ");
        $check->execute([$email, $user_id]);

        if ($check->rowCount() > 0) {
            $error = "Email already in use!";
        } else {
            // Update profile
            $update = $pdo->prepare("
                UPDATE users 
                SET full_name=?, email=?, phone=?, address=?, updated_at=NOW()
                WHERE user_id=?
            ");

            if ($update->execute([$full_name, $email, $phone, $address, $user_id])) {
                $_SESSION['full_name']   = $full_name;
                $_SESSION['user_email'] = $email;
                $user_email = $email;

                // Also update student_name in exam_results if it exists
                $update_exam_results = $pdo->prepare("
                    UPDATE exam_results 
                    SET student_name = ?
                    WHERE student_email = ?
                ");
                $update_exam_results->execute([$full_name, $email]);

                $success = "Profile updated successfully!";

                // Reload user
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } else {
                $error = "Failed to update profile.";
            }
        }
    }
}

/* ===========================
   CHANGE PASSWORD
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!$current || !$new || !$confirm) {
        $error = "Fill all password fields!";
    } elseif ($new !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($new) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif (!password_verify($current, $user['password_hash'])) {
        $error = "Incorrect current password.";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);

        $updatePass = $pdo->prepare("
            UPDATE users 
            SET password_hash=?, updated_at=NOW()
            WHERE user_id = ?
        ");

        if ($updatePass->execute([$hash, $user_id])) {
            $success = "Password changed successfully!";
        } else {
            $error = "Failed to change password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile | Exam Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --bg-primary: #0f172a;
    --bg-secondary: #1e293b;
    --bg-card: #1e293b;
    --bg-hover: #334155;
    --bg-input: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
    --border-color: #334155;
    --accent-blue: #3b82f6;
    --accent-green: #10b981;
    --accent-purple: #8b5cf6;
    --accent-amber: #f59e0b;
    --accent-red: #ef4444;
    --accent-cyan: #06b6d4;
    --success: #10b981;
    --error: #ef4444;
    --warning: #f59e0b;
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: var(--bg-primary);
    color: var(--text-primary);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    line-height: 1.6;
    min-height: 100vh;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px;
}

/* Header */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-color);
}

.header h1 {
    font-size: 28px;
    font-weight: 700;
    background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.user-badge {
    background: var(--bg-secondary);
    padding: 8px 16px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
}

.user-badge i {
    color: var(--accent-amber);
}

/* Alerts */
.alert {
    padding: 16px;
    border-radius: var(--radius-md);
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideIn 0.3s ease;
}

.alert-success {
    background: rgba(16, 185, 129, 0.15);
    border-left: 4px solid var(--success);
    color: var(--success);
}

.alert-error {
    background: rgba(239, 68, 68, 0.15);
    border-left: 4px solid var(--error);
    color: var(--error);
}

.alert i {
    font-size: 20px;
}

@keyframes slideIn {
    from { transform: translateY(-10px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* Layout */
.dashboard {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
}

@media (min-width: 768px) {
    .dashboard {
        grid-template-columns: 1fr 1fr;
    }
    
    .profile-card, .stats-card {
        grid-column: span 2;
    }
    
    .recent-card, .rank-card {
        grid-column: span 1;
    }
}

/* Cards */
.card {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    padding: 24px;
    box-shadow: var(--shadow);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border-color);
}

.card-header h2, .card-header h3 {
    font-size: 20px;
    font-weight: 600;
    color: var(--text-primary);
}

.card-header i {
    font-size: 24px;
    color: var(--accent-blue);
}

/* Profile Info */
.profile-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.info-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.info-label {
    font-size: 14px;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 16px;
    font-weight: 500;
    color: var(--text-primary);
}

.user-type {
    display: inline-block;
    padding: 4px 12px;
    background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
    color: white;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 16px;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 20px;
    background: rgba(30, 41, 59, 0.7);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.stat-item:hover {
    background: var(--bg-hover);
    border-color: var(--accent-blue);
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 8px;
    background: linear-gradient(135deg, var(--accent-blue), var(--accent-cyan));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.stat-label {
    font-size: 14px;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Progress Circle */
.progress-ring {
    width: 100px;
    height: 100px;
    margin: 0 auto 16px;
}

.progress-ring circle {
    fill: none;
    stroke-width: 8;
    stroke-linecap: round;
    transform: rotate(-90deg);
    transform-origin: 50% 50%;
}

.progress-bg {
    stroke: var(--bg-hover);
}

.progress-bar {
    stroke: var(--accent-blue);
    stroke-dasharray: 283;
    stroke-dashoffset: calc(283 - (283 * <?= $stats['avg_score'] ?? 0 ?> / 100));
    transition: stroke-dashoffset 1s ease;
}

/* Recent Exams */
.recent-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.recent-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: var(--bg-secondary);
    border-radius: var(--radius-md);
    border-left: 4px solid var(--accent-blue);
}

.recent-item.pass {
    border-left-color: var(--success);
}

.recent-item.fail {
    border-left-color: var(--error);
}

.exam-name {
    font-weight: 500;
    margin-bottom: 4px;
}

.exam-date {
    font-size: 12px;
    color: var(--text-muted);
}

.exam-score {
    font-size: 18px;
    font-weight: 600;
    text-align: right;
    margin-bottom: 4px;
}

.exam-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    display: inline-block;
}

.status-pass {
    background: rgba(16, 185, 129, 0.2);
    color: var(--success);
}

.status-fail {
    background: rgba(239, 68, 68, 0.2);
    color: var(--error);
}

/* Rank Card */
.rank-card .card-header {
    justify-content: center;
}

.rank-display {
    text-align: center;
    padding: 40px 20px;
}

.rank-number {
    font-size: 72px;
    font-weight: 800;
    background: linear-gradient(135deg, var(--accent-amber), var(--accent-red));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    line-height: 1;
    margin-bottom: 8px;
}

.rank-label {
    font-size: 18px;
    color: var(--text-secondary);
}

.rank-subtitle {
    margin-top: 20px;
    color: var(--text-muted);
    font-size: 14px;
}

/* Forms */
.form-section {
    margin-top: 40px;
    padding-top: 32px;
    border-top: 1px solid var(--border-color);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
}

.form-card {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    padding: 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: var(--text-secondary);
    font-weight: 500;
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    background: var(--bg-input);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    color: var(--text-primary);
    font-family: 'Inter', sans-serif;
    font-size: 15px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--accent-blue);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn {
    padding: 12px 24px;
    background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
    color: white;
    border: none;
    border-radius: var(--radius-md);
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

/* Footer */
.footer {
    margin-top: 60px;
    padding-top: 24px;
    border-top: 1px solid var(--border-color);
    text-align: center;
    color: var(--text-muted);
    font-size: 14px;
}
</style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <h1><i class="fas fa-user-circle"></i> My Profile</h1>
        <div class="user-badge">
            <i class="fas fa-user"></i>
            <span><?= htmlspecialchars($user['full_name']) ?> • <?= ucfirst($user['user_type']) ?></span>
        </div>
    </div>

    <!-- Alerts -->
    <?php if($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <span><?= $success ?></span>
    </div>
    <?php endif ?>

    <?php if($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <span><?= $error ?></span>
    </div>
    <?php endif ?>

    <!-- Dashboard Grid -->
    <div class="dashboard">
        <!-- Profile Card -->
        <div class="card profile-card">
            <div class="card-header">
                <h2><i class="fas fa-id-card"></i> Personal Information</h2>
                <span class="user-type"><?= ucfirst($user['user_type']) ?></span>
            </div>
            <div class="profile-info">
                <div class="info-group">
                    <span class="info-label">Full Name</span>
                    <span class="info-value"><?= htmlspecialchars($user['full_name']) ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Email Address</span>
                    <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Username</span>
                    <span class="info-value"><?= htmlspecialchars($user['username']) ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Phone</span>
                    <span class="info-value"><?= $user['phone'] ? htmlspecialchars($user['phone']) : '<span style="color: var(--text-muted);">Not set</span>' ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Address</span>
                    <span class="info-value"><?= $user['address'] ? htmlspecialchars($user['address']) : '<span style="color: var(--text-muted);">Not set</span>' ?></span>
                </div>
            </div>
        </div>

        <!-- Stats Card -->
        <div class="card stats-card">
            <div class="card-header">
                <h2><i class="fas fa-chart-line"></i> Exam Statistics</h2>
            </div>
            
            <!-- Progress Circle for Average Score -->
            <div class="progress-ring">
                <svg viewBox="0 0 100 100">
                    <circle class="progress-bg" cx="50" cy="50" r="45"></circle>
                    <circle class="progress-bar" cx="50" cy="50" r="45"></circle>
                </svg>
            </div>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-value"><?= $stats['total_exams'] ?></span>
                    <span class="stat-label">Total Exams</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= number_format($stats['avg_score'] ?? 0, 1) ?>%</span>
                    <span class="stat-label">Avg Score</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= number_format($stats['highest_score'] ?? 0, 1) ?>%</span>
                    <span class="stat-label">Highest Score</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= $stats['passed_exams'] ?></span>
                    <span class="stat-label">Passed Exams</span>
                </div>
            </div>
        </div>

        <!-- Recent Exams -->
        <div class="card recent-card">
            <div class="card-header">
                <h3><i class="fas fa-history"></i> Recent Exams</h3>
            </div>
            <div class="recent-list">
                <?php if (!empty($recent_exams)): ?>
                    <?php foreach ($recent_exams as $exam): ?>
                    <div class="recent-item <?= strtolower($exam['status']) ?>">
                        <div class="exam-info">
                            <div class="exam-name">
                                <?= $exam['exam_name'] ? htmlspecialchars($exam['exam_name']) : 'Exam #' . $exam['exam_id'] ?>
                            </div>
                            <?php if ($exam['exam_date']): ?>
                            <div class="exam-date">
                                <i class="far fa-calendar"></i> <?= $exam['exam_date'] ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="exam-result">
                            <div class="exam-score"><?= number_format($exam['percentage'], 1) ?>%</div>
                            <span class="exam-status status-<?= strtolower($exam['status']) ?>">
                                <?= $exam['status'] ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 30px; color: var(--text-muted);">
                        <i class="fas fa-clipboard-list" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                        <p>No exam history yet</p>
                        <p style="font-size: 12px; margin-top: 8px;">Take an exam to see your results here</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Global Rank -->
        <div class="card rank-card">
            <div class="card-header">
                <h3><i class="fas fa-trophy"></i> Global Rank</h3>
            </div>
            <div class="rank-display">
                <div class="rank-number">#<?= $global_rank ?></div>
                <div class="rank-label">Out of all students</div>
                <div class="rank-subtitle">
                    Based on average performance across all exams
                </div>
            </div>
        </div>
    </div>

    <!-- Forms Section -->
    <div class="form-section">
        <h2 style="margin-bottom: 24px; font-size: 24px;">Account Settings</h2>
        
        <div class="form-grid">
            <!-- Update Profile Form -->
            <div class="form-card">
                <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-user-edit"></i> Update Profile
                </h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               value="<?= htmlspecialchars($user['full_name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="form-card">
                <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-lock"></i> Change Password
                </h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="current_password">Current Password *</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                        <small style="color: var(--text-muted); font-size: 12px; margin-top: 4px; display: block;">
                            Must be at least 8 characters long
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn">
                        <i class="fas fa-key"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>© <?= date('Y') ?> Exam Portal. All rights reserved.</p>
        <p style="margin-top: 8px; font-size: 12px; opacity: 0.7;">
            Last updated: <?= date('F j, Y, g:i a') ?>
        </p>
    </div>
</div>

<script>
// Add some interactive animations
document.addEventListener('DOMContentLoaded', function() {
    // Animate progress ring
    const progressRing = document.querySelector('.progress-bar');
    if (progressRing) {
        setTimeout(() => {
            progressRing.style.transition = 'stroke-dashoffset 1.5s ease';
        }, 500);
    }

    // Add click animation to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('click', function() {
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });

    // Form validation feedback
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const inputs = this.querySelectorAll('input[required], textarea[required]');
            let valid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.style.borderColor = 'var(--accent-red)';
                    valid = false;
                } else {
                    input.style.borderColor = '';
                }
            });
            
            if (!valid) {
                e.preventDefault();
            }
        });
    });
});
</script>
</body>
</html>