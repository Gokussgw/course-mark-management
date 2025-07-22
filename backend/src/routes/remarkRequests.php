<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

// Remark Request routes
$app->group('/api/remark-requests', function (RouteCollectorProxy $group) {
    // Submit a new remark request
    $group->post('', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $markId = $data['markId'] ?? null;
        $justification = $data['justification'] ?? '';
        $userId = $request->getAttribute('userId');

        if (empty($markId) || empty($justification)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $pdo = $this->get('pdo');

        try {
            // Verify the mark belongs to the student
            $stmt = $pdo->prepare('
                SELECT m.*, a.name as assessment_name, c.name as course_name, c.id as course_id
                FROM marks m
                JOIN assessments a ON m.assessment_id = a.id
                JOIN courses c ON a.course_id = c.id
                WHERE m.id = :markId AND m.student_id = :studentId
            ');
            $stmt->execute([
                'markId' => $markId,
                'studentId' => $userId
            ]);
            $mark = $stmt->fetch();

            if (!$mark) {
                $response->getBody()->write(json_encode(['error' => 'Mark not found or does not belong to the student']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Check if there's an existing open request
            $stmt = $pdo->prepare('
                SELECT * FROM remark_requests 
                WHERE mark_id = :markId AND status != "resolved"
            ');
            $stmt->execute(['markId' => $markId]);
            $existingRequest = $stmt->fetch();

            if ($existingRequest) {
                $response->getBody()->write(json_encode(['error' => 'A remark request is already open for this assessment']));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }

            // Create the remark request
            $stmt = $pdo->prepare('
                INSERT INTO remark_requests 
                (mark_id, student_id, course_id, justification, status, created_at, updated_at) 
                VALUES 
                (:markId, :studentId, :courseId, :justification, "pending", NOW(), NOW())
            ');
            $stmt->execute([
                'markId' => $markId,
                'studentId' => $userId,
                'courseId' => $mark['course_id'],
                'justification' => $justification
            ]);

            $remarkRequestId = $pdo->lastInsertId();

            // Add notification for the lecturer
            $stmt = $pdo->prepare('
                SELECT lecturer_id FROM courses WHERE id = :courseId
            ');
            $stmt->execute(['courseId' => $mark['course_id']]);
            $course = $stmt->fetch();

            if ($course && $course['lecturer_id']) {
                $stmt = $pdo->prepare('
                    INSERT INTO notifications 
                    (user_id, type, content, related_id, created_at, is_read) 
                    VALUES 
                    (:userId, "remark_request", :content, :relatedId, NOW(), 0)
                ');
                $stmt->execute([
                    'userId' => $course['lecturer_id'],
                    'content' => "New remark request for " . $mark['course_name'] . " - " . $mark['assessment_name'],
                    'relatedId' => $remarkRequestId
                ]);
            }

            $response->getBody()->write(json_encode([
                'message' => 'Remark request submitted successfully',
                'remarkRequestId' => $remarkRequestId
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Get remark requests for a student
    $group->get('/student', function (Request $request, Response $response) {
        $userId = $request->getAttribute('userId');
        $pdo = $this->get('pdo');

        try {
            $stmt = $pdo->prepare('
                SELECT 
                    rr.*, m.mark, m.assessment_id, 
                    a.name as assessment_name, a.max_mark,
                    c.name as course_name, c.code as course_code
                FROM remark_requests rr
                JOIN marks m ON rr.mark_id = m.id
                JOIN assessments a ON m.assessment_id = a.id
                JOIN courses c ON rr.course_id = c.id
                WHERE rr.student_id = :studentId
                ORDER BY rr.created_at DESC
            ');
            $stmt->execute(['studentId' => $userId]);
            $remarkRequests = $stmt->fetchAll();

            $response->getBody()->write(json_encode($remarkRequests));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Get remark requests for a lecturer (by course)
    $group->get('/lecturer/{courseId}', function (Request $request, Response $response, $args) {
        $courseId = $args['courseId'];
        $userId = $request->getAttribute('userId');
        $pdo = $this->get('pdo');

        try {
            // Verify the lecturer teaches this course
            $stmt = $pdo->prepare('
                SELECT * FROM courses 
                WHERE id = :courseId AND lecturer_id = :lecturerId
            ');
            $stmt->execute([
                'courseId' => $courseId,
                'lecturerId' => $userId
            ]);
            $course = $stmt->fetch();

            if (!$course) {
                $response->getBody()->write(json_encode(['error' => 'Course not found or not taught by this lecturer']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $stmt = $pdo->prepare('
                SELECT 
                    rr.*, m.mark, m.assessment_id, 
                    a.name as assessment_name, a.max_mark,
                    u.name as student_name, u.matric_number
                FROM remark_requests rr
                JOIN marks m ON rr.mark_id = m.id
                JOIN assessments a ON m.assessment_id = a.id
                JOIN users u ON rr.student_id = u.id
                WHERE rr.course_id = :courseId
                ORDER BY rr.status, rr.created_at DESC
            ');
            $stmt->execute(['courseId' => $courseId]);
            $remarkRequests = $stmt->fetchAll();

            $response->getBody()->write(json_encode($remarkRequests));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Update a remark request (lecturer only)
    $group->put('/{id}', function (Request $request, Response $response, $args) {
        $remarkRequestId = $args['id'];
        $data = $request->getParsedBody();
        $status = $data['status'] ?? null;
        $lecturerResponse = $data['lecturerResponse'] ?? '';
        $newMark = $data['newMark'] ?? null;
        $userId = $request->getAttribute('userId');

        if (empty($status) || !in_array($status, ['approved', 'rejected', 'resolved'])) {
            $response->getBody()->write(json_encode(['error' => 'Invalid status value']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $pdo = $this->get('pdo');

        try {
            // Verify the remark request belongs to a course taught by this lecturer
            $stmt = $pdo->prepare('
                SELECT rr.*, m.mark, m.id as mark_id, m.student_id, c.lecturer_id, a.max_mark
                FROM remark_requests rr
                JOIN marks m ON rr.mark_id = m.id
                JOIN assessments a ON m.assessment_id = a.id
                JOIN courses c ON rr.course_id = c.id
                WHERE rr.id = :remarkRequestId AND c.lecturer_id = :lecturerId
            ');
            $stmt->execute([
                'remarkRequestId' => $remarkRequestId,
                'lecturerId' => $userId
            ]);
            $remarkRequest = $stmt->fetch();

            if (!$remarkRequest) {
                $response->getBody()->write(json_encode(['error' => 'Remark request not found or not for a course taught by this lecturer']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Begin transaction
            $pdo->beginTransaction();

            // Update the remark request
            $stmt = $pdo->prepare('
                UPDATE remark_requests 
                SET status = :status, lecturer_response = :lecturerResponse, updated_at = NOW() 
                WHERE id = :remarkRequestId
            ');
            $stmt->execute([
                'status' => $status,
                'lecturerResponse' => $lecturerResponse,
                'remarkRequestId' => $remarkRequestId
            ]);

            // If approved, update the mark
            if ($status === 'approved' && $newMark !== null) {
                // Validate new mark
                if (!is_numeric($newMark) || $newMark < 0 || $newMark > $remarkRequest['max_mark']) {
                    $pdo->rollBack();
                    $response->getBody()->write(json_encode(['error' => 'Invalid new mark value']));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }

                $stmt = $pdo->prepare('
                    UPDATE marks 
                    SET mark = :newMark, updated_at = NOW() 
                    WHERE id = :markId
                ');
                $stmt->execute([
                    'newMark' => $newMark,
                    'markId' => $remarkRequest['mark_id']
                ]);
            }

            // Add notification for the student
            $stmt = $pdo->prepare('
                INSERT INTO notifications 
                (user_id, type, content, related_id, created_at, is_read) 
                VALUES 
                (:userId, "remark_response", :content, :relatedId, NOW(), 0)
            ');
            $stmt->execute([
                'userId' => $remarkRequest['student_id'],
                'content' => "Your remark request has been " . $status .
                    ($status === 'approved' ? ". New mark: " . $newMark : ""),
                'relatedId' => $remarkRequestId
            ]);

            $pdo->commit();

            $response->getBody()->write(json_encode([
                'message' => 'Remark request updated successfully',
                'status' => $status
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
})->add($app->getContainer()->get('jwt'));
