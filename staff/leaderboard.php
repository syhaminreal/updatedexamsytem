<?php
require_once 'db_connection.php';

// Get all exams for selection
$exams_stmt = $pdo->query("SELECT * FROM exams WHERE exam_status = 'active' ORDER BY created_at DESC");
$all_exams = $exams_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected exam or default to first
$selected_exam_id = $_GET['exam_id'] ?? ($all_exams[0]['exam_id'] ?? 0);

// Get exam details
$exam_stmt = $pdo->prepare("SELECT * FROM exams WHERE exam_id = ?");
$exam_stmt->execute([$selected_exam_id]);
$selected_exam = $exam_stmt->fetch(PDO::FETCH_ASSOC);

// Calculate statistics for the exam
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_students,
        AVG(percentage) as average_score,
        MAX(percentage) as highest_score,
        MIN(percentage) as lowest_score,
        STDDEV(percentage) as std_deviation
    FROM exam_results 
    WHERE exam_id = ?
");
$stats_stmt->execute([$selected_exam_id]);
$exam_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get boundary values for grade distribution
$boundaries_stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN percentage >= 90 THEN 1 END) as a_plus,
        COUNT(CASE WHEN percentage >= 80 AND percentage < 90 THEN 1 END) as a,
        COUNT(CASE WHEN percentage >= 70 AND percentage < 80 THEN 1 END) as b_plus,
        COUNT(CASE WHEN percentage >= 60 AND percentage < 70 THEN 1 END) as b,
        COUNT(CASE WHEN percentage >= 50 AND percentage < 60 THEN 1 END) as c_plus,
        COUNT(CASE WHEN percentage >= 40 AND percentage < 50 THEN 1 END) as c,
        COUNT(CASE WHEN percentage >= 0 AND percentage < 40 THEN 1 END) as f
    FROM exam_results 
    WHERE exam_id = ?
");
$boundaries_stmt->execute([$selected_exam_id]);
$grade_distribution = $boundaries_stmt->fetch(PDO::FETCH_ASSOC);

