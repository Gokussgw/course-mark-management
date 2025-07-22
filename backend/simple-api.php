<?php
// Simple backend for testing CORS and login
header('Access-Control-Allow-Origin: http://localhost:8083');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple routing
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

if (($requestUri === '/simple-api.php' || $requestUri === '/api/auth/login') && $requestMethod === 'POST') {
    header('Content-Type: application/json');

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    // Simple validation for testing
    if ($email === 'admin@example.com' && $password === 'password') {
        echo json_encode([
            'token' => 'test-token-123',
            'user' => [
                'id' => 1,
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'role' => 'admin'
            ]
        ]);
    } elseif ($email === 'lecturer1@example.com' && $password === 'password') {
        echo json_encode([
            'token' => 'test-token-456',
            'user' => [
                'id' => 2,
                'name' => 'Lecturer One',
                'email' => 'lecturer1@example.com',
                'role' => 'lecturer'
            ]
        ]);
    } elseif ($email === 'student1@example.com' && $password === 'password') {
        echo json_encode([
            'token' => 'test-token-789',
            'user' => [
                'id' => 4,
                'name' => 'Student One',
                'email' => 'student1@example.com',
                'role' => 'student'
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
    }
} else {
    // Default response
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Simple API working', 'path' => $requestUri]);
}
