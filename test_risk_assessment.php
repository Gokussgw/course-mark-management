<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

function calculateRiskIndicators($student, $courses)
{
    $riskIndicators = [];

    $gpa = floatval($student['overall_gpa'] ?? 0);
    $completedCourses = count(array_filter($courses, function ($c) {
        return $c['final_grade'] !== null;
    }));

    // GPA-based risk indicators
    if ($gpa < 2.0) {
        $riskIndicators[] = 'low_gpa';
    }

    // Check for failing grades
    $failingGrades = count(array_filter($courses, function ($c) {
        return $c['letter_grade'] === 'F';
    }));

    if ($failingGrades > 0) {
        $riskIndicators[] = 'failing_grades';
    }

    // Check component performance using raw marks instead of weighted percentages
    if ($completedCourses > 0) {
        $avgAssignment = array_sum(array_column($courses, 'assignment_mark')) / $completedCourses;
        $avgQuiz = array_sum(array_column($courses, 'quiz_mark')) / $completedCourses;

        if ($avgAssignment < 60) {
            $riskIndicators[] = 'poor_assignment_performance';
        }

        if ($avgQuiz < 60) {
            $riskIndicators[] = 'poor_quiz_performance';
        }
    }

    // Check completion rate - only flag if very low number of completed courses
    if ($completedCourses < 3) {
        $riskIndicators[] = 'insufficient_completed_courses';
    }

    return $riskIndicators;
}

echo "=== Risk Assessment Test - Multiple Students ===\n\n";

// Test students 10, 11, 12
$studentIds = [10, 11, 12];

foreach ($studentIds as $studentId) {
    // Get student data
    $stmt = $pdo->prepare('
        SELECT u.*, 
               AVG(fm.gpa) as overall_gpa
        FROM users u
        LEFT JOIN final_marks_custom fm ON u.id = fm.student_id
        WHERE u.id = ?
        GROUP BY u.id
    ');
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo "Student ID $studentId not found\n";
        continue;
    }

    // Get courses data
    $stmt = $pdo->prepare('
        SELECT 
            c.code,
            fm.assignment_mark,
            fm.quiz_mark,
            fm.test_mark,
            fm.final_exam_mark,
            fm.final_grade,
            fm.letter_grade,
            fm.gpa
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN final_marks_custom fm ON e.student_id = fm.student_id AND e.course_id = fm.course_id
        WHERE e.student_id = ?
        ORDER BY c.code
    ');
    $stmt->execute([$studentId]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $riskIndicators = calculateRiskIndicators($student, $courses);

    echo "👤 {$student['name']} (ID: $studentId):\n";
    echo "   GPA: " . round($student['overall_gpa'], 2) . "\n";
    echo "   Completed Courses: " . count(array_filter($courses, function ($c) {
        return $c['final_grade'] !== null;
    })) . "\n";

    if (empty($riskIndicators)) {
        echo "   Risk Status: ✅ No Risk Indicators\n";
        echo "   Display: 'No Risk Indicators Identified' (Green)\n";
    } else {
        echo "   Risk Status: ❌ Risk Indicators Found\n";
        echo "   Indicators: " . implode(', ', $riskIndicators) . "\n";
        echo "   Display: Risk indicators with warning\n";
    }
    echo "\n";
}

echo "=== Expected Results ===\n";
echo "Emma Thompson (ID: 10): Should show NO risk indicators (excellent student)\n";
echo "James Rodriguez (ID: 11): May show risk indicators if performance is poor\n";
echo "Other students: Depends on their actual performance data\n";
