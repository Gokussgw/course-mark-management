<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Fixing CS101 and CS201 Calculation Issues ===\n";

// Standard weightages for most courses
$weightages = [
    'assignment' => 25,
    'quiz' => 15,
    'test' => 30,
    'final_exam' => 30
];

// Check which courses have calculation issues
$stmt = $pdo->prepare('
    SELECT 
        c.id,
        c.code,
        c.name,
        COUNT(fm.id) as student_count
    FROM courses c
    JOIN final_marks_custom fm ON c.id = fm.course_id
    WHERE c.id IN (1, 2)  -- CS101 and CS201
    GROUP BY c.id, c.code, c.name
    ORDER BY c.code
');
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($courses as $course) {
    echo "\n🔧 Fixing {$course['code']} - {$course['name']} ({$course['student_count']} students):\n";

    // Get all students in this course
    $stmt = $pdo->prepare('
        SELECT fm.*, u.name as student_name, u.matric_number
        FROM final_marks_custom fm
        JOIN users u ON fm.student_id = u.id
        WHERE fm.course_id = ?
        ORDER BY u.name
    ');
    $stmt->execute([$course['id']]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($students as $student) {
        // Calculate correct percentages
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

        echo "  {$student['student_name']}: {$student['final_grade']}% → " . number_format($overall_percentage, 2) . "% ($letter_grade)\n";

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
            WHERE student_id = ? AND course_id = ?
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
            $student['student_id'],
            $course['id']
        ]);

        if (!$result) {
            echo "    ❌ Update failed for {$student['student_name']}\n";
        }
    }
}

echo "\n=== FIX COMPLETE ===\n";
echo "All course calculations have been corrected!\n";
