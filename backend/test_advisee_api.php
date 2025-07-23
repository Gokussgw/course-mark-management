<?php
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection
function getDbConnection()
{
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'course_mark_management';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

// Test advisee reports functionality directly
try {
    $pdo = getDbConnection();

    // Test login first
    echo "Testing login...\n";
    $stmt = $pdo->prepare('SELECT id, name, email, role, password FROM users WHERE email = ? AND role = "advisor"');
    $stmt->execute(['advisor1@example.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify('password', $user['password'])) {
        echo "✅ Login successful for advisor: " . $user['name'] . "\n";
        $advisorId = $user['id'];

        // Now test the advisee query
        echo "\nTesting advisee data from final_marks_custom...\n";

        // Get all advisees with their comprehensive academic information
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

        echo "Found " . count($advisees) . " advisees:\n";
        foreach ($advisees as $advisee) {
            echo "- " . $advisee['name'] . " (ID: " . $advisee['id'] . ")\n";
            echo "  Matric: " . $advisee['matric_number'] . "\n";
            echo "  GPA: " . round($advisee['overall_gpa'], 2) . "\n";
            echo "  Courses: " . $advisee['completed_courses'] . "/" . $advisee['total_courses'] . "\n";
            echo "  Grades: A=" . $advisee['a_grades'] . ", B=" . $advisee['b_grades'] . ", C=" . $advisee['c_grades'] . ", D=" . $advisee['d_grades'] . ", F=" . $advisee['f_grades'] . "\n";
            echo "\n";
        }

        // Calculate summary statistics
        $totalAdvisees = count($advisees);
        $avgGpa = $totalAdvisees > 0 ? array_sum(array_column($advisees, 'overall_gpa')) / $totalAdvisees : 0;
        $atRiskCount = count(array_filter($advisees, function ($a) {
            return ($a['overall_gpa'] ?? 0) < 2.0;
        }));
        $excellentCount = count(array_filter($advisees, function ($a) {
            return ($a['overall_gpa'] ?? 0) >= 3.5;
        }));

        echo "Summary Statistics:\n";
        echo "- Total Advisees: " . $totalAdvisees . "\n";
        echo "- Average GPA: " . round($avgGpa, 2) . "\n";
        echo "- At Risk Students: " . $atRiskCount . "\n";
        echo "- Excellent Performers: " . $excellentCount . "\n";
    } else {
        echo "❌ Login failed\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
