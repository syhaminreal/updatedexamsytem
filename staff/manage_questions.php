<?php
// staff/manage_questions.php
session_start();
require '../db_connection.php';

// Check if questions table has options JSON column or individual columns
try {
    $pdo->query("SELECT options FROM questions LIMIT 1");
    $useJsonOptions = true;
} catch (PDOException $e) {
    $useJsonOptions = false;
}

$exam_id = (int)$_GET['exam_id'];

// Fetch exam details
$stmt = $pdo->prepare("SELECT exam_title FROM exams WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    header("Location: manage_exams.php");
    exit();
}

// Fetch questions for this exam
$stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY question_id ASC");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions</title>
    <link rel="stylesheet" href="./style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manage Questions: <?php echo htmlspecialchars($exam['exam_title']); ?></h1>
            <div>
                <a href="manage_exams.php" class="btn-back">← Back to Exams</a>
                <a href="add_question.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-primary">+ Add Question</a>
            </div>
        </div>

        <div class="card">
            <?php if (!empty($questions)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Question</th>
                            <th>Options</th>
                            <th>Correct Answer</th>
                            <th>Marks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; foreach($questions as $row): ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><?php echo htmlspecialchars(substr($row['question_text'], 0, 100)); ?>...</td>
                            <td>
                                <?php 
                                if ($useJsonOptions) {
                                    $options = json_decode($row['options'], true);
                                    if (is_array($options)) {
                                        echo "A: " . htmlspecialchars($options['a'] ?? '') . "<br>";
                                        echo "B: " . htmlspecialchars($options['b'] ?? '') . "<br>";
                                        echo "C: " . htmlspecialchars($options['c'] ?? '') . "<br>";
                                        echo "D: " . htmlspecialchars($options['d'] ?? '');
                                    }
                                } else {
                                    echo "A: " . htmlspecialchars($row['option_a'] ?? '') . "<br>";
                                    echo "B: " . htmlspecialchars($row['option_b'] ?? '') . "<br>";
                                    echo "C: " . htmlspecialchars($row['option_c'] ?? '') . "<br>";
                                    echo "D: " . htmlspecialchars($row['option_d'] ?? '');
                                }
                                ?>
                            </td>
                            <td><?php echo strtoupper(htmlspecialchars($row['correct_answer'])); ?></td>
                            <td><?php echo $row['marks']; ?></td>
                            <td class="actions">
                                <a href="edit_question.php?id=<?php echo $row['question_id']; ?>" class="btn-edit">Edit</a>
                                <a href="delete_question.php?id=<?php echo $row['question_id']; ?>&exam_id=<?php echo $exam_id; ?>" class="btn-delete" onclick="return confirm('Delete this question?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>No questions found for this exam. <a href="add_question.php?exam_id=<?php echo $exam_id; ?>">Add first question</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
