<?php
$pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');
$stmt = $pdo->query('SELECT email, password FROM users WHERE role = "student" LIMIT 2');
while ($row = $stmt->fetch()) {
    echo "Email: {$row['email']}, Password (first 20): " . substr($row['password'], 0, 20) . "\n";
}
