<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Computer Networks Course Data Verification ===\n";

// Expected weightages
$weightages = [
    'assignment' => 25,
    'quiz' => 15,
    'test' => 30,
    'final_exam' => 30
];

// Get all students enrolled in CS401
$stmt = $pdo->prepare('
    SELECT fm.*, u.name as student_name, u.matric_number as student_number
    FROM final_marks_custom fm
    JOIN users u ON fm.student_id = u.id
    WHERE fm.course_id = 5
    ORDER BY u.name
');
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Verifying calculations for CS401 Computer Networks:\n";

foreach ($students as $student) {
    // Calculate expected percentages
    $expected_assignment = ($student['assignment_mark'] / 100) * $weightages['assignment'];
    $expected_quiz = ($student['quiz_mark'] / 100) * $weightages['quiz'];
    $expected_test = ($student['test_mark'] / 100) * $weightages['test'];
    $expected_final = ($student['final_exam_mark'] / 100) * $weightages['final_exam'];
    $expected_total = $expected_assignment + $expected_quiz + $expected_test + $expected_final;

    $assignment_correct = abs($student['assignment_percentage'] - $expected_assignment) < 0.01;
    $quiz_correct = abs($student['quiz_percentage'] - $expected_quiz) < 0.01;
    $test_correct = abs($student['test_percentage'] - $expected_test) < 0.01;
    $final_correct = abs($student['final_exam_percentage'] - $expected_final) < 0.01;
    $total_correct = abs($student['final_grade'] - $expected_total) < 0.01;

    $all_correct = $assignment_correct && $quiz_correct && $test_correct && $final_correct && $total_correct;

    echo "\n" . ($all_correct ? "✅" : "❌") . " {$student['student_name']} ({$student['student_number']}):\n";
    echo "  Assignment: {$student['assignment_mark']}/100 = {$student['assignment_percentage']}%";
    echo ($assignment_correct ? " ✅" : " ❌ (expected " . number_format($expected_assignment, 2) . "%)") . "\n";

    echo "  Quiz: {$student['quiz_mark']}/100 = {$student['quiz_percentage']}%";
    echo ($quiz_correct ? " ✅" : " ❌ (expected " . number_format($expected_quiz, 2) . "%)") . "\n";

    echo "  Test: {$student['test_mark']}/100 = {$student['test_percentage']}%";
    echo ($test_correct ? " ✅" : " ❌ (expected " . number_format($expected_test, 2) . "%)") . "\n";

    echo "  Final: {$student['final_exam_mark']}/100 = {$student['final_exam_percentage']}%";
    echo ($final_correct ? " ✅" : " ❌ (expected " . number_format($expected_final, 2) . "%)") . "\n";

    echo "  Overall: {$student['final_grade']}% ({$student['letter_grade']}, GPA: {$student['gpa']})";
    echo ($total_correct ? " ✅" : " ❌ (expected " . number_format($expected_total, 2) . "%)") . "\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";
