<?php
// Test the API with null student_id to see the exact error

// Test URL
$url = 'http://localhost:8080/api/marks/student_course_detail';

// Test with null student_id
$data = array(
    'student_id' => null,
    'course_id' => 1
);

// Initialize cURL
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($data))
));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// Execute request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

echo "Testing with null student_id:\n";
echo "HTTP Code: " . $http_code . "\n";
echo "Response: " . $response . "\n";

// Try to decode JSON if it's valid
if ($response) {
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "\nDecoded Response:\n";
        print_r($decoded);
    }
}
