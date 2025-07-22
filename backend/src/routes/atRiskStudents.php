<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

// At-Risk Students routes
$app->group('/api/at-risk', function (RouteCollectorProxy $group) {
    // Get at-risk students for an advisor
    $group->get('/students', function (Request $request, Response $response) {
        $advisorId = $request->getAttribute('userId');
        $threshold = $request->getQueryParams()['threshold'] ?? 40; // Default threshold for at-risk is < 40%
        $pdo = $this->get('pdo');

        try {
            // Get all students advised by this advisor
            $stmt = $pdo->prepare('
                SELECT u.* 
                FROM users u
                WHERE u.role = "student" AND u.advisor_id = :advisorId
            ');
            $stmt->execute(['advisorId' => $advisorId]);
            $advisees = $stmt->fetchAll();

            if (empty($advisees)) {
                $response->getBody()->write(json_encode([]));
                return $response->withHeader('Content-Type', 'application/json');
            }

            $atRiskStudents = [];

            // For each advisee, get their course marks and check if they're at risk
            foreach ($advisees as $student) {
                $stmt = $pdo->prepare('
                    SELECT 
                        c.id as course_id, c.name as course_name, c.code as course_code,
                        SUM(IFNULL(m.mark, 0) * a.weightage / 100) / SUM(a.weightage) * 100 as overall_percentage
                    FROM enrollments e
                    JOIN courses c ON e.course_id = c.id
                    JOIN assessments a ON c.id = a.course_id
                    LEFT JOIN marks m ON a.id = m.assessment_id AND m.student_id = :studentId
                    WHERE e.student_id = :studentId AND e.academic_year = YEAR(CURDATE())
                    GROUP BY c.id
                    HAVING overall_percentage < :threshold
                ');
                $stmt->execute([
                    'studentId' => $student['id'],
                    'threshold' => $threshold
                ]);
                $atRiskCourses = $stmt->fetchAll();

                if (!empty($atRiskCourses)) {
                    // Get any existing notes for this student
                    $stmt = $pdo->prepare('
                        SELECT * FROM advisor_notes
                        WHERE student_id = :studentId
                        ORDER BY created_at DESC
                    ');
                    $stmt->execute(['studentId' => $student['id']]);
                    $notes = $stmt->fetchAll();

                    // Add to at-risk list
                    $atRiskStudents[] = [
                        'student' => $student,
                        'atRiskCourses' => $atRiskCourses,
                        'notes' => $notes
                    ];
                }
            }

            $response->getBody()->write(json_encode($atRiskStudents));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Add a note for an at-risk student
    $group->post('/notes', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $studentId = $data['studentId'] ?? null;
        $note = $data['note'] ?? '';
        $advisorId = $request->getAttribute('userId');

        if (empty($studentId) || empty($note)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $pdo = $this->get('pdo');

        try {
            // Verify the student is advised by this advisor
            $stmt = $pdo->prepare('
                SELECT * FROM users
                WHERE id = :studentId AND role = "student" AND advisor_id = :advisorId
            ');
            $stmt->execute([
                'studentId' => $studentId,
                'advisorId' => $advisorId
            ]);
            $student = $stmt->fetch();

            if (!$student) {
                $response->getBody()->write(json_encode(['error' => 'Student not found or not advised by this advisor']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Add the note
            $stmt = $pdo->prepare('
                INSERT INTO advisor_notes 
                (student_id, advisor_id, note, created_at) 
                VALUES 
                (:studentId, :advisorId, :note, NOW())
            ');
            $stmt->execute([
                'studentId' => $studentId,
                'advisorId' => $advisorId,
                'note' => $note
            ]);

            $noteId = $pdo->lastInsertId();

            $response->getBody()->write(json_encode([
                'message' => 'Note added successfully',
                'noteId' => $noteId
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Get all notes for a specific student
    $group->get('/notes/{studentId}', function (Request $request, Response $response, $args) {
        $studentId = $args['studentId'];
        $advisorId = $request->getAttribute('userId');

        $pdo = $this->get('pdo');

        try {
            // Verify the student is advised by this advisor
            $stmt = $pdo->prepare('
                SELECT * FROM users
                WHERE id = :studentId AND role = "student" AND advisor_id = :advisorId
            ');
            $stmt->execute([
                'studentId' => $studentId,
                'advisorId' => $advisorId
            ]);
            $student = $stmt->fetch();

            if (!$student) {
                $response->getBody()->write(json_encode(['error' => 'Student not found or not advised by this advisor']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Get all notes for the student
            $stmt = $pdo->prepare('
                SELECT an.*, u.name as advisor_name
                FROM advisor_notes an
                JOIN users u ON an.advisor_id = u.id
                WHERE an.student_id = :studentId
                ORDER BY an.created_at DESC
            ');
            $stmt->execute(['studentId' => $studentId]);
            $notes = $stmt->fetchAll();

            // Get student's course performance
            $stmt = $pdo->prepare('
                SELECT 
                    c.id, c.name, c.code,
                    SUM(IFNULL(m.mark, 0) * a.weightage / 100) / SUM(a.weightage) * 100 as overall_percentage
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                JOIN assessments a ON c.id = a.course_id
                LEFT JOIN marks m ON a.id = m.assessment_id AND m.student_id = :studentId
                WHERE e.student_id = :studentId AND e.academic_year = YEAR(CURDATE())
                GROUP BY c.id
            ');
            $stmt->execute(['studentId' => $studentId]);
            $courses = $stmt->fetchAll();

            $response->getBody()->write(json_encode([
                'student' => $student,
                'notes' => $notes,
                'courses' => $courses
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Delete a note
    $group->delete('/notes/{noteId}', function (Request $request, Response $response, $args) {
        $noteId = $args['noteId'];
        $advisorId = $request->getAttribute('userId');

        $pdo = $this->get('pdo');

        try {
            // Verify the note was created by this advisor
            $stmt = $pdo->prepare('
                SELECT * FROM advisor_notes
                WHERE id = :noteId AND advisor_id = :advisorId
            ');
            $stmt->execute([
                'noteId' => $noteId,
                'advisorId' => $advisorId
            ]);
            $note = $stmt->fetch();

            if (!$note) {
                $response->getBody()->write(json_encode(['error' => 'Note not found or not created by this advisor']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Delete the note
            $stmt = $pdo->prepare('DELETE FROM advisor_notes WHERE id = :noteId');
            $stmt->execute(['noteId' => $noteId]);

            $response->getBody()->write(json_encode([
                'message' => 'Note deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
})->add($app->getContainer()->get('jwt'));
