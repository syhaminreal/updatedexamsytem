<?php
// staff/manage_exams.php
session_start();
require_once '../db_connection.php';

// Authentication (optional - enable if needed)
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

    <!-- Your CSS -->
    <link rel="stylesheet" href="./manage.css">
</head>
<body>

<div class="container">

    <!-- ================= HEADER ================= -->
    <div class="header">
        <h1>Manage Exams</h1>

        <div class="header-actions">
            <!-- ✅ BACK BUTTON -->
             <a href="index.php" class="btn btn-primary">Back</a>

            <!-- ADD EXAM BUTTON -->
            <a href="create_exam.php" class="btn btn-primary">+ Add New Exam</a>
        </div>
    </div>

    <!-- ================= ALERTS ================= -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?php 
                if ($_GET['msg'] == 'deleted') echo "Exam deleted successfully!";
                if ($_GET['msg'] == 'updated') echo "Exam updated successfully!";
            ?>
        </div>
    <?php endif; ?>

    <!-- ================= TABLE CARD ================= -->
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
                    <td><?= $row['exam_id']; ?></td>

                    <td>
                        <strong><?= htmlspecialchars($row['exam_title']); ?></strong>
                        <?php if ($row['exam_description']): ?>
                            <br>
                            <small>
                                <?= substr(htmlspecialchars($row['exam_description']), 0, 50); ?>...
                            </small>
                        <?php endif; ?>
                    </td>

                    <td><?= $row['exam_duration']; ?> min</td>
                    <td><?= $row['total_marks']; ?></td>
                    <td><?= $row['passing_marks']; ?></td>

                    <td>
                        <span class="status-badge status-<?= $row['exam_status']; ?>">
                            <?= ucfirst($row['exam_status']); ?>
                        </span>
                    </td>

                    <td>
                        <?= date('d M Y', strtotime($row['created_at'])); ?>
                    </td>

                    <td class="actions">
                        <a href="edit_exam.php?id=<?= $row['exam_id']; ?>" class="btn-edit">Edit</a>

                        <a href="?delete=<?= $row['exam_id']; ?>"
                           class="btn-delete"
                           onclick="return confirm('Delete this exam?')">
                            Delete
                        </a>

                        <a href="manage_questions.php?exam_id=<?= $row['exam_id']; ?>"
                           class="btn-view">
                            Questions
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>

        </table>

        <?php else: ?>

        <!-- ================= EMPTY STATE ================= -->
        <div class="empty-state">
            <p>
                No exams found.
                <a href="create_exam.php">Create your first exam</a>
            </p>
        </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
