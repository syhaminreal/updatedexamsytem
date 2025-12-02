<?php
require_once 'db_connection.php';

$question_id = $_GET['question_id'];
$stmt = $pdo->prepare("SELECT * FROM question_table WHERE question_id = ?");
$stmt->execute([$question_id]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$question) {
    die("Question not found!");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = $_POST['question_text'];
    $option_a = $_POST['option_a'];
    $option_b = $_POST['option_b'];
    $option_c = $_POST['option_c'];
    $option_d = $_POST['option_d'];
    $correct_answer = $_POST['correct_answer'];
    $marks = $_POST['marks'];
    
    $stmt = $pdo->prepare("UPDATE question_table SET question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_answer = ?, marks = ? WHERE question_id = ?");
    
    if ($stmt->execute([$question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $marks, $question_id])) {
        header("Location: manage_questions.php?exam_id=" . $question['exam_id']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Edit Question</h2>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Question Text *</label>
                        <textarea class="form-control" name="question_text" rows="3" required><?php echo $question['question_text']; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Option A *</label>
                                <input type="text" class="form-control" name="option_a" value="<?php echo $question['option_a']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Option B *</label>
                                <input type="text" class="form-control" name="option_b" value="<?php echo $question['option_b']; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Option C *</label>
                                <input type="text" class="form-control" name="option_c" value="<?php echo $question['option_c']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Option D *</label>
                                <input type="text" class="form-control" name="option_d" value="<?php echo $question['option_d']; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Correct Answer *</label>
                                <select class="form-control" name="correct_answer" required>
                                    <option value="A" <?php echo $question['correct_answer'] == 'A' ? 'selected' : ''; ?>>Option A</option>
                                    <option value="B" <?php echo $question['correct_answer'] == 'B' ? 'selected' : ''; ?>>Option B</option>
                                    <option value="C" <?php echo $question['correct_answer'] == 'C' ? 'selected' : ''; ?>>Option C</option>
                                    <option value="D" <?php echo $question['correct_answer'] == 'D' ? 'selected' : ''; ?>>Option D</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Marks *</label>
                                <input type="number" class="form-control" name="marks" value="<?php echo $question['marks']; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Update Question</button>
                        <a href="manage_questions.php?exam_id=<?php echo $question['exam_id']; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>