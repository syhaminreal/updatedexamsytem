<?php
// staff/manage_exams.php
session_start();
require_once '../db_connection.php'; // provides $pdo

// // Authentication check
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php");
//     exit();
// }

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("UPDATE exams SET is_deleted = 1 WHERE exam_id = ?");
    $stmt->execute([$id]);
    header("Location: manage_exams.php?msg=deleted");
    exit();
}

// Fetch all non-deleted exams
$stmt = $pdo->query("SELECT * FROM exams WHERE is_deleted = 0 ORDER BY created_at DESC");
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams</title>
    <link rel="stylesheet" href="./manage.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manage Exams</h1>
            <a href="create_exam.php" class="btn btn-primary">+ Add New Exam</a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php 
                if ($_GET['msg'] == 'deleted') echo "Exam deleted successfully!";
                if ($_GET['msg'] == 'updated') echo "Exam updated successfully!";
                ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <?php if (!empty($exams)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Duration</th>
                            <th>Marks</th>
                            <th>Passing</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exams as $row): ?>
                        <tr>
                            <td><?php echo $row['exam_id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['exam_title']); ?></strong>
                                <?php if ($row['exam_description']): ?>
                                    <br><small><?php echo substr(htmlspecialchars($row['exam_description']), 0, 50); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['exam_duration']; ?> min</td>
                            <td><?php echo $row['total_marks']; ?></td>
                            <td><?php echo $row['passing_marks']; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $row['exam_status']; ?>">
                                    <?php echo ucfirst($row['exam_status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                            <td class="actions">
                                <a href="edit_exam.php?id=<?php echo $row['exam_id']; ?>" class="btn-edit">Edit</a>
                                <a href="?delete=<?php echo $row['exam_id']; ?>" class="btn-delete" onclick="return confirm('Delete this exam?')">Delete</a>
                                <a href="manage_questions.php?exam_id=<?php echo $row['exam_id']; ?>" class="btn-view">Questions</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>No exams found. <a href="create_exam.php">Create your first exam</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
