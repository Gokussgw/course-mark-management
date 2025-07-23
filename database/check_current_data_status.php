<?php
require_once '../backend/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable('../backend');
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

try {
    $pdo = getDbConnection();

    echo "Current data summary:\n";
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM final_marks_custom');
    echo "Total records: " . $stmt->fetch()['total'] . "\n";

    $stmt = $pdo->query('SELECT COUNT(DISTINCT student_id) as students FROM final_marks_custom');
    echo "Unique students: " . $stmt->fetch()['students'] . "\n";

    $stmt = $pdo->query('SELECT COUNT(DISTINCT course_id) as courses FROM final_marks_custom');
    echo "Unique courses: " . $stmt->fetch()['courses'] . "\n";

    echo "\nAvailable courses:\n";
    $stmt = $pdo->query('SELECT id, code, name FROM courses ORDER BY id');
    while ($row = $stmt->fetch()) {
        echo "Course " . $row['id'] . ": " . $row['code'] . " - " . $row['name'] . "\n";
    }

    echo "\nStudent enrollment summary:\n";
    $stmt = $pdo->query('
        SELECT 
            u.id, u.name, u.advisor_id,
            COUNT(fm.id) as marks_records,
            COUNT(DISTINCT fm.course_id) as courses_with_marks
        FROM users u 
        LEFT JOIN final_marks_custom fm ON u.id = fm.student_id 
        WHERE u.role = "student" 
        GROUP BY u.id 
        ORDER BY u.advisor_id, u.name
    ');
    while ($row = $stmt->fetch()) {
        echo "Student " . $row['id'] . " (" . $row['name'] . ") - Advisor: " . $row['advisor_id'] . " - Records: " . $row['marks_records'] . " - Courses: " . $row['courses_with_marks'] . "\n";
    }

    echo "\nEnrollments summary:\n";
    $stmt = $pdo->query('
        SELECT 
            COUNT(*) as total_enrollments,
            COUNT(DISTINCT student_id) as enrolled_students,
            COUNT(DISTINCT course_id) as enrolled_courses
        FROM enrollments
    ');
    $enrollment_summary = $stmt->fetch();
    echo "Total enrollments: " . $enrollment_summary['total_enrollments'] . "\n";
    echo "Students with enrollments: " . $enrollment_summary['enrolled_students'] . "\n";
    echo "Courses with enrollments: " . $enrollment_summary['enrolled_courses'] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
