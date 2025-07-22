<?php
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

    // Test credentials
    if ($email === 'admin@example.com' && $password === 'password') {
        echo json_encode([
            'token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwiZW1haWwiOiJhZG1pbkBleGFtcGxlLmNvbSIsInJvbGUiOiJhZG1pbiJ9.test',
            'user' => [
                'id' => 1,
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'role' => 'admin'
            ]
        ]);
    } elseif ($email === 'lecturer1@example.com' && $password === 'password') {
        echo json_encode([
            'token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MiwiZW1haWwiOiJsZWN0dXJlcjFAZXhhbXBsZS5jb20iLCJyb2xlIjoibGVjdHVyZXIifQ.test',
            'user' => [
                'id' => 2,
                'name' => 'Lecturer One',
                'email' => 'lecturer1@example.com',
                'role' => 'lecturer'
            ]
        ]);
    } elseif ($email === 'student1@example.com' && $password === 'password') {
        echo json_encode([
            'token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6NCwiZW1haWwiOiJzdHVkZW50MUBleGFtcGxlLmNvbSIsInJvbGUiOiJzdHVkZW50In0.test',
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
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
