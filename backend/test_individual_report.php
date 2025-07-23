<?php
// Debug token and API call
require_once 'vendor/autoload.php';

try {
    $pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Testing Individual Advisee Report API ===\n\n";

    // First, let's login as advisor to get a fresh token
    $loginData = [
        'email' => 'advisor1@example.com',
        'password' => 'password'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/auth/login');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $loginResponse = curl_exec($ch);
    $loginData = json_decode($loginResponse, true);

    if (isset($loginData['token'])) {
        echo "✅ Login successful\n";
        $token = $loginData['token'];
        echo "Token: " . substr($token, 0, 50) . "...\n\n";

        // Now test the individual report API
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/advisee-reports/individual/10');
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);

        $reportResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        echo "HTTP Code: $httpCode\n";
        echo "Response: " . $reportResponse . "\n\n";

        if ($httpCode === 200) {
            $reportData = json_decode($reportResponse, true);
            if ($reportData['success']) {
                echo "✅ API call successful!\n";
                echo "Student: " . $reportData['data']['student']['name'] . "\n";
                echo "GPA: " . $reportData['data']['student']['overall_gpa'] . "\n";
                echo "Courses: " . count($reportData['data']['courses']) . "\n";
            } else {
                echo "❌ API call failed: " . $reportData['error'] . "\n";
            }
        } else {
            echo "❌ HTTP Error $httpCode\n";
        }
    } else {
        echo "❌ Login failed: " . $loginResponse . "\n";
    }

    curl_close($ch);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
