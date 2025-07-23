<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Course Performance Data Check for Student ID 10 (Emma Thompson) ===\n";

// Get student's course performance data
$stmt = $pdo->prepare('
    SELECT 
        c.code,
        c.name as course_name,
        fm.assignment_mark,
        fm.assignment_percentage,
        fm.quiz_mark,
        fm.quiz_percentage,
        fm.test_mark,
        fm.test_percentage,
        fm.final_exam_mark,
        fm.final_exam_percentage,
        fm.final_grade,
        fm.letter_grade,
        fm.gpa
    FROM final_marks_custom fm
    JOIN courses c ON fm.course_id = c.id
    WHERE fm.student_id = 10
    ORDER BY c.code
');
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($courses as $course) {
    echo "\n📚 {$course['code']} - {$course['course_name']}:\n";
    echo "  Assignment: {$course['assignment_mark']}/100 ({$course['assignment_percentage']}%)\n";
    echo "  Quiz: {$course['quiz_mark']}/100 ({$course['quiz_percentage']}%)\n";
    echo "  Test: {$course['test_mark']}/100 ({$course['test_percentage']}%)\n";
    echo "  Final Exam: {$course['final_exam_mark']}/100 ({$course['final_exam_percentage']}%)\n";
    echo "  Overall: {$course['final_grade']}% ({$course['letter_grade']}, GPA: {$course['gpa']})\n";

    // Verify total calculation
    $calculated_total = $course['assignment_percentage'] + $course['quiz_percentage'] +
        $course['test_percentage'] + $course['final_exam_percentage'];
    $matches_stored = abs($calculated_total - $course['final_grade']) < 0.01;
    echo "  Calculation Check: " . number_format($calculated_total, 2) . "% " .
        ($matches_stored ? "✅ Matches" : "❌ Mismatch") . "\n";
}

echo "\n=== API Response Check ===\n";

// Simulate the API call that the frontend makes
$stmt = $pdo->prepare('
    SELECT 
        u.id,
        u.name,
        u.matric_number,
        u.email
    FROM users u 
    WHERE u.id = 10 AND u.role = "student"
');
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    echo "Student: {$student['name']} ({$student['matric_number']})\n";

    // Get courses with performance data
    $stmt = $pdo->prepare('
        SELECT 
            c.id,
            c.code,
            c.name,
            c.semester,
            c.academic_year,
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
    $courseData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nCourses data that frontend receives:\n";
    foreach ($courseData as $course) {
        echo "  {$course['code']}: Assignment={$course['assignment_mark']}, Quiz={$course['quiz_mark']}, Test={$course['test_mark']}, Final={$course['final_exam_mark']}\n";
    }
}
