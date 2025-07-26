<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Coursemate comparison routes
$app->group('/api/comparisons', function ($group) {

    // Get student's performance comparison with coursemates
    $group->get('/coursemates/{courseId}', function (Request $request, Response $response, $args) {
        $courseId = $args['courseId'];
        $user = $request->getAttribute('user');
        $pdo = $this->get('pdo');

        try {
            // Get the student ID (if user is student) or from query params (if advisor)
            $studentId = null;
            if ($user && $user->role === 'student') {
                $studentId = $user->id;
            } elseif ($user && $user->role === 'advisor') {
                $studentId = $request->getQueryParams()['student_id'] ?? null;
                if (!$studentId) {
                    $response->getBody()->write(json_encode(['error' => 'student_id parameter required for advisors']));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }

                // Verify the student is under this advisor
                $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND advisor_id = ?');
                $stmt->execute([$studentId, $user->id]);
                if (!$stmt->fetch()) {
                    $response->getBody()->write(json_encode(['error' => 'Student not under your advisory']));
                    return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
                }
            } else {
                $response->getBody()->write(json_encode(['error' => 'Unauthorized access']));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }

            // Verify student is enrolled in the course
            $stmt = $pdo->prepare('SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?');
            $stmt->execute([$studentId, $courseId]);
            if (!$stmt->fetch()) {
                $response->getBody()->write(json_encode(['error' => 'Student not enrolled in this course']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Get course information
            $stmt = $pdo->prepare('SELECT code, name FROM courses WHERE id = ?');
            $stmt->execute([$courseId]);
            $course = $stmt->fetch();

            if (!$course) {
                $response->getBody()->write(json_encode(['error' => 'Course not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Get student's marks from final_marks_custom
            $stmt = $pdo->prepare('
                SELECT 
                    assignment_mark,
                    assignment_percentage,
                    quiz_mark,
                    quiz_percentage,
                    test_mark,
                    test_percentage,
                    final_exam_mark,
                    final_exam_percentage,
                    component_total,
                    final_grade,
                    letter_grade,
                    gpa
                FROM final_marks_custom 
                WHERE student_id = ? AND course_id = ?
            ');
            $stmt->execute([$studentId, $courseId]);
            $studentMarks = $stmt->fetch();

            if (!$studentMarks) {
                $response->getBody()->write(json_encode(['error' => 'Student marks not found for this course']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Get class statistics for each component
            $components = ['assignment', 'quiz', 'test', 'final_exam'];
            $classStats = [];

            foreach ($components as $component) {
                $markColumn = $component . '_mark';

                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(fm.$markColumn) as total_submissions,
                        ROUND(AVG(fm.$markColumn), 2) as class_average,
                        MAX(fm.$markColumn) as highest_mark,
                        MIN(fm.$markColumn) as lowest_mark,
                        ROUND(STDDEV(fm.$markColumn), 2) as standard_deviation
                    FROM final_marks_custom fm
                    INNER JOIN enrollments e ON fm.student_id = e.student_id
                    WHERE fm.course_id = ? AND e.course_id = ?
                ");
                $stmt->execute([$courseId, $courseId]);
                $stats = $stmt->fetch();

                // Calculate percentiles for this component
                $stmt = $pdo->prepare("
                    SELECT fm.$markColumn
                    FROM final_marks_custom fm
                    INNER JOIN enrollments e ON fm.student_id = e.student_id
                    WHERE fm.course_id = ? AND e.course_id = ?
                    ORDER BY fm.$markColumn ASC
                ");
                $stmt->execute([$courseId, $courseId]);
                $allMarks = $stmt->fetchAll(PDO::FETCH_COLUMN);

                $percentiles = [];
                if (!empty($allMarks)) {
                    $percentiles = [
                        '25th' => calculatePercentile($allMarks, 25),
                        '50th' => calculatePercentile($allMarks, 50),
                        '75th' => calculatePercentile($allMarks, 75)
                    ];
                }

                $classStats[$component] = array_merge($stats, ['percentiles' => $percentiles]);
            }

            // Get overall final grade statistics
            $stmt = $pdo->prepare('
                SELECT 
                    COUNT(fm.final_grade) as total_submissions,
                    ROUND(AVG(fm.final_grade), 2) as class_average,
                    MAX(fm.final_grade) as highest_mark,
                    MIN(fm.final_grade) as lowest_mark,
                    ROUND(STDDEV(fm.final_grade), 2) as standard_deviation
                FROM final_marks_custom fm
                INNER JOIN enrollments e ON fm.student_id = e.student_id
                WHERE fm.course_id = ? AND e.course_id = ?
            ');
            $stmt->execute([$courseId, $courseId]);
            $overallStats = $stmt->fetch();

            // Calculate percentiles for overall grade
            $stmt = $pdo->prepare('
                SELECT fm.final_grade
                FROM final_marks_custom fm
                INNER JOIN enrollments e ON fm.student_id = e.student_id
                WHERE fm.course_id = ? AND e.course_id = ?
                ORDER BY fm.final_grade ASC
            ');
            $stmt->execute([$courseId, $courseId]);
            $allGrades = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $overallPercentiles = [];
            if (!empty($allGrades)) {
                $overallPercentiles = [
                    '25th' => calculatePercentile($allGrades, 25),
                    '50th' => calculatePercentile($allGrades, 50),
                    '75th' => calculatePercentile($allGrades, 75)
                ];
            }

            $classStats['overall'] = array_merge($overallStats, ['percentiles' => $overallPercentiles]);

            // Calculate student's position in class
            $stmt = $pdo->prepare('
                SELECT COUNT(*) + 1 as position
                FROM final_marks_custom fm
                INNER JOIN enrollments e ON fm.student_id = e.student_id
                WHERE fm.course_id = ? AND e.course_id = ? AND fm.final_grade > ?
            ');
            $stmt->execute([$courseId, $courseId, $studentMarks['final_grade']]);
            $position = $stmt->fetchColumn();

            // Get total number of students in course
            $stmt = $pdo->prepare('
                SELECT COUNT(DISTINCT e.student_id) as total_students
                FROM enrollments e
                WHERE e.course_id = ?
            ');
            $stmt->execute([$courseId]);
            $totalStudents = $stmt->fetchColumn();

            // Prepare the comparison data structure
            $comparison = [
                'course' => [
                    'id' => $courseId,
                    'code' => $course['code'],
                    'name' => $course['name']
                ],
                'student' => [
                    'position' => $position,
                    'total_students' => $totalStudents,
                    'marks' => [
                        'assignment' => [
                            'mark' => $studentMarks['assignment_mark'],
                            'percentage' => $studentMarks['assignment_percentage'],
                            'class_stats' => $classStats['assignment']
                        ],
                        'quiz' => [
                            'mark' => $studentMarks['quiz_mark'],
                            'percentage' => $studentMarks['quiz_percentage'],
                            'class_stats' => $classStats['quiz']
                        ],
                        'test' => [
                            'mark' => $studentMarks['test_mark'],
                            'percentage' => $studentMarks['test_percentage'],
                            'class_stats' => $classStats['test']
                        ],
                        'final_exam' => [
                            'mark' => $studentMarks['final_exam_mark'],
                            'percentage' => $studentMarks['final_exam_percentage'],
                            'class_stats' => $classStats['final_exam']
                        ],
                        'overall' => [
                            'final_grade' => $studentMarks['final_grade'],
                            'letter_grade' => $studentMarks['letter_grade'],
                            'gpa' => $studentMarks['gpa'],
                            'class_stats' => $classStats['overall']
                        ]
                    ]
                ]
            ];

            $response->getBody()->write(json_encode($comparison));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Server error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Get advisor's overall comparison view
    $group->get('/advisor-overview', function (Request $request, Response $response) {
        $user = $request->getAttribute('user');
        $pdo = $this->get('pdo');

        if (!$user || $user->role !== 'advisor') {
            $response->getBody()->write(json_encode(['error' => 'Advisor access required']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        try {
            $advisorId = $user->id;

            // Get summary statistics for advisor's students using final_marks_custom
            $stmt = $pdo->prepare('
                SELECT 
                    COUNT(DISTINCT u.id) as total_students,
                    ROUND(AVG(fm.gpa), 2) as avg_gpa,
                    ROUND(AVG(fm.final_grade), 2) as avg_final_grade,
                    COUNT(DISTINCT fm.course_id) as courses_covered,
                    SUM(CASE WHEN fm.letter_grade IN ("A", "A-") THEN 1 ELSE 0 END) as excellent_grades,
                    SUM(CASE WHEN fm.letter_grade IN ("B+", "B", "B-") THEN 1 ELSE 0 END) as good_grades,
                    SUM(CASE WHEN fm.letter_grade IN ("C+", "C", "C-") THEN 1 ELSE 0 END) as average_grades,
                    SUM(CASE WHEN fm.letter_grade IN ("D+", "D", "F") THEN 1 ELSE 0 END) as poor_grades
                FROM users u
                LEFT JOIN final_marks_custom fm ON u.id = fm.student_id
                WHERE u.advisor_id = ? AND u.role = "student"
            ');
            $stmt->execute([$advisorId]);
            $overview = $stmt->fetch();

            $response->getBody()->write(json_encode([
                'overview' => $overview
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Server error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
})->add(new JwtMiddleware($container));

// Helper function to calculate percentiles
function calculatePercentile($data, $percentile)
{
    if (empty($data)) return 0;

    sort($data);
    $index = ($percentile / 100) * (count($data) - 1);

    if (floor($index) == $index) {
        return $data[$index];
    } else {
        $lower = $data[floor($index)];
        $upper = $data[ceil($index)];
        return $lower + ($upper - $lower) * ($index - floor($index));
    }
}
