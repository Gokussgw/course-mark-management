<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Raw Marks Display for Emma Thompson (ID: 10) ===\n\n";

// Get all courses for Emma Thompson with raw marks
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
    echo "  Assignment: {$course['assignment_mark']}/100\n";
    echo "  Quiz: {$course['quiz_mark']}/100\n";
    echo "  Test: {$course['test_mark']}/100\n";
    echo "  Final Exam: {$course['final_exam_mark']}/100\n";
    echo "  Overall: {$course['final_grade']}% ({$course['letter_grade']})\n\n";
}

echo "=== Display Format Summary ===\n";
echo "Now showing RAW MARKS instead of weighted percentages:\n";
echo "- Assignment: Raw score out of 100 (e.g., 95/100)\n";
echo "- Quiz: Raw score out of 100 (e.g., 88/100)\n";
echo "- Test: Raw score out of 100 (e.g., 87/100)\n";
echo "- Final Exam: Raw score out of 100 (e.g., 88/100)\n";
echo "\nThis gives a clearer view of actual performance on each component.\n";
