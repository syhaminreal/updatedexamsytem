<?php
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exam_title = sanitize($_POST['exam_title']);
    $exam_description = sanitize($_POST['exam_description']);
    $exam_duration_minutes = intval($_POST['exam_duration_minutes']);
    $total_marks = intval($_POST['total_marks']);
    $passing_marks = intval($_POST['passing_marks']);
    $exam_date = sanitize($_POST['exam_date']);
    $exam_time = sanitize($_POST['exam_time']);
    
    $stmt = $pdo->prepare("INSERT INTO exam_table (exam_title, exam_description, exam_duration_minutes, total_marks, passing_marks, exam_date, exam_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$exam_title, $exam_description, $exam_duration_minutes, $total_marks, $passing_marks, $exam_date, $exam_time])) {
        header("Location: exam_crud.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Exam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">Create New Exam</h2>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Exam Title *</label>
                                <input type="text" class="form-control" name="exam_title" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Duration (minutes) *</label>
                                <input type="number" class="form-control" name="exam_duration_minutes" value="120" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="exam_description" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Total Marks *</label>
                                <input type="number" class="form-control" name="total_marks" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Passing Marks</label>
                                <input type="number" class="form-control" name="passing_marks" value="40">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Exam Date *</label>
                                <input type="date" class="form-control" name="exam_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Exam Time *</label>
                        <input type="time" class="form-control" name="exam_time" required>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Create Exam</button>
                        <a href="exam_crud.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>