<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');
    $stmt = $pdo->query('SELECT id, name, email, role, advisor_id FROM users ORDER BY role, id');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "All Users:\n";
    foreach ($users as $user) {
        echo "ID: {$user['id']}, Name: {$user['name']}, Role: {$user['role']}, Advisor: {$user['advisor_id']}\n";
    }

    echo "\nStudents with their advisors:\n";
    $stmt = $pdo->query('
        SELECT s.id, s.name as student_name, s.email, 
               a.id as advisor_id, a.name as advisor_name
        FROM users s 
        LEFT JOIN users a ON s.advisor_id = a.id 
        WHERE s.role = "student"
        ORDER BY s.id
    ');
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($students as $student) {
        echo "Student: {$student['student_name']} (ID: {$student['id']}) -> Advisor: {$student['advisor_name']} (ID: {$student['advisor_id']})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
