<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=course_mark_management;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Check password verification for a known user
    $email = 'emma.thompson@university.edu';
    $testPassword = 'password';

    $stmt = $pdo->prepare('SELECT id, name, email, password FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        echo "User found: {$user['name']} ({$user['email']})\n";
        echo "Password hash: " . substr($user['password'], 0, 50) . "...\n";
        echo "Password verification result: " . (password_verify($testPassword, $user['password']) ? 'SUCCESS' : 'FAILED') . "\n";

        // Try a few common passwords
        $commonPasswords = ['password', 'password123', 'student123', 'emma123'];
        echo "\nTrying common passwords:\n";
        foreach ($commonPasswords as $pwd) {
            $result = password_verify($pwd, $user['password']) ? 'SUCCESS' : 'FAILED';
            echo "  '$pwd': $result\n";
        }
    } else {
        echo "User not found!\n";
    }

    // Also check advisor
    echo "\n--- Checking advisor account ---\n";
    $stmt = $pdo->prepare('SELECT id, name, email, password FROM users WHERE email = ?');
    $stmt->execute(['advisor1@example.com']);
    $advisor = $stmt->fetch();

    if ($advisor) {
        echo "Advisor found: {$advisor['name']} ({$advisor['email']})\n";
        echo "Password verification result: " . (password_verify('password', $advisor['password']) ? 'SUCCESS' : 'FAILED') . "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
