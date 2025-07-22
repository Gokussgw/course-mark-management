<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Get all enrollments for a course
$app->get('/api/courses/{courseId}/enrollments', function (Request $request, Response $response, $args) {
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
$app->get('/api/courses/{courseId}/available-students', function (Request $request, Response $response, $args) {
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
$app->post('/api/courses/{courseId}/enroll', function (Request $request, Response $response, $args) {
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

// Remove enrollment
$app->delete('/api/enrollments/{enrollmentId}', function (Request $request, Response $response, $args) {
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
