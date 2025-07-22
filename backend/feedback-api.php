<?php
// Lecturer Feedback API - feedback-api.php
header('Content-Type: application/json');

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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $host = $_ENV['DB_HOST'];
    $dbname = $_ENV['DB_NAME'];
    $username = $_ENV['DB_USER'];
    $password = $_ENV['DB_PASS'];

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    switch ($method) {
        case 'GET':
            handleGetRequest($pdo, $action);
            break;
        case 'POST':
            handlePostRequest($pdo, $action);
            break;
        case 'PUT':
            handlePutRequest($pdo, $action);
            break;
        case 'DELETE':
            handleDeleteRequest($pdo, $action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetRequest($pdo, $action)
{
    switch ($action) {
        case 'lecturer_feedback':
            getLecturerFeedback($pdo);
            break;
        case 'student_feedback':
            getStudentFeedback($pdo);
            break;
        case 'advisor_feedback':
            getAdvisorFeedback($pdo);
            break;
        case 'course_feedback':
            getCourseFeedback($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($pdo, $action)
{
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'add_feedback':
            addFeedback($pdo, $input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePutRequest($pdo, $action)
{
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'update_feedback':
            updateFeedback($pdo, $input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDeleteRequest($pdo, $action)
{
    switch ($action) {
        case 'delete_feedback':
            deleteFeedback($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

// Get feedback created by a lecturer
function getLecturerFeedback($pdo)
{
    $lecturerId = $_GET['lecturer_id'] ?? null;
    $courseId = $_GET['course_id'] ?? null;

    if (!$lecturerId) {
        http_response_code(400);
        echo json_encode(['error' => 'Lecturer ID is required']);
        return;
    }

    $sql = "
        SELECT 
            lf.id,
            lf.feedback_type,
            lf.subject,
            lf.feedback,
            lf.priority,
            lf.is_visible_to_student,
            lf.is_visible_to_advisor,
            lf.created_at,
            lf.updated_at,
            s.id as student_id,
            s.name as student_name,
            s.email as student_email,
            s.matric_number,
            c.id as course_id,
            c.code as course_code,
            c.name as course_name
        FROM lecturer_feedback lf
        JOIN users s ON lf.student_id = s.id
        JOIN courses c ON lf.course_id = c.id
        WHERE lf.lecturer_id = ?
    ";

    $params = [$lecturerId];

    if ($courseId) {
        $sql .= " AND lf.course_id = ?";
        $params[] = $courseId;
    }

    $sql .= " ORDER BY lf.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $feedback = $stmt->fetchAll();

    echo json_encode(['feedback' => $feedback]);
}

// Get feedback for a specific student
function getStudentFeedback($pdo)
{
    $studentId = $_GET['student_id'] ?? null;
    $courseId = $_GET['course_id'] ?? null;

    if (!$studentId) {
        http_response_code(400);
        echo json_encode(['error' => 'Student ID is required']);
        return;
    }

    $sql = "
        SELECT 
            lf.id,
            lf.feedback_type,
            lf.subject,
            lf.feedback,
            lf.priority,
            lf.created_at,
            lf.updated_at,
            l.name as lecturer_name,
            l.email as lecturer_email,
            c.code as course_code,
            c.name as course_name
        FROM lecturer_feedback lf
        JOIN users l ON lf.lecturer_id = l.id
        JOIN courses c ON lf.course_id = c.id
        WHERE lf.student_id = ? AND lf.is_visible_to_student = TRUE
    ";

    $params = [$studentId];

    if ($courseId) {
        $sql .= " AND lf.course_id = ?";
        $params[] = $courseId;
    }

    $sql .= " ORDER BY lf.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $feedback = $stmt->fetchAll();

    echo json_encode(['feedback' => $feedback]);
}

// Get feedback for students under an advisor
function getAdvisorFeedback($pdo)
{
    $advisorId = $_GET['advisor_id'] ?? null;

    if (!$advisorId) {
        http_response_code(400);
        echo json_encode(['error' => 'Advisor ID is required']);
        return;
    }

    $sql = "
        SELECT 
            lf.id,
            lf.feedback_type,
            lf.subject,
            lf.feedback,
            lf.priority,
            lf.created_at,
            lf.updated_at,
            s.name as student_name,
            s.email as student_email,
            s.matric_number,
            l.name as lecturer_name,
            c.code as course_code,
            c.name as course_name
        FROM lecturer_feedback lf
        JOIN users s ON lf.student_id = s.id
        JOIN users l ON lf.lecturer_id = l.id
        JOIN courses c ON lf.course_id = c.id
        WHERE s.advisor_id = ? AND lf.is_visible_to_advisor = TRUE
        ORDER BY lf.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$advisorId]);
    $feedback = $stmt->fetchAll();

    echo json_encode(['feedback' => $feedback]);
}

// Get all feedback for a specific course
function getCourseFeedback($pdo)
{
    $courseId = $_GET['course_id'] ?? null;

    if (!$courseId) {
        http_response_code(400);
        echo json_encode(['error' => 'Course ID is required']);
        return;
    }

    $sql = "
        SELECT 
            lf.id,
            lf.feedback_type,
            lf.subject,
            lf.feedback,
            lf.priority,
            lf.created_at,
            s.name as student_name,
            s.matric_number,
            COUNT(lf.id) as feedback_count
        FROM lecturer_feedback lf
        JOIN users s ON lf.student_id = s.id
        WHERE lf.course_id = ?
        GROUP BY s.id
        ORDER BY lf.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$courseId]);
    $feedback = $stmt->fetchAll();

    echo json_encode(['feedback' => $feedback]);
}

// Add new feedback
function addFeedback($pdo, $input)
{
    $required = ['student_id', 'course_id', 'lecturer_id', 'subject', 'feedback'];

    foreach ($required as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            return;
        }
    }

    $sql = "
        INSERT INTO lecturer_feedback 
        (student_id, course_id, lecturer_id, feedback_type, subject, feedback, priority, is_visible_to_student, is_visible_to_advisor)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $input['student_id'],
        $input['course_id'],
        $input['lecturer_id'],
        $input['feedback_type'] ?? 'general',
        $input['subject'],
        $input['feedback'],
        $input['priority'] ?? 'medium',
        $input['is_visible_to_student'] ?? true,
        $input['is_visible_to_advisor'] ?? true
    ]);

    if ($result) {
        $feedbackId = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Feedback added successfully',
            'feedback_id' => $feedbackId
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add feedback']);
    }
}

// Update existing feedback
function updateFeedback($pdo, $input)
{
    $feedbackId = $input['id'] ?? null;

    if (!$feedbackId) {
        http_response_code(400);
        echo json_encode(['error' => 'Feedback ID is required']);
        return;
    }

    $fields = [];
    $params = [];

    $allowedFields = ['feedback_type', 'subject', 'feedback', 'priority', 'is_visible_to_student', 'is_visible_to_advisor'];

    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $fields[] = "$field = ?";
            $params[] = $input[$field];
        }
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }

    $params[] = $feedbackId;

    $sql = "UPDATE lecturer_feedback SET " . implode(', ', $fields) . " WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Feedback updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update feedback']);
    }
}

// Delete feedback
function deleteFeedback($pdo)
{
    $feedbackId = $_GET['id'] ?? null;

    if (!$feedbackId) {
        http_response_code(400);
        echo json_encode(['error' => 'Feedback ID is required']);
        return;
    }

    $sql = "DELETE FROM lecturer_feedback WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$feedbackId]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Feedback deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete feedback']);
    }
}
