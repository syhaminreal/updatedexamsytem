<?php
// staff/edit_question.php
session_start();
include '../db_connection.php';

// Check if questions table has options JSON column or individual columns
try {
    $pdo->query("SELECT options FROM questions LIMIT 1");
    $useJsonOptions = true;
} catch (PDOException $e) {
    $useJsonOptions = false;
}

$question_id = (int)$_GET['id'];
$error = '';
$success = '';

// Fetch question data using PDO
$stmt = $pdo->prepare("
    SELECT q.*, e.exam_title 
    FROM questions q 
    JOIN exams e ON q.exam_id = e.exam_id 
    WHERE q.question_id = ?
");
$stmt->execute([$question_id]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$question) {
    header("Location: manage_exams.php");
    exit();
}

// Get options based on column type
if ($useJsonOptions) {
    $options = json_decode($question['options'], true);
    $option_a = $options['a'] ?? '';
    $option_b = $options['b'] ?? '';
    $option_c = $options['c'] ?? '';
    $option_d = $options['d'] ?? '';
} else {
    $option_a = $question['option_a'] ?? '';
    $option_b = $question['option_b'] ?? '';
    $option_c = $question['option_c'] ?? '';
    $option_d = $question['option_d'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = trim($_POST['question_text']);
    $option_a = trim($_POST['option_a']);
    $option_b = trim($_POST['option_b']);
    $option_c = trim($_POST['option_c']);
    $option_d = trim($_POST['option_d']);
    $correct_answer = strtolower($_POST['correct_answer']);
    $marks = (int)$_POST['marks'];
    
    // Validation
    if (empty($question_text) || empty($option_a) || empty($option_b)) {
        $error = "Question text and at least two options are required!";
    } else {
        try {
            if ($useJsonOptions) {
                $updated_options = [
                    'a' => $option_a,
                    'b' => $option_b,
                    'c' => $option_c,
                    'd' => $option_d
                ];
                $options_json = json_encode($updated_options);
                
                $stmt = $pdo->prepare("
                    UPDATE questions 
                    SET question_text = ?, options = ?, correct_answer = ?, marks = ? 
                    WHERE question_id = ?
                ");
                $stmt->execute([$question_text, $options_json, $correct_answer, $marks, $question_id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE questions 
                    SET question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_answer = ?, marks = ? 
                    WHERE question_id = ?
                ");
                $stmt->execute([$question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $marks, $question_id]);
            }
            
            $success = "Question updated successfully!";
            header("refresh:2;url=manage_questions.php?exam_id=" . $question['exam_id']);
        } catch (PDOException $e) {
            $error = "Error updating question: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question</title>
    <link rel="stylesheet" href="./style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Edit Question</h1>
        <a href="manage_questions.php?exam_id=<?php echo $question['exam_id']; ?>" class="btn-back">← Back to Questions</a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <form method="POST" action="">
            <div class="form-group">
                <label for="question_text">Question Text *</label>
                <textarea id="question_text" name="question_text" rows="3" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="option_a">Option A *</label>
                    <input type="text" id="option_a" name="option_a" value="<?php echo htmlspecialchars($option_a); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="option_b">Option B *</label>
                    <input type="text" id="option_b" name="option_b" value="<?php echo htmlspecialchars($option_b); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="option_c">Option C</label>
                    <input type="text" id="option_c" name="option_c" value="<?php echo htmlspecialchars($option_c); ?>">
                </div>
                
                <div class="form-group">
                    <label for="option_d">Option D</label>
                    <input type="text" id="option_d" name="option_d" value="<?php echo htmlspecialchars($option_d); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="correct_answer">Correct Answer *</label>
                    <select id="correct_answer" name="correct_answer" required>
                        <option value="a" <?php echo $question['correct_answer'] == 'a' ? 'selected' : ''; ?>>Option A</option>
                        <option value="b" <?php echo $question['correct_answer'] == 'b' ? 'selected' : ''; ?>>Option B</option>
                        <option value="c" <?php echo $question['correct_answer'] == 'c' ? 'selected' : ''; ?>>Option C</option>
                        <option value="d" <?php echo $question['correct_answer'] == 'd' ? 'selected' : ''; ?>>Option D</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="marks">Marks</label>
                    <input type="number" id="marks" name="marks" value="<?php echo $question['marks']; ?>" min="1">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Question</button>
                <a href="manage_questions.php?exam_id=<?php echo $question['exam_id']; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
