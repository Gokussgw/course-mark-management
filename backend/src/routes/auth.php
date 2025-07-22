<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;

// Authentication routes
$app->post('/api/auth/login', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        $response->getBody()->write(json_encode(['error' => 'Email and password are required']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $pdo = $this->get('pdo');

    try {
        // Login with email and password for all users
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);

        $user = $stmt->fetch();

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Invalid email or password']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Invalid email or password']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Create JWT token
        $jwtSecret = $this->get('settings')['jwt']['secret'];
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // Token valid for 1 hour

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'name' => $user['name']
        ];

        $token = JWT::encode($payload, $jwtSecret, 'HS256');

        $responseData = [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];

        $response->getBody()->write(json_encode($responseData));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->post('/api/auth/register', function (Request $request, Response $response) {
    // This would be admin-only in a production environment
    $data = $request->getParsedBody();
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? 'student';
    $matricNumber = $data['matricNumber'] ?? null;
    $pin = $data['pin'] ?? null;

    if (empty($name) || empty($email) || empty($password)) {
        $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $hashedPin = !empty($pin) ? password_hash($pin, PASSWORD_DEFAULT) : null;

    $pdo = $this->get('pdo');

    try {
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, matric_number, pin) VALUES (:name, :email, :password, :role, :matricNumber, :pin)');
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role,
            'matricNumber' => $matricNumber,
            'pin' => $hashedPin
        ]);

        $userId = $pdo->lastInsertId();

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
