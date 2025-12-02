<?php
// admin/delete_exam.php
session_start();
include '../db_connection.php';

// Authentication check
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php");
//     exit();
// }

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Soft delete using PDO
    $stmt = $pdo->prepare("UPDATE exams SET is_deleted = 1 WHERE exam_id = ?");
    
    if ($stmt->execute([$id])) {
        header("Location: manage_exams.php?msg=deleted");
    } else {
        header("Location: manage_exams.php?error=delete_failed");
    }
} else {
    header("Location: manage_exams.php");
}
exit();
?>
