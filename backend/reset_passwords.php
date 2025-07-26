<?php
// Simple password hasher and database updater
$pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');

// Let's set simple passwords for testing
$users = [
    ['email' => 'admin@example.com', 'password' => 'admin123'],
    ['email' => 'lecturer1@example.com', 'password' => 'lecturer123']
];

foreach ($users as $user) {
    $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
    $stmt->execute([$hashedPassword, $user['email']]);
    echo "Updated password for {$user['email']} to '{$user['password']}'\n";
}

echo "\nYou can now log in with:\n";
echo "Admin: admin@example.com / admin123\n";
echo "Lecturer: lecturer1@example.com / lecturer123\n";
?>
