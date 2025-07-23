<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Testing FIXED Comprehensive Endpoint Risk Calculation ===\n\n";

$advisorId = 3; // Advisor One

// Updated query using raw marks
$stmt = $pdo->prepare('
    SELECT 
        u.id,
        u.name,
        u.email,
        u.matric_number,
        u.created_at as enrollment_date,
        COUNT(DISTINCT e.course_id) as total_courses,
        COUNT(DISTINCT fm.id) as completed_courses,
        AVG(fm.gpa) as overall_gpa,
        AVG(fm.assignment_mark) as avg_assignment_mark,
        AVG(fm.quiz_mark) as avg_quiz_mark,
        AVG(fm.test_mark) as avg_test_mark,
        AVG(fm.final_exam_mark) as avg_final_exam_mark,
        COUNT(CASE WHEN fm.letter_grade IN ("A+", "A", "A-") THEN 1 END) as a_grades,
        COUNT(CASE WHEN fm.letter_grade IN ("B+", "B", "B-") THEN 1 END) as b_grades,
        COUNT(CASE WHEN fm.letter_grade IN ("C+", "C", "C-") THEN 1 END) as c_grades,
        COUNT(CASE WHEN fm.letter_grade IN ("D+", "D") THEN 1 END) as d_grades,
        COUNT(CASE WHEN fm.letter_grade = "F" THEN 1 END) as f_grades
    FROM users u
    LEFT JOIN enrollments e ON u.id = e.student_id
    LEFT JOIN final_marks_custom fm ON u.id = fm.student_id AND e.course_id = fm.course_id
    WHERE u.advisor_id = ? AND u.role = "student"
    GROUP BY u.id
    ORDER BY u.name
');
$stmt->execute([$advisorId]);
$advisees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Updated identifyRiskIndicators function
function identifyRiskIndicators($advisee)
{
    $indicators = [];

    if (($advisee['overall_gpa'] ?? 0) < 2.0) {
        $indicators[] = 'low_gpa';
    }

    if (($advisee['f_grades'] ?? 0) > 0) {
        $indicators[] = 'failing_grades';
    }

    // Use raw marks with 60% threshold (consistent with individual reports)
    if (($advisee['avg_assignment_mark'] ?? 0) < 60) {
        $indicators[] = 'poor_assignment_performance';
    }

    if (($advisee['avg_quiz_mark'] ?? 0) < 60) {
        $indicators[] = 'poor_quiz_performance';
    }

    // Check for insufficient completed courses (less than 3)
    if (($advisee['completed_courses'] ?? 0) < 3) {
        $indicators[] = 'insufficient_completed_courses';
    }

    return $indicators;
}

function getRiskLevel($riskIndicators)
{
    $riskCount = count($riskIndicators);
    if ($riskCount >= 3) return 'High';
    if ($riskCount >= 2) return 'Medium';
    if ($riskCount >= 1) return 'Low';
    return 'None';
}

echo "FIXED Comprehensive API Data Analysis:\n\n";

foreach ($advisees as $advisee) {
    $riskIndicators = identifyRiskIndicators($advisee);
    $riskLevel = getRiskLevel($riskIndicators);

    echo "👤 {$advisee['name']} ({$advisee['matric_number']}):\n";
    echo "   Overall GPA: " . round($advisee['overall_gpa'] ?? 0, 2) . "\n";
    echo "   Completed/Total Courses: {$advisee['completed_courses']}/{$advisee['total_courses']}\n";
    echo "   Avg Assignment Mark: " . round($advisee['avg_assignment_mark'] ?? 0, 1) . "\n";
    echo "   Avg Quiz Mark: " . round($advisee['avg_quiz_mark'] ?? 0, 1) . "\n";
    echo "   F Grades: {$advisee['f_grades']}\n";

    echo "   Risk Indicators (" . count($riskIndicators) . "): ";
    if (empty($riskIndicators)) {
        echo "None\n";
    } else {
        echo implode(', ', $riskIndicators) . "\n";
    }

    echo "   Risk Level: $riskLevel\n";

    // Check each risk criterion individually
    echo "   Risk Analysis:\n";
    echo "     - GPA < 2.0: " . (($advisee['overall_gpa'] ?? 0) < 2.0 ? "YES" : "NO") . " (GPA: " . round($advisee['overall_gpa'] ?? 0, 2) . ")\n";
    echo "     - Has F grades: " . (($advisee['f_grades'] ?? 0) > 0 ? "YES" : "NO") . " (F count: {$advisee['f_grades']})\n";
    echo "     - Assignment Mark < 60: " . (($advisee['avg_assignment_mark'] ?? 0) < 60 ? "YES" : "NO") . " (Avg: " . round($advisee['avg_assignment_mark'] ?? 0, 1) . ")\n";
    echo "     - Quiz Mark < 60: " . (($advisee['avg_quiz_mark'] ?? 0) < 60 ? "YES" : "NO") . " (Avg: " . round($advisee['avg_quiz_mark'] ?? 0, 1) . ")\n";
    echo "     - Completed < 3 courses: " . (($advisee['completed_courses'] ?? 0) < 3 ? "YES" : "NO") . " (Completed: {$advisee['completed_courses']})\n";
    echo "\n";
}

echo "=== FIXED RESULTS ===\n";
echo "Now using raw marks (60% threshold) instead of percentage contributions.\n";
echo "Risk criteria consistent with individual reports.\n";
echo "\nCounting risk levels:\n";

$riskCounts = ['None' => 0, 'Low' => 0, 'Medium' => 0, 'High' => 0];
foreach ($advisees as $advisee) {
    $riskIndicators = identifyRiskIndicators($advisee);
    $riskLevel = getRiskLevel($riskIndicators);
    $riskCounts[$riskLevel]++;
}

foreach ($riskCounts as $level => $count) {
    echo "- $level: $count students\n";
}
