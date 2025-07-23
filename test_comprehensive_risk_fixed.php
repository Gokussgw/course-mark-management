<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Testing Comprehensive Endpoint Risk Calculation ===\n\n";

// Replicate the exact query from comprehensive endpoint
$advisorId = 3; // Advisor One - the actual advisor with students

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
        AVG(fm.assignment_percentage) as avg_assignment_percentage,
        AVG(fm.quiz_percentage) as avg_quiz_percentage,
        AVG(fm.test_percentage) as avg_test_percentage,
        AVG(fm.final_exam_percentage) as avg_final_exam_percentage,
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

// Replicate the identifyRiskIndicators function from comprehensive endpoint
function identifyRiskIndicators($advisee)
{
    $indicators = [];

    if (($advisee['overall_gpa'] ?? 0) < 2.0) {
        $indicators[] = 'low_gpa';
    }

    if (($advisee['f_grades'] ?? 0) > 0) {
        $indicators[] = 'failing_grades';
    }

    if (($advisee['avg_assignment_percentage'] ?? 0) < 50) {
        $indicators[] = 'poor_assignment_performance';
    }

    if (($advisee['avg_quiz_percentage'] ?? 0) < 50) {
        $indicators[] = 'poor_quiz_performance';
    }

    if (($advisee['completed_courses'] ?? 0) < ($advisee['total_courses'] ?? 1) * 0.7) {
        $indicators[] = 'low_completion_rate';
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

echo "Comprehensive API Data Analysis:\n\n";

foreach ($advisees as $advisee) {
    $riskIndicators = identifyRiskIndicators($advisee);
    $riskLevel = getRiskLevel($riskIndicators);

    echo "👤 {$advisee['name']} ({$advisee['matric_number']}):\n";
    echo "   Overall GPA: " . round($advisee['overall_gpa'] ?? 0, 2) . "\n";
    echo "   Completed/Total Courses: {$advisee['completed_courses']}/{$advisee['total_courses']}\n";
    echo "   Avg Assignment %: " . round($advisee['avg_assignment_percentage'] ?? 0, 1) . "%\n";
    echo "   Avg Quiz %: " . round($advisee['avg_quiz_percentage'] ?? 0, 1) . "%\n";
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
    echo "     - Assignment % < 50: " . (($advisee['avg_assignment_percentage'] ?? 0) < 50 ? "YES" : "NO") . " (Avg: " . round($advisee['avg_assignment_percentage'] ?? 0, 1) . "%)\n";
    echo "     - Quiz % < 50: " . (($advisee['avg_quiz_percentage'] ?? 0) < 50 ? "YES" : "NO") . " (Avg: " . round($advisee['avg_quiz_percentage'] ?? 0, 1) . "%)\n";
    echo "     - Low completion: " . (($advisee['completed_courses'] ?? 0) < ($advisee['total_courses'] ?? 1) * 0.7 ? "YES" : "NO") . " (Completed: {$advisee['completed_courses']}, 70% of {$advisee['total_courses']} = " . round(($advisee['total_courses'] ?? 1) * 0.7, 1) . ")\n";
    echo "\n";
}

echo "=== ANALYSIS ===\n";
echo "The comprehensive endpoint uses different criteria:\n";
echo "- Assignment/Quiz threshold: 50% (instead of 60% in individual reports)\n";
echo "- Uses percentage values instead of raw marks\n";
echo "- Different completion rate calculation (70% vs minimum course count)\n";
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
