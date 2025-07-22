<?php
require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// CORS headers - Dynamic origin handling
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (preg_match('/^http:\/\/localhost:\d+$/', $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: http://localhost:3000'); // fallback
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'course_mark_management';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// JWT verification function
function verifyJWT($token)
{
    try {
        $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your_jwt_secret_key_here';
        $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));
        return (array)$decoded;
    } catch (Exception $e) {
        return null;
    }
}

// Get JWT token from Authorization header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$token = '';

if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $token = $matches[1];
}

$user = $token ? verifyJWT($token) : null;

// Handle requests
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'student_ranking':
        getStudentRanking($pdo, $user);
        break;
    case 'class_rankings':
        getClassRankings($pdo, $user);
        break;
    case 'course_rankings':
        getCourseRankings($pdo, $user);
        break;
    case 'advisor_students_rankings':
        getAdvisorStudentsRankings($pdo, $user);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

// Get individual student ranking
function getStudentRanking($pdo, $user)
{
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }

    try {
        $studentId = $user['role'] === 'student' ? $user['id'] : ($_GET['student_id'] ?? null);

        if (!$studentId) {
            http_response_code(400);
            echo json_encode(['error' => 'Student ID required']);
            return;
        }

        // Calculate overall ranking based on GPA from final_marks_custom
        $stmt = $pdo->prepare("
            WITH student_performance AS (
                SELECT 
                    u.id,
                    u.name,
                    u.matric_number,
                    COALESCE(AVG(fm.gpa), 0) as gpa,
                    COUNT(DISTINCT fm.course_id) as courses_taken,
                    AVG(fm.final_grade) as average_grade
                FROM users u
                LEFT JOIN final_marks_custom fm ON u.id = fm.student_id
                WHERE u.role = 'student'
                GROUP BY u.id, u.name, u.matric_number
            ),
            ranked_students AS (
                SELECT 
                    *,
                    RANK() OVER (ORDER BY gpa DESC, average_grade DESC) as overall_rank,
                    COUNT(*) OVER() as total_students
                FROM student_performance
            )
            SELECT 
                *
            FROM ranked_students 
            WHERE id = ?
        ");

        $stmt->execute([$studentId]);
        $ranking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ranking) {
            http_response_code(404);
            echo json_encode(['error' => 'Student not found']);
            return;
        }

        // Get course-specific rankings from final_marks_custom
        // Get course-specific rankings from final_marks_custom
        $courseStmt = $pdo->prepare("
            SELECT 
                c.id as course_id,
                c.code,
                c.name as course_name,
                fm.final_grade as course_average,
                fm.letter_grade,
                fm.gpa as course_gpa,
                fm.assignment_mark,
                fm.quiz_mark,
                fm.test_mark,
                fm.final_exam_mark,
                RANK() OVER (
                    PARTITION BY c.id 
                    ORDER BY fm.final_grade DESC
                ) as course_rank,
                COUNT(*) OVER (PARTITION BY c.id) as students_in_course
            FROM courses c
            JOIN final_marks_custom fm ON c.id = fm.course_id
            WHERE fm.student_id = ?
        ");

        $courseStmt->execute([$studentId]);
        $courseRankings = $courseStmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the response
        $ranking['gpa'] = round($ranking['gpa'], 2);
        $ranking['average_grade'] = round($ranking['average_grade'], 2);
        $ranking['course_rankings'] = array_map(function ($course) {
            $course['course_average'] = round($course['course_average'], 2);
            return $course;
        }, $courseRankings);

        echo json_encode(['ranking' => $ranking]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get student ranking: ' . $e->getMessage()]);
    }
}

// Get class rankings (top students)
function getClassRankings($pdo, $user)
{
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }

    try {
        $limit = $_GET['limit'] ?? 20;
        $offset = $_GET['offset'] ?? 0;

        $stmt = $pdo->prepare("
            WITH student_performance AS (
                SELECT 
                    u.id,
                    u.name,
                    u.matric_number,
                    COALESCE(AVG(fm.gpa), 0) as gpa,
                    COUNT(DISTINCT fm.course_id) as courses_taken,
                    AVG(fm.final_grade) as average_grade
                FROM users u
                LEFT JOIN final_marks_custom fm ON u.id = fm.student_id
                WHERE u.role = 'student'
                GROUP BY u.id, u.name, u.matric_number
            ),
            ranked_students AS (
                SELECT 
                    *,
                    RANK() OVER (ORDER BY gpa DESC, average_grade DESC) as overall_rank,
                    COUNT(*) OVER() as total_students
                FROM student_performance
            )
            SELECT 
                *
            FROM ranked_students 
            ORDER BY overall_rank
            LIMIT ? OFFSET ?
        ");

        $stmt->execute([$limit, $offset]);
        $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the data
        foreach ($rankings as &$student) {
            $student['gpa'] = round($student['gpa'], 2);
            $student['average_grade'] = round($student['average_grade'], 2);
            $student['overall_rank'] = (int)$student['overall_rank'];
            $student['total_students'] = (int)$student['total_students'];
            $student['courses_taken'] = (int)$student['courses_taken'];
        }

        echo json_encode(['rankings' => $rankings]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get class rankings: ' . $e->getMessage()]);
    }
}

// Get course-specific rankings
function getCourseRankings($pdo, $user)
{
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }

    try {
        $courseId = $_GET['course_id'] ?? null;

        if (!$courseId) {
            http_response_code(400);
            echo json_encode(['error' => 'Course ID required']);
            return;
        }

        $stmt = $pdo->prepare("
            WITH course_performance AS (
                SELECT 
                    u.id,
                    u.name,
                    u.matric_number,
                    c.code,
                    c.name as course_name,
                    fm.final_grade as course_average,
                    fm.letter_grade,
                    fm.gpa as course_gpa,
                    fm.assignment_mark,
                    fm.quiz_mark,
                    fm.test_mark,
                    fm.final_exam_mark
                FROM users u
                JOIN final_marks_custom fm ON u.id = fm.student_id
                JOIN courses c ON fm.course_id = c.id
                WHERE c.id = ? AND u.role = 'student'
            ),
            ranked_course_students AS (
                SELECT 
                    *,
                    RANK() OVER (ORDER BY course_average DESC) as course_rank,
                    COUNT(*) OVER() as total_students_in_course
                FROM course_performance
            )
            SELECT 
                *
            FROM ranked_course_students 
            ORDER BY course_rank
        ");

        $stmt->execute([$courseId]);
        $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the data
        foreach ($rankings as &$student) {
            $student['course_average'] = round($student['course_average'], 2);
            $student['course_gpa'] = round($student['course_gpa'], 2);
            $student['course_rank'] = (int)$student['course_rank'];
            $student['total_students_in_course'] = (int)$student['total_students_in_course'];
        }

        echo json_encode(['rankings' => $rankings]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get course rankings: ' . $e->getMessage()]);
    }
}

// Get advisor's students rankings
function getAdvisorStudentsRankings($pdo, $user)
{
    if (!$user || $user['role'] !== 'advisor') {
        http_response_code(401);
        echo json_encode(['error' => 'Advisor access required']);
        return;
    }

    try {
        $advisorId = $user['id'];

        $stmt = $pdo->prepare("
            WITH advisor_student_performance AS (
                SELECT 
                    u.id,
                    u.name,
                    u.matric_number,
                    COALESCE(AVG(fm.gpa), 0) as gpa,
                    COUNT(DISTINCT fm.course_id) as courses_taken,
                    AVG(fm.final_grade) as average_grade
                FROM users u
                LEFT JOIN final_marks_custom fm ON u.id = fm.student_id
                WHERE u.advisor_id = ? AND u.role = 'student'
                GROUP BY u.id, u.name, u.matric_number
            ),
            ranked_advisor_students AS (
                SELECT 
                    *,
                    RANK() OVER (ORDER BY gpa DESC, average_grade DESC) as advisor_rank,
                    COUNT(*) OVER() as total_advisor_students
                FROM advisor_student_performance
            ),
            overall_student_performance AS (
                SELECT 
                    u.id,
                    COALESCE(AVG(fm.gpa), 0) as overall_gpa
                FROM users u
                LEFT JOIN final_marks_custom fm ON u.id = fm.student_id
                WHERE u.role = 'student'
                GROUP BY u.id
            ),
            overall_rankings AS (
                SELECT 
                    id,
                    RANK() OVER (ORDER BY overall_gpa DESC) as overall_rank,
                    COUNT(*) OVER() as total_students
                FROM overall_student_performance
            )
            SELECT 
                ras.*,
                or_table.overall_rank,
                or_table.total_students
            FROM ranked_advisor_students ras
            LEFT JOIN overall_rankings or_table ON ras.id = or_table.id
            ORDER BY ras.advisor_rank
        ");

        $stmt->execute([$advisorId]);
        $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the data
        foreach ($rankings as &$student) {
            $student['gpa'] = round($student['gpa'], 2);
            $student['average_grade'] = round($student['average_grade'], 2);
            $student['advisor_rank'] = (int)$student['advisor_rank'];
            $student['total_advisor_students'] = (int)$student['total_advisor_students'];
            $student['overall_rank'] = (int)$student['overall_rank'];
            $student['total_students'] = (int)$student['total_students'];
            $student['courses_taken'] = (int)$student['courses_taken'];
        }

        echo json_encode(['rankings' => $rankings]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get advisor students rankings: ' . $e->getMessage()]);
    }
}
