<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Advisor Dashboard Risk Level Check ===\n\n";

// Simulate the comprehensive API call for advisor dashboard
// First get all students with an advisor

$stmt = $pdo->prepare('
    SELECT 
        u.id,
        u.name,
        u.matric_number,
        u.email,
        u.advisor_id,
        AVG(fm.gpa) as overall_gpa,
        COUNT(DISTINCT e.course_id) as total_courses,
        COUNT(DISTINCT CASE WHEN fm.final_grade IS NOT NULL THEN e.course_id END) as completed_courses
    FROM users u
    LEFT JOIN enrollments e ON u.id = e.student_id
    LEFT JOIN final_marks_custom fm ON u.id = fm.student_id
    WHERE u.role = "student" AND u.advisor_id IS NOT NULL
    GROUP BY u.id, u.name, u.matric_number, u.email, u.advisor_id
    ORDER BY u.name
    LIMIT 5
');
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

function identifyRiskIndicators($student, $pdo)
{
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
    $stmt->execute([$student['id']]);
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

function getRiskLevel($riskIndicators)
{
    $riskCount = count($riskIndicators);
    if ($riskCount >= 3) return 'High';
    if ($riskCount >= 2) return 'Medium';
    if ($riskCount >= 1) return 'Low';
    return 'None'; // This should be 'None' instead of 'Low'
}

echo "Student Risk Level Analysis:\n\n";

foreach ($students as $student) {
    $riskIndicators = identifyRiskIndicators($student, $pdo);
    $riskLevel = getRiskLevel($riskIndicators);

    echo "👤 {$student['name']} ({$student['matric_number']}):\n";
    echo "   GPA: " . round($student['overall_gpa'], 2) . "\n";
    echo "   Completed: {$student['completed_courses']}/{$student['total_courses']} courses\n";
    echo "   Risk Indicators: " . (empty($riskIndicators) ? "None" : implode(', ', $riskIndicators)) . "\n";
    echo "   Risk Level: $riskLevel\n";

    // Show what color this should be
    switch ($riskLevel) {
        case 'High':
            echo "   Badge Color: 🔴 RED (bg-danger)\n";
            break;
        case 'Medium':
            echo "   Badge Color: 🟡 YELLOW (bg-warning)\n";
            break;
        case 'Low':
            echo "   Badge Color: 🟢 GREEN (bg-success)\n";
            break;
        case 'None':
            echo "   Badge Color: 🟢 GREEN (should be a better indicator)\n";
            break;
        default:
            echo "   Badge Color: ⚪ GRAY (bg-secondary)\n";
            break;
    }
    echo "\n";
}

echo "=== ISSUE IDENTIFIED ===\n";
echo "Problem: Students with NO risk indicators show as 'Low' risk instead of 'No Risk'\n";
echo "Solution: Update getRiskLevel function to return 'None' for 0 risk indicators\n";
