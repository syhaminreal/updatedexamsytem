<?php
session_start();
require_once './db_connection.php'; // adjust path if needed

try {
    // Use single JOIN query instead of N+1 queries
    $stmt = $pdo->prepare("
        SELECT 
            e.*, 
            COUNT(q.question_id) as question_count 
        FROM exams e 
        LEFT JOIN questions q ON e.exam_id = q.exam_id 
        WHERE e.exam_status = 'active' AND (e.is_deleted = 0 OR e.is_deleted IS NULL)
        GROUP BY e.exam_id
        ORDER BY e.created_at DESC
    ");
    $stmt->execute();
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching exams: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title>ExamPro - Online Examination Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
=======
    <title>Online Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
>>>>>>> ddc0de7c3f954b4d531394e99259a86b3a9bff16
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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
            line-height: 1.6;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            box-shadow: var(--shadow-sm);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--text-primary) !important;
        }

        .hero-section {
            background: var(--primary-gradient);
            color: white;
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="50" r="1" fill="white" opacity="0.05"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grain)"/></svg>');
            opacity: 0.1;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .hero-subtitle {
            font-size: 1.25rem;
            font-weight: 400;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 2rem;
            display: inline-block;
            margin-top: 2rem;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            display: block;
        }

        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 0.5rem;
        }

        .exam-grid {
            padding: 4rem 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            color: var(--text-primary);
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .section-title p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .exam-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
        }

        .exam-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: #667eea;
        }

        .exam-card-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            position: relative;
        }

        .exam-card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .exam-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .exam-description {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }

        .exam-body {
            padding: 2rem;
        }

        .exam-details {
            margin-bottom: 2rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .detail-value {
            font-weight: 600;
            color: var(--text-primary);
        }

        .start-exam-btn {
            width: 100%;
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .start-exam-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .footer {
            background: var(--text-primary);
            color: white;
            padding: 3rem 0 2rem;
            margin-top: 4rem;
        }

        .footer-content {
            text-align: center;
        }

        .footer-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .footer-text {
            opacity: 0.8;
            margin-bottom: 1rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 2rem;
        }

        .footer-link {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: white;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .section-title h2 {
                font-size: 2rem;
            }

            .exam-card-header {
                padding: 1.5rem;
            }

            .exam-body {
                padding: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .hero-section {
                padding: 3rem 0;
            }

            .stats-card {
                padding: 1.5rem;
            }

            .stats-number {
                font-size: 2rem;
            }
        }
        
        .home-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            color: white;
            font-size: 24px;
            margin-bottom: 20px;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }
        
        .home-icon:hover {
            background: rgba(255,255,255,0.25);
            transform: scale(1.1);
            color: white;
        }
    </style>
</head>
<body>
<<<<<<< HEAD
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap me-2"></i>
                ExamPro
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Master Your Exams with Confidence</h1>
                <p class="hero-subtitle">Take professional online examinations instantly. No registration required. Get detailed results immediately after completion.</p>

                <div class="stats-card">
                    <div class="stats-number"><?php echo count($exams); ?></div>
                    <div class="stats-label">Available Exams</div>
                </div>
=======
    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <a href="staff/index.php" class="home-icon" title="Go to Dashboard">
                <i class="fas fa-home"></i>
            </a>
            <h1 class="display-4 mb-3">📝 Online Exam System</h1>
            <p class="lead mb-4">Take exams instantly without registration. Get results immediately!</p>
            <div class="exam-count">
                <h3><?php echo count($exams); ?> Exams Available</h3>
                <p class="mb-0">Start any exam by clicking "Start Exam"</p>
>>>>>>> ddc0de7c3f954b4d531394e99259a86b3a9bff16
            </div>
        </div>
    </section>

    <!-- Exams Section -->
    <section class="exam-grid">
        <div class="container">
            <div class="section-title">
                <h2>Available Examinations</h2>
                <p>Choose from our collection of professional exams. Each exam is designed to test your knowledge and provide instant feedback.</p>
            </div>

            <?php if (empty($exams)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No Exams Available</h3>
                    <p>Please check back later for new examinations.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($exams as $exam): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="exam-card">
                                <div class="exam-card-header">
                                    <h3 class="exam-title"><?php echo htmlspecialchars($exam['exam_title']); ?></h3>
                                    <p class="exam-description"><?php echo htmlspecialchars($exam['exam_description'] ?: 'Professional examination to test your knowledge and skills.'); ?></p>
                                </div>

                                <div class="exam-body">
                                    <div class="exam-details">
                                        <div class="detail-item">
                                            <span class="detail-label">
                                                <i class="fas fa-question-circle"></i>
                                                Questions
                                            </span>
                                            <span class="detail-value"><?php echo $exam['question_count']; ?></span>
                                        </div>

                                        <div class="detail-item">
                                            <span class="detail-label">
                                                <i class="fas fa-clock"></i>
                                                Duration
                                            </span>
                                            <span class="detail-value"><?php echo $exam['exam_duration']; ?> min</span>
                                        </div>

                                        <div class="detail-item">
                                            <span class="detail-label">
                                                <i class="fas fa-trophy"></i>
                                                Total Marks
                                            </span>
                                            <span class="detail-value"><?php echo $exam['total_marks']; ?></span>
                                        </div>

                                        <div class="detail-item">
                                            <span class="detail-label">
                                                <i class="fas fa-check-circle"></i>
                                                Passing Score
                                            </span>
                                            <span class="detail-value"><?php echo $exam['passing_marks']; ?>%</span>
                                        </div>
                                    </div>

                                    <a href="start_exam.php?exam_id=<?php echo $exam['exam_id']; ?>" class="start-exam-btn">
                                        <i class="fas fa-play me-2"></i>
                                        Start Examination
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

<<<<<<< HEAD
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <h3 class="footer-title">ExamPro</h3>
                <p class="footer-text">Professional online examination platform designed for modern learning and assessment needs.</p>
                <p class="footer-text">© <?php echo date('Y'); ?> ExamPro. All rights reserved.</p>
=======
        <!-- Footer -->
        <footer class="text-center text-white mt-5 pt-4">
            <p>
                <a href="staff/index.php" class="text-white text-decoration-none">
                    <i class="fas fa-home me-1"></i> Dashboard
                </a> 
                | &copy; <?php echo date('Y'); ?> Online Exam System. All rights reserved.
            </p>
            <p class="small">No registration required. Take exams directly and get instant results.</p>
        </footer>
    </div>
>>>>>>> ddc0de7c3f954b4d531394e99259a86b3a9bff16

                <div class="footer-links">
                    <a href="#" class="footer-link">
                        <i class="fas fa-shield-alt me-1"></i>
                        Privacy Policy
                    </a>
                    <a href="#" class="footer-link">
                        <i class="fas fa-file-contract me-1"></i>
                        Terms of Service
                    </a>
                    <a href="#" class="footer-link">
                        <i class="fas fa-envelope me-1"></i>
                        Contact Us
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>