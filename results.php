<?php
// results.php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Fetch user's exam results
// Note: You need to create exam_results table with appropriate structure
$results = [];
$overall_stats = [
    'total_exams' => 0,
    'average_score' => 0,
    'highest_score' => 0,
    'passed_exams' => 0
];

// Fetch user's exam results from database
try {
    $stmt = $pdo->prepare("
        SELECT 
            er.*, 
            e.exam_title,
            e.exam_duration,
            (SELECT COUNT(*) FROM questions WHERE exam_id = er.exam_id) as total_questions
        FROM exam_results er 
        JOIN exams e ON er.exam_id = e.exam_id 
        WHERE er.student_name = ?
        ORDER BY er.completed_at DESC
    ");
    $stmt->execute([$_SESSION['full_name']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If table doesn't exist or query fails, show empty results
    $results = [];
}

// Calculate overall stats
if (!empty($results)) {
    $overall_stats['total_exams'] = count($results);
    $total_percentage = 0;
    $highest = 0;
    $passed = 0;
    
    foreach ($results as $result) {
        $total_percentage += $result['percentage'];
        if ($result['percentage'] > $highest) {
            $highest = $result['percentage'];
        }
        if ($result['status'] == 'PASS') {
            $passed++;
        }
    }
    
    $overall_stats['average_score'] = round($total_percentage / count($results), 1);
    $overall_stats['highest_score'] = $highest;
    $overall_stats['passed_exams'] = $passed;
}

// Check if viewing specific exam result
$view_exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
?>
<!DOCTYPE html>
<html lang="en" class="dark-theme">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results - ExamPro</title>
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
            --warning: #f59e0b;
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
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--accent);
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .nav-link {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .nav-link:hover {
            color: var(--accent);
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            color: white;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        
        .btn-secondary:hover {
            background: var(--bg-secondary);
            border-color: var(--accent);
            color: var(--accent);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), #0d9c6f);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .results-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .results-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--text-primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .results-header p {
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: var(--bg-secondary);
            padding: 1.5rem;
            border-radius: 15px;
            border: 1px solid var(--border);
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: var(--accent);
            font-size: 1.5rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        /* Results Table */
        .results-table-container {
            background: var(--bg-secondary);
            border-radius: 15px;
            border: 1px solid var(--border);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        
        .table-header h2 {
            font-size: 1.3rem;
            color: var(--text-primary);
        }
        
        .table-actions {
            display: flex;
            gap: 1rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: rgba(15, 23, 42, 0.6);
        }
        
        th {
            padding: 1rem 1.5rem;
            text-align: left;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.9rem;
            border-bottom: 1px solid var(--border);
        }
        
        td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        
        tbody tr {
            transition: background 0.3s;
        }
        
        tbody tr:hover {
            background: rgba(59, 130, 246, 0.05);
        }
        
        .exam-title {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .exam-meta {
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-passed {
            background: rgba(16, 185, 129, 0.1);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .status-failed {
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .score-cell {
            text-align: center;
        }
        
        .score-value {
            font-size: 1.2rem;
            font-weight: 700;
        }
        
        .score-passed {
            color: var(--success);
        }
        
        .score-failed {
            color: var(--danger);
        }
        
        .progress-bar {
            height: 6px;
            background: var(--border);
            border-radius: 3px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 3px;
        }
        
        .progress-success {
            background: linear-gradient(90deg, var(--success), #0d9c6f);
        }
        
        .progress-warning {
            background: linear-gradient(90deg, var(--warning), #d97706);
        }
        
        .progress-danger {
            background: linear-gradient(90deg, var(--danger), #dc2626);
        }
        
        /* Performance Chart */
        .performance-chart {
            background: var(--bg-secondary);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid var(--border);
            margin-top: 2rem;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        .chart-placeholder {
            height: 100%;
            background: rgba(15, 23, 42, 0.3);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-size: 1.1rem;
        }
        
        /* Exam Detail View */
        .exam-detail-view {
            background: var(--bg-secondary);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid var(--border);
            margin-bottom: 2rem;
        }
        
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        
        .detail-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .detail-stat {
            text-align: center;
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.3);
            border-radius: 10px;
            border: 1px solid var(--border);
        }
        
        .detail-stat .value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .detail-stat .label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .question-analysis {
            margin-top: 2rem;
        }
        
        .question-item {
            background: rgba(15, 23, 42, 0.3);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid var(--border);
        }
        
        .question-status {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .status-correct {
            background: var(--success);
        }
        
        .status-incorrect {
            background: var(--danger);
        }
        
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .detail-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .navbar {
                padding: 1rem;
            }
            
            .nav-links {
                gap: 1rem;
            }
            
            .results-header h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .detail-stats {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .table-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <a href="dashboard.php" class="logo">
            <i class="fas fa-graduation-cap"></i>
            <span>ExamPro</span>
        </a>
        
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="results.php" class="nav-link">
                <i class="fas fa-chart-bar"></i> Results
            </a>
            <a href="edit_profile.php" class="nav-link">
                <i class="fas fa-user"></i> Profile
            </a>
            <a href="logout.php" class="btn btn-secondary">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container">
        <div class="results-header">
            <h1>My Examination Results</h1>
            <p>Track your performance, view detailed analytics, and monitor your progress</p>
        </div>
        
        <?php if ($view_exam_id > 0): ?>
            <!-- Detailed Exam Result View -->
            <?php 
            $selected_exam = null;
            foreach ($results as $result) {
                if ($result['exam_id'] == $view_exam_id) {
                    $selected_exam = $result;
                    break;
                }
            }
            
            if ($selected_exam):
            ?>
            <div class="exam-detail-view">
                <div class="detail-header">
                    <div>
                        <h2 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($selected_exam['exam_title']); ?></h2>
                        <p style="color: var(--text-secondary);">
                            Completed: <?php echo date('F d, Y', strtotime($selected_exam['completed_at'])); ?>
                        </p>
                    </div>
                    <div>
                        <span class="status-badge <?php echo $selected_exam['status'] == 'PASS' ? 'status-passed' : 'status-failed'; ?>">
                            <?php echo ucfirst(strtolower($selected_exam['status'])); ?>
                        </span>
                    </div>
                </div>
                
                <div class="detail-stats">
                    <div class="detail-stat">
                        <div class="value <?php echo $selected_exam['status'] == 'PASS' ? 'score-passed' : 'score-failed'; ?>">
                            <?php echo $selected_exam['marks_obtained']; ?>/<?php echo $selected_exam['total_marks']; ?>
                        </div>
                        <div class="label">Marks Obtained</div>
                    </div>
                    
                    <div class="detail-stat">
                        <div class="value <?php echo $selected_exam['percentage'] >= 60 ? 'score-passed' : ($selected_exam['percentage'] >= 40 ? 'score-warning' : 'score-failed'); ?>">
                            <?php echo $selected_exam['percentage']; ?>%
                        </div>
                        <div class="label">Percentage</div>
                    </div>
                    
                    <div class="detail-stat">
                        <div class="value">
                            <?php echo $selected_exam['duration']; ?>
                        </div>
                        <div class="label">Duration</div>
                    </div>
                    
                    <div class="detail-stat">
                        <div class="value">
                            <?php echo $selected_exam['questions']; ?>
                        </div>
                        <div class="label">Questions</div>
                    </div>
                </div>
                
                <!-- Performance Summary -->
                <div style="margin-top: 2rem;">
                    <h3 style="margin-bottom: 1rem; color: var(--text-primary);">
                        <i class="fas fa-chart-pie"></i> Performance Summary
                    </h3>
                    <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                        <div style="flex: 1; padding: 1rem; background: rgba(16, 185, 129, 0.1); border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.3);">
                            <div style="color: #6ee7b7; font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem;">
                                <?php echo round(($selected_exam['obtained_marks'] / $selected_exam['questions']) * 100, 1); ?>%
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.9rem;">Accuracy Rate</div>
                        </div>
                        <div style="flex: 1; padding: 1rem; background: rgba(59, 130, 246, 0.1); border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.3);">
                            <div style="color: var(--accent); font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem;">
                                <?php echo $selected_exam['percentage'] >= $selected_exam['passing_marks'] ? 'Passed' : 'Failed'; ?>
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.9rem;">Result Status</div>
                        </div>
                    </div>
                </div>
                
                <!-- Question Analysis (Placeholder) -->
                <div class="question-analysis">
                    <h3 style="margin-bottom: 1rem; color: var(--text-primary);">
                        <i class="fas fa-list-ol"></i> Question Analysis
                    </h3>
                    <div style="background: rgba(15, 23, 42, 0.3); padding: 1.5rem; border-radius: 10px; border: 1px solid var(--border);">
                        <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                            Detailed question-by-question analysis will be available soon.
                        </p>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <?php 
                            $correct_questions = floor($selected_exam['obtained_marks'] / ($selected_exam['total_marks'] / $selected_exam['questions']));
                            $incorrect_questions = $selected_exam['questions'] - $correct_questions;
                            
                            for ($i = 1; $i <= $selected_exam['questions']; $i++) {
                                $status = $i <= $correct_questions ? 'correct' : 'incorrect';
                                echo '<span class="question-status status-' . $status . '" title="Question ' . $i . ': ' . ucfirst($status) . '"></span>';
                            }
                            ?>
                        </div>
                        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span class="question-status status-correct"></span>
                                <span style="color: var(--text-secondary); font-size: 0.9rem;">
                                    Correct: <?php echo $correct_questions; ?>
                                </span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span class="question-status status-incorrect"></span>
                                <span style="color: var(--text-secondary); font-size: 0.9rem;">
                                    Incorrect: <?php echo $incorrect_questions; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <a href="results.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to All Results
                    </a>
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Result
                    </button>
                    <button class="btn btn-success" onclick="alert('Certificate will be available soon!')">
                        <i class="fas fa-award"></i> Download Certificate
                    </button>
                </div>
            </div>
            <?php else: ?>
                <div style="background: var(--bg-secondary); padding: 2rem; border-radius: 15px; border: 1px solid var(--border); text-align: center;">
                    <i class="fas fa-exclamation-circle" style="font-size: 3rem; color: var(--warning); margin-bottom: 1rem;"></i>
                    <h3>Exam Result Not Found</h3>
                    <p style="color: var(--text-secondary); margin: 1rem 0;">The requested exam result could not be found.</p>
                    <a href="results.php" class="btn btn-primary">Back to Results</a>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Overall Results View -->
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-number"><?php echo $overall_stats['total_exams']; ?></div>
                    <div class="stat-label">Total Exams Taken</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-number"><?php echo $overall_stats['average_score']; ?>%</div>
                    <div class="stat-label">Average Score</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-number"><?php echo $overall_stats['highest_score']; ?>%</div>
                    <div class="stat-label">Highest Score</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?php echo $overall_stats['passed_exams']; ?></div>
                    <div class="stat-label">Exams Passed</div>
                </div>
            </div>
            
            <!-- Results Table -->
            <div class="results-table-container">
                <div class="table-header">
                    <h2><i class="fas fa-history"></i> Exam History</h2>
                    <div class="table-actions">
                        <button class="btn btn-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print All
                        </button>
                        <button class="btn btn-secondary" onclick="alert('Export feature coming soon!')">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Exam</th>
                            <th>Date</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Performance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): ?>
                        <tr>
                            <td>
                                <div class="exam-title"><?php echo htmlspecialchars($result['exam_title']); ?></div>
                                <div class="exam-meta">
                                    <?php echo $result['duration']; ?> • <?php echo $result['questions']; ?> questions
                                </div>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($result['completed_at'])); ?>
                            </td>
                            <td class="score-cell">
                                <div class="score-value <?php echo $result['status'] == 'PASS' ? 'score-passed' : 'score-failed'; ?>">
                                    <?php echo $result['marks_obtained']; ?>/<?php echo $result['total_marks']; ?>
                                </div>
                                <div style="color: var(--text-secondary); font-size: 0.9rem;">
                                    <?php echo $result['percentage']; ?>%
                                </div>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $result['status'] == 'PASS' ? 'status-passed' : 'status-failed'; ?>">
                                    <?php echo ucfirst(strtolower($result['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div style="color: var(--text-secondary); font-size: 0.9rem;">
                                    <?php echo $result['percentage']; ?>%
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill <?php 
                                        echo $result['percentage'] >= 80 ? 'progress-success' : 
                                            ($result['percentage'] >= 50 ? 'progress-warning' : 'progress-danger');
                                    ?>" style="width: <?php echo min($result['percentage'], 100); ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <a href="results.php?exam_id=<?php echo $result['exam_id']; ?>" class="btn btn-secondary" style="padding: 0.25rem 0.75rem;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Performance Chart -->
            <div class="performance-chart">
                <div class="chart-header">
                    <h2><i class="fas fa-chart-bar"></i> Performance Trend</h2>
                    <select style="padding: 0.5rem; background: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--border); border-radius: 6px;">
                        <option>Last 6 Months</option>
                        <option>Last Year</option>
                        <option>All Time</option>
                    </select>
                </div>
                <div class="chart-container">
                    <div class="chart-placeholder">
                        <div style="text-align: center;">
                            <i class="fas fa-chart-line" style="font-size: 3rem; color: var(--accent); margin-bottom: 1rem;"></i>
                            <p>Performance chart visualization<br>will be available soon</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Additional Information -->
            <div style="margin-top: 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 10px; border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1rem; color: var(--text-primary);">
                        <i class="fas fa-lightbulb"></i> Tips for Improvement
                    </h3>
                    <ul style="color: var(--text-secondary); padding-left: 1.5rem;">
                        <li style="margin-bottom: 0.5rem;">Review questions you answered incorrectly</li>
                        <li style="margin-bottom: 0.5rem;">Focus on topics with lowest scores</li>
                        <li style="margin-bottom: 0.5rem;">Take practice exams regularly</li>
                        <li>Manage your time better during exams</li>
                    </ul>
                </div>
                
                <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 10px; border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 1rem; color: var(--text-primary);">
                        <i class="fas fa-calendar-check"></i> Upcoming Exams
                    </h3>
                    <div style="color: var(--text-secondary);">
                        <p style="margin-bottom: 0.5rem;"><strong>Science Advanced:</strong> Dec 10, 2025</p>
                        <p style="margin-bottom: 0.5rem;"><strong>Mathematics Quiz:</strong> Dec 15, 2025</p>
                        <p><strong>English Literature:</strong> Dec 20, 2025</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Sort table functionality
        function sortTable(columnIndex) {
            const table = document.querySelector('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                const aValue = a.cells[columnIndex].textContent.trim();
                const bValue = b.cells[columnIndex].textContent.trim();
                
                // Try to parse as number first
                const aNum = parseFloat(aValue);
                const bNum = parseFloat(bValue);
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return aNum - bNum;
                }
                
                // Otherwise sort as string
                return aValue.localeCompare(bValue);
            });
            
            // Reverse if already sorted
            if (table.dataset.sortedColumn == columnIndex) {
                rows.reverse();
                table.dataset.sortedColumn = -1;
            } else {
                table.dataset.sortedColumn = columnIndex;
            }
            
            // Reappend rows
            rows.forEach(row => tbody.appendChild(row));
        }
        
        // Add click handlers to table headers
        document.querySelectorAll('th').forEach((th, index) => {
            th.style.cursor = 'pointer';
            th.addEventListener('click', () => sortTable(index));
        });
        
        // Filter functionality
        function filterTable(status) {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if (status === 'all') {
                    row.style.display = '';
                } else {
                    const rowStatus = row.querySelector('.status-badge').textContent.toLowerCase();
                    row.style.display = rowStatus === status ? '' : 'none';
                }
            });
        }
    </script>
</body>
</html>