<?php
require_once 'db_connection.php';

$exam_id = $_GET['exam_id'];
$stmt = $pdo->prepare("SELECT * FROM exam_table WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Exam not found!");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exam_title = $_POST['exam_title'];
    $exam_description = $_POST['exam_description'];
    $exam_duration_minutes = $_POST['exam_duration_minutes'];
    $total_marks = $_POST['total_marks'];
    $passing_marks = $_POST['passing_marks'];
    $exam_date = $_POST['exam_date'];
    $exam_time = $_POST['exam_time'];
    
    $stmt = $pdo->prepare("UPDATE exam_table SET exam_title = ?, exam_description = ?, exam_duration_minutes = ?, total_marks = ?, passing_marks = ?, exam_date = ?, exam_time = ? WHERE exam_id = ?");
    
    if ($stmt->execute([$exam_title, $exam_description, $exam_duration_minutes, $total_marks, $passing_marks, $exam_date, $exam_time, $exam_id])) {
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
    <title>Edit Exam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">Edit Exam: <?php echo $exam['exam_title']; ?></h2>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Exam Title *</label>
                                <input type="text" class="form-control" name="exam_title" value="<?php echo $exam['exam_title']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Duration (minutes) *</label>
                                <input type="number" class="form-control" name="exam_duration_minutes" value="<?php echo $exam['exam_duration_minutes']; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="exam_description" rows="2"><?php echo $exam['exam_description']; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Total Marks *</label>
                                <input type="number" class="form-control" name="total_marks" value="<?php echo $exam['total_marks']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Passing Marks</label>
                                <input type="number" class="form-control" name="passing_marks" value="<?php echo $exam['passing_marks']; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Exam Date *</label>
                                <input type="date" class="form-control" name="exam_date" value="<?php echo $exam['exam_date']; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Exam Time *</label>
                        <input type="time" class="form-control" name="exam_time" value="<?php echo $exam['exam_time']; ?>" required>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Update Exam</button>
                        <a href="exam_crud.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>