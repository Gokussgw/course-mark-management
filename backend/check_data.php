<?php
// Database configuration (same as marks-api.php)
$host = 'localhost';
$dbname = 'course_mark_management';
$username = 'root';
$password = '';

// Connect to database
$pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

echo "=== CHECKING DATABASE DATA ===\n\n";

// Check courses
echo "COURSES:\n";
$stmt = $pdo->query("SELECT c.*, u.name as lecturer_name FROM courses c JOIN users u ON c.lecturer_id = u.id");
$courses = $stmt->fetchAll();
if (empty($courses)) {
    echo "No courses found!\n";
} else {
    foreach ($courses as $course) {
        echo "ID: {$course['id']}, Code: {$course['code']}, Name: {$course['name']}, Lecturer: {$course['lecturer_name']}\n";
    }
}

echo "\nENROLLMENTS:\n";
$stmt = $pdo->query("SELECT e.*, c.code, u.name as student_name FROM enrollments e JOIN courses c ON e.course_id = c.id JOIN users u ON e.student_id = u.id");
$enrollments = $stmt->fetchAll();
if (empty($enrollments)) {
    echo "No enrollments found!\n";
} else {
    foreach ($enrollments as $enrollment) {
        echo "Course: {$enrollment['code']}, Student: {$enrollment['student_name']}\n";
    }
}

echo "\nASSESSMENTS:\n";
$stmt = $pdo->query("SELECT a.*, c.code FROM assessments a JOIN courses c ON a.course_id = c.id");
$assessments = $stmt->fetchAll();
if (empty($assessments)) {
    echo "No assessments found!\n";
} else {
    foreach ($assessments as $assessment) {
        echo "Course: {$assessment['code']}, Type: {$assessment['type']}, Name: {$assessment['name']}, Weight: {$assessment['weight']}%\n";
    }
}

echo "\nMARKS:\n";
$stmt = $pdo->query("SELECT m.*, a.name as assessment_name, u.name as student_name FROM marks m JOIN assessments a ON m.assessment_id = a.id JOIN users u ON m.student_id = u.id LIMIT 10");
$marks = $stmt->fetchAll();
if (empty($marks)) {
    echo "No marks found!\n";
} else {
    foreach ($marks as $mark) {
        echo "Student: {$mark['student_name']}, Assessment: {$mark['assessment_name']}, Mark: {$mark['mark']}\n";
    }
}
