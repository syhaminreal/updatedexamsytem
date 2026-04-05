<?php
require_once 'db_connection.php';

// Handle delete exam
if (isset($_GET['delete_exam'])) {
    $exam_id = $_GET['delete_exam'];
    $stmt = $pdo->prepare("DELETE FROM exam_table WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $message = "Exam deleted successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">📝 Exam Management System</h2>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <!-- Create New Exam Button -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <h5 class="card-title">Create New Exam</h5>
                <p class="card-text">Create a new examination with date, time, and questions</p>
                <a href="create_exam.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle"></i> Create New Exam
                </a>
            </div>
        </div>
        
        <!-- Exams List -->
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-list-task"></i> All Exams</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Exam Title</th>
                            <th>Date & Time</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM exam_table ORDER BY exam_date DESC, exam_time DESC");
                        while ($exam = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>";
                            echo "<td>{$exam['exam_id']}</td>";
                            echo "<td><strong>{$exam['exam_title']}</strong><br><small class='text-muted'>{$exam['exam_description']}</small></td>";
                            echo "<td>{$exam['exam_date']}<br><small>{$exam['exam_time']}</small></td>";
                            echo "<td>{$exam['exam_duration_minutes']} min<br><small>{$exam['total_marks']} marks</small></td>";
                            echo "<td><span class='badge bg-" . getStatusColor($exam['exam_status']) . "'>{$exam['exam_status']}</span></td>";
                            echo "<td>
                                    <div class='btn-group'>
                                        <a href='mange_question.php?exam_id={$exam['exam_id']}' class='btn btn-sm btn-info' title='Manage Questions'>
                                            <i class='bi bi-question-circle'></i>
                                        </a>
                                        <a href='edit_exam.php?exam_id={$exam['exam_id']}' class='btn btn-sm btn-warning' title='Edit Exam'>
                                            <i class='bi bi-pencil'></i>
                                        </a>
                                        <a href='exam_crud.php?delete_exam={$exam['exam_id']}' class='btn btn-sm btn-danger' 
                                           onclick='return confirm(\"Are you sure you want to delete this exam? All questions will be deleted.\")' title='Delete Exam'>
                                            <i class='bi bi-trash'></i>
                                        </a>
                                    </div>
                                  </td>";
                            echo "</tr>";
                        }
                        
                        function getStatusColor($status) {
                            switch($status) {
                                case 'scheduled': return 'primary';
                                case 'ongoing': return 'warning';
                                case 'completed': return 'success';
                                case 'cancelled': return 'danger';
                                default: return 'secondary';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- View Public Exams -->
        <div class="mt-4 text-center">
            <a href="view_exams.php" class="btn btn-outline-primary">
                <i class="bi bi-eye"></i> View Public Exams List
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>