<?php
// Mark Breakdown API - breakdown-api.php
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

        // Get all assessments for the course - now using component types
        $components = [
            ['type' => 'assignment', 'name' => 'Assignment', 'weightage' => 25, 'max_mark' => 100],
            ['type' => 'quiz', 'name' => 'Quiz', 'weightage' => 20, 'max_mark' => 100],
            ['type' => 'test', 'name' => 'Test', 'weightage' => 25, 'max_mark' => 100],
            ['type' => 'final_exam', 'name' => 'Final Exam', 'weightage' => 30, 'max_mark' => 100]
        ];

        // Get all students enrolled in the course with their marks from final_marks_custom
        $studentsStmt = $pdo->prepare("
            SELECT DISTINCT
                u.id,
                u.name,
                u.email,
                u.matric_number,
                fm.assignment_mark,
                fm.assignment_percentage,
                fm.quiz_mark,
                fm.quiz_percentage,
                fm.test_mark,
                fm.test_percentage,
                fm.final_exam_mark,
                fm.final_exam_percentage,
                fm.component_total,
                fm.final_grade,
                fm.letter_grade,
                fm.gpa
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            LEFT JOIN final_marks_custom fm ON e.student_id = fm.student_id AND e.course_id = fm.course_id
            WHERE e.course_id = ?
            ORDER BY u.name
        ");
        $studentsStmt->execute([$courseId]);
        $students = $studentsStmt->fetchAll();

        // Structure marks data for each student
        foreach ($students as &$student) {
            $student['marks'] = [
                'assignment' => [
                    'obtained' => $student['assignment_mark'] ?? 0,
                    'max_mark' => 100,
                    'percentage' => $student['assignment_percentage'] ?? 0,
                    'weighted' => ($student['assignment_percentage'] ?? 0) * 0.25
                ],
                'quiz' => [
                    'obtained' => $student['quiz_mark'] ?? 0,
                    'max_mark' => 100,
                    'percentage' => $student['quiz_percentage'] ?? 0,
                    'weighted' => ($student['quiz_percentage'] ?? 0) * 0.20
                ],
                'test' => [
                    'obtained' => $student['test_mark'] ?? 0,
                    'max_mark' => 100,
                    'percentage' => $student['test_percentage'] ?? 0,
                    'weighted' => ($student['test_percentage'] ?? 0) * 0.25
                ],
                'final_exam' => [
                    'obtained' => $student['final_exam_mark'] ?? 0,
                    'max_mark' => 100,
                    'percentage' => $student['final_exam_percentage'] ?? 0,
                    'weighted' => ($student['final_exam_percentage'] ?? 0) * 0.30
                ]
            ];

            $student['finalMark'] = $student['final_grade'] ?? 0;
            $student['grade'] = $student['letter_grade'] ?? 'F';
        }

        // Calculate course statistics
        $totalStudents = count($students);
        $totalComponents = count($components);

        $classTotal = array_sum(array_column($students, 'finalMark'));
        $classAverage = $totalStudents > 0 ? round($classTotal / $totalStudents, 2) : 0;

        $atRiskStudents = count(array_filter($students, function ($s) {
            return $s['finalMark'] < 50;
        }));

        // Component breakdown statistics
        $componentBreakdown = [];
        foreach ($components as $component) {
            $submissionCount = 0;
            $totalObtained = 0;
            $totalPossible = 0;

            foreach ($students as $student) {
                if (isset($student['marks'][$component['type']])) {
                    $submissionCount++;
                    $totalObtained += $student['marks'][$component['type']]['obtained'];
                    $totalPossible += $student['marks'][$component['type']]['max_mark'];
                }
            }

            $componentBreakdown[] = [
                'type' => $component['type'],
                'name' => $component['name'],
                'weightage' => $component['weightage'],
                'submissions' => $submissionCount,
                'total_students' => $totalStudents,
                'average_percentage' => $totalPossible > 0 ? round(($totalObtained / $totalPossible) * 100, 2) : 0
            ];
        }

        echo json_encode([
            'course' => $course,
            'students' => $students,
            'components' => $components,
            'statistics' => [
                'total_students' => $totalStudents,
                'total_components' => $totalComponents,
                'class_average' => $classAverage,
                'at_risk_students' => $atRiskStudents
            ],
            'component_breakdown' => $componentBreakdown
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

        // Get detailed component performance from final_marks_custom
        $performanceStmt = $pdo->prepare("
            SELECT 
                fm.assignment_mark,
                fm.assignment_percentage,
                fm.quiz_mark,
                fm.quiz_percentage,
                fm.test_mark,
                fm.test_percentage,
                fm.final_exam_mark,
                fm.final_exam_percentage,
                fm.component_total,
                fm.final_grade,
                fm.letter_grade,
                fm.gpa,
                fm.created_at,
                fm.updated_at
            FROM final_marks_custom fm
            WHERE fm.student_id = ? AND fm.course_id = ?
        ");
        $performanceStmt->execute([$studentId, $courseId]);
        $studentMarks = $performanceStmt->fetch();

        if (!$studentMarks) {
            // Return default structure if no marks found
            $studentMarks = [
                'assignment_mark' => 0,
                'assignment_percentage' => 0,
                'quiz_mark' => 0,
                'quiz_percentage' => 0,
                'test_mark' => 0,
                'test_percentage' => 0,
                'final_exam_mark' => 0,
                'final_exam_percentage' => 0,
                'component_total' => 0,
                'final_grade' => 0,
                'letter_grade' => 'F',
                'gpa' => 0.00
            ];
        }

        // Structure component performance
        $componentPerformance = [
            [
                'type' => 'assignment',
                'name' => 'Assignment',
                'max_mark' => 100,
                'weightage' => 25,
                'mark' => $studentMarks['assignment_mark'],
                'percentage' => $studentMarks['assignment_percentage'],
                'weighted_score' => ($studentMarks['assignment_percentage'] * 25) / 100
            ],
            [
                'type' => 'quiz',
                'name' => 'Quiz',
                'max_mark' => 100,
                'weightage' => 20,
                'mark' => $studentMarks['quiz_mark'],
                'percentage' => $studentMarks['quiz_percentage'],
                'weighted_score' => ($studentMarks['quiz_percentage'] * 20) / 100
            ],
            [
                'type' => 'test',
                'name' => 'Test',
                'max_mark' => 100,
                'weightage' => 25,
                'mark' => $studentMarks['test_mark'],
                'percentage' => $studentMarks['test_percentage'],
                'weighted_score' => ($studentMarks['test_percentage'] * 25) / 100
            ],
            [
                'type' => 'final_exam',
                'name' => 'Final Exam',
                'max_mark' => 100,
                'weightage' => 30,
                'mark' => $studentMarks['final_exam_mark'],
                'percentage' => $studentMarks['final_exam_percentage'],
                'weighted_score' => ($studentMarks['final_exam_percentage'] * 30) / 100
            ]
        ];

        // Calculate class averages for each component
        foreach ($componentPerformance as &$component) {
            $columnName = $component['type'] . '_mark';
            $classAvgStmt = $pdo->prepare("
                SELECT 
                    AVG(COALESCE(fm.$columnName, 0)) as class_average
                FROM final_marks_custom fm
                INNER JOIN enrollments e ON fm.student_id = e.student_id
                WHERE fm.course_id = ? AND e.course_id = ?
            ");
            $classAvgStmt->execute([$courseId, $courseId]);
            $classAvgResult = $classAvgStmt->fetch();
            $component['class_average'] = round($classAvgResult['class_average'] ?? 0, 2);
        }

        // Calculate overall statistics
        $finalMark = $studentMarks['final_grade'];
        $grade = $studentMarks['letter_grade'];

        // Calculate class rank using final_grade
        $rankStmt = $pdo->prepare("
            SELECT COUNT(*) + 1 as rank
            FROM final_marks_custom fm
            INNER JOIN enrollments e ON fm.student_id = e.student_id
            WHERE fm.course_id = ? AND e.course_id = ? AND fm.final_grade > ?
        ");
        $rankStmt->execute([$courseId, $courseId, $finalMark]);
        $rankResult = $rankStmt->fetch();
        $rank = $rankResult['rank'] ?? 1;

        // Get total students in course
        $totalStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT student_id) as total
            FROM enrollments
            WHERE course_id = ?
        ");
        $totalStmt->execute([$courseId]);
        $totalResult = $totalStmt->fetch();
        $totalStudents = $totalResult['total'] ?? 1;

        // Analyze performance trends (simplified for components)
        $trends = [
            'strongest_component' => '',
            'weakest_component' => '',
            'improvement_needed' => []
        ];

        $componentScores = [];
        foreach ($componentPerformance as $component) {
            $componentScores[$component['type']] = $component['percentage'];
        }

        if (!empty($componentScores)) {
            $maxScore = max($componentScores);
            $minScore = min($componentScores);
            $trends['strongest_component'] = array_search($maxScore, $componentScores);
            $trends['weakest_component'] = array_search($minScore, $componentScores);

            foreach ($componentScores as $type => $score) {
                if ($score < 60) {
                    $trends['improvement_needed'][] = $type;
                }
            }
        }

        echo json_encode([
            'student' => $student,
            'component_performance' => $componentPerformance,
            'overall_statistics' => [
                'final_mark' => $finalMark,
                'grade' => $grade,
                'gpa' => $studentMarks['gpa'],
                'rank' => $rank,
                'total_students' => $totalStudents
            ],
            'performance_trends' => $trends
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
        // Get grade distribution using final_marks_custom
        $gradeDistStmt = $pdo->prepare("
            SELECT 
                fm.letter_grade as grade,
                COUNT(*) as count
            FROM enrollments e
            LEFT JOIN final_marks_custom fm ON e.student_id = fm.student_id AND e.course_id = fm.course_id
            WHERE e.course_id = ?
            GROUP BY fm.letter_grade
            ORDER BY fm.letter_grade
        ");
        $gradeDistStmt->execute([$courseId]);
        $gradeDistribution = $gradeDistStmt->fetchAll();

        // Get performance by component type
        $componentPerformanceStmt = $pdo->prepare("
            SELECT 
                'assignment' as component_type,
                'Assignment' as component_name,
                AVG(fm.assignment_percentage) as average_percentage,
                COUNT(fm.assignment_mark) as submissions,
                25 as weightage
            FROM enrollments e
            LEFT JOIN final_marks_custom fm ON e.student_id = fm.student_id AND e.course_id = fm.course_id
            WHERE e.course_id = ?
            
            UNION ALL
            
            SELECT 
                'quiz' as component_type,
                'Quiz' as component_name,
                AVG(fm.quiz_percentage) as average_percentage,
                COUNT(fm.quiz_mark) as submissions,
                20 as weightage
            FROM enrollments e
            LEFT JOIN final_marks_custom fm ON e.student_id = fm.student_id AND e.course_id = fm.course_id
            WHERE e.course_id = ?
            
            UNION ALL
            
            SELECT 
                'test' as component_type,
                'Test' as component_name,
                AVG(fm.test_percentage) as average_percentage,
                COUNT(fm.test_mark) as submissions,
                25 as weightage
            FROM enrollments e
            LEFT JOIN final_marks_custom fm ON e.student_id = fm.student_id AND e.course_id = fm.course_id
            WHERE e.course_id = ?
            
            UNION ALL
            
            SELECT 
                'final_exam' as component_type,
                'Final Exam' as component_name,
                AVG(fm.final_exam_percentage) as average_percentage,
                COUNT(fm.final_exam_mark) as submissions,
                30 as weightage
            FROM enrollments e
            LEFT JOIN final_marks_custom fm ON e.student_id = fm.student_id AND e.course_id = fm.course_id
            WHERE e.course_id = ?
        ");
        $componentPerformanceStmt->execute([$courseId, $courseId, $courseId, $courseId]);
        $componentPerformance = $componentPerformanceStmt->fetchAll();

        // Get overall class statistics
        $overallStatsStmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT e.student_id) as total_students,
                AVG(fm.final_grade) as class_average,
                MAX(fm.final_grade) as highest_grade,
                MIN(fm.final_grade) as lowest_grade,
                STDDEV(fm.final_grade) as grade_deviation
            FROM enrollments e
            LEFT JOIN final_marks_custom fm ON e.student_id = fm.student_id AND e.course_id = fm.course_id
            WHERE e.course_id = ?
        ");
        $overallStatsStmt->execute([$courseId]);
        $overallStats = $overallStatsStmt->fetch();

        echo json_encode([
            'grade_distribution' => $gradeDistribution,
            'component_performance' => $componentPerformance,
            'overall_statistics' => $overallStats
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get class performance: ' . $e->getMessage()]);
    }
}

// Get component analytics (updated from assessment analytics)
function getAssessmentAnalytics($pdo)
{
    $courseId = $_GET['course_id'] ?? null;
    $componentType = $_GET['component_type'] ?? null; // assignment, quiz, test, final_exam

    if (!$courseId) {
        http_response_code(400);
        echo json_encode(['error' => 'Course ID is required']);
        return;
    }

    try {
        $components = ['assignment', 'quiz', 'test', 'final_exam'];
        $componentData = [];

        foreach ($components as $component) {
            if ($componentType && $componentType !== $component) {
                continue; // Skip if specific component requested and this isn't it
            }

            $markColumn = $component . '_mark';
            $percentageColumn = $component . '_percentage';

            $analyticsStmt = $pdo->prepare("
                SELECT 
                    '$component' as component_type,
                    CASE 
                        WHEN '$component' = 'assignment' THEN 'Assignment'
                        WHEN '$component' = 'quiz' THEN 'Quiz'
                        WHEN '$component' = 'test' THEN 'Test'
                        WHEN '$component' = 'final_exam' THEN 'Final Exam'
                    END as component_name,
                    CASE 
                        WHEN '$component' = 'assignment' THEN 25
                        WHEN '$component' = 'quiz' THEN 20
                        WHEN '$component' = 'test' THEN 25
                        WHEN '$component' = 'final_exam' THEN 30
                    END as weightage,
                    COUNT(fm.$markColumn) as submissions,
                    COUNT(DISTINCT e.student_id) as total_enrolled,
                    ROUND(AVG(fm.$markColumn), 2) as average_mark,
                    ROUND(AVG(fm.$percentageColumn), 2) as average_percentage,
                    MAX(fm.$markColumn) as highest_mark,
                    MIN(fm.$markColumn) as lowest_mark,
                    ROUND(STDDEV(fm.$markColumn), 2) as standard_deviation,
                    SUM(CASE WHEN fm.$percentageColumn >= 80 THEN 1 ELSE 0 END) as excellent_count,
                    SUM(CASE WHEN fm.$percentageColumn >= 60 AND fm.$percentageColumn < 80 THEN 1 ELSE 0 END) as good_count,
                    SUM(CASE WHEN fm.$percentageColumn >= 40 AND fm.$percentageColumn < 60 THEN 1 ELSE 0 END) as fair_count,
                    SUM(CASE WHEN fm.$percentageColumn < 40 THEN 1 ELSE 0 END) as poor_count
                FROM enrollments e
                LEFT JOIN final_marks_custom fm ON e.student_id = fm.student_id AND e.course_id = fm.course_id
                WHERE e.course_id = ?
            ");
            $analyticsStmt->execute([$courseId]);
            $componentData[] = $analyticsStmt->fetch();
        }

        echo json_encode([
            'course_id' => $courseId,
            'component_analytics' => $componentData
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get component analytics: ' . $e->getMessage()]);
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
