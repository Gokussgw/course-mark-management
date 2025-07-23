<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Course Performance Percentage Display Check ===\n";
echo "Student: Emma Thompson (ID: 10)\n\n";

// Get all courses for Emma Thompson
$stmt = $pdo->prepare('
    SELECT 
        c.code,
        c.name,
        fm.assignment_mark,
        fm.assignment_percentage,
        fm.quiz_mark,
        fm.quiz_percentage,
        fm.test_mark,
        fm.test_percentage,
        fm.final_exam_mark,
        fm.final_exam_percentage,
        fm.final_grade,
        fm.letter_grade
    FROM final_marks_custom fm
    JOIN courses c ON fm.course_id = c.id
    WHERE fm.student_id = 10
    ORDER BY c.code
');
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($courses as $course) {
    echo "📚 {$course['code']} - {$course['name']}:\n";
    echo "  Assignment: {$course['assignment_mark']}/100 → {$course['assignment_percentage']}%\n";
    echo "  Quiz: {$course['quiz_mark']}/100 → {$course['quiz_percentage']}%\n";
    echo "  Test: {$course['test_mark']}/100 → {$course['test_percentage']}%\n";
    echo "  Final Exam: {$course['final_exam_mark']}/100 → {$course['final_exam_percentage']}%\n";
    echo "  Overall: {$course['final_grade']}% ({$course['letter_grade']})\n\n";
}

echo "=== Display Format Summary ===\n";
echo "All courses now show:\n";
echo "- Assignment: Raw mark → Weighted percentage (e.g., 90/100 → 22.50%)\n";
echo "- Quiz: Raw mark → Weighted percentage (e.g., 88/100 → 13.20%)\n";
echo "- Test: Raw mark → Weighted percentage (e.g., 87/100 → 26.10%)\n";
echo "- Final Exam: Raw mark → Weighted percentage (e.g., 88/100 → 26.40%)\n";
echo "\nThe frontend displays only the weighted percentages for consistency.\n";
