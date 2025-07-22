<?php
// Database configuration
$host = 'localhost';
$dbname = 'course_mark_management';
$username = 'root';
$password = '';

// Connect to database
$pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

echo "=== USER CREDENTIALS ===\n\n";
$stmt = $pdo->query("SELECT id, name, email, role, matric_number FROM users ORDER BY role, id");
$users = $stmt->fetchAll();

foreach ($users as $user) {
    echo "ID: {$user['id']}\n";
    echo "Name: {$user['name']}\n";
    echo "Email: {$user['email']}\n";
    echo "Role: {$user['role']}\n";
    if ($user['matric_number']) {
        echo "Matric: {$user['matric_number']}\n";
    }
    echo "Password: password (for all users)\n";
    echo "------------------------\n";
}
