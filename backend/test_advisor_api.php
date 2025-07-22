<?php
// Test the advisor dashboard API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/advisor-dashboard-api.php?action=advisees');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer fake_token_for_testing'
]);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $result\n";
