<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Checking Advisor-Student Relationships ===\n\n";

// Check advisors
$stmt = $pdo->query('SELECT id, name, email FROM users WHERE role = "advisor" ORDER BY name');
$advisors = $stmt->fetchAll();
echo "Advisors:\n";
foreach ($advisors as $advisor) {
    echo "  ID {$advisor['id']}: {$advisor['name']} ({$advisor['email']})\n";
}

echo "\n";

// Check students with advisors
$stmt = $pdo->query('SELECT id, name, advisor_id FROM users WHERE role = "student" AND advisor_id IS NOT NULL ORDER BY advisor_id, name');
$students = $stmt->fetchAll();
echo "Students with Advisors:\n";
foreach ($students as $student) {
    echo "  Student ID {$student['id']}: {$student['name']} -> Advisor ID {$student['advisor_id']}\n";
}

echo "\n";

// Check enrollments for students with advisors
echo "Checking enrollments and marks for advisor's students:\n";
foreach ($advisors as $advisor) {
    echo "\n🏫 Advisor: {$advisor['name']} (ID: {$advisor['id']})\n";

    $stmt = $pdo->prepare('
        SELECT u.id, u.name, u.matric_number,
               COUNT(DISTINCT e.course_id) as total_courses,
               COUNT(DISTINCT fm.id) as completed_courses
        FROM users u
        LEFT JOIN enrollments e ON u.id = e.student_id
        LEFT JOIN final_marks_custom fm ON u.id = fm.student_id
        WHERE u.advisor_id = ? AND u.role = "student"
        GROUP BY u.id
        ORDER BY u.name
    ');
    $stmt->execute([$advisor['id']]);
    $advisorStudents = $stmt->fetchAll();

    if (empty($advisorStudents)) {
        echo "  No students found\n";
    } else {
        foreach ($advisorStudents as $student) {
            echo "  👤 {$student['name']} ({$student['matric_number']}) - {$student['completed_courses']}/{$student['total_courses']} courses\n";
        }
    }
}
