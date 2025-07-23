<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Risk Assessment Check for Emma Thompson (ID: 10) ===\n\n";

// Get student data
$stmt = $pdo->prepare('
    SELECT u.*, 
           AVG(fm.gpa) as overall_gpa,
           COUNT(e.id) as total_courses
    FROM users u
    LEFT JOIN enrollments e ON u.id = e.student_id
    LEFT JOIN final_marks_custom fm ON u.id = fm.student_id
    WHERE u.id = 10
    GROUP BY u.id
');
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Get courses data
$stmt = $pdo->prepare('
    SELECT 
        c.id,
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
        fm.final_grade as overall_percentage,
        fm.letter_grade,
        fm.gpa
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN final_marks_custom fm ON e.student_id = fm.student_id AND e.course_id = fm.course_id
    WHERE e.student_id = 10
    ORDER BY c.code
');
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Student: {$student['name']}\n";
echo "Overall GPA: " . round($student['overall_gpa'], 2) . "\n";
echo "Total Courses: {$student['total_courses']}\n\n";

$completedCourses = count(array_filter($courses, function ($c) {
    return $c['overall_percentage'] !== null;
}));

echo "=== Risk Calculation Analysis ===\n\n";

// GPA Check
echo "1. GPA Check:\n";
echo "   GPA: " . round($student['overall_gpa'], 2) . " (Risk if < 2.0)\n";
echo "   Status: " . ($student['overall_gpa'] < 2.0 ? "❌ AT RISK" : "✅ OK") . "\n\n";

// Failing Grades Check
echo "2. Failing Grades Check:\n";
$failingGrades = count(array_filter($courses, function ($c) {
    return $c['letter_grade'] === 'F';
}));
echo "   Failing courses: $failingGrades\n";
echo "   Status: " . ($failingGrades > 0 ? "❌ AT RISK" : "✅ OK") . "\n\n";

// Component Performance Check (PROBLEMATIC - using weighted percentages)
echo "3. Component Performance Check (CURRENT - PROBLEMATIC):\n";
if ($completedCourses > 0) {
    $avgAssignment = array_sum(array_column($courses, 'assignment_percentage')) / $completedCourses;
    $avgQuiz = array_sum(array_column($courses, 'quiz_percentage')) / $completedCourses;

    echo "   Average Assignment (weighted %): " . round($avgAssignment, 1) . "% (Risk if < 60%)\n";
    echo "   Assignment Status: " . ($avgAssignment < 60 ? "❌ AT RISK" : "✅ OK") . "\n";
    echo "   Average Quiz (weighted %): " . round($avgQuiz, 1) . "% (Risk if < 60%)\n";
    echo "   Quiz Status: " . ($avgQuiz < 60 ? "❌ AT RISK" : "✅ OK") . "\n";
}

echo "\n4. Component Performance Check (CORRECTED - using raw marks):\n";
if ($completedCourses > 0) {
    $avgAssignmentRaw = array_sum(array_column($courses, 'assignment_mark')) / $completedCourses;
    $avgQuizRaw = array_sum(array_column($courses, 'quiz_mark')) / $completedCourses;

    echo "   Average Assignment (raw marks): " . round($avgAssignmentRaw, 1) . "% (Risk if < 60%)\n";
    echo "   Assignment Status: " . ($avgAssignmentRaw < 60 ? "❌ AT RISK" : "✅ OK") . "\n";
    echo "   Average Quiz (raw marks): " . round($avgQuizRaw, 1) . "% (Risk if < 60%)\n";
    echo "   Quiz Status: " . ($avgQuizRaw < 60 ? "❌ AT RISK" : "✅ OK") . "\n";
}

// Completion Rate
echo "\n5. Completion Rate Check:\n";
$completionRate = $student['total_courses'] > 0 ? ($completedCourses / $student['total_courses']) : 1;
echo "   Completed: $completedCourses / {$student['total_courses']} = " . round($completionRate * 100, 1) . "%\n";
echo "   Status: " . ($completionRate < 0.8 ? "❌ AT RISK" : "✅ OK") . "\n";

echo "\n=== CONCLUSION ===\n";
echo "Emma Thompson should show: 'No Risk Indicators Identified' (all green)\n";
echo "If showing risk indicators, it's due to the weighted percentage bug in component analysis.\n";
