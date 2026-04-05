<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Check if user has admin/teacher role
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'teacher'])) {
    die("Unauthorized access!");
}

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$stmt = $pdo->prepare("SELECT * FROM exam_table WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Exam not found!");
}

// Handle delete question
if (isset($_GET['delete_question'])) {
    $question_id = $_GET['delete_question'];
    $stmt = $pdo->prepare("DELETE FROM question_table WHERE question_id = ?");
    $stmt->execute([$question_id]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - <?php echo $exam['exam_title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Manage Questions: <?php echo $exam['exam_title']; ?></h2>
        <p class="text-muted">Exam Date: <?php echo $exam['exam_date']; ?> | Time: <?php echo $exam['exam_time']; ?></p>
        
        <!-- Add Question Button -->
        <div class="mb-3">
            <a href="add_question.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Question
            </a>
            <a href="exam_crud.php" class="btn btn-secondary">Back to Exams</a>
        </div>
        
        <!-- Questions List -->
        <div class="card">
            <div class="card-header">
                <h5>Exam Questions</h5>
            </div>
            <div class="card-body">
                <?php
                $stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY question_id");
                $stmt->execute([$exam_id]);
                $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($questions)) {
                    echo "<div class='alert alert-info'>No questions added yet. Add your first question!</div>";
                } else {
                    $total_marks = 0;
                    foreach ($questions as $index => $question) {
                        $total_marks += $question['marks'];
                        echo "<div class='card mb-3'>";
                        echo "<div class='card-body'>";
                        echo "<h5>Q" . ($index + 1) . ". {$question['question_text']} <span class='badge bg-secondary'>{$question['marks']} marks</span></h5>";
                        
                        echo "<div class='row mt-3'>";
                        echo "<div class='col-md-6'>";
                        echo "<p><strong>A)</strong> {$question['option_a']} " . ($question['correct_answer'] == 'A' ? '<span class="badge bg-success">Correct</span>' : '') . "</p>";
                        echo "<p><strong>B)</strong> {$question['option_b']} " . ($question['correct_answer'] == 'B' ? '<span class="badge bg-success">Correct</span>' : '') . "</p>";
                        echo "</div>";
                        echo "<div class='col-md-6'>";
                        echo "<p><strong>C)</strong> {$question['option_c']} " . ($question['correct_answer'] == 'C' ? '<span class="badge bg-success">Correct</span>' : '') . "</p>";
                        echo "<p><strong>D)</strong> {$question['option_d']} " . ($question['correct_answer'] == 'D' ? '<span class="badge bg-success">Correct</span>' : '') . "</p>";
                        echo "</div>";
                        echo "</div>";
                        
                        echo "<div class='mt-3'>";
                        echo "<a href='edit_question.php?question_id={$question['question_id']}' class='btn btn-sm btn-warning'>Edit</a> ";
                        echo "<a href='manage_questions.php?exam_id={$exam_id}&delete_question={$question['question_id']}' 
                               class='btn btn-sm btn-danger' 
                               onclick='return confirm(\"Are you sure you want to delete this question?\")'>Delete</a>";
                        echo "</div>";
                        echo "</div></div>";
                    }
                    
                    echo "<div class='alert alert-info'>Total Questions: " . count($questions) . " | Total Marks: $total_marks</div>";
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>