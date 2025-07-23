<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Simple Percentage Display for Emma Thompson (ID: 10) ===\n\n";

// Get all courses for Emma Thompson
$stmt = $pdo->prepare('
    SELECT 
        c.code,
        c.name,
        fm.assignment_mark,
        fm.quiz_mark,
        fm.test_mark,
        fm.final_exam_mark,
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
    echo "  Assignment: " . intval($course['assignment_mark']) . "%\n";
    echo "  Quiz: " . intval($course['quiz_mark']) . "%\n";
    echo "  Test: " . intval($course['test_mark']) . "%\n";
    echo "  Final Exam: " . intval($course['final_exam_mark']) . "%\n";
    echo "  Overall: {$course['final_grade']}% ({$course['letter_grade']})\n\n";
}

echo "=== Display Format Summary ===\n";
echo "Now showing SIMPLE PERCENTAGES:\n";
echo "- Assignment: Raw mark as percentage (e.g., 95%)\n";
echo "- Quiz: Raw mark as percentage (e.g., 88%)\n";
echo "- Test: Raw mark as percentage (e.g., 87%)\n";
echo "- Final Exam: Raw mark as percentage (e.g., 88%)\n";
echo "\nClean, simple percentage display for easy reading!\n";
