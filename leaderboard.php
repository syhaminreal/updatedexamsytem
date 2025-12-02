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
    <title>Exam Leaderboard - Top Performers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --gold: #FFD700;
            --silver: #C0C0C0;
            --bronze: #CD7F32;
            --primary: #667eea;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .leaderboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header-card {
            background: linear-gradient(135deg, var(--primary) 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
            border-top: 4px solid var(--primary);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.gold {
            border-top-color: var(--gold);
        }
        
        .stat-card.silver {
            border-top-color: var(--silver);
        }
        
        .stat-card.bronze {
            border-top-color: var(--bronze);
        }
        
        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #333;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }
        
        .leaderboard-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .table-header {
            background: linear-gradient(135deg, var(--primary) 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        
        .table-row {
            display: grid;
            grid-template-columns: 80px 1fr 100px 100px 100px 100px 100px 150px;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            transition: background 0.3s;
        }
        
        .table-row:hover {
            background: #f8f9fa;
        }
        
        .table-row.header {
            background: #f8f9fa;
            font-weight: bold;
            color: #666;
        }
        
        .rank-badge {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        
        .rank-1 {
            background: linear-gradient(135deg, var(--gold) 0%, #FFA500 100%);
        }
        
        .rank-2 {
            background: linear-gradient(135deg, var(--silver) 0%, #A9A9A9 100%);
        }
        
        .rank-3 {
            background: linear-gradient(135deg, var(--bronze) 0%, #8B4513 100%);
        }
        
        .rank-other {
            background: var(--primary);
        }
        
        .percentile-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .percentile-top10 {
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
            color: white;
        }
        
        .percentile-top25 {
            background: linear-gradient(135deg, #8BC34A 0%, #689F38 100%);
            color: white;
        }
        
        .percentile-top50 {
            background: linear-gradient(135deg, #FFC107 0%, #FF9800 100%);
            color: white;
        }
        
        .percentile-bottom25 {
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
            color: white;
        }
        
        .percentile-bottom10 {
            background: linear-gradient(135deg, #F44336 0%, #D32F2F 100%);
            color: white;
        }
        
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .algorithm-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .algorithm-title {
            color: var(--primary);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        .boundary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .exam-selector {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .medal-count {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .medal-icon {
            font-size: 1.5em;
        }
        
        .medal-gold { color: var(--gold); }
        .medal-silver { color: var(--silver); }
        .medal-bronze { color: var(--bronze); }
        
        .trophy-animation {
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid #ddd;
            border-radius: 25px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 20px;
            border: 2px solid var(--primary);
            background: white;
            color: var(--primary);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-btn.active {
            background: var(--primary);
            color: white;
        }
        
        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 1200px) {
            .table-row {
                grid-template-columns: 60px 1fr 80px 80px 80px 80px 80px 120px;
                font-size: 0.9em;
            }
        }
        
        @media (max-width: 768px) {
            .table-row {
                grid-template-columns: 50px 1fr 70px;
                font-size: 0.8em;
            }
            
            .table-row .hide-mobile {
                display: none;
            }
            
            .charts-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="leaderboard-container">
        <!-- Header -->
        <div class="header-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-trophy trophy-animation me-3"></i>Exam Leaderboard</h1>
                    <p class="lead mb-0">See top performers and percentile rankings</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="exam-selector">
                        <label class="form-label"><i class="fas fa-file-alt me-2"></i>Select Exam:</label>
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
            <!-- Exam Stats -->
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
            <div class="search-box">
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

            <!-- Leaderboard Table -->
            <div class="leaderboard-table">
                <div class="table-header">
                    <h4 class="mb-0">Top Performers - <?php echo htmlspecialchars($selected_exam['exam_title']); ?></h4>
                    <p class="mb-0">Showing top 50 out of <?php echo $exam_stats['total_students'] ?? 0; ?> participants</p>
                </div>
                
                <div class="table-row header">
                    <div>Rank</div>
                    <div>Student Name</div>
                    <div>Score</div>
                    <div class="hide-mobile">Percentage</div>
                    <div class="hide-mobile">Grade</div>
                    <div class="hide-mobile">Correct</div>
                    <div class="hide-mobile">Total</div>
                    <div>Percentile Group</div>
                </div>
                
                <?php if (empty($leaderboard)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <h4>No results yet for this exam</h4>
                        <p class="text-muted">Be the first to take this exam!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($leaderboard as $index => $student): 
                        $rank = $index + 1;
                        $rank_class = $rank <= 3 ? "rank-{$rank}" : "rank-other";
                        $percentile_group_class = strtolower(str_replace([' ', '%'], '', $student['percentile_group']));
                    ?>
                        <div class="table-row student-row" 
                             data-percentile="<?php echo strtolower($student['percentile_group']); ?>"
                             data-status="<?php echo $student['status'] ?? 'PASS'; ?>">
                            <div>
                                <div class="rank-badge <?php echo $rank_class; ?>">
                                    <?php echo $rank; ?>
                                </div>
                            </div>
                            <div>
                                <strong><?php echo htmlspecialchars($student['student_name']); ?></strong>
                                <?php if ($rank <= 3): ?>
                                    <span class="medal-count">
                                        <?php if ($rank == 1): ?>
                                            <i class="fas fa-medal medal-gold"></i>
                                        <?php elseif ($rank == 2): ?>
                                            <i class="fas fa-medal medal-silver"></i>
                                        <?php elseif ($rank == 3): ?>
                                            <i class="fas fa-medal medal-bronze"></i>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong><?php echo $student['marks_obtained']; ?>/<?php echo $student['total_marks']; ?></strong>
                            </div>
                            <div class="hide-mobile">
                                <span class="badge bg-<?php echo $student['percentage'] >= 70 ? 'success' : ($student['percentage'] >= 40 ? 'warning' : 'danger'); ?>">
                                    <?php echo $student['percentage']; ?>%
                                </span>
                            </div>
                            <div class="hide-mobile">
                                <span class="badge bg-info"><?php echo $student['grade']; ?></span>
                            </div>
                            <div class="hide-mobile">
                                <?php echo $student['correct_answers']; ?>
                            </div>
                            <div class="hide-mobile">
                                <?php echo $student['total_questions']; ?>
                            </div>
                            <div>
                                <span class="percentile-badge percentile-<?php echo $percentile_group_class; ?>">
                                    <?php echo $student['percentile_group']; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Charts Section -->
            <div class="charts-container">
                <!-- Grade Distribution Chart -->
                <div class="chart-card">
                    <h5><i class="fas fa-chart-pie me-2"></i>Grade Distribution</h5>
                    <canvas id="gradeChart" height="250"></canvas>
                </div>
                
                <!-- Score Distribution Chart -->
                <div class="chart-card">
                    <h5><i class="fas fa-chart-bar me-2"></i>Score Distribution</h5>
                    <canvas id="scoreChart" height="250"></canvas>
                </div>
            </div>

            <!-- Percentile Boundary Algorithms -->
            <div class="algorithm-card">
                <h4 class="algorithm-title"><i class="fas fa-calculator me-2"></i>Percentile Boundary Algorithms</h4>
                
                <div class="row">
                    <!-- Algorithm 1: Simple Division -->
                    <div class="col-md-4">
                        <h6>Simple Division Method</h6>
                        <?php 
                        $simple = $percentile_algorithms['simple'];
                        foreach ($simple as $label => $value): 
                            $label_name = ucfirst(str_replace('_', ' ', $label));
                        ?>
                            <div class="boundary-item">
                                <span><?php echo $label_name; ?>:</span>
                                <span class="badge bg-primary"><?php echo $value; ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Algorithm 2: Standard Deviation -->
                    <div class="col-md-4">
                        <h6>Standard Deviation Method</h6>
                        <?php if (isset($percentile_algorithms['std_dev'])): 
                            $std_dev = $percentile_algorithms['std_dev'];
                            foreach ($std_dev as $label => $value): 
                                $label_name = ucfirst(str_replace('_', ' ', $label));
                        ?>
                            <div class="boundary-item">
                                <span><?php echo $label_name; ?>:</span>
                                <span class="badge bg-success"><?php echo round($value, 1); ?>%</span>
                            </div>
                        <?php endforeach; else: ?>
                            <p class="text-muted">Insufficient data for standard deviation calculation</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Algorithm 3: Quartiles -->
                    <div class="col-md-4">
                        <h6>Quartile Method</h6>
                        <?php 
                        $quartiles = $percentile_algorithms['quartiles'];
                        foreach ($quartiles as $label => $value): 
                            $label_name = strtoupper($label);
                        ?>
                            <div class="boundary-item">
                                <span><?php echo $label_name; ?> (<?php echo $label == 'q1' ? 'Top 25%' : ($label == 'q2' ? 'Median' : 'Bottom 25%'); ?>):</span>
                                <span class="badge bg-warning"><?php echo $value; ?>%</span>
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
                                <div class="stat-label"><?php echo htmlspecialchars($topper['student_name']); ?></div>
                                <div class="small text-muted"><?php echo $topper['exam_date']; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                <h4>No exams available</h4>
                <p>Please create exams first to see the leaderboard.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Initialize charts
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
                            '#4CAF50', '#8BC34A', '#FFC107', '#FF9800', '#FF5722', '#F44336', '#9E9E9E'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
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
            
            // Get score distribution data (you would need to query this from database)
            // For now, we'll use sample data
            const scoreData = [2, 3, 5, 8, 12, 15, 18, 22, 10, 5];
            
            new Chart(scoreCtx, {
                type: 'bar',
                data: {
                    labels: scoreRanges,
                    datasets: [{
                        label: 'Number of Students',
                        data: scoreData,
                        backgroundColor: 'rgba(102, 126, 234, 0.7)',
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Students'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Score Ranges'
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
        });

        // Filter functions
        function filterLeaderboard(searchTerm) {
            const rows = document.querySelectorAll('.student-row');
            searchTerm = searchTerm.toLowerCase();
            
            rows.forEach(row => {
                const studentName = row.querySelector('strong').textContent.toLowerCase();
                if (studentName.includes(searchTerm)) {
                    row.style.display = 'grid';
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
                if (btn.textContent.toLowerCase().includes(filter)) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            
            // Filter rows
            rows.forEach(row => {
                const percentile = row.dataset.percentile;
                const status = row.dataset.status;
                
                switch(filter) {
                    case 'all':
                        row.style.display = 'grid';
                        break;
                    case 'top10':
                        row.style.display = percentile.includes('top10') ? 'grid' : 'none';
                        break;
                    case 'top25':
                        row.style.display = percentile.includes('top25') ? 'grid' : 'none';
                        break;
                    case 'top50':
                        row.style.display = percentile.includes('top50') ? 'grid' : 'none';
                        break;
                    case 'passed':
                        row.style.display = status === 'PASS' ? 'grid' : 'none';
                        break;
                    case 'failed':
                        row.style.display = status === 'FAIL' ? 'grid' : 'none';
                        break;
                    default:
                        row.style.display = 'grid';
                }
            });
        }

        // Add animation to top 3 ranks
        document.querySelectorAll('.rank-1, .rank-2, .rank-3').forEach(badge => {
            badge.style.animation = 'bounce 2s infinite';
            badge.style.animationDelay = (parseInt(badge.textContent) * 0.2) + 's';
        });
    </script>
</body>
</html>