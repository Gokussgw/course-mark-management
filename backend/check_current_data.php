<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
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

    echo "=== CURRENT DATABASE CONTENT ===" . PHP_EOL;

    echo "Students and Advisors:" . PHP_EOL;
    $stmt = $pdo->query('SELECT id, name, email, matric_number, role FROM users WHERE role IN ("student", "advisor")');
    $users = $stmt->fetchAll();
    foreach ($users as $user) {
        echo "- {$user['role']}: {$user['name']} ({$user['email']}) - {$user['matric_number']}" . PHP_EOL;
    }

    echo PHP_EOL . "Courses:" . PHP_EOL;
    $stmt = $pdo->query('SELECT id, code, name, lecturer_id FROM courses');
    $courses = $stmt->fetchAll();
    foreach ($courses as $course) {
        echo "- {$course['code']}: {$course['name']} (Lecturer ID: {$course['lecturer_id']})" . PHP_EOL;
    }

    echo PHP_EOL . "Sample Final Marks Data:" . PHP_EOL;
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM final_marks_custom');
    $count = $stmt->fetch();
    echo "Total records: {$count['count']}" . PHP_EOL;

    $stmt = $pdo->query('
        SELECT u.name, c.code, fm.assignment_percentage, fm.quiz_percentage, 
               fm.test_percentage, fm.final_exam_percentage, fm.final_grade, fm.letter_grade, fm.gpa
        FROM final_marks_custom fm 
        JOIN users u ON fm.student_id = u.id 
        JOIN courses c ON fm.course_id = c.id 
        ORDER BY u.name, c.code
    ');
    $marks = $stmt->fetchAll();
    foreach ($marks as $mark) {
        echo "- {$mark['name']} in {$mark['code']}: Assignment {$mark['assignment_percentage']}%, Quiz {$mark['quiz_percentage']}%, Test {$mark['test_percentage']}%, Final {$mark['final_exam_percentage']}% = Grade {$mark['final_grade']} ({$mark['letter_grade']}, GPA {$mark['gpa']})" . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
