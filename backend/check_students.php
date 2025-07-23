<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=course_mark_management;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $pdo->query('SELECT id, name, email, role FROM users WHERE role = "student" LIMIT 5');
    $students = $stmt->fetchAll();

    echo "Available student accounts:\n";
    foreach ($students as $student) {
        echo "ID: {$student['id']}, Name: {$student['name']}, Email: {$student['email']}, Role: {$student['role']}\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
