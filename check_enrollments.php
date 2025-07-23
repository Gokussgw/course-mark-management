<?php
// Check what enrollments and courses we have in the database

require_once 'backend/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable('backend');
$dotenv->load();

try {
    $host = $_ENV['DB_HOST'];
    $dbname = $_ENV['DB_NAME'];
    $username = $_ENV['DB_USER'];
    $password = $_ENV['DB_PASS'];

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "=== STUDENTS ===\n";
    $stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE role = 'student' LIMIT 5");
    $students = $stmt->fetchAll();
    foreach ($students as $student) {
        echo "ID: {$student['id']}, Name: {$student['name']}, Email: {$student['email']}\n";
    }

    echo "\n=== COURSES ===\n";
    $stmt = $pdo->query("SELECT id, code, name, semester, academic_year FROM courses LIMIT 5");
    $courses = $stmt->fetchAll();
    foreach ($courses as $course) {
        echo "ID: {$course['id']}, Code: {$course['code']}, Name: {$course['name']}, Semester: {$course['semester']}, Year: {$course['academic_year']}\n";
    }

    echo "\n=== ENROLLMENTS ===\n";
    $stmt = $pdo->query("
        SELECT e.id, e.student_id, e.course_id, u.name as student_name, c.code as course_code, c.name as course_name
        FROM enrollments e
        JOIN users u ON e.student_id = u.id
        JOIN courses c ON e.course_id = c.id
        LIMIT 10
    ");
    $enrollments = $stmt->fetchAll();
    foreach ($enrollments as $enrollment) {
        echo "Enrollment ID: {$enrollment['id']}, Student: {$enrollment['student_name']} (ID: {$enrollment['student_id']}), Course: {$enrollment['course_code']} - {$enrollment['course_name']} (ID: {$enrollment['course_id']})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
