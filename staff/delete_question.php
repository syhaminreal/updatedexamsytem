<?php
// admin/delete_question.php
session_start();
include '../db_connection.php';

// Authentication check
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php");
//     exit();
// }

if (isset($_GET['id']) && isset($_GET['exam_id'])) {
    $question_id = (int)$_GET['id'];
    $exam_id = (int)$_GET['exam_id'];
    
    // Delete question using PDO
    $stmt = $pdo->prepare("DELETE FROM questions WHERE question_id = ?");
    
    if ($stmt->execute([$question_id])) {
        header("Location: manage_questions.php?exam_id=" . $exam_id . "&msg=deleted");
    } else {
        header("Location: manage_questions.php?exam_id=" . $exam_id . "&error=delete_failed");
    }
} else {
    header("Location: manage_exams.php");
}
exit();
?>
