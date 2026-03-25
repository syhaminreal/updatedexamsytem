-- =====================================================
-- Exam System Database Schema
-- Database Name: exaam (keep as is per existing config)
-- =====================================================

-- Drop tables if they exist (in correct order)
DROP TABLE IF EXISTS user_answers;
DROP TABLE IF EXISTS exam_results;
DROP TABLE IF EXISTS exam_attempts;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS exams;
DROP TABLE IF EXISTS users;

-- =====================================================
-- Users Table
-- =====================================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    user_type ENUM('student', 'teacher', 'admin') NOT NULL DEFAULT 'student',
    phone VARCHAR(20) DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Remember-me feature columns
    remember_token VARCHAR(64) DEFAULT NULL,
    remember_expires DATETIME DEFAULT NULL,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_user_type (user_type)
);

-- =====================================================
-- Exams Table
-- =====================================================
CREATE TABLE exams (
    exam_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_title VARCHAR(200) NOT NULL,
    exam_description TEXT,
    exam_duration INT NOT NULL DEFAULT 60, -- in minutes
    total_marks INT NOT NULL DEFAULT 100,
    passing_marks INT NOT NULL DEFAULT 40,
    exam_status ENUM('active', 'inactive', 'draft') DEFAULT 'active',
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_exam_status (exam_status),
    INDEX idx_created_by (created_by)
);

-- =====================================================
-- Questions Table
-- =====================================================
CREATE TABLE questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    options JSON,
    correct_answer ENUM('A', 'B', 'C', 'D') NOT NULL,
    marks INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE,
    INDEX idx_exam_id (exam_id)
);

-- =====================================================
-- Exam Attempts Table
-- =====================================================
CREATE TABLE exam_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    student_email VARCHAR(100) DEFAULT NULL,
    student_roll VARCHAR(50) DEFAULT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE,
    INDEX idx_exam_id (exam_id),
    INDEX idx_student_email (student_email)
);

-- =====================================================
-- User Answers Table
-- =====================================================
CREATE TABLE user_answers (
    answer_id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    user_answer VARCHAR(10) DEFAULT NULL,
    is_correct TINYINT(1) DEFAULT 0,
    marks_obtained INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(attempt_id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE,
    UNIQUE KEY unique_attempt_question (attempt_id, question_id),
    INDEX idx_attempt_id (attempt_id)
);

-- =====================================================
-- Exam Results Table
-- =====================================================
CREATE TABLE exam_results (
    result_id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    exam_id INT NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    total_questions INT NOT NULL DEFAULT 0,
    attempted_questions INT NOT NULL DEFAULT 0,
    correct_answers INT NOT NULL DEFAULT 0,
    wrong_answers INT NOT NULL DEFAULT 0,
    total_marks INT NOT NULL DEFAULT 0,
    marks_obtained INT NOT NULL DEFAULT 0,
    percentage DECIMAL(5,2) DEFAULT 0.00,
    grade VARCHAR(2) DEFAULT 'F',
    status ENUM('PASS', 'FAIL') DEFAULT 'FAIL',
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(attempt_id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE,
    INDEX idx_exam_id (exam_id),
    INDEX idx_student_name (student_name)
);

-- =====================================================
-- Sample Data (Optional - for testing)
-- =====================================================

-- Insert sample admin/teacher user (password: admin123)
INSERT INTO users (username, email, password_hash, full_name, user_type, phone) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', '1234567890'),
('teacher', 'teacher@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Teacher', 'teacher', '9876543210'),
('student', 'student@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Student', 'student', '5551234567');

-- Insert sample exam
INSERT INTO exams (exam_title, exam_description, exam_duration, total_marks, passing_marks, exam_status, created_by) VALUES
('PHP Fundamentals Test', 'Test your knowledge of PHP basics including variables, functions, and loops', 30, 20, 10, 'active', 1),
('Web Development Quiz', 'Questions about HTML, CSS, JavaScript and web development concepts', 45, 50, 25, 'active', 1),
('Database Management', 'SQL queries, database design and normalization', 60, 100, 40, 'active', 1);

-- Insert sample questions for exam 1 (PHP Fundamentals)
INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer, marks) VALUES
(1, 'What does PHP stand for?', 'Personal Home Page', 'PHP: Hypertext Preprocessor', 'Preprocessor Home Page', 'Private Home Page', 'B', 2),
(1, 'Which symbol is used for variables in PHP?', '$', '#', '@', '%', 'A', 2),
(1, 'How do you start a PHP block?', '<?php', '<?', '<php', '<?php ', 'A', 2),
(1, 'Which function is used to output text in PHP?', 'echo', 'print', 'Both echo and print', 'printf', 'C', 2),
(1, 'What is the correct way to end a PHP statement?', '.', ';', ',', ':', 'B', 2);

-- Insert sample questions for exam 2 (Web Development)
INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer, marks) VALUES
(2, 'What does HTML stand for?', 'Hyper Text Markup Language', 'High Tech Modern Language', 'Hyperlink and Text Markup Language', 'Home Tool Markup Language', 'A', 5),
(2, 'Which CSS property is used to change text color?', 'text-color', 'font-color', 'color', 'text-style', 'C', 5),
(2, 'What is the correct JavaScript syntax to output "Hello World"?', 'echo "Hello World"', 'print("Hello World")', 'document.write("Hello World")', 'console.log("Hello World")', 'C', 5);

-- Insert sample questions for exam 3 (Database)
INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer, marks) VALUES
(3, 'Which SQL statement is used to extract data from a database?', 'GET', 'EXTRACT', 'SELECT', 'FROM', 'C', 10),
(3, 'What does SQL stand for?', 'Structured Query Language', 'Simple Query Language', 'Standard Query Language', 'System Question Language', 'A', 10),
(3, 'Which MySQL function is used to count records?', 'COUNT()', 'SUM()', 'TOTAL()', 'NUMBER()', 'A', 10);

-- =====================================================
-- Database Configuration Note
-- =====================================================
-- The current db_connection.php uses:
--   Host: localhost
--   Database: exaam (keep this name)
--   Username: root
--   Password: (empty)
--
-- If you need to create the database, run:
-- CREATE DATABASE exaam CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
