<?php
$host = 'localhost';
$dbname = 'course_mark_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Checking advisor credentials...\n\n";

    $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = 'advisor1@example.com'");
    $stmt->execute();
    $advisor = $stmt->fetch();

    if ($advisor) {
        echo "Found advisor:\n";
        echo "- ID: {$advisor['id']}\n";
        echo "- Name: {$advisor['name']}\n";
        echo "- Email: {$advisor['email']}\n";
        echo "- Password hash: {$advisor['password']}\n\n";
        
        // Test password verification
        $testPasswords = ['password123', 'password', 'admin', 'admin123'];
        
        foreach ($testPasswords as $testPass) {
            if (password_verify($testPass, $advisor['password'])) {
                echo "✅ Password '$testPass' is CORRECT!\n";
                break;
            } else {
                echo "❌ Password '$testPass' is incorrect\n";
            }
        }
    } else {
        echo "❌ Advisor account not found\n";
    }

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
