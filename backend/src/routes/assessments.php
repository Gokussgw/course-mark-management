<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Assessment routes
$app->group('/api/assessments', function ($group) {
    // Get all assessments
    $group->get('', function (Request $request, Response $response) {
        $courseId = $request->getQueryParams()['course_id'] ?? null;

        $pdo = $this->get('pdo');

        try {
            if ($courseId) {
                $stmt = $pdo->prepare('SELECT * FROM assessments WHERE course_id = :courseId ORDER BY date ASC');
                $stmt->execute(['courseId' => $courseId]);
            } else {
                $stmt = $pdo->query('SELECT * FROM assessments ORDER BY course_id, date ASC');
            }

            $assessments = $stmt->fetchAll();

            $response->getBody()->write(json_encode($assessments));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Get assessment by ID
    $group->get('/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];

        $pdo = $this->get('pdo');

        try {
            $stmt = $pdo->prepare('SELECT * FROM assessments WHERE id = :id');
            $stmt->execute(['id' => $id]);

            $assessment = $stmt->fetch();

            if (!$assessment) {
                $response->getBody()->write(json_encode(['error' => 'Assessment not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($assessment));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Create assessment
    $group->post('', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $courseId = $data['course_id'] ?? null;
        $name = $data['name'] ?? '';
        $type = $data['type'] ?? '';
        $weightage = $data['weightage'] ?? 0;
        $maxMark = $data['max_mark'] ?? 100;
        $isFinalExam = $data['is_final_exam'] ?? false;
        $date = $data['date'] ?? null;

        if (empty($courseId) || empty($name) || empty($type)) {
            $response->getBody()->write(json_encode(['error' => 'Course ID, name and type are required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $pdo = $this->get('pdo');

        try {
            // Check if course exists
            $stmt = $pdo->prepare('SELECT id FROM courses WHERE id = :id');
            $stmt->execute(['id' => $courseId]);

            if (!$stmt->fetch()) {
                $response->getBody()->write(json_encode(['error' => 'Course not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $stmt = $pdo->prepare('
                INSERT INTO assessments (course_id, name, type, weightage, max_mark, is_final_exam, date)
                VALUES (:course_id, :name, :type, :weightage, :max_mark, :is_final_exam, :date)
            ');
            $stmt->execute([
                'course_id' => $courseId,
                'name' => $name,
                'type' => $type,
                'weightage' => $weightage,
                'max_mark' => $maxMark,
                'is_final_exam' => $isFinalExam ? 1 : 0,
                'date' => $date
            ]);

            $assessmentId = $pdo->lastInsertId();

            $response->getBody()->write(json_encode([
                'message' => 'Assessment created successfully',
                'assessmentId' => $assessmentId
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Update assessment
    $group->put('/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];
        $data = $request->getParsedBody();

        $pdo = $this->get('pdo');

        try {
            // Check if assessment exists
            $stmt = $pdo->prepare('SELECT id FROM assessments WHERE id = :id');
            $stmt->execute(['id' => $id]);

            if (!$stmt->fetch()) {
                $response->getBody()->write(json_encode(['error' => 'Assessment not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Build update statement
            $updateFields = [];
            $params = ['id' => $id];

            if (isset($data['course_id'])) {
                // Check if course exists
                $stmt = $pdo->prepare('SELECT id FROM courses WHERE id = :id');
                $stmt->execute(['id' => $data['course_id']]);

                if (!$stmt->fetch()) {
                    $response->getBody()->write(json_encode(['error' => 'Course not found']));
                    return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
                }

                $updateFields[] = 'course_id = :course_id';
                $params['course_id'] = $data['course_id'];
            }

            if (isset($data['name'])) {
                $updateFields[] = 'name = :name';
                $params['name'] = $data['name'];
            }

            if (isset($data['type'])) {
                $updateFields[] = 'type = :type';
                $params['type'] = $data['type'];
            }

            if (isset($data['weightage'])) {
                $updateFields[] = 'weightage = :weightage';
                $params['weightage'] = $data['weightage'];
            }

            if (isset($data['max_mark'])) {
                $updateFields[] = 'max_mark = :max_mark';
                $params['max_mark'] = $data['max_mark'];
            }

            if (isset($data['is_final_exam'])) {
                $updateFields[] = 'is_final_exam = :is_final_exam';
                $params['is_final_exam'] = $data['is_final_exam'] ? 1 : 0;
            }

            if (isset($data['date'])) {
                $updateFields[] = 'date = :date';
                $params['date'] = $data['date'];
            }

            if (empty($updateFields)) {
                $response->getBody()->write(json_encode(['error' => 'No fields to update']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $updateQuery = 'UPDATE assessments SET ' . implode(', ', $updateFields) . ' WHERE id = :id';

            $stmt = $pdo->prepare($updateQuery);
            $stmt->execute($params);

            $response->getBody()->write(json_encode([
                'message' => 'Assessment updated successfully',
                'assessmentId' => $id
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Delete assessment
    $group->delete('/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];

        $pdo = $this->get('pdo');

        try {
            $stmt = $pdo->prepare('DELETE FROM assessments WHERE id = :id');
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() === 0) {
                $response->getBody()->write(json_encode(['error' => 'Assessment not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode([
                'message' => 'Assessment deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
});
