<?php
session_start();
require_once 'db_connection.php';

// Check if exam is in progress
if (!isset($_SESSION['attempt_id']) || !isset($_SESSION['exam_id'])) {
    header('Location: index.php');
    exit();
}

$attempt_id = $_SESSION['attempt_id'];
$exam_id = $_SESSION['exam_id'];

// Get exam details
$stmt = $pdo->prepare("SELECT * FROM exams WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Get questions for this exam
$stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY question_id");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Check if time is up
$current_time = time();
$start_time = $_SESSION['start_time'];
$duration = $_SESSION['exam_duration'];
$time_left = $duration - ($current_time - $start_time);

if ($time_left <= 0) {
    // Time's up - auto submit
    header('Location: submit_exam.php?time_up=1');
    exit();
}

// Save answers as user selects them (via AJAX or form)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_answer'])) {
    $question_id = $_POST['question_id'];
    $user_answer = $_POST['user_answer'];
    
    // Check if answer already exists
    $stmt = $pdo->prepare("SELECT answer_id FROM user_answers WHERE attempt_id = ? AND question_id = ?");
    $stmt->execute([$attempt_id, $question_id]);
    
    if ($stmt->rowCount() > 0) {
        // Update existing answer
        $stmt = $pdo->prepare("UPDATE user_answers SET user_answer = ? WHERE attempt_id = ? AND question_id = ?");
        $stmt->execute([$user_answer, $attempt_id, $question_id]);
    } else {
        // Insert new answer
        $stmt = $pdo->prepare("INSERT INTO user_answers (attempt_id, question_id, user_answer) VALUES (?, ?, ?)");
        $stmt->execute([$attempt_id, $question_id, $user_answer]);
    }
    
    echo "Answer saved";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taking Exam - <?php echo htmlspecialchars($exam['exam_title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .exam-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .timer-container {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 1.2em;
            font-weight: bold;
            z-index: 1000;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .question-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 5px solid #667eea;
        }
        .question-number {
            background: #667eea;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .option-label {
            display: block;
            padding: 12px 15px;
            margin-bottom: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .option-label:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .option-label.selected {
            border-color: #28a745;
            background: #d4edda;
        }
        .option-input {
            display: none;
        }
        .option-input:checked + .option-label {
            border-color: #28a745;
            background: #d4edda;
        }
        .question-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 15px;
            box-shadow: 0 -5px 15px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .question-nav-btns {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
        .nav-btn:hover {
            background: #5a6fd8;
            color: white;
        }
        .question-dots {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }
        .question-dot {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e9ecef;
            color: #495057;
            text-decoration: none;
            font-weight: bold;
        }
        .question-dot.answered {
            background: #28a745;
            color: white;
        }
        .question-dot.current {
            background: #667eea;
            color: white;
            border: 2px solid #495057;
        }
    </style>
</head>
<body>
    <!-- Timer -->
    <div class="timer-container" id="timer">
        Time Left: <span id="time-display">00:00</span>
    </div>

    <div class="exam-container">
        <!-- Exam Header -->
        <div class="mb-4">
            <h2><?php echo htmlspecialchars($exam['exam_title']); ?></h2>
            <p class="text-muted">Student: <?php echo htmlspecialchars($_SESSION['student_name']); ?></p>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar" id="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
        </div>

        <!-- Questions -->
        <form id="examForm" action="submit_exam.php" method="POST">
            <?php foreach ($questions as $index => $question): ?>
                <div class="question-card" id="question-<?php echo $question['question_id']; ?>" 
                     style="<?php echo $index > 0 ? 'display: none;' : ''; ?>">
                    
                    <div class="question-number"><?php echo $index + 1; ?></div>
                    
                    <h5 class="mb-4"><?php echo htmlspecialchars($question['question_text']); ?></h5>
                    <p class="text-muted mb-3">(<?php echo $question['marks']; ?> marks)</p>
                    
                    <div class="options">
                        <div class="mb-3">
                            <input type="radio" name="answer_<?php echo $question['question_id']; ?>" 
                                   value="A" id="q<?php echo $question['question_id']; ?>_a" 
                                   class="option-input">
                            <label for="q<?php echo $question['question_id']; ?>_a" class="option-label">
                                <strong>A)</strong> <?php echo htmlspecialchars($question['option_a']); ?>
                            </label>
                        </div>
                        
                        <div class="mb-3">
                            <input type="radio" name="answer_<?php echo $question['question_id']; ?>" 
                                   value="B" id="q<?php echo $question['question_id']; ?>_b" 
                                   class="option-input">
                            <label for="q<?php echo $question['question_id']; ?>_b" class="option-label">
                                <strong>B)</strong> <?php echo htmlspecialchars($question['option_b']); ?>
                            </label>
                        </div>
                        
                        <div class="mb-3">
                            <input type="radio" name="answer_<?php echo $question['question_id']; ?>" 
                                   value="C" id="q<?php echo $question['question_id']; ?>_c" 
                                   class="option-input">
                            <label for="q<?php echo $question['question_id']; ?>_c" class="option-label">
                                <strong>C)</strong> <?php echo htmlspecialchars($question['option_c']); ?>
                            </label>
                        </div>
                        
                        <div class="mb-3">
                            <input type="radio" name="answer_<?php echo $question['question_id']; ?>" 
                                   value="D" id="q<?php echo $question['question_id']; ?>_d" 
                                   class="option-input">
                            <label for="q<?php echo $question['question_id']; ?>_d" class="option-label">
                                <strong>D)</strong> <?php echo htmlspecialchars($question['option_d']); ?>
                            </label>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </form>

        <!-- Question Navigation -->
        <div class="question-nav">
            <div class="question-nav-btns">
                <button type="button" class="btn btn-outline-primary" id="prevBtn" onclick="prevQuestion()" disabled>
                    ← Previous
                </button>
                
                <div class="question-dots">
                    <?php foreach ($questions as $index => $question): ?>
                        <a href="#question-<?php echo $question['question_id']; ?>" 
                           class="question-dot <?php echo $index == 0 ? 'current' : ''; ?>" 
                           data-index="<?php echo $index; ?>"
                           onclick="goToQuestion(<?php echo $index; ?>)">
                            <?php echo $index + 1; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" class="btn btn-outline-primary" id="nextBtn" onclick="nextQuestion()">
                    Next →
                </button>
            </div>
            
            <div class="text-center mt-3">
                <button type="button" class="btn btn-danger btn-lg" onclick="confirmSubmit()">
                    Submit Exam
                </button>
            </div>
        </div>
    </div>

    <script>
        // Timer functionality
        const totalSeconds = <?php echo $time_left; ?>;
        let timeLeft = totalSeconds;
        
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('time-display').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Update progress bar
            const progress = ((totalSeconds - timeLeft) / totalSeconds) * 100;
            document.getElementById('progress-bar').style.width = `${progress}%`;
            
            // Color change based on time
            if (timeLeft < 300) { // Less than 5 minutes
                document.getElementById('timer').style.background = '#dc3545';
            } else if (timeLeft < 600) { // Less than 10 minutes
                document.getElementById('timer').style.background = '#ffc107';
            }
            
            if (timeLeft <= 0) {
                alert('Time is up! Submitting exam...');
                document.getElementById('examForm').submit();
            } else {
                timeLeft--;
            }
        }
        
        setInterval(updateTimer, 1000);
        
        // Question navigation
        let currentQuestion = 0;
        const totalQuestions = <?php echo count($questions); ?>;
        const questionCards = document.querySelectorAll('.question-card');
        
        function showQuestion(index) {
            // Hide all questions
            questionCards.forEach(card => card.style.display = 'none');
            
            // Show selected question
            questionCards[index].style.display = 'block';
            
            // Update navigation buttons
            document.getElementById('prevBtn').disabled = index === 0;
            document.getElementById('nextBtn').disabled = index === totalQuestions - 1;
            
            // Update question dots
            document.querySelectorAll('.question-dot').forEach((dot, i) => {
                dot.classList.remove('current');
                if (i === index) {
                    dot.classList.add('current');
                }
            });
            
            currentQuestion = index;
        }
        
        function nextQuestion() {
            if (currentQuestion < totalQuestions - 1) {
                showQuestion(currentQuestion + 1);
            }
        }
        
        function prevQuestion() {
            if (currentQuestion > 0) {
                showQuestion(currentQuestion - 1);
            }
        }
        
        function goToQuestion(index) {
            showQuestion(index);
        }
        
        // Save answer on selection
        document.querySelectorAll('.option-input').forEach(radio => {
            radio.addEventListener('change', function() {
                const questionId = this.name.replace('answer_', '');
                const answer = this.value;
                
                // Mark question as answered
                const dotIndex = Array.from(this.closest('.question-card').parentNode.children).indexOf(this.closest('.question-card'));
                document.querySelectorAll('.question-dot')[dotIndex].classList.add('answered');
                
                // Send answer to server (optional - you can remove this if not needed)
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `save_answer=1&question_id=${questionId}&user_answer=${answer}`
                });
            });
        });
        
        // Auto-save every 30 seconds
        setInterval(() => {
            const formData = new FormData(document.getElementById('examForm'));
            fetch('', {
                method: 'POST',
                body: formData
            });
        }, 30000);
        
        // Submit confirmation
        function confirmSubmit() {
            const answered = document.querySelectorAll('.question-dot.answered').length;
            const unanswered = totalQuestions - answered;
            
            let message = 'Are you sure you want to submit the exam?';
            if (unanswered > 0) {
                message += `\n\nYou have ${unanswered} unanswered question(s).`;
            }
            
            if (confirm(message)) {
                document.getElementById('examForm').submit();
            }
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === ' ') {
                nextQuestion();
            } else if (e.key === 'ArrowLeft') {
                prevQuestion();
            } else if (e.key >= '1' && e.key <= '9') {
                const num = parseInt(e.key) - 1;
                if (num < totalQuestions) {
                    goToQuestion(num);
                }
            }
        });
    </script>
</body>
</html>