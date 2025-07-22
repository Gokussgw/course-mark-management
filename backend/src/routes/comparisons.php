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

            // Get all assessments for the course
            $stmt = $pdo->prepare('
                SELECT id, name, type, max_mark, weightage, date
                FROM assessments 
                WHERE course_id = ? 
                ORDER BY date ASC, id ASC
            ');
            $stmt->execute([$courseId]);
            $assessments = $stmt->fetchAll();

            // Get student's marks
            $stmt = $pdo->prepare('
                SELECT a.id as assessment_id, a.name, a.type, a.max_mark, a.weightage, 
                       COALESCE(m.mark, 0) as student_mark
                FROM assessments a
                LEFT JOIN marks m ON a.id = m.assessment_id AND m.student_id = ?
                WHERE a.course_id = ?
                ORDER BY a.date ASC, a.id ASC
            ');
            $stmt->execute([$studentId, $courseId]);
            $studentMarks = $stmt->fetchAll();

            // Get class statistics for each assessment
            $classStats = [];
            foreach ($assessments as $assessment) {
                $stmt = $pdo->prepare('
                    SELECT 
                        COUNT(m.mark) as total_submissions,
                        ROUND(AVG(m.mark), 2) as class_average,
                        MAX(m.mark) as highest_mark,
                        MIN(m.mark) as lowest_mark,
                        ROUND(STDDEV(m.mark), 2) as standard_deviation
                    FROM marks m
                    INNER JOIN enrollments e ON m.student_id = e.student_id
                    WHERE m.assessment_id = ? AND e.course_id = ?
                ');
                $stmt->execute([$assessment['id'], $courseId]);
                $stats = $stmt->fetch();

                // Calculate percentiles
                $stmt = $pdo->prepare('
                    SELECT m.mark
                    FROM marks m
                    INNER JOIN enrollments e ON m.student_id = e.student_id
                    WHERE m.assessment_id = ? AND e.course_id = ?
                    ORDER BY m.mark ASC
                ');
                $stmt->execute([$assessment['id'], $courseId]);
                $allMarks = $stmt->fetchAll(PDO::FETCH_COLUMN);

                $percentiles = [];
                if (!empty($allMarks)) {
                    $percentiles = [
                        '25th' => calculatePercentile($allMarks, 25),
                        '50th' => calculatePercentile($allMarks, 50),
                        '75th' => calculatePercentile($allMarks, 75)
                    ];
                }

                $classStats[$assessment['id']] = array_merge($stats, ['percentiles' => $percentiles]);
            }

            // Calculate student's overall performance
            $totalWeightage = array_sum(array_column($assessments, 'weightage'));
            $studentTotal = 0;
            $classTotal = 0;

            foreach ($studentMarks as $mark) {
                $percentage = ($mark['student_mark'] / $mark['max_mark']) * 100;
                $weightedScore = ($percentage * $mark['weightage']) / 100;
                $studentTotal += $weightedScore;

                $classAvgPercentage = ($classStats[$mark['assessment_id']]['class_average'] / $mark['max_mark']) * 100;
                $classWeightedScore = ($classAvgPercentage * $mark['weightage']) / 100;
                $classTotal += $classWeightedScore;
            }

            // Get student's ranking in the course
            $stmt = $pdo->prepare('
                SELECT 
                    student_ranking.student_id,
                    student_ranking.total_score,
                    ROW_NUMBER() OVER (ORDER BY student_ranking.total_score DESC) as rank
                FROM (
                    SELECT 
                        e.student_id,
                        SUM(
                            CASE 
                                WHEN m.mark IS NOT NULL THEN 
                                    ((m.mark / a.max_mark) * a.weightage)
                                ELSE 0 
                            END
                        ) as total_score
                    FROM enrollments e
                    LEFT JOIN assessments a ON a.course_id = e.course_id
                    LEFT JOIN marks m ON m.assessment_id = a.id AND m.student_id = e.student_id
                    WHERE e.course_id = ?
                    GROUP BY e.student_id
                ) as student_ranking
                ORDER BY student_ranking.total_score DESC
            ');
            $stmt->execute([$courseId]);
            $rankings = $stmt->fetchAll();

            $studentRank = null;
            $totalStudents = count($rankings);
            foreach ($rankings as $index => $ranking) {
                if ($ranking['student_id'] == $studentId) {
                    $studentRank = $index + 1;
                    break;
                }
            }

            // Get anonymized coursemate performance (for privacy)
            $stmt = $pdo->prepare('
                SELECT COUNT(DISTINCT e.student_id) as total_enrolled
                FROM enrollments e
                WHERE e.course_id = ?
            ');
            $stmt->execute([$courseId]);
            $enrollmentInfo = $stmt->fetch();

            // Prepare response
            $comparison = [
                'course' => $course,
                'student_performance' => [
                    'total_score' => round($studentTotal, 2),
                    'rank' => $studentRank,
                    'total_students' => $totalStudents,
                    'percentile' => $studentRank ? round((($totalStudents - $studentRank + 1) / $totalStudents) * 100, 1) : null
                ],
                'class_performance' => [
                    'average_score' => round($classTotal, 2),
                    'total_enrolled' => $enrollmentInfo['total_enrolled']
                ],
                'assessments' => []
            ];

            // Add detailed assessment comparison
            foreach ($studentMarks as $mark) {
                $assessmentId = $mark['assessment_id'];
                $stats = $classStats[$assessmentId];

                $studentPercentage = ($mark['student_mark'] / $mark['max_mark']) * 100;
                $classAvgPercentage = ($stats['class_average'] / $mark['max_mark']) * 100;

                $comparison['assessments'][] = [
                    'assessment' => [
                        'name' => $mark['name'],
                        'type' => $mark['type'],
                        'max_mark' => $mark['max_mark'],
                        'weightage' => $mark['weightage']
                    ],
                    'student_performance' => [
                        'mark' => $mark['student_mark'],
                        'percentage' => round($studentPercentage, 2)
                    ],
                    'class_statistics' => [
                        'average' => $stats['class_average'],
                        'average_percentage' => round($classAvgPercentage, 2),
                        'highest' => $stats['highest_mark'],
                        'lowest' => $stats['lowest_mark'],
                        'standard_deviation' => $stats['standard_deviation'],
                        'total_submissions' => $stats['total_submissions'],
                        'percentiles' => $stats['percentiles']
                    ],
                    'comparison' => [
                        'above_average' => $mark['student_mark'] > $stats['class_average'],
                        'difference_from_average' => round($mark['student_mark'] - $stats['class_average'], 2)
                    ]
                ];
            }

            $response->getBody()->write(json_encode($comparison));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Server error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Get advisor's view of student comparisons
    $group->get('/advisor/students', function (Request $request, Response $response) {
        $user = $request->getAttribute('user');
        $pdo = $this->get('pdo');

        try {
            if (!$user || $user->role !== 'advisor') {
                $response->getBody()->write(json_encode(['error' => 'Advisor access required']));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }

            // Get all advisees and their performance summary
            $stmt = $pdo->prepare('
                SELECT 
                    u.id,
                    u.name,
                    u.matric_number,
                    COUNT(DISTINCT e.course_id) as total_courses
                FROM users u
                LEFT JOIN enrollments e ON u.id = e.student_id
                WHERE u.advisor_id = ? AND u.role = "student"
                GROUP BY u.id, u.name, u.matric_number
                ORDER BY u.name
            ');
            $stmt->execute([$user->id]);
            $advisees = $stmt->fetchAll();

            // Get performance summary for each advisee
            foreach ($advisees as &$advisee) {
                $stmt = $pdo->prepare('
                    SELECT 
                        c.id as course_id,
                        c.code,
                        c.name as course_name,
                        COUNT(DISTINCT a.id) as total_assessments,
                        COUNT(DISTINCT m.id) as completed_assessments,
                        ROUND(AVG(
                            CASE 
                                WHEN m.mark IS NOT NULL THEN (m.mark / a.max_mark) * 100
                                ELSE NULL 
                            END
                        ), 2) as average_percentage
                    FROM enrollments e
                    INNER JOIN courses c ON e.course_id = c.id
                    LEFT JOIN assessments a ON c.id = a.course_id
                    LEFT JOIN marks m ON a.id = m.assessment_id AND m.student_id = e.student_id
                    WHERE e.student_id = ?
                    GROUP BY c.id, c.code, c.name
                    ORDER BY c.code
                ');
                $stmt->execute([$advisee['id']]);
                $advisee['courses'] = $stmt->fetchAll();
            }

            $response->getBody()->write(json_encode([
                'advisor' => [
                    'name' => $user->name,
                    'total_advisees' => count($advisees)
                ],
                'advisees' => $advisees
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Server error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
})->add($container->get('jwt'));

// Helper function to calculate percentiles
function calculatePercentile($values, $percentile)
{
    sort($values);
    $count = count($values);
    if ($count === 0) return 0;

    $index = ($percentile / 100) * ($count - 1);

    if (floor($index) == $index) {
        return $values[$index];
    } else {
        $lower = $values[floor($index)];
        $upper = $values[ceil($index)];
        return $lower + ($upper - $lower) * ($index - floor($index));
    }
}
