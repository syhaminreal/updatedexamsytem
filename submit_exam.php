<?php
session_start();
require_once 'db_connection.php';


if (!isset($_SESSION['attempt_id']) || !isset($_SESSION['exam_id'])) {
    header('Location: index.php');
    exit();
}

$attempt_id   = $_SESSION['attempt_id'];
$exam_id      = $_SESSION['exam_id'];
$student_name = $_SESSION['student_name'] ?? 'Student';

$stmt = $pdo->prepare("SELECT * FROM exams WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    session_destroy();
    header('Location: index.php');
    exit();
}


$stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

<<<<<<< HEAD
// Process questions to ensure options are available
foreach ($questions as &$question) {
    // Check if options are stored as JSON
    if (!empty($question['options']) && is_string($question['options'])) {
        $json_options = json_decode($question['options'], true);
        if (is_array($json_options)) {
            $question['option_a'] = $json_options['a'] ?? '';
            $question['option_b'] = $json_options['b'] ?? '';
            $question['option_c'] = $json_options['c'] ?? '';
            $question['option_d'] = $json_options['d'] ?? '';
        }
    }
    // Ensure all option fields exist
    $question['option_a'] = $question['option_a'] ?? '';
    $question['option_b'] = $question['option_b'] ?? '';
    $question['option_c'] = $question['option_c'] ?? '';
    $question['option_d'] = $question['option_d'] ?? '';
}

/*
|---------------------------------------------------------
| Calculate result
|---------------------------------------------------------
*/
=======
// Ensure individual option columns are populated (fallback from JSON options column)
foreach ($questions as &$q) {
    if ((empty($q['option_a']) || empty($q['option_b'])) && !empty($q['options'])) {
        $opts = json_decode($q['options'], true);
        if (is_array($opts)) {
            $q['option_a'] = $q['option_a'] ?: ($opts['a'] ?? $opts['A'] ?? '');
            $q['option_b'] = $q['option_b'] ?: ($opts['b'] ?? $opts['B'] ?? '');
            $q['option_c'] = $q['option_c'] ?: ($opts['c'] ?? $opts['C'] ?? '');
            $q['option_d'] = $q['option_d'] ?: ($opts['d'] ?? $opts['D'] ?? '');
        }
    }
}
unset($q);


>>>>>>> ddc0de7c3f954b4d531394e99259a86b3a9bff16
$total_questions     = count($questions);
$attempted_questions = 0;
$correct_answers     = 0;
$wrong_answers       = 0;
$total_marks         = 0;
$marks_obtained      = 0;
$user_answers_data   = [];

foreach ($questions as $question) {
    // Get submitted answer safely
    $user_answer = $_POST['answer_' . $question['question_id']] ?? null;
    $total_marks += $question['marks'];
    
    $is_correct = 0;
    $marks = 0;

    if ($user_answer !== null) {
        $attempted_questions++;

        if (strtoupper($user_answer) == strtoupper($question['correct_answer'])) {
            $correct_answers++;
            $marks_obtained += $question['marks'];
            $marks = $question['marks'];
            $is_correct = 1;
        } else {
            $wrong_answers++;
        }
    }

    // Store for display
    $user_answers_data[] = [
        'question' => $question['question_text'],
        'options' => [
            'A' => $question['option_a'],
            'B' => $question['option_b'],
            'C' => $question['option_c'],
            'D' => $question['option_d']
        ],
        'correct_answer' => $question['correct_answer'],
        'user_answer' => $user_answer,
        'is_correct' => $is_correct,
        'marks' => $question['marks'],
        'marks_obtained' => $marks
    ];

    // Save individual answer to database
    try {
        $ans_stmt = $pdo->prepare(
            "INSERT INTO user_answers (attempt_id, question_id, user_answer, is_correct, marks_obtained) 
             VALUES (?, ?, ?, ?, ?)"
        );
        $ans_stmt->execute([$attempt_id, $question['question_id'], $user_answer, $is_correct, $marks]);
    } catch (PDOException $e) {
        // Continue even if individual answer saving fails
        error_log("Error saving answer: " . $e->getMessage());
    }
}


$percentage = ($total_marks > 0) ? round(($marks_obtained / $total_marks) * 100, 2) : 0;
$unattempted = $total_questions - $attempted_questions;

// Grade logic
$grade = 'F';
if ($percentage >= 90) {
    $grade = 'A+';
} elseif ($percentage >= 80) {
    $grade = 'A';
} elseif ($percentage >= 70) {
    $grade = 'B';
} elseif ($percentage >= 60) {
    $grade = 'C';
} elseif ($percentage >= 50) {
    $grade = 'D';
}

// Pass/fail status
$status = ($percentage >= $exam['passing_marks']) ? 'PASS' : 'FAIL';


