<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Admin Users Check ===\n";
$stmt = $pdo->query('SELECT id, name, email, role FROM users WHERE role = "admin"');
$admins = $stmt->fetchAll();

if (empty($admins)) {
    echo "No admin users found!\n";
    echo "Creating a test admin user...\n";
    
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt->execute(['Admin User', 'admin@example.com', $hashedPassword, 'admin']);
    
    echo "Created admin user: admin@example.com / admin123\n";
} else {
    foreach ($admins as $admin) {
        echo "ID: {$admin['id']}, Name: {$admin['name']}, Email: {$admin['email']}\n";
    }
}

// Test login endpoint
echo "\n=== Testing Admin Login ===\n";
$loginData = json_encode([
    'email' => 'admin@example.com',
    'password' => 'admin123'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login Response Code: $httpCode\n";
if ($response) {
    $data = json_decode($response, true);
    if (isset($data['token'])) {
        echo "✅ Login successful\n";
        echo "Token: " . substr($data['token'], 0, 50) . "...\n";
        
        // Test admin stats with this token
        echo "\n=== Testing Admin Stats with Token ===\n";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:3000/api/admin/stats');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $data['token']
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $statsResponse = curl_exec($ch);
        $statsCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "Stats Response Code: $statsCode\n";
        if ($statsResponse) {
            echo "Stats Response: " . substr($statsResponse, 0, 200) . "...\n";
        }
    } else {
        echo "❌ Login failed\n";
        echo "Response: $response\n";
    }
}
?>
