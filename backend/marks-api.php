<?php
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

// Include notification utilities
require_once __DIR__ . '/src/utils/NotificationUtils.php';
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'course_mark_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$request = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost();
        break;
    case 'PUT':
        handlePut();
        break;
    case 'DELETE':
        handleDelete();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleGet()
{
    global $pdo;

    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'lecturer_courses':
            getLecturerCourses();
            break;
        case 'course_students_marks':
            getCourseStudentsWithMarks();
            break;
        case 'course_students_with_marks':
            getCourseStudentsWithMarks();
            break;
        case 'course_students_list':
            getCourseStudentsList();
            break;
        case 'student_marks':
            getStudentMarks();
            break;
        case 'export_marks_csv':
            exportMarksCSV();
            break;
        case 'unread_notifications':
            getUnreadNotificationCountAPI();
            break;
        case 'recent_notifications':
            getRecentUserNotifications();
            break;
        case 'mark_notification_read':
            markNotificationAsRead();
            break;
        case 'student_dashboard_courses':
            getStudentDashboardCourses();
            break;
        case 'student_dashboard_assessments':
            getStudentDashboardAssessments();
            break;
        case 'student_dashboard_performance':
            getStudentDashboardPerformance();
            break;
        case 'student_course_detail':
            getStudentCourseDetail();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function getLecturerCourses()
{
    global $pdo;

    $lecturer_id = $_GET['lecturer_id'] ?? null;

    if (!$lecturer_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Lecturer ID required']);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT c.id, c.code, c.name, c.academic_year, c.semester, c.lecturer_id,
                   COUNT(e.student_id) as student_count
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            WHERE c.lecturer_id = ?
            GROUP BY c.id
            ORDER BY c.code
        ");
        $stmt->execute([$lecturer_id]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['courses' => $courses]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getCourseStudentsWithMarks()
{
    global $pdo;

    $course_id = $_GET['course_id'] ?? null;

    if (!$course_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Course ID required']);
        return;
    }

    try {
        // Get students enrolled in the course with their marks
        $stmt = $pdo->prepare("
            SELECT 
                u.id as student_id,
                u.name as student_name,
                u.matric_number,
                u.email,
                e.created_at as enrolled_at,
                fm.assignment_mark,
                fm.quiz_mark,
                fm.test_mark,
                fm.assignment_percentage,
                fm.quiz_percentage,
                fm.test_percentage,
                fm.component_total,
                fm.final_exam_mark,
                fm.final_exam_percentage,
                fm.final_grade,
                fm.letter_grade,
                fm.gpa
            FROM enrollments e
            INNER JOIN users u ON e.student_id = u.id
            LEFT JOIN final_marks_custom fm ON e.student_id = fm.student_id AND e.course_id = fm.course_id
            WHERE e.course_id = ? AND u.role = 'student'
            ORDER BY u.name
        ");
        $stmt->execute([$course_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the data for frontend compatibility
        foreach ($students as &$student) {
            // Convert NULL values to 0 for display
            $student['assignment_mark'] = floatval($student['assignment_mark'] ?? 0);
            $student['quiz_mark'] = floatval($student['quiz_mark'] ?? 0);
            $student['test_mark'] = floatval($student['test_mark'] ?? 0);
            $student['assignment_percentage'] = floatval($student['assignment_percentage'] ?? 0);
            $student['quiz_percentage'] = floatval($student['quiz_percentage'] ?? 0);
            $student['test_percentage'] = floatval($student['test_percentage'] ?? 0);
            $student['component_total'] = floatval($student['component_total'] ?? 0);
            $student['final_exam_mark'] = floatval($student['final_exam_mark'] ?? 0);
            $student['final_exam_percentage'] = floatval($student['final_exam_percentage'] ?? 0);
            $student['final_grade'] = floatval($student['final_grade'] ?? 0);
            $student['letter_grade'] = $student['letter_grade'] ?? 'F';
            $student['gpa'] = floatval($student['gpa'] ?? 0);

            // Add the marks structure for frontend compatibility
            $student['marks'] = [
                'assignment' => $student['assignment_mark'],
                'quiz' => $student['quiz_mark'],
                'test' => $student['test_mark'],
                'final_exam' => $student['final_exam_mark'],
                'final_mark' => $student['final_grade'],
                'grade' => $student['letter_grade'],
                'gpa_points' => $student['gpa']
            ];

            // Format ID for compatibility
            $student['id'] = $student['student_id'];
            $student['name'] = $student['student_name'];
        }

        echo json_encode(['success' => true, 'students' => $students]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getCourseStudentsList()
{
    global $pdo;

    $course_id = $_GET['course_id'] ?? null;

    if (!$course_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Course ID required']);
        return;
    }

    try {
        // Get basic student list enrolled in the course
        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.name,
                u.matric_number as student_id,
                u.email
            FROM enrollments e
            INNER JOIN users u ON e.student_id = u.id
            WHERE e.course_id = ? AND u.role = 'student'
            ORDER BY u.name
        ");
        $stmt->execute([$course_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'students' => $students]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getStudentMarks()
{
    global $pdo;

    $student_id = $_GET['student_id'] ?? null;
    $course_id = $_GET['course_id'] ?? null;

    if (!$student_id || !$course_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Student ID and Course ID required']);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT assessment_type, marks, max_marks, percentage, created_at, updated_at
            FROM marks
            WHERE student_id = ? AND course_id = ?
        ");
        $stmt->execute([$student_id, $course_id]);
        $marks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['marks' => $marks]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handlePost()
{
    global $pdo, $request;

    $action = $request['action'] ?? '';

    switch ($action) {
        case 'save_marks':
            saveStudentMarks();
            break;
        case 'calculate_final_marks':
            calculateFinalMarks();
            break;
        case 'send_course_announcement':
            sendCourseAnnouncement();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function saveStudentMarks()
{
    global $pdo, $request;

    $student_id = $request['student_id'] ?? null;
    $course_id = $request['course_id'] ?? null;
    $marks = $request['marks'] ?? [];
    $lecturer_id = $request['lecturer_id'] ?? null;

    if (!$student_id || !$course_id || !$lecturer_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Student ID, Course ID, and Lecturer ID required']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Extract marks
        $assignment_mark = floatval($marks['assignment'] ?? 0);
        $quiz_mark = floatval($marks['quiz'] ?? 0);
        $test_mark = floatval($marks['test'] ?? 0);
        $final_exam_mark = floatval($marks['final_exam'] ?? 0);

        // Calculate component total (70%)
        $component_total = ($assignment_mark * 0.25) + ($quiz_mark * 0.20) + ($test_mark * 0.25);

        // Calculate final grade
        $final_grade = $component_total + ($final_exam_mark * 0.30);

        // Determine letter grade and GPA
        $letter_grade = 'F';
        $gpa = 0.00;

        if ($final_grade >= 80) {
            $letter_grade = 'A';
            $gpa = 4.00;
        } elseif ($final_grade >= 75) {
            $letter_grade = 'A-';
            $gpa = 3.67;
        } elseif ($final_grade >= 70) {
            $letter_grade = 'B+';
            $gpa = 3.33;
        } elseif ($final_grade >= 65) {
            $letter_grade = 'B';
            $gpa = 3.00;
        } elseif ($final_grade >= 60) {
            $letter_grade = 'B-';
            $gpa = 2.67;
        } elseif ($final_grade >= 55) {
            $letter_grade = 'C+';
            $gpa = 2.33;
        } elseif ($final_grade >= 50) {
            $letter_grade = 'C';
            $gpa = 2.00;
        }

        // Check existing marks BEFORE updating for notification comparison
        $existingStmt = $pdo->prepare("
            SELECT assignment_mark, quiz_mark, test_mark, final_exam_mark 
            FROM final_marks_custom 
            WHERE student_id = ? AND course_id = ?
        ");
        $existingStmt->execute([$student_id, $course_id]);
        $existing = $existingStmt->fetch();

        // Save final marks with individual component marks
        $stmt = $pdo->prepare("
            INSERT INTO final_marks_custom (
                student_id, course_id, 
                assignment_mark, assignment_percentage,
                quiz_mark, quiz_percentage,
                test_mark, test_percentage,
                final_exam_mark, final_exam_percentage, 
                component_total, final_grade, letter_grade, gpa
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                assignment_mark = VALUES(assignment_mark),
                assignment_percentage = VALUES(assignment_percentage),
                quiz_mark = VALUES(quiz_mark),
                quiz_percentage = VALUES(quiz_percentage),
                test_mark = VALUES(test_mark),
                test_percentage = VALUES(test_percentage),
                final_exam_mark = VALUES(final_exam_mark),
                final_exam_percentage = VALUES(final_exam_percentage),
                component_total = VALUES(component_total),
                final_grade = VALUES(final_grade),
                letter_grade = VALUES(letter_grade),
                gpa = VALUES(gpa),
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            $student_id,
            $course_id,
            $assignment_mark,
            $assignment_mark, // assignment_percentage (raw score for now)
            $quiz_mark,
            $quiz_mark, // quiz_percentage (raw score for now)
            $test_mark,
            $test_mark, // test_percentage (raw score for now)
            $final_exam_mark,
            $final_exam_mark, // final_exam_percentage (raw score for now)
            $component_total,
            $final_grade,
            $letter_grade,
            $gpa
        ]);

        // Send notifications for mark updates
        $notificationsSent = 0;
        $components = [
            'assignment' => $assignment_mark,
            'quiz' => $quiz_mark,
            'test' => $test_mark,
            'final_exam' => $final_exam_mark
        ];

        // Send notifications for updated components
        foreach ($components as $componentType => $newMark) {
            if ($newMark > 0) { // Only notify if mark is actually set
                $existingMark = 0;
                if ($existing) {
                    $existingMark = $existing[$componentType . '_mark'] ?? 0;
                }
                
                // Send notification if this is a new mark or if the mark changed significantly
                if (!$existing || abs($newMark - $existingMark) > 1) {
                    if (sendMarkUpdateNotification($pdo, $course_id, $student_id, $lecturer_id, $componentType, $newMark)) {
                        $notificationsSent++;
                    }
                }
            }
        }

        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Marks saved successfully',
            'notifications_sent' => $notificationsSent > 0,
            'notification_count' => $notificationsSent
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function calculateFinalMarks()
{
    global $pdo, $request;

    $course_id = $request['course_id'] ?? null;
    $lecturer_id = $request['lecturer_id'] ?? null;

    if (!$course_id || !$lecturer_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Course ID and Lecturer ID required']);
        return;
    }

    try {
        // Get all students in the course
        $stmt = $pdo->prepare("
            SELECT DISTINCT student_id
            FROM enrollments
            WHERE course_id = ?
        ");
        $stmt->execute([$course_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pdo->beginTransaction();

        foreach ($students as $student) {
            calculateFinalMarksForStudent($student['student_id'], $course_id, $lecturer_id);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Final marks calculated for all students']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function calculateFinalMarksForStudent($student_id, $course_id, $lecturer_id)
{
    global $pdo;

    // Get all marks for the student in this course
    $stmt = $pdo->prepare("
        SELECT assessment_type, percentage
        FROM marks
        WHERE student_id = ? AND course_id = ?
    ");
    $stmt->execute([$student_id, $course_id]);
    $marks_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $marks = [
        'assignment' => 0,
        'quiz' => 0,
        'test' => 0,
        'final_exam' => 0
    ];

    foreach ($marks_data as $mark) {
        $marks[$mark['assessment_type']] = (float)$mark['percentage'];
    }

    // Calculate component marks (70%): Assignment (25%) + Quiz (20%) + Test (25%)
    $component_marks = ($marks['assignment'] * 0.25) + ($marks['quiz'] * 0.20) + ($marks['test'] * 0.25);

    // Calculate final marks: Components (70%) + Final Exam (30%)
    $final_marks = ($component_marks * 0.70) + ($marks['final_exam'] * 0.30);

    // Determine grade
    $grade = 'F';
    $gpa_points = 0.00;

    if ($final_marks >= 80) {
        $grade = 'A';
        $gpa_points = 4.00;
    } elseif ($final_marks >= 70) {
        $grade = 'B';
        $gpa_points = 3.00;
    } elseif ($final_marks >= 60) {
        $grade = 'C';
        $gpa_points = 2.00;
    } elseif ($final_marks >= 50) {
        $grade = 'D';
        $gpa_points = 1.00;
    }

    // Grade and GPA are now stored in final_marks_custom table via the main INSERT
}

function handlePut()
{
    global $request;

    $action = $request['action'] ?? '';

    switch ($action) {
        case 'update_marks':
            saveStudentMarks(); // Same as save for updating
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function handleDelete()
{
    global $pdo, $request;

    $action = $request['action'] ?? '';

    switch ($action) {
        case 'delete_marks':
            deleteStudentMarks();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function deleteStudentMarks()
{
    global $pdo, $request;

    $student_id = $request['student_id'] ?? null;
    $course_id = $request['course_id'] ?? null;
    $assessment_type = $request['assessment_type'] ?? null;

    if (!$student_id || !$course_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Student ID and Course ID required']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Delete all marks for student in course
        $stmt = $pdo->prepare("
            DELETE FROM final_marks_custom 
            WHERE student_id = ? AND course_id = ?
        ");
        $stmt->execute([$student_id, $course_id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Marks deleted successfully']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function exportMarksCSV()
{
    global $pdo;

    $course_id = $_GET['course_id'] ?? null;
    $lecturer_id = $_GET['lecturer_id'] ?? null;

    if (!$course_id || !$lecturer_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Course ID and Lecturer ID required']);
        return;
    }

    try {
        // Verify lecturer owns this course
        $stmt = $pdo->prepare("SELECT id, code, name, academic_year, semester FROM courses WHERE id = ? AND lecturer_id = ?");
        $stmt->execute([$course_id, $lecturer_id]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$course) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied or course not found']);
            return;
        }

        // Get students with their marks
        $stmt = $pdo->prepare("
            SELECT 
                u.id, u.name, u.matric_number,
                fm.assignment_mark, fm.quiz_mark, fm.test_mark, fm.component_total,
                fm.final_exam_mark, fm.final_exam_percentage, fm.final_grade, fm.letter_grade, fm.gpa,
                fm.created_at as marks_updated
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            LEFT JOIN final_marks_custom fm ON e.student_id = fm.student_id AND e.course_id = fm.course_id
            WHERE e.course_id = ?
            ORDER BY u.name
        ");
        $stmt->execute([$course_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $course['code'] . '_Marks_' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

        // Create file handle for output
        $output = fopen('php://output', 'w');

        // Add BOM for proper UTF-8 encoding in Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Course information header
        fputcsv($output, ['Course Information']);
        fputcsv($output, ['Course Code', $course['code']]);
        fputcsv($output, ['Course Name', $course['name']]);
        fputcsv($output, ['Academic Year', $course['academic_year']]);
        fputcsv($output, ['Semester', $course['semester']]);
        fputcsv($output, ['Export Date', date('Y-m-d H:i:s')]);
        fputcsv($output, ['Total Students', count($students)]);
        fputcsv($output, []); // Empty row

        // Column headers
        fputcsv($output, [
            'Student Name',
            'Matric Number',
            'Assignment (25%)',
            'Quiz (20%)',
            'Test (25%)',
            'Component Total',
            'Final Exam (30%)',
            'Final Grade',
            'Letter Grade',
            'GPA',
            'Last Updated'
        ]);

        // Student data
        foreach ($students as $student) {
            fputcsv($output, [
                $student['name'],
                $student['matric_number'] ?: 'N/A',
                $student['assignment_mark'] ?: '',
                $student['quiz_mark'] ?: '',
                $student['test_mark'] ?: '',
                $student['component_total'] ? number_format($student['component_total'], 2) : '',
                $student['final_exam_mark'] ?: '',
                $student['final_grade'] ? number_format($student['final_grade'], 2) : '',
                $student['letter_grade'] ?: '',
                $student['gpa'] ? number_format($student['gpa'], 2) : '',
                $student['marks_updated'] ?: 'Never'
            ]);
        }

        // Calculate and add statistics
        $validFinalGrades = array_filter(array_column($students, 'final_grade'), function ($grade) {
            return $grade !== null && $grade !== '';
        });

        if (!empty($validFinalGrades)) {
            $average = array_sum($validFinalGrades) / count($validFinalGrades);
            $highest = max($validFinalGrades);
            $lowest = min($validFinalGrades);

            // Grade distribution
            $gradeDistribution = array_count_values(array_filter(array_column($students, 'letter_grade')));

            fputcsv($output, []); // Empty row
            fputcsv($output, ['STATISTICS']);
            fputcsv($output, ['Students with Marks', count($validFinalGrades)]);
            fputcsv($output, ['Class Average', number_format($average, 2)]);
            fputcsv($output, ['Highest Mark', number_format($highest, 2)]);
            fputcsv($output, ['Lowest Mark', number_format($lowest, 2)]);

            fputcsv($output, []); // Empty row
            fputcsv($output, ['GRADE DISTRIBUTION']);
            foreach ($gradeDistribution as $grade => $count) {
                fputcsv($output, [$grade, $count]);
            }
        }

        fclose($output);
        exit; // Important: exit to prevent any additional output

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getUnreadNotificationCountAPI()
{
    global $pdo;
    
    $user_id = $_GET['user_id'] ?? null;
    
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        return;
    }
    
    $count = getUnreadNotificationCount($pdo, $user_id);
    echo json_encode(['unread_count' => $count]);
}

function getRecentUserNotifications()
{
    global $pdo;
    
    $user_id = $_GET['user_id'] ?? null;
    $limit = intval($_GET['limit'] ?? 10);
    
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        return;
    }
    
    $notifications = getRecentNotifications($pdo, $user_id, $limit);
    echo json_encode(['notifications' => $notifications]);
}

function sendCourseAnnouncement()
{
    global $pdo, $request;
    
    $course_id = $request['course_id'] ?? null;
    $lecturer_id = $request['lecturer_id'] ?? null;
    $title = $request['title'] ?? '';
    $message = $request['message'] ?? '';
    $include_marks = $request['include_marks'] ?? false;
    
    if (!$course_id || !$lecturer_id || !$title || !$message) {
        http_response_code(400);
        echo json_encode(['error' => 'Course ID, Lecturer ID, title, and message required']);
        return;
    }
    
    $notificationsSent = sendCourseAnnouncementNotification($pdo, $course_id, $lecturer_id, $title, $message, $include_marks);
    
    if ($notificationsSent !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'Course announcement sent successfully',
            'notifications_sent' => $notificationsSent
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send course announcement']);
    }
}

function markNotificationAsRead()
{
    global $pdo, $request;
    
    $notification_id = $request['notification_id'] ?? null;
    
    if (!$notification_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Notification ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $success = $stmt->execute([$notification_id]);
        
        if ($success && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Notification not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getStudentDashboardCourses()
{
    global $pdo;
    
    $student_id = $_GET['student_id'] ?? null;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Student ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.id,
                c.code,
                c.name,
                c.semester,
                c.academic_year,
                u.name as lecturer_name,
                fm.final_grade as average,
                fm.letter_grade,
                fm.gpa,
                CASE 
                    WHEN fm.final_grade IS NOT NULL THEN 
                        CASE 
                            WHEN fm.final_grade >= 80 THEN 100
                            WHEN fm.final_grade >= 60 THEN 85
                            WHEN fm.final_grade >= 40 THEN 65
                            ELSE 40
                        END
                    ELSE 0
                END as progress
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            LEFT JOIN users u ON c.lecturer_id = u.id
            LEFT JOIN final_marks_custom fm ON fm.student_id = e.student_id AND fm.course_id = c.id
            WHERE e.student_id = ?
            ORDER BY c.code
        ");
        
        $stmt->execute([$student_id]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data
        foreach ($courses as &$course) {
            $course['progress'] = (int)$course['progress'];
            $course['average'] = $course['average'] ? round($course['average'], 1) : 0;
            
            // Get ranking for each course
            $rankStmt = $pdo->prepare("
                SELECT 
                    COUNT(*) + 1 as rank,
                    total_students.total
                FROM final_marks_custom fm1
                CROSS JOIN (
                    SELECT COUNT(DISTINCT student_id) as total
                    FROM final_marks_custom
                    WHERE course_id = ?
                ) total_students
                WHERE fm1.course_id = ? 
                AND fm1.final_grade > (
                    SELECT COALESCE(final_grade, 0)
                    FROM final_marks_custom
                    WHERE student_id = ? AND course_id = ?
                )
            ");
            $rankStmt->execute([$course['id'], $course['id'], $student_id, $course['id']]);
            $rankData = $rankStmt->fetch();
            
            $course['rank'] = $rankData ? $rankData['rank'] . '/' . $rankData['total'] : 'N/A';
        }
        
        echo json_encode(['courses' => $courses]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getStudentDashboardAssessments()
{
    global $pdo;
    
    $student_id = $_GET['student_id'] ?? null;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Student ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                a.id,
                a.name,
                a.type,
                a.date,
                a.weightage,
                a.course_id,
                c.code as course_code,
                c.name as course_name
            FROM assessments a
            JOIN courses c ON a.course_id = c.id
            JOIN enrollments e ON e.course_id = c.id
            WHERE e.student_id = ? 
            AND a.date >= CURDATE()
            ORDER BY a.date ASC
            LIMIT 10
        ");
        
        $stmt->execute([$student_id]);
        $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['assessments' => $assessments]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getStudentDashboardPerformance()
{
    global $pdo;
    
    $student_id = $_GET['student_id'] ?? null;
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Student ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.code,
                c.name as course_name,
                fm.assignment_mark,
                fm.quiz_mark,
                fm.test_mark,
                fm.final_exam_mark,
                fm.final_grade,
                fm.updated_at
            FROM final_marks_custom fm
            JOIN courses c ON fm.course_id = c.id
            WHERE fm.student_id = ?
            ORDER BY c.code
        ");
        
        $stmt->execute([$student_id]);
        $performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data for chart
        $chartData = [
            'labels' => [],
            'datasets' => []
        ];
        
        foreach ($performance as $course) {
            $courseCode = $course['code'];
            $chartData['labels'] = ['Assignment', 'Quiz', 'Test', 'Final Exam'];
            
            $chartData['datasets'][] = [
                'label' => $courseCode,
                'data' => [
                    $course['assignment_mark'] ?: 0,
                    $course['quiz_mark'] ?: 0,
                    $course['test_mark'] ?: 0,
                    $course['final_exam_mark'] ?: 0
                ],
                'borderColor' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
                'backgroundColor' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)) . '20',
                'tension' => 0.4
            ];
        }
        
        echo json_encode(['performance' => $chartData]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getStudentCourseDetail()
{
    global $pdo;
    
    $student_id = $_GET['student_id'] ?? null;
    $course_id = $_GET['course_id'] ?? null;
    
    if (!$student_id || !$course_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Student ID and Course ID required']);
        return;
    }
    
    try {
        // Get course details and enrollment verification
        $courseStmt = $pdo->prepare("
            SELECT 
                c.id,
                c.code,
                c.name,
                c.semester,
                c.academic_year,
                u.name as lecturer_name,
                u.email as lecturer_email,
                e.id as enrollment_id
            FROM courses c
            LEFT JOIN users u ON c.lecturer_id = u.id
            LEFT JOIN enrollments e ON e.course_id = c.id AND e.student_id = ?
            WHERE c.id = ?
        ");
        $courseStmt->execute([$student_id, $course_id]);
        $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$course || !$course['enrollment_id']) {
            http_response_code(404);
            echo json_encode(['error' => 'Course not found or student not enrolled']);
            return;
        }
        
        // Get student's marks for this course
        $marksStmt = $pdo->prepare("
            SELECT 
                assignment_mark,
                quiz_mark,
                test_mark,
                final_exam_mark,
                component_total,
                final_grade,
                letter_grade,
                gpa,
                updated_at
            FROM final_marks_custom
            WHERE student_id = ? AND course_id = ?
        ");
        $marksStmt->execute([$student_id, $course_id]);
        $marks = $marksStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get assessments for this course
        $assessmentsStmt = $pdo->prepare("
            SELECT 
                id,
                name,
                type,
                weightage,
                date,
                description
            FROM assessments
            WHERE course_id = ?
            ORDER BY date ASC
        ");
        $assessmentsStmt->execute([$course_id]);
        $assessments = $assessmentsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get student's ranking in this course
        $rankStmt = $pdo->prepare("
            SELECT 
                COUNT(*) + 1 as rank,
                total_students.total
            FROM final_marks_custom fm1
            CROSS JOIN (
                SELECT COUNT(DISTINCT student_id) as total
                FROM final_marks_custom
                WHERE course_id = ?
            ) total_students
            WHERE fm1.course_id = ? 
            AND fm1.final_grade > COALESCE((
                SELECT final_grade
                FROM final_marks_custom
                WHERE student_id = ? AND course_id = ?
            ), 0)
        ");
        $rankStmt->execute([$course_id, $course_id, $student_id, $course_id]);
        $rankData = $rankStmt->fetch();
        
        // Format the response
        $response = [
            'course' => $course,
            'marks' => $marks ?: [
                'assignment_mark' => null,
                'quiz_mark' => null,
                'test_mark' => null,
                'final_exam_mark' => null,
                'component_total' => null,
                'final_grade' => null,
                'letter_grade' => null,
                'gpa' => null,
                'updated_at' => null
            ],
            'assessments' => $assessments,
            'ranking' => $rankData ? [
                'position' => $rankData['rank'],
                'total_students' => $rankData['total'],
                'rank_text' => $rankData['rank'] . '/' . $rankData['total']
            ] : null
        ];
        
        echo json_encode($response);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

?>
