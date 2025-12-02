<?php
// staff/create_exam.php
session_start();
require_once '../db_connection.php'; // This provides $pdo

// If you want, you can temporarily skip authentication for now
// // Authentication check
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php");
//     exit();
// }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exam_title = trim($_POST['exam_title']);
    $exam_description = trim($_POST['exam_description']);
    $exam_duration = (int)$_POST['exam_duration'];
    $total_marks = (int)$_POST['total_marks'];
    $passing_marks = (int)$_POST['passing_marks'];
    $exam_status = $_POST['exam_status'];

    // Validation
    if (empty($exam_title)) {
        $error = "Exam title is required!";
    } elseif ($passing_marks > $total_marks) {
        $error = "Passing marks cannot be greater than total marks!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO exams (exam_title, exam_description, exam_duration, total_marks, passing_marks, exam_status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$exam_title, $exam_description, $exam_duration, $total_marks, $passing_marks, $exam_status]);
            
            $success = "Exam created successfully!";
            header("refresh:2;url=manage_exams.php");
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Exam</title>
    <link rel="stylesheet" href="./create.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Create New Exam</h1>
            <a href="manage_exams.php" class="btn-back">← Back to Exams</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="exam_title">Exam Title *</label>
                    <input type="text" id="exam_title" name="exam_title" required>
                </div>

                <div class="form-group">
                    <label for="exam_description">Description</label>
                    <textarea id="exam_description" name="exam_description" rows="4"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="exam_duration">Duration (minutes)</label>
                        <input type="number" id="exam_duration" name="exam_duration" value="60" min="1">
                    </div>

                    <div class="form-group">
                        <label for="total_marks">Total Marks</label>
                        <input type="number" id="total_marks" name="total_marks" value="100" min="1">
                    </div>

                    <div class="form-group">
                        <label for="passing_marks">Passing Marks</label>
                        <input type="number" id="passing_marks" name="passing_marks" value="40" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label for="exam_status">Status</label>
                    <select id="exam_status" name="exam_status">
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Exam</button>
                    <a href="manage_exams.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
