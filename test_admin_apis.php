<?php
echo "=== Testing Admin API Endpoints ===\n\n";

// Test /api/admin/stats endpoint
echo "Testing http://localhost:3000/api/admin/stats\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:3000/api/admin/stats');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
if ($response) {
    $data = json_decode($response, true);
    if ($data) {
        echo "✅ Stats API working\n";
        echo "Response: " . substr($response, 0, 200) . "...\n\n";
    } else {
        echo "❌ Invalid JSON response\n";
        echo "Raw response: " . substr($response, 0, 200) . "\n\n";
    }
} else {
    echo "❌ No response from stats API\n\n";
}

// Test /api/admin/users endpoint  
echo "Testing http://localhost:3000/api/admin/users\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:3000/api/admin/users');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
if ($response) {
    $data = json_decode($response, true);
    if ($data) {
        echo "✅ Users API working\n";
        echo "Users count: " . count($data) . "\n";
        echo "Sample user: " . (isset($data[0]['name']) ? $data[0]['name'] : 'N/A') . "\n\n";
    } else {
        echo "❌ Invalid JSON response\n";
        echo "Raw response: " . substr($response, 0, 200) . "\n\n";
    }
} else {
    echo "❌ No response from users API\n\n";
}

echo "=== Backend Server Status ===\n";
echo "Make sure the backend server is running on port 3000\n";
echo "Run: php -S localhost:3000 -t backend/ backend/index.php\n";
?>
