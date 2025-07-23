<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== UPDATED: Risk Levels with 'Low' for Zero Indicators ===\n\n";

function identifyRiskIndicators($advisee)
{
    $indicators = [];

    if (($advisee['overall_gpa'] ?? 0) < 2.0) {
        $indicators[] = 'low_gpa';
    }

    if (($advisee['f_grades'] ?? 0) > 0) {
        $indicators[] = 'failing_grades';
    }

    if (($advisee['avg_assignment_mark'] ?? 0) < 60) {
        $indicators[] = 'poor_assignment_performance';
    }

    if (($advisee['avg_quiz_mark'] ?? 0) < 60) {
        $indicators[] = 'poor_quiz_performance';
    }

    if (($advisee['completed_courses'] ?? 0) < 3) {
        $indicators[] = 'insufficient_completed_courses';
    }

    return $indicators;
}

// Updated getRiskLevel function - returns 'Low' for 0 indicators
function getRiskLevel($riskIndicators)
{
    $riskCount = count($riskIndicators);
    if ($riskCount >= 3) return 'High';
    if ($riskCount >= 2) return 'Medium';
    if ($riskCount >= 1) return 'Low';
    return 'Low'; // Changed from 'None' to 'Low'
}

function getRiskBadgeClass($risk)
{
    switch ($risk) {
        case "High":
            return "🔴 bg-danger (RED)";
        case "Medium":
            return "🟡 bg-warning (YELLOW)";
        case "Low":
            return "🟢 bg-success (GREEN)";
        default:
            return "⚪ bg-secondary (GRAY)";
    }
}

$advisorId = 3;

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

echo "Updated Dashboard Risk Level Summary:\n\n";

$riskCounts = ['Low' => 0, 'Medium' => 0, 'High' => 0];
foreach ($advisees as $advisee) {
    $riskIndicators = identifyRiskIndicators($advisee);
    $riskLevel = getRiskLevel($riskIndicators);
    $badgeInfo = getRiskBadgeClass($riskLevel);
    $riskCounts[$riskLevel]++;

    echo "👤 {$advisee['name']}: $riskLevel $badgeInfo";
    if (count($riskIndicators) === 0) {
        echo " (was 'None', now 'Low')";
    }
    echo "\n";
}

echo "\n=== UPDATED SUMMARY ===\n";
foreach ($riskCounts as $level => $count) {
    echo "• $level Risk: $count students\n";
}

echo "\n✅ CHANGE MADE:\n";
echo "Students with zero risk indicators now show as 'Low' risk instead of 'None'\n";
echo "All risk levels now use green badges for consistency\n";

echo "\n🎯 RESULT: Dashboard now shows 'Low', 'Medium', and 'High' risk levels only!\n";
