<?php
$pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');
$stmt = $pdo->query('SELECT id, name, email, role FROM users WHERE role = "student" LIMIT 5');
echo "Available students:\n";
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']}, Name: {$row['name']}, Email: {$row['email']}\n";
}
?>
