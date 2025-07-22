<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Marks routes
$app->group('/api/marks', function ($group) {
    // Get all marks
    $group->get('', function (Request $request, Response $response) {
        $studentId = $request->getQueryParams()['student_id'] ?? null;
        $assessmentId = $request->getQueryParams()['assessment_id'] ?? null;
        $courseId = $request->getQueryParams()['course_id'] ?? null;

        $pdo = $this->get('pdo');

        try {
            $params = [];
            $whereClause = [];

            if ($studentId) {
                $whereClause[] = 'm.student_id = :studentId';
                $params['studentId'] = $studentId;
            }

            if ($assessmentId) {
                $whereClause[] = 'm.assessment_id = :assessmentId';
                $params['assessmentId'] = $assessmentId;
            }

            if ($courseId) {
                $whereClause[] = 'a.course_id = :courseId';
                $params['courseId'] = $courseId;
            }

            $query = '
                SELECT m.*, a.name as assessment_name, a.type as assessment_type, 
                       a.weightage, a.max_mark, a.is_final_exam,
                       c.name as course_name, c.code as course_code,
                       u.name as student_name
                FROM marks m
                JOIN assessments a ON m.assessment_id = a.id
                JOIN courses c ON a.course_id = c.id
                JOIN users u ON m.student_id = u.id
            ';

            if (!empty($whereClause)) {
                $query .= ' WHERE ' . implode(' AND ', $whereClause);
            }

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);

            $marks = $stmt->fetchAll();

            $response->getBody()->write(json_encode($marks));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Get mark by ID
    $group->get('/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];

        $pdo = $this->get('pdo');

        try {
            $stmt = $pdo->prepare('
                SELECT m.*, a.name as assessment_name, a.type as assessment_type, 
                       a.weightage, a.max_mark, a.is_final_exam,
                       c.name as course_name, c.code as course_code,
                       u.name as student_name
                FROM marks m
                JOIN assessments a ON m.assessment_id = a.id
                JOIN courses c ON a.course_id = c.id
                JOIN users u ON m.student_id = u.id
                WHERE m.id = :id
            ');
            $stmt->execute(['id' => $id]);

            $mark = $stmt->fetch();

            if (!$mark) {
                $response->getBody()->write(json_encode(['error' => 'Mark not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($mark));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Create mark
    $group->post('', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $studentId = $data['student_id'] ?? null;
        $assessmentId = $data['assessment_id'] ?? null;
        $mark = $data['mark'] ?? null;

        if (empty($studentId) || empty($assessmentId) || $mark === null) {
            $response->getBody()->write(json_encode(['error' => 'Student ID, assessment ID and mark are required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $pdo = $this->get('pdo');

        try {
            // Check if student exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = :id AND role = "student"');
            $stmt->execute(['id' => $studentId]);

            if (!$stmt->fetch()) {
                $response->getBody()->write(json_encode(['error' => 'Student not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Check if assessment exists
            $stmt = $pdo->prepare('SELECT id, max_mark FROM assessments WHERE id = :id');
            $stmt->execute(['id' => $assessmentId]);

            $assessment = $stmt->fetch();
            if (!$assessment) {
                $response->getBody()->write(json_encode(['error' => 'Assessment not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Validate mark is within range
            if ($mark < 0 || $mark > $assessment['max_mark']) {
                $response->getBody()->write(json_encode(['error' => 'Mark must be between 0 and ' . $assessment['max_mark']]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // Check if mark already exists
            $stmt = $pdo->prepare('SELECT id FROM marks WHERE student_id = :student_id AND assessment_id = :assessment_id');
            $stmt->execute([
                'student_id' => $studentId,
                'assessment_id' => $assessmentId
            ]);

            if ($stmt->fetch()) {
                $response->getBody()->write(json_encode(['error' => 'Mark already exists for this student and assessment']));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }

            $stmt = $pdo->prepare('
                INSERT INTO marks (student_id, assessment_id, mark)
                VALUES (:student_id, :assessment_id, :mark)
            ');
            $stmt->execute([
                'student_id' => $studentId,
                'assessment_id' => $assessmentId,
                'mark' => $mark
            ]);

            $markId = $pdo->lastInsertId();

            $response->getBody()->write(json_encode([
                'message' => 'Mark created successfully',
                'markId' => $markId
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Update mark
    $group->put('/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];
        $data = $request->getParsedBody();
        $mark = $data['mark'] ?? null;

        if ($mark === null) {
            $response->getBody()->write(json_encode(['error' => 'Mark value is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $pdo = $this->get('pdo');

        try {
            // Get the current mark record
            $stmt = $pdo->prepare('SELECT m.*, a.max_mark FROM marks m JOIN assessments a ON m.assessment_id = a.id WHERE m.id = :id');
            $stmt->execute(['id' => $id]);

            $markRecord = $stmt->fetch();
            if (!$markRecord) {
                $response->getBody()->write(json_encode(['error' => 'Mark not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Validate mark is within range
            if ($mark < 0 || $mark > $markRecord['max_mark']) {
                $response->getBody()->write(json_encode(['error' => 'Mark must be between 0 and ' . $markRecord['max_mark']]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $stmt = $pdo->prepare('UPDATE marks SET mark = :mark, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $stmt->execute([
                'id' => $id,
                'mark' => $mark
            ]);

            $response->getBody()->write(json_encode([
                'message' => 'Mark updated successfully',
                'markId' => $id
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Delete mark
    $group->delete('/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];

        $pdo = $this->get('pdo');

        try {
            $stmt = $pdo->prepare('DELETE FROM marks WHERE id = :id');
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() === 0) {
                $response->getBody()->write(json_encode(['error' => 'Mark not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode([
                'message' => 'Mark deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Get student's marks summary for a course
    $group->get('/summary/student/{studentId}/course/{courseId}', function (Request $request, Response $response, array $args) {
        $studentId = $args['studentId'];
        $courseId = $args['courseId'];

        $pdo = $this->get('pdo');

        try {
            // Check if student exists
            $stmt = $pdo->prepare('SELECT id, name FROM users WHERE id = :id AND role = "student"');
            $stmt->execute(['id' => $studentId]);

            $student = $stmt->fetch();
            if (!$student) {
                $response->getBody()->write(json_encode(['error' => 'Student not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Check if course exists
            $stmt = $pdo->prepare('SELECT id, name, code FROM courses WHERE id = :id');
            $stmt->execute(['id' => $courseId]);

            $course = $stmt->fetch();
            if (!$course) {
                $response->getBody()->write(json_encode(['error' => 'Course not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Get all assessments for the course
            $stmt = $pdo->prepare('SELECT * FROM assessments WHERE course_id = :courseId ORDER BY date ASC');
            $stmt->execute(['courseId' => $courseId]);

            $assessments = $stmt->fetchAll();

            // Get student's marks for these assessments
            $assessmentIds = array_column($assessments, 'id');

            if (empty($assessmentIds)) {
                $response->getBody()->write(json_encode([
                    'student' => $student,
                    'course' => $course,
                    'marks' => [],
                    'totalWeightage' => 0,
                    'weightedAverage' => 0
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }

            $placeholders = implode(',', array_fill(0, count($assessmentIds), '?'));

            $stmt = $pdo->prepare("
                SELECT m.*, a.name as assessment_name, a.type as assessment_type, 
                       a.weightage, a.max_mark, a.is_final_exam
                FROM marks m
                JOIN assessments a ON m.assessment_id = a.id
                WHERE m.student_id = ? AND m.assessment_id IN ($placeholders)
            ");

            $params = array_merge([$studentId], $assessmentIds);
            $stmt->execute($params);

            $marks = $stmt->fetchAll();

            // Calculate weighted average
            $totalWeightage = 0;
            $weightedSum = 0;

            foreach ($marks as &$mark) {
                $mark['percentage'] = ($mark['mark'] / $mark['max_mark']) * 100;
                $mark['weighted_mark'] = ($mark['percentage'] / 100) * $mark['weightage'];
                $totalWeightage += $mark['weightage'];
                $weightedSum += $mark['weighted_mark'];
            }

            $weightedAverage = $totalWeightage > 0 ? $weightedSum / $totalWeightage * 100 : 0;

            // Get course rankings
            $stmt = $pdo->prepare("
                SELECT 
                    m.student_id,
                    u.name as student_name,
                    AVG((m.mark / a.max_mark) * 100) as avg_percentage
                FROM marks m
                JOIN assessments a ON m.assessment_id = a.id
                JOIN users u ON m.student_id = u.id
                WHERE a.course_id = ?
                GROUP BY m.student_id, u.name
                ORDER BY avg_percentage DESC
            ");

            $stmt->execute([$courseId]);
            $rankings = $stmt->fetchAll();

            // Find student's rank
            $studentRank = null;
            $totalStudents = count($rankings);
            foreach ($rankings as $index => $rank) {
                if ($rank['student_id'] == $studentId) {
                    $studentRank = $index + 1;
                    break;
                }
            }

            $percentile = $totalStudents > 0 ? 100 - (($studentRank - 1) / $totalStudents * 100) : null;

            $response->getBody()->write(json_encode([
                'student' => $student,
                'course' => $course,
                'marks' => $marks,
                'totalWeightage' => $totalWeightage,
                'weightedAverage' => $weightedAverage,
                'rank' => $studentRank,
                'totalStudents' => $totalStudents,
                'percentile' => $percentile
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Get course statistics
    $group->get('/statistics/course/{courseId}', function (Request $request, Response $response, array $args) {
        $courseId = $args['courseId'];

        $pdo = $this->get('pdo');

        try {
            // Check if course exists
            $stmt = $pdo->prepare('SELECT id, name, code FROM courses WHERE id = :id');
            $stmt->execute(['id' => $courseId]);

            $course = $stmt->fetch();
            if (!$course) {
                $response->getBody()->write(json_encode(['error' => 'Course not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Get assessments for the course
            $stmt = $pdo->prepare('SELECT * FROM assessments WHERE course_id = :courseId ORDER BY date ASC');
            $stmt->execute(['courseId' => $courseId]);

            $assessments = $stmt->fetchAll();

            // Get statistics for each assessment
            $assessmentStats = [];

            foreach ($assessments as $assessment) {
                $stmt = $pdo->prepare('
                    SELECT 
                        COUNT(*) as count,
                        MIN(mark) as min_mark,
                        MAX(mark) as max_mark,
                        AVG(mark) as avg_mark,
                        STDDEV(mark) as std_dev
                    FROM marks 
                    WHERE assessment_id = :assessmentId
                ');
                $stmt->execute(['assessmentId' => $assessment['id']]);

                $stats = $stmt->fetch();

                if ($stats && $stats['count'] > 0) {
                    $assessmentStats[$assessment['id']] = [
                        'assessment' => $assessment,
                        'statistics' => [
                            'count' => (int)$stats['count'],
                            'min' => (float)$stats['min_mark'],
                            'max' => (float)$stats['max_mark'],
                            'average' => (float)$stats['avg_mark'],
                            'stdDev' => (float)$stats['std_dev'],
                            'avgPercentage' => (float)$stats['avg_mark'] / $assessment['max_mark'] * 100
                        ]
                    ];
                } else {
                    $assessmentStats[$assessment['id']] = [
                        'assessment' => $assessment,
                        'statistics' => [
                            'count' => 0,
                            'min' => null,
                            'max' => null,
                            'average' => null,
                            'stdDev' => null,
                            'avgPercentage' => null
                        ]
                    ];
                }

                // Get mark distribution
                $stmt = $pdo->prepare('
                    SELECT 
                        FLOOR((mark / max_mark) * 10) as grade_bucket,
                        COUNT(*) as count
                    FROM marks m
                    JOIN assessments a ON m.assessment_id = a.id
                    WHERE assessment_id = :assessmentId
                    GROUP BY grade_bucket
                    ORDER BY grade_bucket
                ');
                $stmt->execute(['assessmentId' => $assessment['id']]);

                $distribution = $stmt->fetchAll();

                $gradeDistribution = array_fill(0, 10, 0);
                foreach ($distribution as $item) {
                    $bucket = min(9, max(0, (int)$item['grade_bucket']));
                    $gradeDistribution[$bucket] = (int)$item['count'];
                }

                $assessmentStats[$assessment['id']]['distribution'] = $gradeDistribution;
            }

            // Get overall course statistics
            $stmt = $pdo->prepare('
                SELECT 
                    u.id as student_id,
                    u.name as student_name,
                    AVG((m.mark / a.max_mark) * a.weightage) as weighted_avg
                FROM users u
                JOIN marks m ON u.id = m.student_id
                JOIN assessments a ON m.assessment_id = a.id
                WHERE a.course_id = :courseId
                GROUP BY u.id, u.name
                ORDER BY weighted_avg DESC
            ');
            $stmt->execute(['courseId' => $courseId]);

            $overallStats = $stmt->fetchAll();

            $response->getBody()->write(json_encode([
                'course' => $course,
                'assessmentStats' => array_values($assessmentStats),
                'overallStats' => $overallStats,
                'studentCount' => count($overallStats)
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
});
