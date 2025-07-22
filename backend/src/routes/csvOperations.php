<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

// CSV Import/Export routes
$app->group('/api/csv', function (RouteCollectorProxy $group) {
    // Get CSV template for mark imports
    $group->get('/template/{courseId}', function (Request $request, Response $response, $args) {
        $courseId = $args['courseId'];
        $pdo = $this->get('pdo');

        try {
            // Get course details
            $stmt = $pdo->prepare('SELECT * FROM courses WHERE id = :courseId');
            $stmt->execute(['courseId' => $courseId]);
            $course = $stmt->fetch();

            if (!$course) {
                $response->getBody()->write(json_encode(['error' => 'Course not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Get all assessments for the course
            $stmt = $pdo->prepare('SELECT * FROM assessments WHERE course_id = :courseId');
            $stmt->execute(['courseId' => $courseId]);
            $assessments = $stmt->fetchAll();

            // Get all students enrolled in the course
            $stmt = $pdo->prepare('
                SELECT u.id, u.name, u.matric_number
                FROM users u
                JOIN enrollments e ON u.id = e.student_id
                WHERE e.course_id = :courseId AND u.role = "student"
                ORDER BY u.name
            ');
            $stmt->execute(['courseId' => $courseId]);
            $students = $stmt->fetchAll();

            // Prepare template data
            $templateData = [
                'course' => $course,
                'assessments' => $assessments,
                'students' => $students
            ];

            $response->getBody()->write(json_encode($templateData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Import CSV marks for a course
    $group->post('/import/{courseId}', function (Request $request, Response $response, $args) {
        $courseId = $args['courseId'];
        $data = $request->getParsedBody();
        $csvData = $data['csvData'] ?? [];
        $assessmentId = $data['assessmentId'] ?? null;

        if (empty($csvData) || empty($assessmentId)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required data']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $pdo = $this->get('pdo');

        try {
            // Verify the assessment exists and belongs to the course
            $stmt = $pdo->prepare('SELECT * FROM assessments WHERE id = :assessmentId AND course_id = :courseId');
            $stmt->execute([
                'assessmentId' => $assessmentId,
                'courseId' => $courseId
            ]);
            $assessment = $stmt->fetch();

            if (!$assessment) {
                $response->getBody()->write(json_encode(['error' => 'Assessment not found or not associated with this course']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Begin transaction for batch insertion
            $pdo->beginTransaction();

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($csvData as $row) {
                $studentId = $row['studentId'] ?? null;
                $mark = $row['mark'] ?? null;

                // Skip invalid data
                if (empty($studentId) || !is_numeric($mark) || $mark < 0 || $mark > $assessment['max_mark']) {
                    $errorCount++;
                    $errors[] = [
                        'studentId' => $studentId,
                        'reason' => 'Invalid student ID or mark value'
                    ];
                    continue;
                }

                // Verify student is enrolled in the course
                $stmt = $pdo->prepare('
                    SELECT * FROM enrollments 
                    WHERE student_id = :studentId AND course_id = :courseId
                ');
                $stmt->execute([
                    'studentId' => $studentId,
                    'courseId' => $courseId
                ]);
                $enrollment = $stmt->fetch();

                if (!$enrollment) {
                    $errorCount++;
                    $errors[] = [
                        'studentId' => $studentId,
                        'reason' => 'Student not enrolled in this course'
                    ];
                    continue;
                }

                // Check if mark already exists, if so update, if not insert
                $stmt = $pdo->prepare('
                    SELECT * FROM marks 
                    WHERE student_id = :studentId AND assessment_id = :assessmentId
                ');
                $stmt->execute([
                    'studentId' => $studentId,
                    'assessmentId' => $assessmentId
                ]);
                $existingMark = $stmt->fetch();

                if ($existingMark) {
                    $stmt = $pdo->prepare('
                        UPDATE marks 
                        SET mark = :mark, updated_at = NOW() 
                        WHERE id = :markId
                    ');
                    $stmt->execute([
                        'mark' => $mark,
                        'markId' => $existingMark['id']
                    ]);
                } else {
                    $stmt = $pdo->prepare('
                        INSERT INTO marks (student_id, assessment_id, mark, created_at, updated_at) 
                        VALUES (:studentId, :assessmentId, :mark, NOW(), NOW())
                    ');
                    $stmt->execute([
                        'studentId' => $studentId,
                        'assessmentId' => $assessmentId,
                        'mark' => $mark
                    ]);
                }

                $successCount++;
            }

            $pdo->commit();

            $response->getBody()->write(json_encode([
                'message' => 'CSV data imported successfully',
                'stats' => [
                    'success' => $successCount,
                    'errors' => $errorCount,
                    'errorDetails' => $errors
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Export all marks for a course as CSV
    $group->get('/export/{courseId}', function (Request $request, Response $response, $args) {
        $courseId = $args['courseId'];
        $pdo = $this->get('pdo');

        try {
            // Get course details
            $stmt = $pdo->prepare('SELECT * FROM courses WHERE id = :courseId');
            $stmt->execute(['courseId' => $courseId]);
            $course = $stmt->fetch();

            if (!$course) {
                $response->getBody()->write(json_encode(['error' => 'Course not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Get all assessments for the course
            $stmt = $pdo->prepare('
                SELECT * FROM assessments 
                WHERE course_id = :courseId
                ORDER BY type, name
            ');
            $stmt->execute(['courseId' => $courseId]);
            $assessments = $stmt->fetchAll();

            // Get all students and their marks
            $stmt = $pdo->prepare('
                SELECT 
                    u.id, u.name, u.matric_number,
                    JSON_OBJECTAGG(IFNULL(a.id, 0), IFNULL(m.mark, NULL)) as marks
                FROM users u
                JOIN enrollments e ON u.id = e.student_id
                LEFT JOIN marks m ON u.id = m.student_id
                LEFT JOIN assessments a ON m.assessment_id = a.id AND a.course_id = :courseId
                WHERE e.course_id = :courseId AND u.role = "student"
                GROUP BY u.id
                ORDER BY u.name
            ');
            $stmt->execute(['courseId' => $courseId]);
            $students = $stmt->fetchAll();

            foreach ($students as &$student) {
                $student['marks'] = json_decode($student['marks'], true);
            }

            // Prepare export data
            $exportData = [
                'course' => $course,
                'assessments' => $assessments,
                'students' => $students
            ];

            $response->getBody()->write(json_encode($exportData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
})->add($app->getContainer()->get('jwt'));
