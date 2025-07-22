<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

// Admin routes
$app->group('/api/admin', function (RouteCollectorProxy $group) {
    // Get all users
    $group->get('/users', function (Request $request, Response $response) {
        $pdo = $this->get('pdo');

        try {
            $stmt = $pdo->prepare('
                SELECT 
                    u.id, u.name, u.email, u.role, u.matric_number,
                    u.created_at, u.advisor_id,
                    a.name as advisor_name
                FROM users u
                LEFT JOIN users a ON u.advisor_id = a.id
                ORDER BY u.role, u.name
            ');
            $stmt->execute();
            $users = $stmt->fetchAll();

            $response->getBody()->write(json_encode($users));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Create a new user
    $group->post('/users', function (Request $request, Response $response) {
        $data = $request->getParsedBody();
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'student';
        $matricNumber = $data['matricNumber'] ?? null;
        $pin = $data['pin'] ?? null;
        $advisorId = $data['advisorId'] ?? null;

        if (empty($name) || empty($email) || empty($password)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $hashedPin = !empty($pin) ? password_hash($pin, PASSWORD_DEFAULT) : null;

        $pdo = $this->get('pdo');

        try {
            // Check if email already exists
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
            $stmt->execute(['email' => $email]);
            $existingUser = $stmt->fetch();

            if ($existingUser) {
                $response->getBody()->write(json_encode(['error' => 'Email already in use']));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }

            // Check if matric number already exists (if provided)
            if (!empty($matricNumber)) {
                $stmt = $pdo->prepare('SELECT * FROM users WHERE matric_number = :matricNumber');
                $stmt->execute(['matricNumber' => $matricNumber]);
                $existingMatric = $stmt->fetch();

                if ($existingMatric) {
                    $response->getBody()->write(json_encode(['error' => 'Matric number already in use']));
                    return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
                }
            }

            $stmt = $pdo->prepare('
                INSERT INTO users 
                (name, email, password, role, matric_number, pin, advisor_id, created_at) 
                VALUES 
                (:name, :email, :password, :role, :matricNumber, :pin, :advisorId, NOW())
            ');
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => $role,
                'matricNumber' => $matricNumber,
                'pin' => $hashedPin,
                'advisorId' => $advisorId
            ]);

            $userId = $pdo->lastInsertId();

            // Log this action
            $stmt = $pdo->prepare('
                INSERT INTO system_logs 
                (action, description, user_id, created_at) 
                VALUES 
                ("user_create", :description, :userId, NOW())
            ');
            $stmt->execute([
                'description' => "Created new user: " . $name . " (" . $role . ")",
                'userId' => $request->getAttribute('userId')
            ]);

            $response->getBody()->write(json_encode([
                'message' => 'User created successfully',
                'userId' => $userId
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Update a user
    $group->put('/users/{id}', function (Request $request, Response $response, $args) {
        $userId = $args['id'];
        $data = $request->getParsedBody();
        $name = $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $role = $data['role'] ?? null;
        $matricNumber = $data['matricNumber'] ?? null;
        $advisorId = $data['advisorId'] ?? null;
        $password = $data['password'] ?? null; // Optional, only update if provided
        $pin = $data['pin'] ?? null; // Optional, only update if provided

        if (empty($userId)) {
            $response->getBody()->write(json_encode(['error' => 'User ID is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $pdo = $this->get('pdo');

        try {
            // Check if user exists
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :userId');
            $stmt->execute(['userId' => $userId]);
            $user = $stmt->fetch();

            if (!$user) {
                $response->getBody()->write(json_encode(['error' => 'User not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Prepare update fields
            $updates = [];
            $params = ['userId' => $userId];

            if (!empty($name)) {
                $updates[] = 'name = :name';
                $params['name'] = $name;
            }

            if (!empty($email)) {
                // Check if email already exists for another user
                $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email AND id != :userId');
                $stmt->execute(['email' => $email, 'userId' => $userId]);
                $existingUser = $stmt->fetch();

                if ($existingUser) {
                    $response->getBody()->write(json_encode(['error' => 'Email already in use by another user']));
                    return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
                }

                $updates[] = 'email = :email';
                $params['email'] = $email;
            }

            if (!empty($role)) {
                $updates[] = 'role = :role';
                $params['role'] = $role;
            }

            if ($matricNumber !== null) {
                // Check if matric number already exists for another user
                if (!empty($matricNumber)) {
                    $stmt = $pdo->prepare('SELECT * FROM users WHERE matric_number = :matricNumber AND id != :userId');
                    $stmt->execute(['matricNumber' => $matricNumber, 'userId' => $userId]);
                    $existingUser = $stmt->fetch();

                    if ($existingUser) {
                        $response->getBody()->write(json_encode(['error' => 'Matric number already in use by another user']));
                        return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
                    }
                }

                $updates[] = 'matric_number = :matricNumber';
                $params['matricNumber'] = $matricNumber;
            }

            if ($advisorId !== null) {
                $updates[] = 'advisor_id = :advisorId';
                $params['advisorId'] = $advisorId;
            }

            if (!empty($password)) {
                $updates[] = 'password = :password';
                $params['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            if (!empty($pin)) {
                $updates[] = 'pin = :pin';
                $params['pin'] = password_hash($pin, PASSWORD_DEFAULT);
            }

            if (empty($updates)) {
                $response->getBody()->write(json_encode(['message' => 'No changes to update']));
                return $response->withHeader('Content-Type', 'application/json');
            }

            $stmt = $pdo->prepare('
                UPDATE users 
                SET ' . implode(', ', $updates) . ' 
                WHERE id = :userId
            ');
            $stmt->execute($params);

            // Log this action
            $stmt = $pdo->prepare('
                INSERT INTO system_logs 
                (action, description, user_id, created_at) 
                VALUES 
                ("user_update", :description, :adminId, NOW())
            ');
            $stmt->execute([
                'description' => "Updated user: " . $user['name'] . " (ID: " . $userId . ")",
                'adminId' => $request->getAttribute('userId')
            ]);

            $response->getBody()->write(json_encode([
                'message' => 'User updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Delete a user
    $group->delete('/users/{id}', function (Request $request, Response $response, $args) {
        $userId = $args['id'];

        $pdo = $this->get('pdo');

        try {
            // Check if user exists
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :userId');
            $stmt->execute(['userId' => $userId]);
            $user = $stmt->fetch();

            if (!$user) {
                $response->getBody()->write(json_encode(['error' => 'User not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Begin transaction
            $pdo->beginTransaction();

            // Delete related records first (marks, enrollments, etc.)
            if ($user['role'] === 'student') {
                $stmt = $pdo->prepare('DELETE FROM marks WHERE student_id = :userId');
                $stmt->execute(['userId' => $userId]);

                $stmt = $pdo->prepare('DELETE FROM enrollments WHERE student_id = :userId');
                $stmt->execute(['userId' => $userId]);

                $stmt = $pdo->prepare('DELETE FROM remark_requests WHERE student_id = :userId');
                $stmt->execute(['userId' => $userId]);

                $stmt = $pdo->prepare('DELETE FROM advisor_notes WHERE student_id = :userId');
                $stmt->execute(['userId' => $userId]);
            }

            if ($user['role'] === 'lecturer') {
                // Update courses to have no lecturer
                $stmt = $pdo->prepare('UPDATE courses SET lecturer_id = NULL WHERE lecturer_id = :userId');
                $stmt->execute(['userId' => $userId]);
            }

            if ($user['role'] === 'advisor') {
                // Update students to have no advisor
                $stmt = $pdo->prepare('UPDATE users SET advisor_id = NULL WHERE advisor_id = :userId');
                $stmt->execute(['userId' => $userId]);

                $stmt = $pdo->prepare('DELETE FROM advisor_notes WHERE advisor_id = :userId');
                $stmt->execute(['userId' => $userId]);
            }

            // Delete notifications
            $stmt = $pdo->prepare('DELETE FROM notifications WHERE user_id = :userId OR sender_id = :userId');
            $stmt->execute(['userId' => $userId]);

            // Finally delete the user
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :userId');
            $stmt->execute(['userId' => $userId]);

            // Log this action
            $stmt = $pdo->prepare('
                INSERT INTO system_logs 
                (action, description, user_id, created_at) 
                VALUES 
                ("user_delete", :description, :adminId, NOW())
            ');
            $stmt->execute([
                'description' => "Deleted user: " . $user['name'] . " (" . $user['role'] . ")",
                'adminId' => $request->getAttribute('userId')
            ]);

            $pdo->commit();

            $response->getBody()->write(json_encode([
                'message' => 'User deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Get system logs
    $group->get('/logs', function (Request $request, Response $response) {
        $limit = $request->getQueryParams()['limit'] ?? 50;
        $offset = $request->getQueryParams()['offset'] ?? 0;

        $pdo = $this->get('pdo');

        try {
            $stmt = $pdo->prepare('
                SELECT 
                    l.*, u.name as user_name, u.email as user_email
                FROM system_logs l
                LEFT JOIN users u ON l.user_id = u.id
                ORDER BY l.created_at DESC
                LIMIT :limit OFFSET :offset
            ');
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll();

            // Get total count
            $stmt = $pdo->query('SELECT COUNT(*) FROM system_logs');
            $totalCount = $stmt->fetchColumn();

            $response->getBody()->write(json_encode([
                'logs' => $logs,
                'total' => $totalCount,
                'limit' => (int)$limit,
                'offset' => (int)$offset
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Get system stats
    $group->get('/stats', function (Request $request, Response $response) {
        $pdo = $this->get('pdo');

        try {
            $stats = [];

            // Count users by role
            $stmt = $pdo->query('
                SELECT role, COUNT(*) as count 
                FROM users 
                GROUP BY role
            ');
            $stats['usersByRole'] = $stmt->fetchAll();

            // Count courses
            $stmt = $pdo->query('SELECT COUNT(*) FROM courses');
            $stats['totalCourses'] = $stmt->fetchColumn();

            // Count assessments
            $stmt = $pdo->query('SELECT COUNT(*) FROM assessments');
            $stats['totalAssessments'] = $stmt->fetchColumn();

            // Count marks
            $stmt = $pdo->query('SELECT COUNT(*) FROM marks');
            $stats['totalMarks'] = $stmt->fetchColumn();

            // Count remark requests
            $stmt = $pdo->query('SELECT COUNT(*) FROM remark_requests');
            $stats['totalRemarkRequests'] = $stmt->fetchColumn();

            // Count at-risk students (using the threshold of 40%)
            $stmt = $pdo->query('
                SELECT COUNT(DISTINCT s.student_id) FROM (
                    SELECT 
                        e.student_id,
                        c.id as course_id,
                        SUM(IFNULL(m.mark, 0) * a.weightage / 100) / SUM(a.weightage) * 100 as overall_percentage
                    FROM enrollments e
                    JOIN courses c ON e.course_id = c.id
                    JOIN assessments a ON c.id = a.course_id
                    LEFT JOIN marks m ON a.id = m.assessment_id AND m.student_id = e.student_id
                    WHERE e.academic_year = YEAR(CURDATE())
                    GROUP BY e.student_id, c.id
                    HAVING overall_percentage < 40
                ) s
            ');
            $stats['atRiskStudents'] = $stmt->fetchColumn();

            $response->getBody()->write(json_encode($stats));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
})->add($app->getContainer()->get('jwt'))->add($app->getContainer()->get('adminOnly'));
