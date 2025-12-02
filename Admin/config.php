<?php
// config.php
session_start();

// Site configuration
define('SITE_NAME', 'Exam Management System');
define('SITE_URL', 'http://localhost/exam_system/');
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'exam_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Security
define('SECRET_KEY', 'your-secret-key-here');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Auto-load classes
spl_autoload_register(function ($class) {
    require_once 'classes/' . $class . '.php';
});
?>