<?php
// Debug the student course detail function directly

require_once __DIR__ . '/marks-api.php';

// Test database connection
$host = 'localhost';
$dbname = 'course_mark_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful\n";
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Test the query directly
$student_id = 4;
$course_id = 1;

echo "\nTesting course query...\n";
try {
    $courseStmt = $pdo->prepare("
        SELECT 
            c.id,
            c.code,
            c.name,
            c.semester,
            c.academic_year,
            c.description,
            u.name as lecturer_name,
            u.email as lecturer_email,
            e.id as enrollment_id
        FROM courses c
        LEFT JOIN users u ON c.lecturer_id = u.id
        LEFT JOIN enrollments e ON e.course_id = c.id AND e.student_id = ?
        WHERE c.id = ?
    ");
    $courseStmt->execute([$student_id, $course_id]);
    $course = $courseStmt->fetch(PDO::FETCH_ASSOC);

    if ($course) {
        echo "✅ Course found: " . $course['code'] . " - " . $course['name'] . "\n";
        echo "Enrollment ID: " . ($course['enrollment_id'] ?: 'NULL') . "\n";
    } else {
        echo "❌ Course not found\n";
    }
} catch (PDOException $e) {
    echo "❌ Course query error: " . $e->getMessage() . "\n";
}