try {
    // First, let's check if the table has wrong_answers column
    $check_stmt = $pdo->prepare("SHOW COLUMNS FROM exam_results LIKE 'wrong_answers'");
    $check_stmt->execute();
    $has_wrong_answers = $check_stmt->rowCount() > 0;
    
    if ($has_wrong_answers) {
        // Insert with wrong_answers
        $stmt = $pdo->prepare("
            INSERT INTO exam_results
            (
                attempt_id,
                exam_id,
                student_name,
                total_questions,
                attempted_questions,
                correct_answers,
                wrong_answers,
                total_marks,
                marks_obtained,
                percentage,
                grade,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $attempt_id,
            $exam_id,
            $student_name,
            $total_questions,
            $attempted_questions,
            $correct_answers,
            $wrong_answers,
            $total_marks,
            $marks_obtained,
            $percentage,
            $grade,
            $status
        ]);
    } else {
        // Insert without wrong_answers (calculate it)
        $stmt = $pdo->prepare("
            INSERT INTO exam_results
            (
                attempt_id,
                exam_id,
                student_name,
                total_questions,
                attempted_questions,
                correct_answers,
                total_marks,
                marks_obtained,
                percentage,
                grade,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $attempt_id,
            $exam_id,
            $student_name,
            $total_questions,
            $attempted_questions,
            $correct_answers,
            $total_marks,
            $marks_obtained,
            $percentage,
            $grade,
            $status
        ]);
    }
} catch (PDOException $e) {
    // Try alternative insert without status column
    try {
        $stmt = $pdo->prepare("
            INSERT INTO exam_results
            (
                attempt_id,
                exam_id,
                student_name,
                total_questions,
                attempted_questions,
                correct_answers,
                total_marks,
                marks_obtained,
                percentage,
                grade
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $attempt_id,
            $exam_id,
            $student_name,
            $total_questions,
            $attempted_questions,
            $correct_answers,
            $total_marks,
            $marks_obtained,
            $percentage,
            $grade
        ]);
    } catch (PDOException $e2) {
        // Last resort: minimal insert
        $stmt = $pdo->prepare("
            INSERT INTO exam_results
            (
                attempt_id,
                exam_id,
                student_name,
                total_questions,
                correct_answers,
                total_marks,
                marks_obtained,
                percentage
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $attempt_id,
            $exam_id,
            $student_name,
            $total_questions,
            $correct_answers,
            $total_marks,
            $marks_obtained,
            $percentage
        ]);
    }
}

// Update exam attempt as completed
$pdo->prepare("UPDATE exam_attempts SET completed_at = NOW() WHERE attempt_id = ?")->execute([$attempt_id]);


