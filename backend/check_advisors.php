<?php
$pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');

echo "Advisors:\n";
$stmt = $pdo->query('SELECT id, name, email FROM users WHERE role = "advisor"');
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']}, Name: {$row['name']}, Email: {$row['email']}\n";
}

echo "\nStudents with advisors:\n";
$stmt = $pdo->query('SELECT u.id, u.name, u.email, u.advisor_id, a.name as advisor_name FROM users u LEFT JOIN users a ON u.advisor_id = a.id WHERE u.role = "student" AND u.advisor_id IS NOT NULL');
while ($row = $stmt->fetch()) {
    echo "Student ID: {$row['id']}, Name: {$row['name']}, Advisor: {$row['advisor_name']} (ID: {$row['advisor_id']})\n";
}

echo "\nAll students:\n";
$stmt = $pdo->query('SELECT id, name, email, advisor_id FROM users WHERE role = "student" ORDER BY id');
while ($row = $stmt->fetch()) {
    echo "Student ID: {$row['id']}, Name: {$row['name']}, Advisor ID: {$row['advisor_id']}\n";
}
