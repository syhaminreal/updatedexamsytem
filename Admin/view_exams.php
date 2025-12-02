<?php
require_once 'db_connection.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Exams</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">📋 Available Exams</h2>
        
        <div class="mb-3">
            <a href="exam_crud.php" class="btn btn-outline-primary">Go to Admin Panel</a>
        </div>
        
        <div class="row">
            <?php
            $stmt = $pdo->query("SELECT * FROM exam_table WHERE exam_status = 'scheduled' ORDER BY exam_date, exam_time");
            while ($exam = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Count questions
                $question_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM question_table WHERE exam_id = ?");
                $question_stmt->execute([$exam['exam_id']]);
                $question_count = $question_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                echo "<div class='col-md-4 mb-3'>";
                echo "<div class='card h-100'>";
                echo "<div class='card-header bg-primary text-white'>";
                echo "<h5 class='card-title mb-0'>{$exam['exam_title']}</h5>";
                echo "</div>";
                echo "<div class='card-body'>";
                echo "<p class='card-text'>{$exam['exam_description']}</p>";
                echo "<ul class='list-group list-group-flush'>";
                echo "<li class='list-group-item'><strong>Date:</strong> {$exam['exam_date']}</li>";
                echo "<li class='list-group-item'><strong>Time:</strong> {$exam['exam_time']}</li>";
                echo "<li class='list-group-item'><strong>Duration:</strong> {$exam['exam_duration_minutes']} minutes</li>";
                echo "<li class='list-group-item'><strong>Total Marks:</strong> {$exam['total_marks']}</li>";
                echo "<li class='list-group-item'><strong>Questions:</strong> {$question_count}</li>";
                echo "<li class='list-group-item'><strong>Passing Marks:</strong> {$exam['passing_marks']}</li>";
                echo "</ul>";
                echo "</div>";
                echo "<div class='card-footer text-center'>";
                echo "<span class='badge bg-success'>{$exam['exam_status']}</span>";
                echo "</div>";
                echo "</div></div>";
            }
            ?>
        </div>
    </div>
</body>
</html>