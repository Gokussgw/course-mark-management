<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

echo "=== Complete Admin Dashboard Test ===\n\n";

// Step 1: Login to get token
echo "1. Logging in as admin...\n";
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

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $loginData = json_decode($response, true);
    $token = $loginData['token'];
    echo "✅ Login successful\n";
    echo "Token: " . substr($token, 0, 30) . "...\n\n";
    
    // Step 2: Test admin stats endpoint
    echo "2. Testing /api/admin/stats...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:3000/api/admin/stats');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $statsResponse = curl_exec($ch);
    $statsCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Response Code: $statsCode\n";
    if ($statsCode === 200) {
        $statsData = json_decode($statsResponse, true);
        echo "✅ Stats API working\n";
        echo "Courses: " . ($statsData['totalCourses'] ?? 'N/A') . "\n";
        echo "Users: " . count($statsData['usersByRole'] ?? []) . " roles\n\n";
    } else {
        echo "❌ Stats API failed\n";
        echo "Response: $statsResponse\n\n";
    }
    
    // Step 3: Test admin users endpoint
    echo "3. Testing /api/admin/users...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:3000/api/admin/users');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $usersResponse = curl_exec($ch);
    $usersCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Response Code: $usersCode\n";
    if ($usersCode === 200) {
        $usersData = json_decode($usersResponse, true);
        echo "✅ Users API working\n";
        echo "Total users: " . count($usersData) . "\n";
        echo "Sample user: " . ($usersData[0]['name'] ?? 'N/A') . "\n\n";
    } else {
        echo "❌ Users API failed\n";
        echo "Response: $usersResponse\n\n";
    }
    
    echo "🎯 SUMMARY:\n";
    echo "Login: ✅ Working\n";
    echo "Stats API: " . ($statsCode === 200 ? "✅ Working" : "❌ Failed ($statsCode)") . "\n";
    echo "Users API: " . ($usersCode === 200 ? "✅ Working" : "❌ Failed ($usersCode)") . "\n";
    echo "\nTo fix the frontend:\n";
    echo "1. Login as admin@example.com / admin123\n";
    echo "2. Navigate to http://localhost:8081/admin/dashboard\n";
    echo "3. Dashboard should now load real data\n";
    
} else {
    echo "❌ Login failed with code: $httpCode\n";
    echo "Response: $response\n";
}
?>
