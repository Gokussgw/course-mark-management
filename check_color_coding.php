<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Color Coding Check for Emma Thompson (ID: 10) ===\n\n";

function getExpectedColor($score)
{
    if ($score >= 80) return 'GREEN (Excellent)';
    if ($score >= 70) return 'BLUE (Good)';
    if ($score >= 60) return 'YELLOW (Warning)';
    if ($score >= 50) return 'ORANGE (Needs Improvement)';
    return 'RED (Danger)';
}

// Get all courses for Emma Thompson
$stmt = $pdo->prepare('
    SELECT 
        c.code,
        c.name,
        fm.assignment_mark,
        fm.quiz_mark,
        fm.test_mark,
        fm.final_exam_mark
    FROM final_marks_custom fm
    JOIN courses c ON fm.course_id = c.id
    WHERE fm.student_id = 10
    ORDER BY c.code
');
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($courses as $course) {
    echo "📚 {$course['code']} - {$course['name']}:\n";
    echo "  Assignment: " . intval($course['assignment_mark']) . "% → " . getExpectedColor($course['assignment_mark']) . "\n";
    echo "  Quiz: " . intval($course['quiz_mark']) . "% → " . getExpectedColor($course['quiz_mark']) . "\n";
    echo "  Test: " . intval($course['test_mark']) . "% → " . getExpectedColor($course['test_mark']) . "\n";
    echo "  Final Exam: " . intval($course['final_exam_mark']) . "% → " . getExpectedColor($course['final_exam_mark']) . "\n\n";
}

echo "=== Color Legend ===\n";
echo "🟢 GREEN (bg-success): 80-100% - Excellent performance\n";
echo "🔵 BLUE (bg-primary): 70-79% - Good performance\n";
echo "🟡 YELLOW (bg-warning): 60-69% - Satisfactory performance\n";
echo "🟠 ORANGE (bg-orange): 50-59% - Needs improvement\n";
echo "🔴 RED (bg-danger): 0-49% - Poor performance\n";
