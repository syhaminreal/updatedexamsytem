<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in (optional - can be removed for public exams)
// Uncomment if exams should require login:
// if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
//     header('Location: login.php');
//     exit();
// }

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

if (!$exam_id) {
    header('Location: index.php');
    exit();
}

// Get exam details
$stmt = $pdo->prepare("SELECT * FROM exams WHERE exam_id = ? AND exam_status = 'active'");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Exam not found or inactive!");
}

// Count questions
$question_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM questions WHERE exam_id = ?");
$question_stmt->execute([$exam_id]);
$question_count = $question_stmt->fetch(PDO::FETCH_ASSOC)['count'];

if ($question_count == 0) {
    die("No questions available for this exam!");
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_name = trim($_POST['student_name']);
    $student_email = trim($_POST['student_email']);
    $student_roll = trim($_POST['student_roll']);
    
    // Input validation
    if (empty($student_name)) {
        $error = "Student name is required!";
    } elseif (strlen($student_name) < 2) {
        $error = "Student name must be at least 2 characters!";
    } elseif (!empty($student_email) && !filter_var($student_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } else {
        // Create exam attempt
        $stmt = $pdo->prepare("INSERT INTO exam_attempts (exam_id, student_name, student_email, student_roll) VALUES (?, ?, ?, ?)");
        $stmt->execute([$exam_id, $student_name, $student_email, $student_roll]);
        $attempt_id = $pdo->lastInsertId();
        
        // Store in session for the exam
        session_start();
        $_SESSION['attempt_id'] = $attempt_id;
        $_SESSION['exam_id'] = $exam_id;
        $_SESSION['student_name'] = $student_name;
        $_SESSION['exam_duration'] = $exam['exam_duration'] * 60; // Convert to seconds
        $_SESSION['start_time'] = time();
        
        // Redirect to take exam
        header('Location: take_exam.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Exam - <?php echo htmlspecialchars($exam['exam_title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        .exam-start-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            max-width: 600px;
            margin: 0 auto;
            overflow: hidden;
        }
        .exam-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .form-section {
            padding: 40px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 5px;
        }
        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 40px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 1.1em;
            width: 100%;
            margin-top: 20px;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="exam-start-card">
            <div class="exam-header">
                <h2><?php echo htmlspecialchars($exam['exam_title']); ?></h2>
                <p class="mb-0">Please enter your details to start the exam</p>
            </div>
            
            <div class="form-section">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="info-box">
                    <h5>Exam Information:</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Duration:</strong> <?php echo $exam['exam_duration']; ?> minutes</p>
                            <p><strong>Questions:</strong> <?php echo $question_count; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Total Marks:</strong> <?php echo $exam['total_marks']; ?></p>
                            <p><strong>Passing Marks:</strong> <?php echo $exam['passing_marks']; ?></p>
                        </div>
                    </div>
                    <p class="mb-0"><strong>Note:</strong> Timer will start as soon as you begin the exam.</p>
                </div>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="student_name" 
                               placeholder="Enter your full name" required
                               value="<?php echo $_POST['student_name'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="student_email" 
                               placeholder="Enter your email (optional)"
                               value="<?php echo $_POST['student_email'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Roll Number / ID</label>
                        <input type="text" class="form-control" name="student_roll" 
                               placeholder="Enter your roll number (optional)"
                               value="<?php echo $_POST['student_roll'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the exam rules and conditions
                        </label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="submit-btn">Start Exam Now</button>
                        <a href="index.php" class="btn btn-outline-secondary mt-2">Back to Exams</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>