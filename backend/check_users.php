<?php
$pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');
$stmt = $pdo->query('SELECT id, name, email, role FROM users LIMIT 10');
echo "Users in database:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, Name: {$row['name']}, Email: {$row['email']}, Role: {$row['role']}\n";
}
?>
