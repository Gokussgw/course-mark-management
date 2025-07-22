<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;

// Load environment variables
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// CORS headers - Dynamic origin handling
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (preg_match('/^http:\/\/localhost:\d+$/', $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: http://localhost:3000'); // fallback
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Handle POST request for login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    // Create JWT token function
    function createJWTToken($userData)
    {
        $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your_jwt_secret_key_here';
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // Token valid for 1 hour

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'id' => $userData['id'],
            'email' => $userData['email'],
            'role' => $userData['role'],
            'name' => $userData['name']
        ];

        return JWT::encode($payload, $jwtSecret, 'HS256');
    }

    // Test credentials - these would normally be checked against database
    if ($email === 'admin@example.com' && $password === 'password') {
        $userData = [
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin'
        ];
        echo json_encode([
            'token' => createJWTToken($userData),
            'user' => $userData
        ]);
    } elseif ($email === 'lecturer1@example.com' && $password === 'password') {
        $userData = [
            'id' => 2,
            'name' => 'Lecturer One',
            'email' => 'lecturer1@example.com',
            'role' => 'lecturer'
        ];
        echo json_encode([
            'token' => createJWTToken($userData),
            'user' => $userData
        ]);
    } elseif ($email === 'student1@example.com' && $password === 'password') {
        $userData = [
            'id' => 4,
            'name' => 'Student One',
            'email' => 'student1@example.com',
            'role' => 'student'
        ];
        echo json_encode([
            'token' => createJWTToken($userData),
            'user' => $userData
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
