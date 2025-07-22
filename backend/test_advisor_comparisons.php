<?php
// Test advisor comparisons endpoint
$loginData = [
    'email' => 'advisor1@example.com',
    'password' => 'password'
];

// First login to get token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/api/auth/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login HTTP Code: $httpCode\n";

if ($httpCode === 200) {
    $loginResponse = json_decode($result, true);
    $token = $loginResponse['token'] ?? null;

    if ($token) {
        echo "Testing advisor comparisons API with token...\n";

        // Test the advisor comparisons API
        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, 'http://localhost:8080/api/comparisons/advisor/students');
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            'Content-Type: application/json'
        ]);

        $result2 = curl_exec($ch2);
        $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);

        echo "Comparisons API HTTP Code: $httpCode2\n";
        echo "Comparisons API Response: $result2\n";
    }
} else {
    echo "Login failed: $result\n";
}
