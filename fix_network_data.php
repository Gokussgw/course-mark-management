<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Computer Networks Course Data Fix ===\n";

// First, check the course assessment configuration
$stmt = $pdo->prepare('SELECT * FROM assessments WHERE course_id = 5');
$stmt->execute();
$assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Computer Networks (CS401) Assessment Configuration:\n";
$weightages = [];
foreach ($assessments as $assessment) {
    echo "- {$assessment['type']}: {$assessment['weightage']}%\n";
    $weightages[$assessment['type']] = $assessment['weightage'];
}

// If no assessments found, use standard weightages
if (empty($weightages)) {
    echo "No assessment config found, using standard weightages:\n";
    $weightages = [
        'assignment' => 25,
        'quiz' => 15,
        'test' => 30,
        'final_exam' => 30
    ];
    foreach ($weightages as $type => $weight) {
        echo "- $type: $weight%\n";
    }
}

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

echo "\nCurrent incorrect data:\n";
foreach ($students as $student) {
    echo "\n{$student['student_name']} ({$student['student_number']}):\n";
    echo "  Assignment: {$student['assignment_mark']}/100 ({$student['assignment_percentage']}%)\n";
    echo "  Quiz: {$student['quiz_mark']}/100 ({$student['quiz_percentage']}%)\n";
    echo "  Test: {$student['test_mark']}/100 ({$student['test_percentage']}%)\n";
    echo "  Final: {$student['final_exam_mark']}/100 ({$student['final_exam_percentage']}%)\n";
    echo "  Overall: {$student['final_grade']}% ({$student['letter_grade']})\n";
}

// Now let's fix the calculations
echo "\n=== FIXING CALCULATIONS ===\n";

foreach ($students as $student) {
    // Calculate correct percentages based on weightages
    $assignment_percentage = ($student['assignment_mark'] / 100) * $weightages['assignment'];
    $quiz_percentage = ($student['quiz_mark'] / 100) * $weightages['quiz'];
    $test_percentage = ($student['test_mark'] / 100) * $weightages['test'];
    $final_exam_percentage = ($student['final_exam_mark'] / 100) * $weightages['final_exam'];

    $overall_percentage = $assignment_percentage + $quiz_percentage + $test_percentage + $final_exam_percentage;

    // Determine letter grade
    $letter_grade = 'F';
    $gpa = 0.0;

    if ($overall_percentage >= 90) {
        $letter_grade = 'A+';
        $gpa = 4.0;
    } elseif ($overall_percentage >= 85) {
        $letter_grade = 'A';
        $gpa = 4.0;
    } elseif ($overall_percentage >= 80) {
        $letter_grade = 'A-';
        $gpa = 3.7;
    } elseif ($overall_percentage >= 77) {
        $letter_grade = 'B+';
        $gpa = 3.3;
    } elseif ($overall_percentage >= 73) {
        $letter_grade = 'B';
        $gpa = 3.0;
    } elseif ($overall_percentage >= 70) {
        $letter_grade = 'B-';
        $gpa = 2.7;
    } elseif ($overall_percentage >= 67) {
        $letter_grade = 'C+';
        $gpa = 2.3;
    } elseif ($overall_percentage >= 63) {
        $letter_grade = 'C';
        $gpa = 2.0;
    } elseif ($overall_percentage >= 60) {
        $letter_grade = 'C-';
        $gpa = 1.7;
    } elseif ($overall_percentage >= 57) {
        $letter_grade = 'D+';
        $gpa = 1.3;
    } elseif ($overall_percentage >= 50) {
        $letter_grade = 'D';
        $gpa = 1.0;
    }

    echo "\nFixing {$student['student_name']}:\n";
    echo "  Assignment: {$student['assignment_mark']}/100 -> " . number_format($assignment_percentage, 2) . "%\n";
    echo "  Quiz: {$student['quiz_mark']}/100 -> " . number_format($quiz_percentage, 2) . "%\n";
    echo "  Test: {$student['test_mark']}/100 -> " . number_format($test_percentage, 2) . "%\n";
    echo "  Final: {$student['final_exam_mark']}/100 -> " . number_format($final_exam_percentage, 2) . "%\n";
    echo "  Overall: " . number_format($overall_percentage, 2) . "% ($letter_grade, GPA: $gpa)\n";

    // Update the database
    $updateStmt = $pdo->prepare('
        UPDATE final_marks_custom 
        SET 
            assignment_percentage = ?,
            quiz_percentage = ?,
            test_percentage = ?,
            final_exam_percentage = ?,
            component_total = ?,
            final_grade = ?,
            letter_grade = ?,
            gpa = ?
        WHERE student_id = ? AND course_id = 5
    ');

    $component_total = $assignment_percentage + $quiz_percentage + $test_percentage;

    $result = $updateStmt->execute([
        number_format($assignment_percentage, 2),
        number_format($quiz_percentage, 2),
        number_format($test_percentage, 2),
        number_format($final_exam_percentage, 2),
        number_format($component_total, 2),
        number_format($overall_percentage, 2),
        $letter_grade,
        $gpa,
        $student['student_id']
    ]);

    if ($result) {
        echo "  ✅ Updated successfully\n";
    } else {
        echo "  ❌ Update failed\n";
    }
}

echo "\n=== FIX COMPLETE ===\n";
echo "All Computer Networks course calculations have been corrected!\n";