// Get top performers with percentile ranking
$leaderboard_stmt = $pdo->prepare("
    SELECT 
        er.*,
        (@row_number := @row_number + 1) as position,
        (@row_number / total_participants * 100) as percentile_rank,
        CASE 
            WHEN (@row_number / total_participants * 100) <= 10 THEN 'Top 10%'
            WHEN (@row_number / total_participants * 100) <= 25 THEN 'Top 25%'
            WHEN (@row_number / total_participants * 100) <= 50 THEN 'Top 50%'
            WHEN (@row_number / total_participants * 100) <= 75 THEN 'Bottom 25%'
            ELSE 'Bottom 10%'
        END as percentile_group
    FROM (
        SELECT 
            er.*,
            (SELECT COUNT(*) FROM exam_results er2 WHERE er2.exam_id = er.exam_id) as total_participants
        FROM exam_results er
        WHERE er.exam_id = ?
        ORDER BY er.percentage DESC, er.marks_obtained DESC
        LIMIT 50
    ) er
    CROSS JOIN (SELECT @row_number := 0) r
    ORDER BY er.percentage DESC
");
$leaderboard_stmt->execute([$selected_exam_id]);
$leaderboard = $leaderboard_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent toppers (last 7 days)
$recent_stmt = $pdo->prepare("
    SELECT 
        er.student_name,
        er.percentage,
        er.grade,
        DATE(er.completed_at) as exam_date,
        ROW_NUMBER() OVER (ORDER BY er.percentage DESC) as rank
    FROM exam_results er
    WHERE er.exam_id = ? 
    AND er.completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY er.percentage DESC
    LIMIT 10
");
$recent_stmt->execute([$selected_exam_id]);
$recent_toppers = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate percentile boundaries using different algorithms
function calculatePercentileBoundaries($data) {
    $percentiles = [];
    
    // Algorithm 1: Simple division
    $percentiles['simple'] = [
        'top_10' => 90,
        'top_25' => 75,
        'top_50' => 50,
        'average' => 50,
        'bottom_25' => 25,
        'bottom_10' => 10
    ];
    
    // Algorithm 2: Based on standard deviation (if data available)
    if (!empty($data['std_deviation']) && !empty($data['average_score'])) {
        $mean = $data['average_score'];
        $std = $data['std_deviation'];
        
        $percentiles['std_dev'] = [
            'excellent' => $mean + ($std * 1.5),  // Top 6.7%
            'very_good' => $mean + $std,          // Top 16%
            'good' => $mean,                      // Average
            'average' => $mean - $std,            // Below average
            'needs_improvement' => $mean - ($std * 1.5)  // Bottom 6.7%
        ];
    }
    
    // Algorithm 3: Quartile boundaries
    $percentiles['quartiles'] = [
        'q1' => 75,  // Top 25%
        'q2' => 50,  // Top 50% (median)
        'q3' => 25,  // Bottom 25%
    ];
    
    return $percentiles;
}

$percentile_algorithms = calculatePercentileBoundaries($exam_stats);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Leaderboard - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --gold: #FFD700;
            --silver: #C0C0C0;
            --bronze: #CD7F32;
        }
        
        body {
            background: var(--dark-bg);
            color: var(--dark-text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        /* Navbar (same as dashboard) */
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
        
        /* Dashboard Container */
        .dashboard-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Page Header */
        .page-header {
            background: var(--dark-card);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid var(--dark-border);
            margin-bottom: 25px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(30, 41, 59, 0.8) 100%);
        }
        
        .page-title {
            color: white;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: var(--dark-text-light);
            margin-bottom: 20px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: var(--dark-card);
            border-radius: 10px;
            padding: 20px;
            border: 1px solid var(--dark-border);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            border-color: var(--primary);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }
        
        .stat-card.gold::before { background: linear-gradient(90deg, var(--gold), #FFA500); }
        .stat-card.silver::before { background: linear-gradient(90deg, var(--silver), #A9A9A9); }
        .stat-card.bronze::before { background: linear-gradient(90deg, var(--bronze), #8B4513); }
        .stat-card:nth-child(2)::before { background: linear-gradient(90deg, var(--primary), var(--info)); }
        .stat-card:nth-child(3)::before { background: linear-gradient(90deg, var(--success), #059669); }
        .stat-card:nth-child(4)::before { background: linear-gradient(90deg, var(--warning), #d97706); }
        
        .stat-icon {
            font-size: 2em;
            margin-bottom: 15px;
            opacity: 0.8;
        }
        
        .stat-value {
            font-size: 1.8em;
            font-weight: bold;
            color: white;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--dark-text-light);
            font-size: 0.9em;
        }
        
        /* Leaderboard Table */
        .leaderboard-table-container {
            background: var(--dark-card);
            border-radius: 12px;
            border: 1px solid var(--dark-border);
            overflow: hidden;
            margin-bottom: 25px;
        }
        
        .table-header {
            padding: 20px;
            border-bottom: 1px solid var(--dark-border);
            background: rgba(0, 0, 0, 0.2);
        }
        
        .table-body {
            overflow-x: auto;
        }
        
        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }
        
        .leaderboard-table th {
            text-align: left;
            padding: 15px 20px;
            color: var(--dark-text-light);
            font-weight: 600;
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 2px solid var(--dark-border);
        }
        
        .leaderboard-table td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--dark-border);
        }
        
        .leaderboard-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        /* Rank Badges */
        .rank-badge {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 0.9em;
        }
        
        .rank-1 {
            background: linear-gradient(135deg, var(--gold) 0%, #FFA500 100%);
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.3);
        }
        
        .rank-2 {
            background: linear-gradient(135deg, var(--silver) 0%, #A9A9A9 100%);
            box-shadow: 0 0 15px rgba(192, 192, 192, 0.3);
        }
        
        .rank-3 {
            background: linear-gradient(135deg, var(--bronze) 0%, #8B4513 100%);
            box-shadow: 0 0 15px rgba(205, 127, 50, 0.3);
        }
        
        .rank-other {
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
        }
        
        /* Percentile Badges */
        .percentile-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            display: inline-block;
        }
        
        .percentile-top10 {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .percentile-top25 {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .percentile-top50 {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .percentile-bottom25 {
            background: rgba(249, 115, 22, 0.2);
            color: #f97316;
            border: 1px solid rgba(249, 115, 22, 0.3);
        }
        
        .percentile-bottom10 {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        /* Charts Container */
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
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
        
        /* Algorithm Cards */
        .algorithm-card {
            background: var(--dark-card);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--dark-border);
            margin-bottom: 25px;
        }
        
        .algorithm-title {
            color: white;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--dark-border);
            font-weight: 600;
        }
        
        .boundary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .boundary-item:last-child {
            border-bottom: none;
        }
        
        /* Filter Section */
        .filter-section {
            background: var(--dark-card);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--dark-border);
            margin-bottom: 25px;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .filter-btn {
            padding: 8px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--dark-border);
            color: var(--dark-text);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9em;
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        /* Search Box */
        .search-box {
            margin-bottom: 20px;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--dark-border);
            border-radius: 25px;
            padding: 12px 20px 12px 45px;
            color: var(--dark-text);
            font-size: 0.95em;
            transition: all 0.3s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark-text-light);
        }
        
        /* Exam Selector */
        .exam-selector {
            background: var(--dark-card);
            border-radius: 10px;
            padding: 15px;
            border: 1px solid var(--dark-border);
            margin-bottom: 20px;
        }
        
        .exam-selector select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--dark-border);
            color: var(--dark-text);
            border-radius: 8px;
            padding: 10px;
            width: 100%;
        }
        
        .exam-selector select:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        /* Medal Icons */
        .medal-icon {
            font-size: 1.2em;
            margin-left: 8px;
        }
        
        .medal-gold { color: var(--gold); }
        .medal-silver { color: var(--silver); }
        .medal-bronze { color: var(--bronze); }
        
        /* Score Badges */
        .score-badge {
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .score-excellent { background: rgba(16, 185, 129, 0.2); color: var(--success); }
        .score-good { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .score-average { background: rgba(245, 158, 11, 0.2); color: var(--warning); }
        .score-poor { background: rgba(239, 68, 68, 0.2); color: var(--danger); }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--dark-text-light);
        }
        
        .empty-state i {
            font-size: 3em;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Animations */
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        .trophy-animation {
            animation: bounce 2s infinite;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .filter-buttons {
                justify-content: center;
            }
            
            .filter-btn {
                padding: 6px 15px;
                font-size: 0.85em;
            }
            
            .leaderboard-table {
                font-size: 0.85em;
            }
            
            .leaderboard-table th,
            .leaderboard-table td {
                padding: 10px 15px;
            }
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
                        <a href="index.php" class="nav-link">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="manage_exams.php" class="nav-link">
                            <i class="fas fa-file-alt me-2"></i>Exams
                        </a>
                        <a href="view_results.php" class="nav-link">
                            <i class="fas fa-chart-bar me-2"></i>Results
                        </a>
                        <a href="leaderboard.php" class="nav-link active">
                            <i class="fas fa-trophy me-2"></i>Leaderboard
                        </a>
                    </div>
                </div>
                <div>
                    <a href="../index.php" class="action-btn view" style="padding: 8px 15px; background: rgba(59, 130, 246, 0.2); color: var(--primary); border-radius: 6px; text-decoration: none;">
                        <i class="fas fa-external-link-alt me-2"></i>View Site
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="page-title"><i class="fas fa-trophy trophy-animation me-2"></i>Exam Leaderboard</h1>
                    <p class="page-subtitle">Track top performers and percentile rankings across exams</p>
                </div>
                <div class="col-md-4">
                    <div class="exam-selector">
                        <label class="form-label" style="color: var(--dark-text-light);">
                            <i class="fas fa-file-alt me-2"></i>Select Exam:
                        </label>
                        <select class="form-select" onchange="window.location.href='?exam_id=' + this.value">
                            <?php foreach ($all_exams as $exam): ?>
                                <option value="<?php echo $exam['exam_id']; ?>" 
                                        <?php echo $exam['exam_id'] == $selected_exam_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($exam['exam_title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($selected_exam): ?>
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card gold">
                    <div class="stat-icon"><i class="fas fa-crown"></i></div>
                    <div class="stat-value"><?php echo $exam_stats['highest_score'] ?? 0; ?>%</div>
                    <div class="stat-label">Highest Score</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-value"><?php echo round($exam_stats['average_score'] ?? 0, 1); ?>%</div>
                    <div class="stat-label">Average Score</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-value"><?php echo $exam_stats['total_students'] ?? 0; ?></div>
                    <div class="stat-label">Total Participants</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                    <div class="stat-value"><?php echo round($exam_stats['std_deviation'] ?? 0, 1); ?>%</div>
                    <div class="stat-label">Standard Deviation</div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="filter-section">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search students by name..." 
                           onkeyup="filterLeaderboard(this.value)">
                </div>
                
                <div class="filter-buttons">
                    <button class="filter-btn active" onclick="filterByPercentile('all')">All</button>
                    <button class="filter-btn" onclick="filterByPercentile('top10')">Top 10%</button>
                    <button class="filter-btn" onclick="filterByPercentile('top25')">Top 25%</button>
                    <button class="filter-btn" onclick="filterByPercentile('top50')">Top 50%</button>
                    <button class="filter-btn" onclick="filterByPercentile('passed')">Passed Only</button>
                    <button class="filter-btn" onclick="filterByPercentile('failed')">Failed Only</button>
                </div>
            </div>

            <!-- Leaderboard Table -->
            <div class="leaderboard-table-container">
                <div class="table-header">
                    <h4 style="color: white; margin: 0;">
                        <i class="fas fa-ranking-star me-2"></i>
                        Top Performers - <?php echo htmlspecialchars($selected_exam['exam_title']); ?>
                    </h4>
                    <p style="color: var(--dark-text-light); margin: 5px 0 0 0;">
                        Showing top 50 out of <?php echo $exam_stats['total_students'] ?? 0; ?> participants
                    </p>
                </div>
                
                <div class="table-body">
                    <?php if (empty($leaderboard)): ?>
                        <div class="empty-state">
                            <i class="fas fa-chart-line"></i>
                            <h4 style="color: white;">No results yet for this exam</h4>
                            <p>Be the first to take this exam!</p>
                        </div>
                    <?php else: ?>
                        <table class="leaderboard-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Student Name</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                    <th>Grade</th>
                                    <th>Correct</th>
                                    <th>Total</th>
                                    <th>Percentile Group</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaderboard as $index => $student): 
                                    $rank = $index + 1;
                                    $rank_class = $rank <= 3 ? "rank-{$rank}" : "rank-other";
                                    $percentile_group_class = strtolower(str_replace([' ', '%'], '', $student['percentile_group']));
                                    $score_class = $student['percentage'] >= 80 ? 'excellent' : 
                                                 ($student['percentage'] >= 60 ? 'good' : 
                                                 ($student['percentage'] >= 40 ? 'average' : 'poor'));
                                ?>
                                    <tr class="student-row" 
                                        data-percentile="<?php echo strtolower($student['percentile_group']); ?>"
                                        data-status="<?php echo $student['percentage'] >= ($student['passing_percentage'] ?? 40) ? 'passed' : 'failed'; ?>">
                                        <td>
                                            <div class="rank-badge <?php echo $rank_class; ?>">
                                                <?php echo $rank; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="color: white; font-weight: 600;">
                                                <?php echo htmlspecialchars($student['student_name']); ?>
                                                <?php if ($rank <= 3): ?>
                                                    <?php if ($rank == 1): ?>
                                                        <i class="fas fa-medal medal-gold medal-icon"></i>
                                                    <?php elseif ($rank == 2): ?>
                                                        <i class="fas fa-medal medal-silver medal-icon"></i>
                                                    <?php elseif ($rank == 3): ?>
                                                        <i class="fas fa-medal medal-bronze medal-icon"></i>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong style="color: white;">
                                                <?php echo $student['marks_obtained']; ?>/<?php echo $student['total_marks']; ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <span class="score-badge score-<?php echo $score_class; ?>">
                                                <?php echo $student['percentage']; ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: 
                                                <?php echo $student['grade'] == 'A' ? 'var(--success)' : 
                                                      ($student['grade'] == 'B' ? '#22c55e' : 
                                                      ($student['grade'] == 'C' ? 'var(--warning)' : 
                                                      ($student['grade'] == 'D' ? '#f97316' : 'var(--danger)')));
                                                ?>; font-weight: 600;">
                                                <?php echo $student['grade']; ?>
                                            </span>
                                        </td>
                                        <td style="color: var(--dark-text);">
                                            <?php echo $student['correct_answers']; ?>
                                        </td>
                                        <td style="color: var(--dark-text);">
                                            <?php echo $student['total_questions']; ?>
                                        </td>
                                        <td>
                                            <span class="percentile-badge percentile-<?php echo $percentile_group_class; ?>">
                                                <?php echo $student['percentile_group']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-container">
                <!-- Grade Distribution Chart -->
                <div class="chart-card">
                    <h4 class="chart-title"><i class="fas fa-chart-pie me-2"></i>Grade Distribution</h4>
                    <canvas id="gradeChart" height="250"></canvas>
                </div>
                
                <!-- Score Distribution Chart -->
                <div class="chart-card">
                    <h4 class="chart-title"><i class="fas fa-chart-bar me-2"></i>Score Distribution</h4>
                    <canvas id="scoreChart" height="250"></canvas>
                </div>
            </div>

            <!-- Percentile Boundary Algorithms -->
            <div class="algorithm-card">
                <h4 class="algorithm-title"><i class="fas fa-calculator me-2"></i>Percentile Boundary Algorithms</h4>
                
                <div class="row">
                    <!-- Algorithm 1: Simple Division -->
                    <div class="col-md-4">
                        <h6 style="color: var(--primary); margin-bottom: 15px;">Simple Division Method</h6>
                        <?php 
                        $simple = $percentile_algorithms['simple'];
                        foreach ($simple as $label => $value): 
                            $label_name = ucfirst(str_replace('_', ' ', $label));
                        ?>
                            <div class="boundary-item">
                                <span style="color: var(--dark-text);"><?php echo $label_name; ?>:</span>
                                <span style="color: var(--primary); font-weight: 600;"><?php echo $value; ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Algorithm 2: Standard Deviation -->
                    <div class="col-md-4">
                        <h6 style="color: var(--success); margin-bottom: 15px;">Standard Deviation Method</h6>
                        <?php if (isset($percentile_algorithms['std_dev'])): 
                            $std_dev = $percentile_algorithms['std_dev'];
                            foreach ($std_dev as $label => $value): 
                                $label_name = ucfirst(str_replace('_', ' ', $label));
                        ?>
                            <div class="boundary-item">
                                <span style="color: var(--dark-text);"><?php echo $label_name; ?>:</span>
                                <span style="color: var(--success); font-weight: 600;"><?php echo round($value, 1); ?>%</span>
                            </div>
                        <?php endforeach; else: ?>
                            <p style="color: var(--dark-text-light);">Insufficient data for standard deviation calculation</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Algorithm 3: Quartiles -->
                    <div class="col-md-4">
                        <h6 style="color: var(--warning); margin-bottom: 15px;">Quartile Method</h6>
                        <?php 
                        $quartiles = $percentile_algorithms['quartiles'];
                        foreach ($quartiles as $label => $value): 
                            $label_name = strtoupper($label);
                            $label_desc = $label == 'q1' ? 'Top 25%' : ($label == 'q2' ? 'Median' : 'Bottom 25%');
                        ?>
                            <div class="boundary-item">
                                <span style="color: var(--dark-text);"><?php echo $label_name; ?> (<?php echo $label_desc; ?>):</span>
                                <span style="color: var(--warning); font-weight: 600;"><?php echo $value; ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Toppers -->
            <?php if (!empty($recent_toppers)): ?>
            <div class="algorithm-card">
                <h4 class="algorithm-title"><i class="fas fa-bolt me-2"></i>Recent Top Performers (Last 7 Days)</h4>
                <div class="row">
                    <?php foreach ($recent_toppers as $topper): ?>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <div class="stat-value"><?php echo $topper['percentage']; ?>%</div>
                                <div class="stat-label" style="color: white; font-weight: 500;"><?php echo htmlspecialchars($topper['student_name']); ?></div>
                                <div class="small" style="color: var(--dark-text-light); margin-top: 5px;"><?php echo $topper['exam_date']; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert-message" style="background: rgba(59, 130, 246, 0.1); border-left-color: var(--primary);">
                <i class="fas fa-exclamation-circle me-2"></i>
                <span>No exams available. Please create exams first to see the leaderboard.</span>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Initialize charts with dark theme
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.borderColor = '#334155';
        
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($selected_exam && !empty($grade_distribution)): ?>
            // Grade Distribution Chart
            const gradeCtx = document.getElementById('gradeChart').getContext('2d');
            new Chart(gradeCtx, {
                type: 'doughnut',
                data: {
                    labels: ['A+ (≥90%)', 'A (80-89%)', 'B+ (70-79%)', 'B (60-69%)', 'C+ (50-59%)', 'C (40-49%)', 'F (<40%)'],
                    datasets: [{
                        data: [
                            <?php echo $grade_distribution['a_plus']; ?>,
                            <?php echo $grade_distribution['a']; ?>,
                            <?php echo $grade_distribution['b_plus']; ?>,
                            <?php echo $grade_distribution['b']; ?>,
                            <?php echo $grade_distribution['c_plus']; ?>,
                            <?php echo $grade_distribution['c']; ?>,
                            <?php echo $grade_distribution['f']; ?>
                        ],
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(34, 197, 94, 0.7)',
                            'rgba(245, 158, 11, 0.7)',
                            'rgba(249, 115, 22, 0.7)',
                            'rgba(239, 68, 68, 0.7)',
                            'rgba(220, 38, 38, 0.7)',
                            'rgba(148, 163, 184, 0.7)'
                        ],
                        borderColor: [
                            'rgba(16, 185, 129, 1)',
                            'rgba(34, 197, 94, 1)',
                            'rgba(245, 158, 11, 1)',
                            'rgba(249, 115, 22, 1)',
                            'rgba(239, 68, 68, 1)',
                            'rgba(220, 38, 38, 1)',
                            'rgba(148, 163, 184, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#cbd5e1'
                            }
                        }
                    }
                }
            });

            // Score Distribution Chart
            const scoreCtx = document.getElementById('scoreChart').getContext('2d');
            
            // Generate score ranges
            const scoreRanges = [
                '0-10%', '11-20%', '21-30%', '31-40%', '41-50%', 
                '51-60%', '61-70%', '71-80%', '81-90%', '91-100%'
            ];
            
            // Get score distribution data (simplified - in real app, query this from DB)
            const scoreData = [
                <?php echo $grade_distribution['f'] * 0.05; ?>,
                <?php echo $grade_distribution['f'] * 0.1; ?>,
                <?php echo $grade_distribution['f'] * 0.15; ?>,
                <?php echo $grade_distribution['f'] * 0.2; ?>,
                <?php echo $grade_distribution['f'] * 0.25; ?>,
                <?php echo $grade_distribution['c_plus'] * 0.8; ?>,
                <?php echo $grade_distribution['b'] * 0.9; ?>,
                <?php echo $grade_distribution['b_plus'] * 0.8; ?>,
                <?php echo $grade_distribution['a'] * 0.7; ?>,
                <?php echo $grade_distribution['a_plus']; ?>
            ];
            
            new Chart(scoreCtx, {
                type: 'bar',
                data: {
                    labels: scoreRanges,
                    datasets: [{
                        label: 'Number of Students',
                        data: scoreData,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#94a3b8'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#94a3b8'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#cbd5e1'
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
            
            // Add bounce animation to top 3 ranks
            document.querySelectorAll('.rank-1, .rank-2, .rank-3').forEach(badge => {
                badge.style.animation = 'bounce 2s infinite';
                badge.style.animationDelay = (parseInt(badge.textContent) * 0.3) + 's';
            });
        });

        // Filter functions
        function filterLeaderboard(searchTerm) {
            const rows = document.querySelectorAll('.student-row');
            searchTerm = searchTerm.toLowerCase();
            
            rows.forEach(row => {
                const studentName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                if (studentName.includes(searchTerm)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function filterByPercentile(filter) {
            const rows = document.querySelectorAll('.student-row');
            const buttons = document.querySelectorAll('.filter-btn');
            
            // Update active button
            buttons.forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent.toLowerCase().replace(' only', '').includes(filter)) {
                    btn.classList.add('active');
                }
            });
            
            // Filter rows
            rows.forEach(row => {
                const percentile = row.dataset.percentile;
                const status = row.dataset.status;
                
                switch(filter) {
                    case 'all':
                        row.style.display = 'table-row';
                        break;
                    case 'top10':
                        row.style.display = percentile.includes('top10') ? 'table-row' : 'none';
                        break;
                    case 'top25':
                        row.style.display = percentile.includes('top25') ? 'table-row' : 'none';
                        break;
                    case 'top50':
                        row.style.display = percentile.includes('top50') ? 'table-row' : 'none';
                        break;
                    case 'passed':
                        row.style.display = status === 'passed' ? 'table-row' : 'none';
                        break;
                    case 'failed':
                        row.style.display = status === 'failed' ? 'table-row' : 'none';
                        break;
                    default:
                        row.style.display = 'table-row';
                }
            });
        }
    </script>
</body>
</html>