<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

echo "=== Testing Comprehensive API Response ===\n\n";

// Simulate the comprehensive API call
$url = 'http://localhost:3000/api/advisee-reports/comprehensive';
$jwt_token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJjb3Vyc2UtbWFuYWdlbWVudCIsImF1ZCI6ImNvdXJzZS1tYW5hZ2VtZW50IiwiaWF0IjoxNzM3NzI4NDI0LCJuYmYiOjE3Mzc3Mjg0MjQsImV4cCI6MTczNzgxNDgyNCwiZGF0YSI6eyJ1c2VyX2lkIjoxMCwicm9sZSI6ImFkdmlzb3IiLCJuYW1lIjoiRHIuIFNhcmFoIENsYXJrIn19.jA8-qAKJjP6wBuOxuEEG30tEeEygUw8aW2Uf-9Z5q84';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $jwt_token,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";

if ($response) {
    $data = json_decode($response, true);

    if ($data && isset($data['data'])) {
        echo "Number of advisees: " . count($data['data']) . "\n\n";

        foreach ($data['data'] as $advisee) {
            echo "👤 {$advisee['name']} ({$advisee['matric_number']}):\n";
            echo "   Overall GPA: " . ($advisee['overall_gpa'] ?? 'N/A') . "\n";
            echo "   Risk Indicators: " . json_encode($advisee['risk_indicators'] ?? []) . "\n";
            echo "   Risk Indicator Count: " . count($advisee['risk_indicators'] ?? []) . "\n";

            // Simulate frontend getRiskLevel function
            $riskCount = count($advisee['risk_indicators'] ?? []);
            if ($riskCount >= 3) $riskLevel = 'High';
            elseif ($riskCount >= 2) $riskLevel = 'Medium';
            elseif ($riskCount >= 1) $riskLevel = 'Low';
            else $riskLevel = 'None';

            echo "   Frontend Risk Level: $riskLevel\n";
            echo "\n";
        }
    } else {
        echo "Error in API response:\n";
        echo $response . "\n";
    }
} else {
    echo "Failed to get response from API\n";
}
