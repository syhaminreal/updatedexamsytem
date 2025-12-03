<?php
// Database configuration
$host = 'localhost';
$dbname = 'exaam';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get exam results data (last 20 results)
$examResultsQuery = "
    SELECT er.*, e.exam_title, ea.student_email 
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.exam_id
    LEFT JOIN exam_attempts ea ON er.attempt_id = ea.attempt_id
    ORDER BY er.completed_at DESC
    LIMIT 20
";
$examResults = $pdo->query($examResultsQuery)->fetchAll();

// Get exam statistics
$statsQuery = "
    SELECT 
        COUNT(DISTINCT er.result_id) as total_results,
        COUNT(DISTINCT er.student_name) as total_students,
        AVG(er.percentage) as avg_percentage,
        SUM(CASE WHEN er.status = 'PASS' THEN 1 ELSE 0 END) as passed_count,
        SUM(CASE WHEN er.status = 'FAIL' THEN 1 ELSE 0 END) as failed_count
    FROM exam_results er
";
$stats = $pdo->query($statsQuery)->fetch();
$totalResults = $stats['total_results'];
$passedCount = $stats['passed_count'];
$failedCount = $stats['failed_count'];
$passRate = $totalResults > 0 ? ($passedCount / $totalResults) * 100 : 0;
$avgPercentage = $stats['avg_percentage'] ? round($stats['avg_percentage'], 1) : 0;

// Get user data (last 15 users)
$usersQuery = "SELECT * FROM users ORDER BY created_at DESC LIMIT 15";
$users = $pdo->query($usersQuery)->fetchAll();

// Get user statistics
$userStatsQuery = "
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN user_type = 'student' THEN 1 ELSE 0 END) as students_count,
        SUM(CASE WHEN user_type = 'teacher' THEN 1 ELSE 0 END) as teachers_count,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users
    FROM users
";
$userStats = $pdo->query($userStatsQuery)->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --dark-bg: #0f172a;
            --dark-card: #1e293b;
            --dark-card-hover: #334155;
            --dark-border: #475569;
            --dark-text: #f1f5f9;
            --dark-text-secondary: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--dark-text);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--dark-border);
        }

        .header h1 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.8rem;
        }

        .header h1 i {
            color: var(--primary-color);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-box {
            background-color: var(--dark-card);
            border-radius: 8px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            font-size: 22px;
        }

        .stat-icon.results { background-color: rgba(99, 102, 241, 0.15); color: var(--primary-color); }
        .stat-icon.passed { background-color: rgba(16, 185, 129, 0.15); color: var(--secondary-color); }
        .stat-icon.failed { background-color: rgba(239, 68, 68, 0.15); color: var(--danger-color); }
        .stat-icon.avg { background-color: rgba(245, 158, 11, 0.15); color: var(--warning-color); }
        .stat-icon.users { background-color: rgba(99, 102, 241, 0.15); color: var(--primary-color); }
        .stat-icon.students { background-color: rgba(16, 185, 129, 0.15); color: var(--secondary-color); }
        .stat-icon.teachers { background-color: rgba(245, 158, 11, 0.15); color: var(--warning-color); }

        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--dark-text-secondary);
            font-size: 0.9rem;
        }

        /* Content Sections */
        .content-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .content-sections {
                grid-template-columns: 1fr;
            }
        }

        .section {
            background-color: var(--dark-card);
            border-radius: 10px;
            padding: 20px;
            overflow: hidden;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--dark-border);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
        }

        .section-title i {
            color: var(--primary-color);
        }

        .view-all {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            color: var(--dark-text-secondary);
            border-bottom: 1px solid var(--dark-border);
            font-size: 0.9rem;
        }

        td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--dark-border);
            font-size: 0.9rem;
        }

        tbody tr:hover {
            background-color: var(--dark-card-hover);
        }

        /* Status Badges */
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-passed { background-color: rgba(16, 185, 129, 0.15); color: var(--secondary-color); }
        .badge-failed { background-color: rgba(239, 68, 68, 0.15); color: var(--danger-color); }
        .badge-student { background-color: rgba(16, 185, 129, 0.15); color: var(--secondary-color); }
        .badge-teacher { background-color: rgba(245, 158, 11, 0.15); color: var(--warning-color); }
        .badge-admin { background-color: rgba(99, 102, 241, 0.15); color: var(--primary-color); }
        .badge-active { background-color: rgba(16, 185, 129, 0.15); color: var(--secondary-color); }
        .badge-inactive { background-color: rgba(239, 68, 68, 0.15); color: var(--danger-color); }

        /* User Avatar */
        .user-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.85rem;
        }

        /* Actions */
        .action-btn {
            padding: 6px 10px;
            border-radius: 6px;
            border: none;
            background-color: var(--dark-bg);
            color: var(--dark-text);
            cursor: pointer;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
        }

        .action-btn:hover {
            background-color: var(--dark-card-hover);
        }

        .action-btn.view:hover { color: var(--primary-color); }
        .action-btn.delete:hover { color: var(--danger-color); }
        .action-btn.edit:hover { color: var(--warning-color); }

        /* Footer */
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid var(--dark-border);
            color: var(--dark-text-secondary);
            font-size: 0.9rem;
        }
