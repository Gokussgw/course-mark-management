<?php
// Mark Breakdown API - breakdown-api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8085');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database configuration (same as marks-api.php)
$host = 'localhost';
$dbname = 'course_mark_management';
$username = 'root';
$password = '';

try {
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
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
}

function handleGetRequest($pdo, $action)
{
    switch ($action) {
        case 'course_breakdown':
            getCourseBreakdown($pdo);
            break;
        case 'student_breakdown':
            getStudentBreakdown($pdo);
            break;
        case 'advisor_courses':
            getAdvisorCourses($pdo);
            break;
        case 'student_courses':
            getStudentCourses($pdo);
            break;
        case 'class_performance':
            getClassPerformance($pdo);
            break;
        case 'assessment_analytics':
            getAssessmentAnalytics($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

// Get detailed course breakdown with statistics
function getCourseBreakdown($pdo)
{
    $courseId = $_GET['course_id'] ?? null;

    if (!$courseId) {
        http_response_code(400);
        echo json_encode(['error' => 'Course ID is required']);
        return;
    }

    try {
        // Get course info
        $courseStmt = $pdo->prepare("
            SELECT c.*, u.name as lecturer_name 
            FROM courses c 
            LEFT JOIN users u ON c.lecturer_id = u.id 
            WHERE c.id = ?
        ");
        $courseStmt->execute([$courseId]);
        $course = $courseStmt->fetch();

        if (!$course) {
            http_response_code(404);
            echo json_encode(['error' => 'Course not found']);
            return;
        }

        // Get all assessments for the course
        $assessmentsStmt = $pdo->prepare("
            SELECT id, name, type, max_mark, weightage, date 
            FROM assessments 
            WHERE course_id = ? 
            ORDER BY date ASC
        ");
        $assessmentsStmt->execute([$courseId]);
        $assessments = $assessmentsStmt->fetchAll();

        // Get all students enrolled in the course with their marks
        $studentsStmt = $pdo->prepare("
            SELECT DISTINCT
                u.id,
                u.name,
                u.email,
                u.matric_number
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            WHERE e.course_id = ?
            ORDER BY u.name
        ");
        $studentsStmt->execute([$courseId]);
        $students = $studentsStmt->fetchAll();

        // Get marks for all students and assessments
        foreach ($students as &$student) {
            $student['marks'] = [];
            $student['finalMark'] = 0;

            $marksStmt = $pdo->prepare("
                SELECT 
                    a.id as assessment_id,
                    a.type,
                    a.name as assessment_name,
                    a.max_mark,
                    a.weightage,
                    m.mark,
                    ROUND((m.mark / a.max_mark) * 100, 2) as percentage,
                    ROUND((m.mark / a.max_mark) * a.weightage, 2) as weighted_score
                FROM marks m
                JOIN assessments a ON m.assessment_id = a.id
                WHERE m.student_id = ? AND a.course_id = ?
            ");
            $marksStmt->execute([$student['id'], $courseId]);
            $marks = $marksStmt->fetchAll();

            $totalWeighted = 0;
            foreach ($marks as $mark) {
                $student['marks'][$mark['type']] = [
                    'assessment_id' => $mark['assessment_id'],
                    'assessment_name' => $mark['assessment_name'],
                    'obtained' => $mark['mark'],
                    'max_mark' => $mark['max_mark'],
                    'percentage' => $mark['percentage'],
                    'weighted' => $mark['weighted_score']
                ];
                $totalWeighted += $mark['weighted_score'];
            }

            $student['finalMark'] = round($totalWeighted, 2);
            $student['grade'] = calculateGrade($student['finalMark']);
        }

        // Calculate course statistics
        $totalStudents = count($students);
        $totalAssessments = count($assessments);

        $classTotal = array_sum(array_column($students, 'finalMark'));
        $classAverage = $totalStudents > 0 ? round($classTotal / $totalStudents, 2) : 0;

        $atRiskStudents = count(array_filter($students, function ($s) {
            return $s['finalMark'] < 50;
        }));

        // Assessment breakdown statistics
        $assessmentBreakdown = [];
        foreach ($assessments as $assessment) {
            $submissionCount = 0;
            $totalObtained = 0;
            $totalPossible = 0;

            foreach ($students as $student) {
                if (isset($student['marks'][$assessment['type']])) {
                    $submissionCount++;
                    $totalObtained += $student['marks'][$assessment['type']]['obtained'];
                    $totalPossible += $student['marks'][$assessment['type']]['max_mark'];
                }
            }

            $assessmentBreakdown[] = [
                'type' => $assessment['type'],
                'name' => $assessment['name'],
                'weightage' => $assessment['weightage'],
                'submissions' => $submissionCount,
                'total_students' => $totalStudents,
                'average_percentage' => $totalPossible > 0 ? round(($totalObtained / $totalPossible) * 100, 2) : 0
            ];
        }

        echo json_encode([
            'course' => $course,
            'students' => $students,
            'assessments' => $assessments,
            'statistics' => [
                'total_students' => $totalStudents,
                'total_assessments' => $totalAssessments,
                'class_average' => $classAverage,
                'at_risk_students' => $atRiskStudents
            ],
            'assessment_breakdown' => $assessmentBreakdown
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get course breakdown: ' . $e->getMessage()]);
    }
}

// Get detailed breakdown for a specific student
function getStudentBreakdown($pdo)
{
    $studentId = $_GET['student_id'] ?? null;
    $courseId = $_GET['course_id'] ?? null;

    if (!$studentId || !$courseId) {
        http_response_code(400);
        echo json_encode(['error' => 'Student ID and Course ID are required']);
        return;
    }

    try {
        // Get student info
        $studentStmt = $pdo->prepare("
            SELECT u.*, e.enrollment_date 
            FROM users u 
            JOIN enrollments e ON u.id = e.student_id 
            WHERE u.id = ? AND e.course_id = ?
        ");
        $studentStmt->execute([$studentId, $courseId]);
        $student = $studentStmt->fetch();

        if (!$student) {
            http_response_code(404);
            echo json_encode(['error' => 'Student not found or not enrolled in course']);
            return;
        }

        // Get detailed assessment performance
        $performanceStmt = $pdo->prepare("
            SELECT 
                a.id,
                a.name,
                a.type,
                a.max_mark,
                a.weightage,
                a.date,
                COALESCE(m.mark, 0) as mark,
                CASE 
                    WHEN m.mark IS NOT NULL 
                    THEN ROUND((m.mark / a.max_mark) * 100, 2)
                    ELSE 0 
                END as percentage,
                CASE 
                    WHEN m.mark IS NOT NULL 
                    THEN ROUND((m.mark / a.max_mark) * a.weightage, 2)
                    ELSE 0 
                END as weighted_score
            FROM assessments a
            LEFT JOIN marks m ON a.id = m.assessment_id AND m.student_id = ?
            WHERE a.course_id = ?
            ORDER BY a.date ASC
        ");
        $performanceStmt->execute([$studentId, $courseId]);
        $assessmentPerformance = $performanceStmt->fetchAll();

        // Calculate class averages for each assessment
        foreach ($assessmentPerformance as &$assessment) {
            $classAvgStmt = $pdo->prepare("
                SELECT 
                    AVG(COALESCE((m.mark / a.max_mark) * 100, 0)) as class_average
                FROM assessments a
                LEFT JOIN marks m ON a.id = m.assessment_id
                WHERE a.id = ?
            ");
            $classAvgStmt->execute([$assessment['id']]);
            $classAvgResult = $classAvgStmt->fetch();
            $assessment['class_average'] = round($classAvgResult['class_average'] ?? 0, 2);
        }

        // Calculate overall statistics
        $totalWeighted = array_sum(array_column($assessmentPerformance, 'weighted_score'));
        $finalMark = round($totalWeighted, 2);
        $grade = calculateGrade($finalMark);

        // Calculate class rank
        $rankStmt = $pdo->prepare("
            SELECT COUNT(*) + 1 as rank
            FROM (
                SELECT 
                    e.student_id,
                    SUM(COALESCE((m.mark / a.max_mark) * a.weightage, 0)) as total_weighted
                FROM enrollments e
                LEFT JOIN marks m ON e.student_id = m.student_id
                LEFT JOIN assessments a ON m.assessment_id = a.id AND a.course_id = e.course_id
                WHERE e.course_id = ?
                GROUP BY e.student_id
                HAVING total_weighted > ?
            ) higher_scores
        ");
        $rankStmt->execute([$courseId, $totalWeighted]);
        $rankResult = $rankStmt->fetch();
        $rank = $rankResult['rank'] ?? 1;

        // Analyze performance trends
        $trends = analyzePerformanceTrends($assessmentPerformance);

        echo json_encode([
            'student' => $student,
            'assessment_performance' => $assessmentPerformance,
            'final_mark' => $finalMark,
            'grade' => $grade,
            'rank' => $rank,
            'trends' => $trends
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get student breakdown: ' . $e->getMessage()]);
    }
}

// Get courses for advisor
function getAdvisorCourses($pdo)
{
    $advisorId = $_GET['advisor_id'] ?? null;

    if (!$advisorId) {
        http_response_code(400);
        echo json_encode(['error' => 'Advisor ID is required']);
        return;
    }

    try {
        // Get all courses where advisor's advisees are enrolled
        $coursesStmt = $pdo->prepare("
            SELECT DISTINCT
                c.id,
                c.code,
                c.name,
                c.semester,
                c.academic_year,
                u.name as lecturer_name,
                COUNT(DISTINCT e.student_id) as enrolled_advisees
            FROM courses c
            JOIN enrollments e ON c.id = e.course_id
            JOIN users advisees ON e.student_id = advisees.id AND advisees.advisor_id = ?
            LEFT JOIN users u ON c.lecturer_id = u.id
            GROUP BY c.id, c.code, c.name, c.semester, c.academic_year, u.name
            ORDER BY c.code
        ");
        $coursesStmt->execute([$advisorId]);
        $courses = $coursesStmt->fetchAll();

        echo json_encode(['courses' => $courses]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get advisor courses: ' . $e->getMessage()]);
    }
}

// Get courses for a student
function getStudentCourses($pdo)
{
    $studentId = $_GET['student_id'] ?? null;

    if (!$studentId) {
        http_response_code(400);
        echo json_encode(['error' => 'Student ID is required']);
        return;
    }

    try {
        // Get courses where student is enrolled
        $coursesStmt = $pdo->prepare("
            SELECT DISTINCT 
                c.id,
                c.code,
                c.name,
                c.semester,
                c.academic_year,
                u.name as lecturer_name
            FROM courses c
            JOIN enrollments e ON c.id = e.course_id
            LEFT JOIN users u ON c.lecturer_id = u.id
            WHERE e.student_id = ?
            ORDER BY c.code
        ");
        $coursesStmt->execute([$studentId]);
        $courses = $coursesStmt->fetchAll();

        echo json_encode(['courses' => $courses]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get student courses: ' . $e->getMessage()]);
    }
}

// Get class performance analytics
function getClassPerformance($pdo)
{
    $courseId = $_GET['course_id'] ?? null;

    if (!$courseId) {
        http_response_code(400);
        echo json_encode(['error' => 'Course ID is required']);
        return;
    }

    try {
        // Get grade distribution
        $gradeDistStmt = $pdo->prepare("
            SELECT 
                CASE 
                    WHEN total_weighted >= 80 THEN 'A'
                    WHEN total_weighted >= 70 THEN 'B'
                    WHEN total_weighted >= 60 THEN 'C'
                    WHEN total_weighted >= 50 THEN 'D'
                    ELSE 'F'
                END as grade,
                COUNT(*) as count
            FROM (
                SELECT 
                    e.student_id,
                    SUM(COALESCE((m.mark / a.max_mark) * a.weightage, 0)) as total_weighted
                FROM enrollments e
                LEFT JOIN marks m ON e.student_id = m.student_id
                LEFT JOIN assessments a ON m.assessment_id = a.id AND a.course_id = e.course_id
                WHERE e.course_id = ?
                GROUP BY e.student_id
            ) student_totals
            GROUP BY grade
            ORDER BY grade
        ");
        $gradeDistStmt->execute([$courseId]);
        $gradeDistribution = $gradeDistStmt->fetchAll();

        // Get performance by assessment type
        $typePerformanceStmt = $pdo->prepare("
            SELECT 
                a.type,
                AVG((m.mark / a.max_mark) * 100) as average_percentage,
                COUNT(m.id) as submissions,
                COUNT(DISTINCT e.student_id) as total_students
            FROM assessments a
            LEFT JOIN marks m ON a.id = m.assessment_id
            LEFT JOIN enrollments e ON a.course_id = e.course_id
            WHERE a.course_id = ?
            GROUP BY a.type
        ");
        $typePerformanceStmt->execute([$courseId]);
        $typePerformance = $typePerformanceStmt->fetchAll();

        echo json_encode([
            'grade_distribution' => $gradeDistribution,
            'type_performance' => $typePerformance
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get class performance: ' . $e->getMessage()]);
    }
}

// Get assessment analytics
function getAssessmentAnalytics($pdo)
{
    $courseId = $_GET['course_id'] ?? null;
    $assessmentId = $_GET['assessment_id'] ?? null;

    if (!$courseId) {
        http_response_code(400);
        echo json_encode(['error' => 'Course ID is required']);
        return;
    }

    try {
        $whereClause = "WHERE a.course_id = ?";
        $params = [$courseId];

        if ($assessmentId) {
            $whereClause .= " AND a.id = ?";
            $params[] = $assessmentId;
        }

        $analyticsStmt = $pdo->prepare("
            SELECT 
                a.id,
                a.name,
                a.type,
                a.max_mark,
                a.weightage,
                COUNT(m.id) as submissions,
                COUNT(DISTINCT e.student_id) as total_enrolled,
                AVG(m.mark) as avg_raw_score,
                AVG((m.mark / a.max_mark) * 100) as avg_percentage,
                MIN(m.mark) as min_score,
                MAX(m.mark) as max_score,
                STDDEV((m.mark / a.max_mark) * 100) as std_deviation
            FROM assessments a
            LEFT JOIN marks m ON a.id = m.assessment_id
            LEFT JOIN enrollments e ON a.course_id = e.course_id
            $whereClause
            GROUP BY a.id, a.name, a.type, a.max_mark, a.weightage
            ORDER BY a.date
        ");
        $analyticsStmt->execute($params);
        $analytics = $analyticsStmt->fetchAll();

        echo json_encode(['analytics' => $analytics]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get assessment analytics: ' . $e->getMessage()]);
    }
}

// Helper functions
function calculateGrade($mark)
{
    if ($mark >= 80) return 'A';
    if ($mark >= 70) return 'B';
    if ($mark >= 60) return 'C';
    if ($mark >= 50) return 'D';
    return 'F';
}

function analyzePerformanceTrends($assessments)
{
    $trends = [
        'improving' => false,
        'declining' => false,
        'consistent' => false,
        'strongest_area' => '',
        'weakest_area' => ''
    ];

    if (count($assessments) < 2) {
        $trends['consistent'] = true;
        return $trends;
    }

    // Check for improvement/decline trend
    $scores = array_column($assessments, 'percentage');
    $firstHalf = array_slice($scores, 0, ceil(count($scores) / 2));
    $secondHalf = array_slice($scores, floor(count($scores) / 2));

    $firstAvg = array_sum($firstHalf) / count($firstHalf);
    $secondAvg = array_sum($secondHalf) / count($secondHalf);

    if ($secondAvg > $firstAvg + 5) {
        $trends['improving'] = true;
    } elseif ($secondAvg < $firstAvg - 5) {
        $trends['declining'] = true;
    } else {
        $trends['consistent'] = true;
    }

    // Find strongest and weakest areas
    $typeScores = [];
    foreach ($assessments as $assessment) {
        if (!isset($typeScores[$assessment['type']])) {
            $typeScores[$assessment['type']] = [];
        }
        $typeScores[$assessment['type']][] = $assessment['percentage'];
    }

    $typeAverages = [];
    foreach ($typeScores as $type => $scores) {
        $typeAverages[$type] = array_sum($scores) / count($scores);
    }

    if (!empty($typeAverages)) {
        $trends['strongest_area'] = array_search(max($typeAverages), $typeAverages);
        $trends['weakest_area'] = array_search(min($typeAverages), $typeAverages);
    }

    return $trends;
}
