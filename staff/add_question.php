<?php
// admin/add_question.php
session_start();
include '../db_connection.php';

// Authentication check
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php");
//     exit();
// }

$exam_id = (int)$_GET['exam_id'];
$error = '';
$success = '';

// Fetch exam details using PDO
$stmt = $pdo->prepare("SELECT exam_title FROM exams WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    header("Location: manage_exams.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = trim($_POST['question_text']);
    $option_a = trim($_POST['option_a']);
    $option_b = trim($_POST['option_b']);
    $option_c = trim($_POST['option_c']);
    $option_d = trim($_POST['option_d']);
    $correct_answer = $_POST['correct_answer'];
    $marks = (int)$_POST['marks'];
    
    // Validation
    if (empty($question_text) || empty($option_a) || empty($option_b)) {
        $error = "Question text and at least two options are required!";
    } else {
        // Prepare options array
        $options = [
            'a' => $option_a,
            'b' => $option_b,
            'c' => $option_c,
            'd' => $option_d
        ];
        $options_json = json_encode($options);
        
        // Insert question using PDO
        $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, options, correct_answer, marks) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$exam_id, $question_text, $options_json, $correct_answer, $marks])) {
            $success = "Question added successfully!";
            header("refresh:2;url=manage_questions.php?exam_id=" . $exam_id);
        } else {
            $error = "Error adding question!";
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
    <link rel="stylesheet" href="./style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Add Question to: <?php echo htmlspecialchars($exam['exam_title']); ?></h1>
            <a href="manage_questions.php?exam_id=<?php echo $exam_id; ?>" class="btn-back">← Back to Questions</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <form method="POST" action="">
                <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                
                <div class="form-group">
                    <label for="question_text">Question Text *</label>
                    <textarea id="question_text" name="question_text" rows="3" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="option_a">Option A *</label>
                        <input type="text" id="option_a" name="option_a" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="option_b">Option B *</label>
                        <input type="text" id="option_b" name="option_b" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="option_c">Option C</label>
                        <input type="text" id="option_c" name="option_c">
                    </div>
                    
                    <div class="form-group">
                        <label for="option_d">Option D</label>
                        <input type="text" id="option_d" name="option_d">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="correct_answer">Correct Answer *</label>
                        <select id="correct_answer" name="correct_answer" required>
                            <option value="a">Option A</option>
                            <option value="b">Option B</option>
                            <option value="c">Option C</option>
                            <option value="d">Option D</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="marks">Marks</label>
                        <input type="number" id="marks" name="marks" value="1" min="1">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Question</button>
                    <a href="manage_questions.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
