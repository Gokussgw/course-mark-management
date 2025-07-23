<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Component Strengths Analysis for Emma Thompson (ID: 10) ===\n\n";

// Get all courses for Emma Thompson
$stmt = $pdo->prepare('
    SELECT 
        c.code,
        fm.assignment_mark,
        fm.quiz_mark,
        fm.test_mark,
        fm.final_exam_mark,
        fm.assignment_percentage,
        fm.quiz_percentage,
        fm.test_percentage,
        fm.final_exam_percentage
    FROM final_marks_custom fm
    JOIN courses c ON fm.course_id = c.id
    WHERE fm.student_id = 10
    ORDER BY c.code
');
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getExpectedColor($score)
{
    if ($score >= 85) return 'GREEN (Excellent)';
    if ($score >= 75) return 'BLUE (Good)';
    if ($score >= 65) return 'YELLOW (Average)';
    if ($score >= 50) return 'ORANGE (Needs Improvement)';
    return 'RED (Poor)';
}

// Calculate averages using RAW MARKS (what we want)
$components = ['assignment', 'quiz', 'test', 'final_exam'];
$raw_averages = [];

foreach ($components as $component) {
    $marks = [];
    foreach ($courses as $course) {
        $mark = $course[$component . '_mark'];
        if ($mark !== null && $mark > 0) {
            $marks[] = (float)$mark;
        }
    }
    if (!empty($marks)) {
        $raw_averages[$component] = array_sum($marks) / count($marks);
    }
}

// Calculate averages using WEIGHTED PERCENTAGES (old problematic way)
$weighted_averages = [];

foreach ($components as $component) {
    $percentages = [];
    foreach ($courses as $course) {
        $percentage = $course[$component . '_percentage'];
        if ($percentage !== null && $percentage > 0) {
            $percentages[] = (float)$percentage;
        }
    }
    if (!empty($percentages)) {
        $weighted_averages[$component] = array_sum($percentages) / count($percentages);
    }
}

echo "BEFORE FIX (Using weighted percentages - WRONG):\n";
foreach ($weighted_averages as $component => $avg) {
    $name = ucfirst(str_replace('_', ' ', $component));
    echo "  $name: " . round($avg, 1) . "% → " . getExpectedColor($avg) . "\n";
}

echo "\nAFTER FIX (Using raw marks - CORRECT):\n";
foreach ($raw_averages as $component => $avg) {
    $name = ucfirst(str_replace('_', ' ', $component));
    echo "  $name: " . round($avg, 1) . "% → " . getExpectedColor($avg) . "\n";
}

echo "\n=== Individual Course Scores (Raw Marks) ===\n";
foreach ($courses as $course) {
    echo "{$course['code']}: Assignment={$course['assignment_mark']}%, Quiz={$course['quiz_mark']}%, Test={$course['test_mark']}%, Final={$course['final_exam_mark']}%\n";
}
