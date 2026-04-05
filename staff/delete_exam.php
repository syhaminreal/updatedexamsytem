<?php
// staff/delete_exam.php
session_start();
include '../db_connection.php';

try {
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        // Check if is_deleted column exists
        try {
            $pdo->query("SELECT is_deleted FROM exams LIMIT 1");
            $useSoftDelete = true;
        } catch (PDOException $e) {
            $useSoftDelete = false;
        }
        
        if ($useSoftDelete) {
            // Soft delete
            $stmt = $pdo->prepare("UPDATE exams SET is_deleted = 1 WHERE exam_id = ?");
            $stmt->execute([$id]);
        } else {
            // Hard delete (fallback)
            $stmt = $pdo->prepare("DELETE FROM exams WHERE exam_id = ?");
            $stmt->execute([$id]);
        }
        
        header("Location: manage_exams.php?msg=deleted");
        exit();
    } else {
        header("Location: manage_exams.php");
        exit();
    }
} catch (PDOException $e) {
    die("Error deleting exam: " . $e->getMessage());
}
?>
