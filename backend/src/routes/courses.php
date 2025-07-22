<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Course routes with authentication
$app->group('/api/courses', function ($group) {
    // Get all courses (filtered by lecturer if provided)
    $group->get('', function (Request $request, Response $response) {
        $lecturerId = $request->getQueryParams()['lecturer_id'] ?? null;
        $user = $request->getAttribute('user');

        $pdo = $this->get('pdo');

        try {
            // If user is a lecturer, only show their courses
            if ($user && $user->role === 'lecturer') {
                $lecturerId = $user->id;
            }

            if ($lecturerId) {
                $stmt = $pdo->prepare('
                    SELECT c.*, u.name as lecturer_name 
                    FROM courses c
                    LEFT JOIN users u ON c.lecturer_id = u.id
                    WHERE c.lecturer_id = :lecturerId
                    ORDER BY c.code
                ');
                $stmt->execute(['lecturerId' => $lecturerId]);
            } else {
                $stmt = $pdo->query('
                    SELECT c.*, u.name as lecturer_name 
                    FROM courses c
                    LEFT JOIN users u ON c.lecturer_id = u.id
                    ORDER BY c.code
                ');
            }

            $courses = $stmt->fetchAll();

            $response->getBody()->write(json_encode($courses));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Get course by ID
    $group->get('/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];

        $pdo = $this->get('pdo');

        try {
            $stmt = $pdo->prepare('
                SELECT c.*, u.name as lecturer_name 
                FROM courses c
                LEFT JOIN users u ON c.lecturer_id = u.id
                WHERE c.id = :id
            ');
            $stmt->execute(['id' => $id]);

            $course = $stmt->fetch();

            if (!$course) {
                $response->getBody()->write(json_encode(['error' => 'Course not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($course));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Create course
    $group->post('', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $code = $data['code'] ?? '';
        $name = $data['name'] ?? '';
        $semester = $data['semester'] ?? null;
        $academicYear = $data['academic_year'] ?? null;

        if (empty($code) || empty($name)) {
            $response->getBody()->write(json_encode(['error' => 'Course code and name are required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // For lecturers, automatically set them as the lecturer_id
        // For admins, allow setting any lecturer_id
        if ($user && $user->role === 'lecturer') {
            $lecturerId = $user->id;
        } elseif ($user && $user->role === 'admin') {
            $lecturerId = $data['lecturer_id'] ?? null;
        } else {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized to create courses']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $pdo = $this->get('pdo');

        try {
            // Check for duplicate course code
            $stmt = $pdo->prepare('SELECT id FROM courses WHERE code = :code');
            $stmt->execute(['code' => $code]);
            if ($stmt->fetch()) {
                $response->getBody()->write(json_encode(['error' => 'Course code already exists']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $stmt = $pdo->prepare('
                INSERT INTO courses (code, name, lecturer_id, semester, academic_year)
                VALUES (:code, :name, :lecturer_id, :semester, :academic_year)
            ');
            $stmt->execute([
                'code' => $code,
                'name' => $name,
                'lecturer_id' => $lecturerId,
                'semester' => $semester,
                'academic_year' => $academicYear
            ]);

            $courseId = $pdo->lastInsertId();

            $response->getBody()->write(json_encode([
                'message' => 'Course created successfully',
                'courseId' => $courseId
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Update course
    $group->put('/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $pdo = $this->get('pdo');

        try {
            // Check if course exists and user has permission to update it
            $stmt = $pdo->prepare('SELECT * FROM courses WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $course = $stmt->fetch();

            if (!$course) {
                $response->getBody()->write(json_encode(['error' => 'Course not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Check permissions: lecturers can only update their own courses
            if ($user && $user->role === 'lecturer' && $course['lecturer_id'] != $user->id) {
                $response->getBody()->write(json_encode(['error' => 'Unauthorized to update this course']));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }

            // Build update statement
            $updateFields = [];
            $params = ['id' => $id];

            if (isset($data['code'])) {
                // Check for duplicate course code (excluding current course)
                $stmt = $pdo->prepare('SELECT id FROM courses WHERE code = :code AND id != :id');
                $stmt->execute(['code' => $data['code'], 'id' => $id]);
                if ($stmt->fetch()) {
                    $response->getBody()->write(json_encode(['error' => 'Course code already exists']));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }

                $updateFields[] = 'code = :code';
                $params['code'] = $data['code'];
            }

            if (isset($data['name'])) {
                $updateFields[] = 'name = :name';
                $params['name'] = $data['name'];
            }

            // Only admins can change lecturer assignment
            if (array_key_exists('lecturer_id', $data) && $user && $user->role === 'admin') {
                $updateFields[] = 'lecturer_id = :lecturer_id';
                $params['lecturer_id'] = $data['lecturer_id'];
            }

            if (isset($data['semester'])) {
                $updateFields[] = 'semester = :semester';
                $params['semester'] = $data['semester'];
            }

            if (isset($data['academic_year'])) {
                $updateFields[] = 'academic_year = :academic_year';
                $params['academic_year'] = $data['academic_year'];
            }

            if (empty($updateFields)) {
                $response->getBody()->write(json_encode(['error' => 'No fields to update']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $updateQuery = 'UPDATE courses SET ' . implode(', ', $updateFields) . ' WHERE id = :id';

            $stmt = $pdo->prepare($updateQuery);
            $stmt->execute($params);

            $response->getBody()->write(json_encode([
                'message' => 'Course updated successfully',
                'courseId' => $id
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Delete course
    $group->delete('/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];
        $user = $request->getAttribute('user');

        $pdo = $this->get('pdo');

        try {
            // Check if course exists and user has permission to delete it
            $stmt = $pdo->prepare('SELECT * FROM courses WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $course = $stmt->fetch();

            if (!$course) {
                $response->getBody()->write(json_encode(['error' => 'Course not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Check permissions: lecturers can only delete their own courses
            if ($user && $user->role === 'lecturer' && $course['lecturer_id'] != $user->id) {
                $response->getBody()->write(json_encode(['error' => 'Unauthorized to delete this course']));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }

            // Check if course has enrollments
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM enrollments WHERE course_id = :id');
            $stmt->execute(['id' => $id]);
            $enrollmentCount = $stmt->fetch()['count'];

            if ($enrollmentCount > 0) {
                $response->getBody()->write(json_encode([
                    'error' => 'Cannot delete course with enrolled students. Please remove all enrollments first.',
                    'enrollmentCount' => $enrollmentCount
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $stmt = $pdo->prepare('DELETE FROM courses WHERE id = :id');
            $stmt->execute(['id' => $id]);

            $response->getBody()->write(json_encode([
                'message' => 'Course deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
});
