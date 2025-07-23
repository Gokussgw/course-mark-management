<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Detailed Course Check for Emma Thompson (ID: 10) ===\n\n";

// Get courses data
$stmt = $pdo->prepare('
    SELECT 
        c.code,
        c.name,
        fm.final_grade as overall_percentage,
        fm.letter_grade,
        CASE WHEN fm.final_grade IS NOT NULL THEN "Completed" ELSE "Not Completed" END as status
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN final_marks_custom fm ON e.student_id = fm.student_id AND e.course_id = fm.course_id
    WHERE e.student_id = 10
    ORDER BY c.code
');
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$completed = 0;
$total = count($courses);

echo "Course Enrollment Status:\n";
foreach ($courses as $course) {
    echo "  {$course['code']}: {$course['status']}";
    if ($course['status'] === 'Completed') {
        echo " - {$course['overall_percentage']}% ({$course['letter_grade']})";
        $completed++;
    }
    echo "\n";
}

echo "\nSUMMARY:\n";
echo "Total Enrolled: $total\n";
echo "Completed with Grades: $completed\n";
echo "Completion Rate: " . round(($completed / $total) * 100, 1) . "%\n";

echo "\nWith new logic (insufficient_completed_courses if < 3):\n";
echo "Risk Status: " . ($completed < 3 ? "❌ AT RISK" : "✅ OK") . "\n";
