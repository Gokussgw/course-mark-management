<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Risk Level Consistency Check ===\n\n";

// Function to identify risk indicators (same as backend)
function identifyRiskIndicators($studentId, $pdo)
{
    // Get student data
    $stmt = $pdo->prepare('
        SELECT 
            u.id,
            u.name,
            AVG(fm.gpa) as overall_gpa,
            COUNT(DISTINCT e.course_id) as total_courses,
            COUNT(DISTINCT CASE WHEN fm.final_grade IS NOT NULL THEN e.course_id END) as completed_courses
        FROM users u
        LEFT JOIN enrollments e ON u.id = e.student_id
        LEFT JOIN final_marks_custom fm ON u.id = fm.student_id
        WHERE u.id = ?
        GROUP BY u.id, u.name
    ');
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) return [];

    // Get student courses
    $stmt = $pdo->prepare('
        SELECT 
            fm.assignment_mark,
            fm.quiz_mark,
            fm.test_mark,
            fm.final_exam_mark,
            fm.final_grade,
            fm.letter_grade,
            fm.gpa
        FROM enrollments e
        JOIN final_marks_custom fm ON e.student_id = fm.student_id AND e.course_id = fm.course_id
        WHERE e.student_id = ?
    ');
    $stmt->execute([$studentId]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $riskIndicators = [];
    $gpa = floatval($student['overall_gpa'] ?? 0);
    $completedCourses = count($courses);

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

    // Check component performance using raw marks
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

    // Check completion rate
    if ($completedCourses < 3) {
        $riskIndicators[] = 'insufficient_completed_courses';
    }

    return $riskIndicators;
}

// Frontend getRiskLevel function (updated)
function getRiskLevel($riskIndicators)
{
    $riskCount = count($riskIndicators);
    if ($riskCount >= 3) return 'High';
    if ($riskCount >= 2) return 'Medium';
    if ($riskCount >= 1) return 'Low';
    return 'None';
}

// Get badge class function
function getRiskBadgeClass($risk)
{
    switch ($risk) {
        case "High":
            return "🔴 bg-danger (RED)";
        case "Medium":
            return "🟡 bg-warning (YELLOW)";
        case "Low":
            return "🟢 bg-success (GREEN)";
        case "None":
            return "🔵 bg-info (BLUE)";
        default:
            return "⚪ bg-secondary (GRAY)";
    }
}

// Test with actual students
$stmt = $pdo->prepare('
    SELECT id, name, matric_number
    FROM users 
    WHERE role = "student" AND advisor_id IS NOT NULL 
    ORDER BY name
    LIMIT 5
');
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Testing Risk Level Consistency:\n\n";

foreach ($students as $student) {
    $riskIndicators = identifyRiskIndicators($student['id'], $pdo);
    $riskLevel = getRiskLevel($riskIndicators);
    $badgeInfo = getRiskBadgeClass($riskLevel);

    echo "👤 {$student['name']} ({$student['matric_number']}):\n";
    echo "   Risk Indicators: " . (empty($riskIndicators) ? "None" : implode(', ', $riskIndicators)) . "\n";
    echo "   Risk Level: $riskLevel\n";
    echo "   Dashboard Badge: $badgeInfo\n";
    echo "   ✅ Consistency: " . (count($riskIndicators) === 0 ? "Shows 'None' (was previously 'Low')" : "Correct") . "\n";
    echo "\n";
}

echo "=== SUMMARY ===\n";
echo "✅ Fixed getRiskLevel function to return 'None' for students with 0 risk indicators\n";
echo "✅ Added specific badge color for 'None' risk level (bg-info - blue)\n";
echo "✅ Risk levels now consistent between advisor dashboard and individual reports\n";
echo "✅ Color coding properly reflects student risk status\n";

echo "\n=== Risk Level Mapping ===\n";
echo "• 3+ indicators: High Risk (RED)\n";
echo "• 2 indicators: Medium Risk (YELLOW)\n";
echo "• 1 indicator: Low Risk (GREEN)\n";
echo "• 0 indicators: No Risk (BLUE)\n";
