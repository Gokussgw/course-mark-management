<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Reset Admin Password ===\n";

// Update admin password to a known value
$newPassword = 'admin123';
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ? AND role = ?');
$result = $stmt->execute([$hashedPassword, 'admin@example.com', 'admin']);

if ($result) {
    echo "✅ Admin password updated successfully\n";
    echo "Email: admin@example.com\n";
    echo "Password: $newPassword\n";
    
    // Test login immediately
    echo "\n=== Testing Login ===\n";
    $loginData = json_encode([
        'email' => 'admin@example.com',
        'password' => $newPassword
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/auth/login');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Response Code: $httpCode\n";
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['token'])) {
            echo "✅ Login successful!\n";
            echo "Token: " . substr($data['token'], 0, 50) . "...\n";
            echo "User: {$data['user']['name']} ({$data['user']['role']})\n";
        } else {
            echo "❌ Login still failing\n";
            echo "Response: $response\n";
        }
    }
} else {
    echo "❌ Failed to update password\n";
}
?>
