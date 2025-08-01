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
    case 'advisees':
        getAdvisees($pdo, $user);
        break;
    case 'notes':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            getNotes($pdo, $user);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            saveNote($pdo, $user);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            deleteNote($pdo, $user);
        }
        break;
    case 'advisee_courses':
        getAdviseeCourses($pdo, $user);
        break;
    case 'advisor_stats':
        getAdvisorStats($pdo, $user);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

// Get advisor's advisees with their performance data
function getAdvisees($pdo, $user)
{
    if (!$user || $user['role'] !== 'advisor') {
        http_response_code(401);
        echo json_encode(['error' => 'Advisor access required']);
        return;
    }

    try {
        $advisorId = $user['id'];

        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.name,
                u.email,
                u.matric_number,
                u.created_at,
                COALESCE(AVG(fm.gpa), 0) as gpa,
                COUNT(DISTINCT fm.course_id) as enrolled_courses,
                COUNT(DISTINCT fm.id) as completed_credits,
                CASE 
                    WHEN COALESCE(AVG(fm.final_grade), 0) < 50 THEN 'High'
                    WHEN COALESCE(AVG(fm.final_grade), 0) < 65 THEN 'Medium'
                    ELSE 'Low'
                END as risk,
                CASE 
                    WHEN COALESCE(AVG(fm.gpa), 0) >= 3.0 THEN 'Good Standing'
                    WHEN COALESCE(AVG(fm.gpa), 0) >= 2.0 THEN 'Warning'
                    ELSE 'Probation'
                END as status
            FROM users u
            LEFT JOIN final_marks_custom fm ON u.id = fm.student_id
            WHERE u.advisor_id = ? AND u.role = 'student'
            GROUP BY u.id, u.name, u.email, u.matric_number, u.created_at
            ORDER BY u.name
        ");

        $stmt->execute([$advisorId]);
        $advisees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the data
        foreach ($advisees as &$advisee) {
            $advisee['gpa'] = round($advisee['gpa'], 2);
            $advisee['enrolled_courses'] = (int)$advisee['enrolled_courses'];
            $advisee['completed_credits'] = (int)$advisee['completed_credits'];
        }

        echo json_encode(['advisees' => $advisees]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get advisees: ' . $e->getMessage()]);
    }
}

// Get courses for a specific advisee
function getAdviseeCourses($pdo, $user)
{
    if (!$user || $user['role'] !== 'advisor') {
        http_response_code(401);
        echo json_encode(['error' => 'Advisor access required']);
        return;
    }

    try {
        $advisorId = $user['id'];
        $studentId = $_GET['student_id'] ?? null;

        if (!$studentId) {
            http_response_code(400);
            echo json_encode(['error' => 'student_id parameter required']);
            return;
        }

        // Verify the student is under this advisor
        $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND advisor_id = ? AND role = "student"');
        $stmt->execute([$studentId, $advisorId]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Student not under your advisory']);
            return;
        }

        // Get detailed course information for the student
        $stmt = $pdo->prepare("
            SELECT 
                c.id,
                c.code,
                c.name,
                c.semester,
                c.academic_year,
                3 as credits,
                u.name as lecturer_name,
                e.created_at as enrolled_at,
                COALESCE(AVG(
                    CASE 
                        WHEN fm.final_grade IS NOT NULL THEN fm.final_grade
                        ELSE NULL
                    END
                ), 0) as current_grade,
                CASE 
                    WHEN COALESCE(AVG(fm.final_grade), 0) >= 70 THEN 'Good'
                    WHEN COALESCE(AVG(fm.final_grade), 0) >= 50 THEN 'Satisfactory'
                    WHEN COALESCE(AVG(fm.final_grade), 0) > 0 THEN 'At Risk'
                    ELSE 'No Grade'
                END as status,
                COUNT(DISTINCT a.id) as total_assessments,
                COUNT(DISTINCT m.id) as completed_assessments
            FROM enrollments e 
            JOIN courses c ON e.course_id = c.id 
            LEFT JOIN users u ON c.lecturer_id = u.id
            LEFT JOIN final_marks_custom fm ON c.id = fm.course_id AND e.student_id = fm.student_id
            LEFT JOIN assessments a ON c.id = a.course_id
            LEFT JOIN marks m ON a.id = m.assessment_id AND e.student_id = m.student_id
            WHERE e.student_id = ?
            GROUP BY c.id, c.code, c.name, c.semester, c.academic_year, u.name, e.created_at
            ORDER BY c.code
        ");

        $stmt->execute([$studentId]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the data
        foreach ($courses as &$course) {
            $course['current_grade'] = round($course['current_grade'], 2);
            $course['credits'] = (int)$course['credits'];
            $course['total_assessments'] = (int)$course['total_assessments'];
            $course['completed_assessments'] = (int)$course['completed_assessments'];
            $course['progress'] = $course['total_assessments'] > 0
                ? round(($course['completed_assessments'] / $course['total_assessments']) * 100, 1)
                : 0;
        }

        echo json_encode(['courses' => $courses]);
    } catch (Exception $e) {
        http_response_code(500);
        error_log("Error in getAdviseeCourses: " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        echo json_encode(['error' => 'Failed to get advisee courses: ' . $e->getMessage()]);
    }
}

// Get advisor's notes
function getNotes($pdo, $user)
{
    if (!$user || $user['role'] !== 'advisor') {
        http_response_code(401);
        echo json_encode(['error' => 'Advisor access required']);
        return;
    }

    try {
        $advisorId = $user['id'];

        $stmt = $pdo->prepare("
            SELECT 
                n.id,
                n.student_id,
                u.name as student_name,
                n.note,
                n.created_at
            FROM advisor_notes n
            JOIN users u ON n.student_id = u.id
            WHERE n.advisor_id = ?
            ORDER BY n.created_at DESC
        ");

        $stmt->execute([$advisorId]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['notes' => $notes]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get notes: ' . $e->getMessage()]);
    }
}

// Save advisor note
function saveNote($pdo, $user)
{
    if (!$user || $user['role'] !== 'advisor') {
        http_response_code(401);
        echo json_encode(['error' => 'Advisor access required']);
        return;
    }

    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $advisorId = $user['id'];
        $studentId = $input['student_id'] ?? null;
        $note = $input['note'] ?? '';
        $noteId = $input['id'] ?? null;

        if (!$studentId || !$note) {
            http_response_code(400);
            echo json_encode(['error' => 'Student ID and note are required']);
            return;
        }

        // Verify student is under this advisor
        $verifyStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND advisor_id = ?");
        $verifyStmt->execute([$studentId, $advisorId]);
        if (!$verifyStmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Student not under your advisory']);
            return;
        }

        if ($noteId) {
            // Update existing note
            $stmt = $pdo->prepare("
                UPDATE advisor_notes 
                SET note = ?, updated_at = NOW()
                WHERE id = ? AND advisor_id = ?
            ");
            $stmt->execute([$note, $noteId, $advisorId]);
        } else {
            // Create new note
            $stmt = $pdo->prepare("
                INSERT INTO advisor_notes (advisor_id, student_id, note, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$advisorId, $studentId, $note]);
            $noteId = $pdo->lastInsertId();
        }

        // Get the updated note with student name
        $getStmt = $pdo->prepare("
            SELECT 
                n.id,
                n.student_id,
                u.name as student_name,
                n.note,
                n.created_at
            FROM advisor_notes n
            JOIN users u ON n.student_id = u.id
            WHERE n.id = ?
        ");
        $getStmt->execute([$noteId]);
        $savedNote = $getStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['note' => $savedNote]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save note: ' . $e->getMessage()]);
    }
}

// Delete advisor note
function deleteNote($pdo, $user)
{
    if (!$user || $user['role'] !== 'advisor') {
        http_response_code(401);
        echo json_encode(['error' => 'Advisor access required']);
        return;
    }

    try {
        $noteId = $_GET['note_id'] ?? null;
        $advisorId = $user['id'];

        if (!$noteId) {
            http_response_code(400);
            echo json_encode(['error' => 'Note ID is required']);
            return;
        }

        $stmt = $pdo->prepare("DELETE FROM advisor_notes WHERE id = ? AND advisor_id = ?");
        $stmt->execute([$noteId, $advisorId]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete note: ' . $e->getMessage()]);
    }
}

// Get advisor statistics
function getAdvisorStats($pdo, $user)
{
    if (!$user || $user['role'] !== 'advisor') {
        http_response_code(401);
        echo json_encode(['error' => 'Advisor access required']);
        return;
    }

    try {
        $advisorId = $user['id'];

        // Get overall statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT u.id) as total_advisees,
                COUNT(DISTINCT c.id) as total_courses,
                AVG(
                    CASE 
                        WHEN a.max_mark > 0 THEN (m.mark / a.max_mark) * 100 
                        ELSE 0 
                    END
                ) as avg_performance
            FROM users u
            LEFT JOIN enrollments e ON u.id = e.student_id
            LEFT JOIN courses c ON e.course_id = c.id
            LEFT JOIN assessments a ON c.id = a.course_id
            LEFT JOIN marks m ON u.id = m.student_id AND a.id = m.assessment_id
            WHERE u.advisor_id = ? AND u.role = 'student'
        ");

        $stmt->execute([$advisorId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'total_advisees' => (int)$stats['total_advisees'],
            'total_courses' => (int)$stats['total_courses'],
            'avg_performance' => round($stats['avg_performance'] ?? 0, 2)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get stats: ' . $e->getMessage()]);
    }
}
