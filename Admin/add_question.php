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

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

if (!$exam_id) {
    header('Location: manage_exams.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = sanitize($_POST['question_text']);
    $option_a = sanitize($_POST['option_a']);
    $option_b = sanitize($_POST['option_b']);
    $option_c = sanitize($_POST['option_c']);
    $option_d = sanitize($_POST['option_d']);
    $correct_answer = sanitize($_POST['correct_answer']);
    $marks = intval($_POST['marks']);
    
    // Validate required fields
    if (empty($question_text) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d)) {
        $error = "All fields are required!";
    } elseif (!in_array($correct_answer, ['A', 'B', 'C', 'D'])) {
        $error = "Please select a valid correct answer!";
    } elseif ($marks < 1 || $marks > 100) {
        $error = "Marks must be between 1 and 100!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer, marks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$exam_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $marks])) {
            header("Location: manage_questions.php?exam_id=$exam_id");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Add New Question</h2>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Question Text *</label>
                        <textarea class="form-control" name="question_text" rows="3" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Option A *</label>
                                <input type="text" class="form-control" name="option_a" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Option B *</label>
                                <input type="text" class="form-control" name="option_b" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Option C *</label>
                                <input type="text" class="form-control" name="option_c" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Option D *</label>
                                <input type="text" class="form-control" name="option_d" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Correct Answer *</label>
                                <select class="form-control" name="correct_answer" required>
                                    <option value="">Select correct option</option>
                                    <option value="A">Option A</option>
                                    <option value="B">Option B</option>
                                    <option value="C">Option C</option>
                                    <option value="D">Option D</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Marks *</label>
                                <input type="number" class="form-control" name="marks" value="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Add Question</button>
                        <a href="mange_question.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>