<?php
// Update lecturer password to "password"
$pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');

$hashedPassword = password_hash('password', PASSWORD_DEFAULT);
$stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
$stmt->execute([$hashedPassword, 'lecturer1@example.com']);

echo "Updated password for lecturer1@example.com to 'password'\n";
echo "\nYou can now log in with:\n";
echo "Lecturer: lecturer1@example.com / password\n";
echo "Admin: admin@example.com / admin123\n";
?>
