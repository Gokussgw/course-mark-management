<?php
// Test the student course detail API endpoint via Slim framework

// Test URL - using the Slim route
$url = 'http://localhost:8080/api/marks/student_course_detail';

// Sample student ID (you can change this)
$student_id = 4; // Student One
$course_id = 1;  // CS101

// Prepare POST data
$data = array(
    'student_id' => $student_id,
    'course_id' => $course_id
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
