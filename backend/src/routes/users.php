<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Middleware\JwtAuthMiddleware;

// User routes
$app->group('/api/users', function ($group) {
    // Get all users
    $group->get('', function (Request $request, Response $response) {
        $role = $request->getQueryParams()['role'] ?? null;

        $pdo = $this->get('pdo');

        try {
            if ($role) {
                $stmt = $pdo->prepare('SELECT id, name, email, role, matric_number, created_at FROM users WHERE role = :role');
                $stmt->execute(['role' => $role]);
            } else {
                $stmt = $pdo->query('SELECT id, name, email, role, matric_number, created_at FROM users');
            }

            $users = $stmt->fetchAll();

            $response->getBody()->write(json_encode($users));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Get user by ID
    $group->get('/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];

        $pdo = $this->get('pdo');

        try {
            $stmt = $pdo->prepare('SELECT id, name, email, role, matric_number, created_at FROM users WHERE id = :id');
            $stmt->execute(['id' => $id]);

            $user = $stmt->fetch();

            if (!$user) {
                $response->getBody()->write(json_encode(['error' => 'User not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($user));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Update user
    $group->put('/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];
        $data = $request->getParsedBody();

        $pdo = $this->get('pdo');

        try {
            // First check if user exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = :id');
            $stmt->execute(['id' => $id]);

            if (!$stmt->fetch()) {
                $response->getBody()->write(json_encode(['error' => 'User not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Build update statement based on provided fields
            $updateFields = [];
            $params = ['id' => $id];

            if (isset($data['name'])) {
                $updateFields[] = 'name = :name';
                $params['name'] = $data['name'];
            }

            if (isset($data['email'])) {
                $updateFields[] = 'email = :email';
                $params['email'] = $data['email'];
            }

            if (isset($data['password'])) {
                $updateFields[] = 'password = :password';
                $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            if (isset($data['matric_number'])) {
                $updateFields[] = 'matric_number = :matricNumber';
                $params['matricNumber'] = $data['matric_number'];
            }

            if (isset($data['pin'])) {
                $updateFields[] = 'pin = :pin';
                $params['pin'] = password_hash($data['pin'], PASSWORD_DEFAULT);
            }

            if (empty($updateFields)) {
                $response->getBody()->write(json_encode(['error' => 'No fields to update']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $updateQuery = 'UPDATE users SET ' . implode(', ', $updateFields) . ' WHERE id = :id';

            $stmt = $pdo->prepare($updateQuery);
            $stmt->execute($params);

            $response->getBody()->write(json_encode([
                'message' => 'User updated successfully',
                'userId' => $id
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // Delete user
    $group->delete('/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];

        $pdo = $this->get('pdo');

        try {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() === 0) {
                $response->getBody()->write(json_encode(['error' => 'User not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode([
                'message' => 'User deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
});
