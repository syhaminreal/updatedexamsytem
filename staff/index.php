<?php
require_once '../db_connection.php';

// Get statistics
$stats_stmt = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM exams) as total_exams,
        (SELECT COUNT(*) FROM questions) as total_questions,
        (SELECT COUNT(*) FROM exam_results) as total_results,
        (SELECT COUNT(DISTINCT student_name) FROM exam_results) as total_students,
        (SELECT COUNT(*) FROM exam_attempts WHERE completed_at IS NOT NULL) as total_attempts,
        (SELECT AVG(percentage) FROM exam_results) as avg_score,
        (SELECT COUNT(*) FROM exams WHERE exam_status = 'active') as active_exams,
        (SELECT COUNT(*) FROM exams WHERE exam_status = 'inactive') as inactive_exams
");
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent exams
$recent_exams_stmt = $pdo->query("SELECT * FROM exams ORDER BY created_at DESC LIMIT 5");
$recent_exams = $recent_exams_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent results
$recent_results_stmt = $pdo->query("
    SELECT er.*, e.exam_title 
    FROM exam_results er 
    JOIN exams e ON er.exam_id = e.exam_id 
    ORDER BY er.completed_at DESC 
    LIMIT 5
");
$recent_results = $recent_results_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top performing exams
$top_exams_stmt = $pdo->query("
    SELECT 
        e.exam_id,
        e.exam_title,
        COUNT(er.result_id) as attempts,
        AVG(er.percentage) as avg_percentage,
        MAX(er.percentage) as highest_score
    FROM exams e
    LEFT JOIN exam_results er ON e.exam_id = er.exam_id
    GROUP BY e.exam_id
    ORDER BY attempts DESC, avg_percentage DESC
    LIMIT 5
");
$top_exams = $top_exams_stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --dark-bg: #0f172a;
            --dark-card: #1e293b;
            --dark-border: #334155;
            --dark-text: #cbd5e1;
            --dark-text-light: #94a3b8;
            --primary: #3b82f6;
            --primary-dark: #1d4ed8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #0ea5e9;
        }
        
        body {
            background: var(--dark-bg);
            color: var(--dark-text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--dark-card);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--dark-border);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
        
        /* Navbar */
        .admin-navbar {
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--dark-border);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .admin-navbar .nav-link {
            color: var(--dark-text-light);
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .admin-navbar .nav-link:hover,
        .admin-navbar .nav-link.active {
            color: white;
            background: rgba(59, 130, 246, 0.1);
            border-left: 3px solid var(--primary);
        }
        
        .admin-navbar .brand {
            color: var(--primary);
            font-weight: bold;
            font-size: 1.2em;
        }
        
        .admin-navbar .brand i {
            margin-right: 10px;
        }
        
        /* Dashboard Container */
        .dashboard-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--dark-card);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid var(--dark-border);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            border-color: var(--primary);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--info));
        }
        
        .stat-card.primary::before {
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        }
        
        .stat-card.success::before {
            background: linear-gradient(90deg, var(--success), #059669);
        }
        
        .stat-card.warning::before {
            background: linear-gradient(90deg, var(--warning), #d97706);
        }
        
        .stat-card.danger::before {
            background: linear-gradient(90deg, var(--danger), #dc2626);
        }
        
        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
            opacity: 0.8;
        }
        
        .stat-value {
            font-size: 2.2em;
            font-weight: bold;
            color: white;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--dark-text-light);
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        
        .stat-change {
            font-size: 0.85em;
            padding: 3px 10px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            display: inline-block;
        }
        
        /* Section Cards */
        .section-card {
            background: var(--dark-card);
            border-radius: 12px;
            border: 1px solid var(--dark-border);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .section-header {
            padding: 20px;
            border-bottom: 1px solid var(--dark-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-header h4 {
            color: white;
            margin: 0;
            font-weight: 600;
        }
        
        .section-header .view-all {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9em;
        }
        
        .section-header .view-all:hover {
            text-decoration: underline;
        }
        
        .section-body {
            padding: 20px;
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            text-align: left;
            padding: 12px 15px;
            color: var(--dark-text-light);
            font-weight: 600;
            border-bottom: 2px solid var(--dark-border);
        }
        
        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--dark-border);
        }
        
        .data-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }
        
        .status-inactive {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }
        
        /* Action Buttons */
        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            font-size: 0.85em;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .action-btn.view {
            background: rgba(59, 130, 246, 0.2);
            color: var(--primary);
        }
        
        .action-btn.edit {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning);
        }
        
        .action-btn.delete {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }
        
        .quick-action-btn {
            background: var(--dark-card);
            border: 1px solid var(--dark-border);
            color: var(--dark-text);
            padding: 15px 25px;
            border-radius: 10px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            flex: 1;
            min-width: 200px;
        }
        
        .quick-action-btn:hover {
            background: rgba(59, 130, 246, 0.1);
            border-color: var(--primary);
            color: white;
            transform: translateY(-3px);
        }
        
        .quick-action-btn i {
            font-size: 1.2em;
            color: var(--primary);
        }
        
        /* Progress Bars */
        .progress-container {
            margin: 15px 0;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            color: var(--dark-text-light);
        }
        
        .progress-bar-bg {
            height: 8px;
            background: var(--dark-border);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--info));
            border-radius: 4px;
            transition: width 1s ease-out;
        }
        
        /* Charts Container */
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .chart-card {
            background: var(--dark-card);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--dark-border);
        }
        
        .chart-title {
            color: white;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--dark-text-light);
        }
        
        .empty-state i {
            font-size: 3em;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .quick-action-btn {
                min-width: 100%;
            }
            
            .admin-navbar .nav-link {
                padding: 8px 12px;
                font-size: 0.9em;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        /* Loading Skeleton */
        .skeleton {
            background: linear-gradient(90deg, var(--dark-card) 25%, #2d3748 50%, var(--dark-card) 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="admin-navbar">
        <div class="dashboard-container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="brand">
                        <i class="fas fa-shield-alt"></i> Exam Admin
                    </div>
                    <div class="ms-4 d-flex">
                        <a href="index.php" class="nav-link active">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="manage_exams.php" class="nav-link">
                            <i class="fas fa-file-alt me-2"></i>Exams
                        </a>
                        <a href="view_results.php" class="nav-link">
                            <i class="fas fa-chart-bar me-2"></i>Results
                        </a>
                        <a href="leaderboard.php" class="nav-link">
                            <i class="fas fa-trophy me-2"></i>Leaderboard
                        </a>
                        
                    </div>
                </div>
                <div>
                    <a href="../index.php" class="action-btn view">
                        <i class="fas fa-external-link-alt me-2"></i>View Site
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="dashboard-container">
        <!-- Quick Actions -->
        <div class="quick-actions fade-in">
            <a href="create_exam.php" class="quick-action-btn">
                <i class="fas fa-plus-circle"></i>
                <div>
                    <strong>Create New Exam</strong>
                    <small>Add a new exam with questions</small>
                </div>
            </a>
            
            <a href="manage_exams.php" class="quick-action-btn">
                <i class="fas fa-edit"></i>
                <div>
                    <strong>Manage Exams</strong>
                    <small>Edit, delete or view exams</small>
                </div>
            </a>
            
            <a href="view_results.php" class="quick-action-btn">
                <i class="fas fa-chart-line"></i>
                <div>
                    <strong>View Results</strong>
                    <small>See all exam results</small>
                </div>
            </a>
            
            <a href="../leaderboard.php" class="quick-action-btn">
                <i class="fas fa-trophy"></i>
                <div>
                    <strong>Leaderboard</strong>
                    <small>Top performers ranking</small>
                </div>
            </a>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid fade-in">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_exams'] ?? 0; ?></div>
                <div class="stat-label">Total Exams</div>
                <div class="stat-change">
                    <?php echo $stats['active_exams'] ?? 0; ?> Active • <?php echo $stats['inactive_exams'] ?? 0; ?> Inactive
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_questions'] ?? 0; ?></div>
                <div class="stat-label">Total Questions</div>
                <div class="stat-change">Across all exams</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_students'] ?? 0; ?></div>
                <div class="stat-label">Total Students</div>
                <div class="stat-change"><?php echo $stats['total_attempts'] ?? 0; ?> attempts</div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value"><?php echo round($stats['avg_score'] ?? 0, 1); ?>%</div>
                <div class="stat-label">Average Score</div>
                <div class="stat-change">Overall performance</div>
            </div>
        </div>

        <!-- Recent Exams & Results -->
        <div class="row fade-in">
            <div class="col-md-6">
                <div class="section-card">
                    <div class="section-header">
                        <h4><i class="fas fa-clock me-2"></i>Recent Exams</h4>
                        <a href="manage_exams.php" class="view-all">View All</a>
                    </div>
                    <div class="section-body">
                        <?php if (empty($recent_exams)): ?>
                            <div class="empty-state">
                                <i class="fas fa-file-alt"></i>
                                <p>No exams created yet</p>
                                <a href="create_exam.php" class="action-btn view">Create First Exam</a>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Exam Title</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_exams as $exam): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($exam['exam_title']); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $exam['exam_status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo ucfirst($exam['exam_status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($exam['created_at'])); ?></td>
                                            <td>
                                                <a href="manage_questions.php?exam_id=<?php echo $exam['exam_id']; ?>" class="action-btn view me-1">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_exam.php?exam_id=<?php echo $exam['exam_id']; ?>" class="action-btn edit me-1">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete_exam.php?exam_id=<?php echo $exam['exam_id']; ?>" class="action-btn delete" onclick="return confirm('Delete this exam?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="section-card">
                    <div class="section-header">
                        <h4><i class="fas fa-chart-bar me-2"></i>Recent Results</h4>
                        <a href="view_results.php" class="view-all">View All</a>
                    </div>
                    <div class="section-body">
                        <?php if (empty($recent_results)): ?>
                            <div class="empty-state">
                                <i class="fas fa-chart-bar"></i>
                                <p>No results available</p>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Exam</th>
                                        <th>Score</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_results as $result): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($result['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($result['exam_title']); ?></td>
                                            <td>
                                                <strong><?php echo $result['marks_obtained']; ?>/<?php echo $result['total_marks']; ?></strong>
                                                <div class="progress-container">
                                                    <div class="progress-label">
                                                        <span><?php echo $result['percentage']; ?>%</span>
                                                        <span><?php echo $result['grade']; ?></span>
                                                    </div>
                                                    <div class="progress-bar-bg">
                                                        <div class="progress-bar-fill" style="width: <?php echo $result['percentage']; ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo date('M d', strtotime($result['completed_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performing Exams -->
        <div class="section-card fade-in">
            <div class="section-header">
                <h4><i class="fas fa-trophy me-2"></i>Top Performing Exams</h4>
            </div>
            <div class="section-body">
                <?php if (empty($top_exams)): ?>
                    <div class="empty-state">
                        <i class="fas fa-trophy"></i>
                        <p>No exam data available</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($top_exams as $index => $exam): 
                            $rank = $index + 1;
                            $rank_color = $rank == 1 ? 'var(--warning)' : ($rank == 2 ? 'var(--dark-text-light)' : ($rank == 3 ? '#cd7f32' : 'var(--dark-border)'));
                        ?>
                            <div class="col-md-6 mb-3">
                                <div class="stat-card" style="border-color: <?php echo $rank_color; ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3" style="color: <?php echo $rank_color; ?>; font-size: 1.5em; font-weight: bold;">
                                            #<?php echo $rank; ?>
                                        </div>
                                        <div style="flex: 1;">
                                            <h5 style="color: white; margin-bottom: 5px;"><?php echo htmlspecialchars($exam['exam_title']); ?></h5>
                                            <div class="d-flex justify-content-between" style="color: var(--dark-text-light); font-size: 0.9em;">
                                                <span><?php echo $exam['attempts']; ?> attempts</span>
                                                <span>Avg: <?php echo round($exam['avg_percentage'], 1); ?>%</span>
                                                <span>High: <?php echo $exam['highest_score']; ?>%</span>
                                            </div>
                                            <div class="progress-container mt-2">
                                                <div class="progress-bar-bg">
                                                    <div class="progress-bar-fill" style="width: <?php echo $exam['avg_percentage'] ?? 0; ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- System Status -->
        <div class="section-card fade-in">
            <div class="section-header">
                <h4><i class="fas fa-server me-2"></i>System Status</h4>
            </div>
            <div class="section-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <div class="progress-label">
                                <span>Database</span>
                                <span><i class="fas fa-circle text-success"></i> Online</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width: 100%; background: var(--success);"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <div class="progress-label">
                                <span>Storage</span>
                                <span>65% used</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width: 65%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <div class="progress-label">
                                <span>Performance</span>
                                <span>Excellent</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width: 92%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <small style="color: var(--dark-text-light);">
                        <i class="fas fa-info-circle me-1"></i>
                        Last updated: <?php echo date('F j, Y H:i:s'); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Animate progress bars on load
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-bar-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 300);
            });
            
            // Add hover effects to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Auto-refresh data every 30 seconds
            setInterval(() => {
                fetch('?refresh=1')
                    .then(response => response.text())
                    .then(data => {
                        // Update only specific elements if needed
                        console.log('Data refreshed');
                    });
            }, 30000);
        });
    </script>
</body>
</html>