/* Header container */
.header {
    display: flex;
    align-items: center;
    gap: 15px; /* space between button and title */
    margin-bottom: 30px;
}

/* Back button */
.btn-back {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: #2b2b2b;
    color: #fff;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    border: 1px solid #444;
    transition: all 0.2s ease;
}

.btn-back:hover {
    background-color: #444;
    border-color: #666;
    color: #fff;
}

/* Header title */
.header h1 {
    font-size: 26px;
    font-weight: 700;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 10px; /* space between icon and text */
    margin: 0;
}



    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
               <a href="index.php" class="btn-back">⬅ Back</a>
            <h1>
                <i class="fas fa-tachometer-alt"></i>
              Exam Overview
            </h1>

        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <!-- Exam Stats -->
            <div class="stat-box">
                <div class="stat-icon results">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-value"><?php echo number_format($totalResults); ?></div>
                <div class="stat-label">Total Results</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-icon passed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo number_format($passedCount); ?></div>
                <div class="stat-label">Passed</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-icon failed">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-value"><?php echo number_format($failedCount); ?></div>
                <div class="stat-label">Failed</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-icon avg">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-value"><?php echo number_format($avgPercentage, 1); ?>%</div>
                <div class="stat-label">Avg Score</div>
            </div>
            
            <!-- User Stats -->
            <div class="stat-box">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo number_format($userStats['total_users']); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-icon students">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="stat-value"><?php echo number_format($userStats['students_count']); ?></div>
                <div class="stat-label">Students</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-icon teachers">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-value"><?php echo number_format($userStats['teachers_count']); ?></div>
                <div class="stat-label">Teachers</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-value"><?php echo number_format($userStats['active_users']); ?></div>
                <div class="stat-label">Active Users</div>
            </div>
        </div>

        <!-- Content Sections -->
        <div class="content-sections">
            <!-- Recent Exam Results -->
            <div class="section">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-poll"></i>
                        Recent Exam Results
                    </div>
                    <a href="#" class="view-all" onclick="viewAllResults()">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Exam</th>
                                <th>Score</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($examResults)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 30px; color: var(--dark-text-secondary);">
                                        No exam results found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($examResults as $result): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(substr($result['student_name'], 0, 20)); ?></td>
                                        <td><?php echo htmlspecialchars(substr($result['exam_title'], 0, 20)); ?></td>
                                        <td><strong><?php echo $result['marks_obtained']; ?>/<?php echo $result['total_marks']; ?></strong></td>
                                        <td>
                                            <span class="badge <?php echo strtolower($result['status']) == 'pass' ? 'badge-passed' : 'badge-failed'; ?>">
                                                <?php echo $result['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="section">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-users"></i>
                        Recent Users
                    </div>
                    <a href="#" class="view-all" onclick="viewAllUsers()">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 30px; color: var(--dark-text-secondary);">
                                        No users found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <?php 
                                    // Get initials for avatar
                                    $initials = '';
                                    $nameParts = explode(' ', $user['full_name']);
                                    foreach ($nameParts as $part) {
                                        $initials .= strtoupper(substr($part, 0, 1));
                                    }
                                    $initials = substr($initials, 0, 2);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-avatar"><?php echo $initials; ?></div>
                                                <div>
                                                    <div><?php echo htmlspecialchars(substr($user['full_name'], 0, 15)); ?></div>
                                                    <small style="color: var(--dark-text-secondary); font-size: 0.8rem;">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $user['user_type']; ?>">
                                                <?php echo ucfirst($user['user_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $user['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Last updated: <?php echo date('F j, Y H:i:s'); ?> | Total Results: <?php echo number_format($totalResults); ?> | Total Users: <?php echo number_format($userStats['total_users']); ?></p>
        </div>
    </div>

    <script>
        function viewAllResults() {
            alert('This would show all exam results in a detailed view.\n\nIn a real application, this could link to a dedicated results page.');
        }

        function viewAllUsers() {
            alert('This would show all users in a detailed view.\n\nIn a real application, this could link to a dedicated users page.');
        }

        // Auto-refresh every 60 seconds
        setInterval(() => {
            const shouldRefresh = confirm('Refresh data?');
            if (shouldRefresh) {
                window.location.reload();
            }
        }, 60000);
    </script>
</body>
</html>