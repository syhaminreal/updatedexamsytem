<?php
// admin/edit_exam.php
session_start();
include '../db_connection.php';

// // Authentication check
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php");
//     exit();
// }

$id = (int)$_GET['id'];
$error = '';
$success = '';

// Fetch exam data using PDO
$stmt = $pdo->prepare("SELECT * FROM exams WHERE exam_id = ? AND is_deleted = 0");
$stmt->execute([$id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    header("Location: manage_exams.php");
    exit();
}

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
        $stmt = $pdo->prepare("
            UPDATE exams 
            SET exam_title = ?, 
                exam_description = ?, 
                exam_duration = ?, 
                total_marks = ?, 
                passing_marks = ?, 
                exam_status = ?, 
                updated_at = NOW() 
            WHERE exam_id = ?
        ");
        
        if ($stmt->execute([$exam_title, $exam_description, $exam_duration, $total_marks, $passing_marks, $exam_status, $id])) {
            $success = "Exam updated successfully!";
            header("refresh:2;url=manage_exams.php?msg=updated");
        } else {
            $error = "Error updating exam!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Exam</title>
    <link rel="stylesheet" href="./edit.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Edit Exam</h1>
            <a href="manage_exams.php" class="btn-back">← Back to Exams</a>
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
                    <label for="exam_title">Exam Title *</label>
                    <input type="text" id="exam_title" name="exam_title" value="<?php echo htmlspecialchars($exam['exam_title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="exam_description">Description</label>
                    <textarea id="exam_description" name="exam_description" rows="4"><?php echo htmlspecialchars($exam['exam_description']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="exam_duration">Duration (minutes)</label>
                        <input type="number" id="exam_duration" name="exam_duration" value="<?php echo $exam['exam_duration']; ?>" min="1">
                    </div>
                    
                    <div class="form-group">
                        <label for="total_marks">Total Marks</label>
                        <input type="number" id="total_marks" name="total_marks" value="<?php echo $exam['total_marks']; ?>" min="1">
                    </div>
                    
                    <div class="form-group">
                        <label for="passing_marks">Passing Marks</label>
                        <input type="number" id="passing_marks" name="passing_marks" value="<?php echo $exam['passing_marks']; ?>" min="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="exam_status">Status</label>
                    <select id="exam_status" name="exam_status">
                        <option value="active" <?php echo $exam['exam_status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $exam['exam_status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Exam</button>
                    <a href="manage_exams.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