unset($_SESSION['attempt_id']);
unset($_SESSION['exam_id']);
unset($_SESSION['student_name']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - <?php echo htmlspecialchars($exam['exam_title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .result-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        
        .result-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .result-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5em;
            font-weight: bold;
            border: 8px solid rgba(255,255,255,0.3);
        }
        
        .score-circle.pass {
            background: #28a745;
            color: white;
        }
        
        .score-circle.fail {
            background: #dc3545;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            padding: 30px;
        }
        
        .stat-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
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
        
        .result-details {
            padding: 30px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .action-buttons {
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
        }
        
        .btn-action {
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: bold;
            margin: 0 10px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            border: none;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
        }
        
        .question-review {
            max-height: 400px;
            overflow-y: auto;
            padding: 20px;
            margin: 20px;
            border: 1px solid #eee;
            border-radius: 10px;
        }
        
        .question-item {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .question-item.correct {
            border-left: 4px solid #28a745;
            background: #d4edda;
        }
        
        .question-item.incorrect {
            border-left: 4px solid #dc3545;
            background: #f8d7da;
        }
        
        .question-item.unattempted {
            border-left: 4px solid #6c757d;
            background: #e9ecef;
        }
        
        .option-badge {
            display: inline-block;
            padding: 5px 10px;
            margin: 2px;
            border-radius: 15px;
            font-size: 0.85em;
        }
        
        .option-correct {
            background: #28a745;
            color: white;
        }
        
        .option-wrong {
            background: #dc3545;
            color: white;
        }
        
        .option-user {
            background: #667eea;
            color: white;
        }
        
        .performance-bar {
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .performance-fill {
            height: 100%;
            transition: width 1s ease-out;
        }
    </style>
</head>
<body>
    <div class="result-container">
        <div class="result-card">
            <!-- Header -->
            <div class="result-header">
                <h1><?php echo htmlspecialchars($exam['exam_title']); ?></h1>
                <p>Exam Completed Successfully</p>
                
                <div class="score-circle <?php echo $status == 'PASS' ? 'pass' : 'fail'; ?>">
                    <?php echo $percentage; ?>%
                </div>
                
                <h2><?php echo $status; ?>ED</h2>
                <p>Grade: <?php echo $grade; ?> | Score: <?php echo $marks_obtained; ?>/<?php echo $total_marks; ?></p>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $total_questions; ?></div>
                    <div class="stat-label">Total Questions</div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-value"><?php echo $attempted_questions; ?></div>
                    <div class="stat-label">Attempted</div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-value"><?php echo $correct_answers; ?></div>
                    <div class="stat-label">Correct</div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-value"><?php echo $wrong_answers; ?></div>
                    <div class="stat-label">Wrong</div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-value"><?php echo $unattempted; ?></div>
                    <div class="stat-label">Unattempted</div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-value">
                        <?php echo $attempted_questions > 0 ? round(($correct_answers / $attempted_questions) * 100, 1) : 0; ?>%
                    </div>
                    <div class="stat-label">Accuracy</div>
                </div>
            </div>
            
            <!-- Performance Bars -->
            <div class="result-details">
                <h4>Performance Analysis</h4>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Accuracy</span>
                        <span><?php echo $attempted_questions > 0 ? round(($correct_answers / $attempted_questions) * 100, 1) : 0; ?>%</span>
                    </div>
                    <div class="performance-bar">
                        <div class="performance-fill" style="width: <?php echo $attempted_questions > 0 ? ($correct_answers / $attempted_questions) * 100 : 0; ?>%; background: #28a745;"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Completion</span>
                        <span><?php echo round(($attempted_questions / $total_questions) * 100, 1); ?>%</span>
                    </div>
                    <div class="performance-bar">
                        <div class="performance-fill" style="width: <?php echo ($attempted_questions / $total_questions) * 100; ?>%; background: #667eea;"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Score vs Passing</span>
                        <span><?php echo $percentage; ?>% / <?php echo $exam['passing_marks']; ?>%</span>
                    </div>
                    <div class="performance-bar">
                        <div class="performance-fill" style="width: <?php echo $percentage; ?>%; background: <?php echo $status == 'PASS' ? '#28a745' : '#dc3545'; ?>;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Question Review -->
            <?php if (count($user_answers_data) > 0): ?>
            <div class="question-review">
                <h4>Question Review</h4>
                <?php foreach ($user_answers_data as $index => $answer): ?>
                    <div class="question-item <?php 
                        echo $answer['user_answer'] === null ? 'unattempted' : 
                             ($answer['is_correct'] ? 'correct' : 'incorrect'); 
                    ?>">
                        <h6>Q<?php echo $index + 1; ?>. <?php echo htmlspecialchars($answer['question']); ?></h6>
                        <p class="mb-2"><strong>Your Answer:</strong> 
                            <?php if ($answer['user_answer']): ?>
                                <span class="option-badge option-user"><?php echo $answer['user_answer']; ?></span>
                            <?php else: ?>
                                <span class="text-muted">Not Attempted</span>
                            <?php endif; ?>
                        </p>
                        <p class="mb-2"><strong>Correct Answer:</strong> 
                            <span class="option-badge option-correct"><?php echo $answer['correct_answer']; ?></span>
                        </p>
                        <p><strong>Marks:</strong> <?php echo $answer['marks_obtained']; ?>/<?php echo $answer['marks']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="index.php" class="btn-action btn-primary">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
                
                <button onclick="window.print()" class="btn-action btn-secondary">
                    <i class="fas fa-print me-2"></i>Print Result
                </button>
                
                <?php if ($status == 'PASS'): ?>
                <button onclick="alert('Certificate feature coming soon!')" class="btn-action btn-success">
                    <i class="fas fa-certificate me-2"></i>Get Certificate
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Animate progress bars
        document.addEventListener('DOMContentLoaded', function() {
            const progressFills = document.querySelectorAll('.performance-fill');
            progressFills.forEach(fill => {
                const width = fill.style.width;
                fill.style.width = '0%';
                setTimeout(() => {
                    fill.style.width = width;
                }, 500);
            });
            
            // Confetti effect for pass
            <?php if ($status == 'PASS'): ?>
            setTimeout(createConfetti, 1000);
            <?php endif; ?>
        });
        
        function createConfetti() {
            const colors = ['#667eea', '#764ba2', '#28a745', '#20c997', '#ffc107'];
            const confettiCount = 50;
            
            for (let i = 0; i < confettiCount; i++) {
                const confetti = document.createElement('div');
                confetti.style.position = 'fixed';
                confetti.style.width = '10px';
                confetti.style.height = '10px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.borderRadius = '50%';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.top = '-20px';
                confetti.style.zIndex = '9999';
                confetti.style.pointerEvents = 'none';
                document.body.appendChild(confetti);
                
                // Animation
                const animation = confetti.animate([
                    { transform: 'translateY(0) rotate(0deg)', opacity: 1 },
                    { transform: `translateY(${window.innerHeight}px) rotate(${Math.random() * 360}deg)`, opacity: 0 }
                ], {
                    duration: Math.random() * 2000 + 1000,
                    easing: 'cubic-bezier(0.215, 0.610, 0.355, 1)'
                });
                
                animation.onfinish = () => confetti.remove();
            }
        }
    </script>
</body>
</html>