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
    <title>Online Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .exam-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s;
            margin-bottom: 25px;
            overflow: hidden;
        }
        .exam-card:hover {
            transform: translateY(-5px);
        }
        .exam-header {
            background: #667eea;
            color: white;
            padding: 20px;
        }
        .start-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 30px;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s;
        }
        .start-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .hero-section {
            text-align: center;
            color: white;
            padding: 50px 20px;
            margin-bottom: 40px;
        }
        .exam-count {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
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
            </div>
        </div>

        <!-- Exams Grid -->
        <div class="row">
            <?php if (empty($exams)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <h4>No exams available at the moment</h4>
                        <p>Please check back later.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($exams as $exam): ?>
                        <div class="col-md-4">
                            <div class="exam-card">
                                <div class="exam-header">
                                    <h4 class="mb-0"><?php echo htmlspecialchars($exam['exam_title']); ?></h4>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?php echo htmlspecialchars($exam['exam_description']); ?></p>
                                    
                                    <div class="exam-details mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>📋 Questions:</span>
                                            <strong><?php echo $exam['question_count']; ?></strong>
                                        </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>⏱️ Duration:</span>
                                        <strong><?php echo $exam['exam_duration']; ?> minutes</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>🎯 Total Marks:</span>
                                        <strong><?php echo $exam['total_marks']; ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>✅ Passing Marks:</span>
                                        <strong><?php echo $exam['passing_marks']; ?></strong>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <a href="start_exam.php?exam_id=<?php echo $exam['exam_id']; ?>" 
                                       class="start-btn btn btn-lg">
                                        Start Exam
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>