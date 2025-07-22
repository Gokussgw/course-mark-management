<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Enrollment routes with authentication
$app->group('/api', function ($group) {

    // Get all enrollments for a course
    $group->get('/courses/{courseId}/enrollments', function (Request $request, Response $response, $args) {
        try {
            $courseId = $args['courseId'];

            // Get database connection
            $db = $this->get('pdo');

            // Prepare and execute query
            $stmt = $db->prepare("
                SELECT 
                    e.id as enrollment_id,
                    e.academic_year,
                    e.semester,
                    e.enrolled_at,
                    u.id as student_id,
                    u.name as student_name,
                    u.email as student_email,
                    u.matric_number
                FROM enrollments e 
                JOIN users u ON e.student_id = u.id 
                WHERE e.course_id = ? 
                ORDER BY u.name
            ");

            $stmt->execute([$courseId]);
            $enrollments = $stmt->fetchAll();

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $enrollments
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error fetching enrollments: ' . $e->getMessage()
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Get available students for enrollment (not already enrolled in the course)
    $group->get('/courses/{courseId}/available-students', function (Request $request, Response $response, $args) {
        try {
            $courseId = $args['courseId'];

            // Get database connection
            $db = $this->get('pdo');

            // Get students not enrolled in this course
            $stmt = $db->prepare("
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    u.matric_number
                FROM users u 
                WHERE u.role = 'student' 
                AND u.id NOT IN (
                    SELECT e.student_id 
                    FROM enrollments e 
                    WHERE e.course_id = ?
                )
                ORDER BY u.name
            ");

            $stmt->execute([$courseId]);
            $students = $stmt->fetchAll();

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $students
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error fetching available students: ' . $e->getMessage()
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Enroll a single student in a course
    $group->post('/courses/{courseId}/enroll', function (Request $request, Response $response, $args) {
        try {
            $courseId = $args['courseId'];
            $data = $request->getParsedBody();

            $studentId = $data['student_id'] ?? null;
            $academicYear = $data['academic_year'] ?? null;
            $semester = $data['semester'] ?? null;

            if (!$studentId || !$academicYear || !$semester) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Missing required fields: student_id, academic_year, semester'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Get database connection
            $db = $this->get('pdo');

            // Check if student is already enrolled
            $stmt = $db->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ? AND student_id = ?");
            $stmt->execute([$courseId, $studentId]);
            $exists = $stmt->fetchColumn();

            if ($exists > 0) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Student is already enrolled in this course'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Insert enrollment
            $stmt = $db->prepare("
                INSERT INTO enrollments (course_id, student_id, academic_year, semester, enrolled_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");

            $result = $stmt->execute([$courseId, $studentId, $academicYear, $semester]);

            if ($result) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Student enrolled successfully'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            } else {
                throw new Exception('Failed to enroll student');
            }
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error enrolling student: ' . $e->getMessage()
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Bulk enroll students
    $group->post('/courses/{courseId}/bulk-enroll', function (Request $request, Response $response, $args) {
        try {
            $courseId = $args['courseId'];
            $data = $request->getParsedBody();

            $studentIds = $data['student_ids'] ?? [];
            $academicYear = $data['academic_year'] ?? null;
            $semester = $data['semester'] ?? null;

            if (empty($studentIds) || !$academicYear || !$semester) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Missing required fields: student_ids, academic_year, semester'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Get database connection
            $db = $this->get('pdo');

            $db->beginTransaction();

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($studentIds as $studentId) {
                try {
                    // Check if student is already enrolled
                    $stmt = $db->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ? AND student_id = ?");
                    $stmt->execute([$courseId, $studentId]);
                    $exists = $stmt->fetchColumn();

                    if ($exists > 0) {
                        $errorCount++;
                        $errors[] = "Student ID $studentId is already enrolled";
                        continue;
                    }

                    // Insert enrollment
                    $stmt = $db->prepare("
                        INSERT INTO enrollments (course_id, student_id, academic_year, semester, enrolled_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");

                    $result = $stmt->execute([$courseId, $studentId, $academicYear, $semester]);

                    if ($result) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = "Failed to enroll student ID $studentId";
                    }
                } catch (Exception $e) {
                    $errorCount++;
                    $errors[] = "Error enrolling student ID $studentId: " . $e->getMessage();
                }
            }

            $db->commit();

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => "Bulk enrollment completed. $successCount enrolled, $errorCount errors.",
                'details' => [
                    'enrolled' => $successCount,
                    'errors' => $errorCount,
                    'error_messages' => $errors
                ]
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $db->rollback();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error during bulk enrollment: ' . $e->getMessage()
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Remove enrollment
    $group->delete('/enrollments/{enrollmentId}', function (Request $request, Response $response, $args) {
        try {
            $enrollmentId = $args['enrollmentId'];

            // Get database connection
            $db = $this->get('pdo');

            // Delete the enrollment
            $stmt = $db->prepare("DELETE FROM enrollments WHERE id = ?");
            $result = $stmt->execute([$enrollmentId]);

            if ($result && $stmt->rowCount() > 0) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Enrollment removed successfully'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            } else {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Enrollment not found or already removed'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error removing enrollment: ' . $e->getMessage()
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    // Get student's enrollment history
    $group->get('/students/{studentId}/enrollments', function (Request $request, Response $response, $args) {
        try {
            $studentId = $args['studentId'];

            // Get database connection
            $db = $this->get('pdo');

            // Get student's enrollments
            $stmt = $db->prepare("
                SELECT 
                    e.id as enrollment_id,
                    e.academic_year,
                    e.semester,
                    e.enrolled_at,
                    c.id as course_id,
                    c.code as course_code,
                    c.title as course_title,
                    c.credits
                FROM enrollments e 
                JOIN courses c ON e.course_id = c.id 
                WHERE e.student_id = ? 
                ORDER BY e.academic_year DESC, e.semester DESC
            ");

            $stmt->execute([$studentId]);
            $enrollments = $stmt->fetchAll();

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $enrollments
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error fetching student enrollments: ' . $e->getMessage()
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });
})->add($this->get('jwt'));
