<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8085');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
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
        case 'student_marks':
            getStudentMarks();
            break;
        case 'export_marks_csv':
            exportMarksCSV();
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
                e.created_at as enrolled_at,
                cm.assignment_mark,
                cm.quiz_mark,
                cm.test_mark,
                cm.assignment_percentage,
                cm.quiz_percentage,
                cm.test_percentage,
                cm.component_total,
                fm.final_exam_mark,
                fm.final_exam_percentage,
                fm.final_grade,
                fm.letter_grade,
                fm.gpa
            FROM enrollments e
            INNER JOIN users u ON e.student_id = u.id
            LEFT JOIN component_marks cm ON e.student_id = cm.student_id AND e.course_id = cm.course_id
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

        echo json_encode(['students' => $students]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
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

        // Save component marks
        $stmt = $pdo->prepare("
            INSERT INTO component_marks (student_id, course_id, assignment_mark, quiz_mark, test_mark)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                assignment_mark = VALUES(assignment_mark),
                quiz_mark = VALUES(quiz_mark),
                test_mark = VALUES(test_mark),
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$student_id, $course_id, $assignment_mark, $quiz_mark, $test_mark]);

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

        // Save final marks
        $stmt = $pdo->prepare("
            INSERT INTO final_marks_custom (student_id, course_id, final_exam_mark, final_exam_percentage, component_total, final_grade, letter_grade, gpa)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
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
            $final_exam_mark,
            $final_exam_mark * 0.30,
            $component_total,
            $final_grade,
            $letter_grade,
            $gpa
        ]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Marks saved successfully']);
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

    // Save final marks
    $stmt = $pdo->prepare("
        INSERT INTO final_marks (student_id, course_id, component_marks, final_exam_marks, final_marks, grade, gpa_points, calculated_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            component_marks = VALUES(component_marks),
            final_exam_marks = VALUES(final_exam_marks),
            final_marks = VALUES(final_marks),
            grade = VALUES(grade),
            gpa_points = VALUES(gpa_points),
            calculated_by = VALUES(calculated_by),
            updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([
        $student_id,
        $course_id,
        round($component_marks, 2),
        $marks['final_exam'],
        round($final_marks, 2),
        $grade,
        $gpa_points,
        $lecturer_id
    ]);
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
            DELETE FROM component_marks 
            WHERE student_id = ? AND course_id = ?
        ");
        $stmt->execute([$student_id, $course_id]);

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
                cm.assignment_mark, cm.quiz_mark, cm.test_mark, cm.component_total,
                fm.final_exam_mark, fm.final_exam_percentage, fm.final_grade, fm.letter_grade, fm.gpa,
                fm.created_at as marks_updated
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            LEFT JOIN component_marks cm ON e.student_id = cm.student_id AND e.course_id = cm.course_id
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
