<?php
// dashboard.php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Fetch user's exam stats
$user_id = $_SESSION['user_id'];
$completed_exams = 0;
$average_score = 0;
$upcoming_exams = 3; // Default value
?>
<!DOCTYPE html>
<html lang="en" class="dark-theme">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ExamPro</title>
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
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
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
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning), #d97706);
            color: white;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.4);
        }
        
        .dashboard {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .welcome-section {
            background: var(--bg-secondary);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .welcome-content h1 {
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        
        .welcome-content p {
            color: var(--text-secondary);
            max-width: 600px;
        }
        
        .user-badge {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 10px;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .user-badge-info h3 {
            margin-bottom: 0.25rem;
        }
        
        .user-badge-info p {
            color: var(--accent);
            font-size: 0.9rem;
        }
        
        /* Examination Center */
        .examination-center {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
            padding: 2.5rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }
        
        .examination-center::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent), var(--warning), var(--success));
        }
        
        .center-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .center-header h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--text-primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .center-header p {
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .center-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .center-button {
            background: var(--bg-secondary);
            padding: 2rem 1.5rem;
            border-radius: 15px;
            text-align: center;
            border: 1px solid var(--border);
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            display: block;
        }
        
        .center-button:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .button-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.8rem;
        }
        
        .button-1 .button-icon {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), transparent);
            color: var(--accent);
            border: 2px solid var(--accent);
        }
        
        .button-2 .button-icon {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), transparent);
            color: var(--warning);
            border: 2px solid var(--warning);
        }
        
        .button-3 .button-icon {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), transparent);
            color: var(--success);
            border: 2px solid var(--success);
        }
        
        .center-button h3 {
            margin-bottom: 0.75rem;
            font-size: 1.2rem;
        }
        
        .center-button p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
        }
        
        .quick-stat {
            text-align: center;
            padding: 1rem;
        }
        
        .quick-stat .number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--accent);
        }
        
        .quick-stat .label {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--bg-secondary);
            padding: 1.5rem;
            border-radius: 15px;
            border: 1px solid var(--border);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            border-color: var(--accent);
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: var(--accent);
            font-size: 1.2rem;
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
        
        .exams-section {
            margin-top: 2rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .exams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .exam-card {
            background: var(--bg-secondary);
            padding: 1.5rem;
            border-radius: 15px;
            border: 1px solid var(--border);
            transition: all 0.3s;
        }
        
        .exam-card:hover {
            border-color: var(--accent);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .exam-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }
        
        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .status-upcoming {
            background: rgba(245, 158, 11, 0.1);
            color: #fcd34d;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .exam-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .exam-meta {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .exam-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .center-buttons {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
            }
            
            .dashboard {
                padding: 1rem;
            }
            
            .welcome-section {
                flex-direction: column;
                gap: 1.5rem;
                text-align: center;
            }
            
            .center-buttons {
                grid-template-columns: 1fr;
            }
            
            .center-button {
                padding: 1.5rem;
            }
            
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .exams-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-stats {
                grid-template-columns: 1fr;
            }
            
            .exam-actions {
                flex-direction: column;
            }
            
            .user-menu {
                gap: 0.5rem;
            }
            
            .user-menu span {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <span>ExamPro</span>
        </div>
        
        <div class="user-menu">
            <div class="user-avatar" title="<?php echo htmlspecialchars($_SESSION['full_name']); ?>">
                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
            </div>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="logout.php" class="btn btn-secondary">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>
    
    <!-- Dashboard Content -->
    <div class="dashboard">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-content">
                <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>! 👋</h1>
                <p>
                    <?php 
                    if ($_SESSION['user_type'] == 'student') {
                        echo "Ready to ace your next exam? Your examination center is ready with personalized recommendations.";
                    } else if ($_SESSION['user_type'] == 'teacher') {
                        echo "Manage exams, track student progress, and create new assessments from your dashboard.";
                    } else {
                        echo "Administrator dashboard for managing the platform and user activities.";
                    }
                    ?>
                </p>
            </div>
            <div class="user-badge">
                <div class="user-avatar" style="width: 50px; height: 50px; font-size: 1.2rem;">
                    <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                </div>
                <div class="user-badge-info">
                    <h3><?php echo htmlspecialchars($_SESSION['full_name']); ?></h3>
                    <p>
                        <i class="fas fa-user-tag"></i> 
                        <?php echo ucfirst($_SESSION['user_type']); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Examination Center -->
        <section class="examination-center">
            <div class="center-header">
                <h2><i class="fas fa-university"></i> Examination Center</h2>
                <p>Your central hub for all examination activities. Start, manage, and track your progress here.</p>
            </div>
            
            <div class="center-buttons">
                <!-- Take Exam -->
                <a href="take_exam.php" class="center-button button-1">
                    <div class="button-icon">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <h3>Take Exam</h3>
                    <p>Start a new examination session. Choose from available tests and begin immediately.</p>
                    <div style="margin-top: 1rem;">
                        <span class="btn btn-primary">Start Now →</span>
                    </div>
                </a>
                
                <!-- Exam Center -->
                <a href="exam_center.php" class="center-button button-2">
                    <div class="button-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h3>Exam Center</h3>
                    <p>Manage all your exams in one place. View schedules, results, and create new exams.</p>
                    <div style="margin-top: 1rem;">
                        <span class="btn btn-warning">Explore →</span>
                    </div>
                </a>
                
                <!-- Leaderboard -->
                <a href="leaderboard.php" class="center-button button-3">
                    <div class="button-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h3>Leaderboard</h3>
                    <p>See how you rank among other students. Track your progress and achievements.</p>
                    <div style="margin-top: 1rem;">
                        <span class="btn btn-success">View Rankings →</span>
                    </div>
                </a>
            </div>
            
            <div class="quick-stats">
                <div class="quick-stat">
                    <div class="number">5</div>
                    <div class="label">Available Exams</div>
                </div>
                <div class="quick-stat">
                    <div class="number">12</div>
                    <div class="label">Completed</div>
                </div>
                <div class="quick-stat">
                    <div class="number">85%</div>
                    <div class="label">Average Score</div>
                </div>
                <div class="quick-stat">
                    <div class="number">3</div>
                    <div class="label">Upcoming</div>
                </div>
            </div>
        </section>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number">3h 42m</div>
                <div class="stat-label">Total Exam Time</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="stat-number">92%</div>
                <div class="stat-label">Accuracy Rate</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-medal"></i>
                </div>
                <div class="stat-number">#15</div>
                <div class="stat-label">Global Ranking</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="stat-number">98%</div>
                <div class="stat-label">Completion Rate</div>
            </div>
        </div>
        
        <!-- Active Exams Section -->
        <div class="exams-section">
            <h2 class="section-title">
                <span><i class="fas fa-fire"></i> Active Exams</span>
                <a href="all_exams.php" class="btn btn-secondary">View All</a>
            </h2>
            
            <div class="exams-grid">
                <!-- Exam 1 -->
                <div class="exam-card">
                    <span class="exam-status status-active">
                        <i class="fas fa-circle"></i> Live Now
                    </span>
                    <h3 style="margin-bottom: 0.5rem;">Mathematics Final Exam</h3>
                    <div class="exam-meta">
                        <span><i class="far fa-clock"></i> 60 min</span>
                        <span><i class="far fa-question-circle"></i> 40 Qs</span>
                        <span><i class="fas fa-star"></i> 100 Marks</span>
                    </div>
                    <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                        Covers algebra, geometry, and calculus concepts from chapters 1-12.
                    </p>
                    <div class="exam-actions">
                        <a href="take_exam.php?id=1" class="btn btn-primary">
                            <i class="fas fa-play"></i> Start Exam
                        </a>
                        <a href="exam_details.php?id=1" class="btn btn-secondary">
                            <i class="fas fa-info-circle"></i> Details
                        </a>
                    </div>
                </div>
                
                <!-- Exam 2 -->
                <div class="exam-card">
                    <span class="exam-status status-upcoming">
                        <i class="fas fa-clock"></i> Starts Tomorrow
                    </span>
                    <h3 style="margin-bottom: 0.5rem;">Science Quiz - Physics</h3>
                    <div class="exam-meta">
                        <span><i class="far fa-clock"></i> 30 min</span>
                        <span><i class="far fa-question-circle"></i> 20 Qs</span>
                        <span><i class="fas fa-star"></i> 50 Marks</span>
                    </div>
                    <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                        Focus on mechanics, thermodynamics, and modern physics concepts.
                    </p>
                    <div class="exam-actions">
                        <button class="btn btn-warning" disabled>
                            <i class="fas fa-clock"></i> Starts Soon
                        </button>
                        <a href="exam_details.php?id=2" class="btn btn-secondary">
                            <i class="fas fa-info-circle"></i> Preview
                        </a>
                    </div>
                </div>
                
                <!-- Exam 3 -->
                <div class="exam-card">
                    <span class="exam-status status-active">
                        <i class="fas fa-circle"></i> Live Now
                    </span>
                    <h3 style="margin-bottom: 0.5rem;">English Grammar Test</h3>
                    <div class="exam-meta">
                        <span><i class="far fa-clock"></i> 45 min</span>
                        <span><i class="far fa-question-circle"></i> 35 Qs</span>
                        <span><i class="fas fa-star"></i> 75 Marks</span>
                    </div>
                    <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                        Grammar, vocabulary, and comprehension test for intermediate level.
                    </p>
                    <div class="exam-actions">
                        <a href="take_exam.php?id=3" class="btn btn-primary">
                            <i class="fas fa-play"></i> Start Exam
                        </a>
                        <a href="exam_details.php?id=3" class="btn btn-secondary">
                            <i class="fas fa-info-circle"></i> Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div style="margin-top: 3rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="profile.php" class="btn btn-secondary">
                <i class="fas fa-user-cog"></i> Edit Profile
            </a>
            <a href="results.php" class="btn btn-secondary">
                <i class="fas fa-chart-bar"></i> View Results
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-award"></i> Take exams
            </a>
            <a href="exam_center.php" class="btn btn-secondary">
                <i class="fas fa-question-circle"></i> Exam Center
            </a>
        </div>
    </div>
    
    <script>
        // Interactive hover effects
        document.querySelectorAll('.center-button').forEach(button => {
            button.addEventListener('mouseenter', function() {
                const icon = this.querySelector('.button-icon');
                icon.style.transform = 'scale(1.1) rotate(5deg)';
                icon.style.transition = 'transform 0.3s ease';
            });
            
            button.addEventListener('mouseleave', function() {
                const icon = this.querySelector('.button-icon');
                icon.style.transform = 'scale(1) rotate(0deg)';
            });
        });
        
        // Update time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            });
            const dateString = now.toLocaleDateString('en-US', { 
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            // Update any time element if exists
            const timeElement = document.getElementById('current-time');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        
        // Update every minute
        setInterval(updateTime, 60000);
        updateTime();
        
        // Add click effects to buttons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Create ripple effect
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.3);
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    width: ${size}px;
                    height: ${size}px;
                    top: ${y}px;
                    left: ${x}px;
                `;
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                // Remove ripple after animation
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
        
        // Add CSS for ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>