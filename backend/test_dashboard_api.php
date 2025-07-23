<?php
// Test the new student dashboard API endpoints

echo "Testing Student Dashboard API Endpoints...\n\n";

$baseUrl = 'http://localhost:8000/marks-api.php';
$studentId = 4; // Test with student ID 4

// Test 1: Student Courses
echo "1. Testing student dashboard courses:\n";
$url1 = "$baseUrl?action=student_dashboard_courses&student_id=$studentId";
$response1 = file_get_contents($url1);
echo "Response: $response1\n\n";

// Test 2: Student Assessments  
echo "2. Testing student dashboard assessments:\n";
$url2 = "$baseUrl?action=student_dashboard_assessments&student_id=$studentId";
$response2 = file_get_contents($url2);
echo "Response: $response2\n\n";

// Test 3: Student Performance
echo "3. Testing student dashboard performance:\n";
$url3 = "$baseUrl?action=student_dashboard_performance&student_id=$studentId";
$response3 = file_get_contents($url3);
echo "Response: $response3\n\n";

// Validate JSON responses
echo "4. Validating JSON responses:\n";
$data1 = json_decode($response1, true);
$data2 = json_decode($response2, true);
$data3 = json_decode($response3, true);

if (json_last_error() === JSON_ERROR_NONE && isset($data1['courses'])) {
    echo "✅ Courses API: Valid JSON, " . count($data1['courses']) . " courses found\n";
    if (!empty($data1['courses'])) {
        $course = $data1['courses'][0];
        echo "   Sample course: {$course['code']} - {$course['name']} (Average: {$course['average']}%)\n";
    }
} else {
    echo "❌ Courses API: Invalid JSON or no courses data\n";
}

if (json_last_error() === JSON_ERROR_NONE && isset($data2['assessments'])) {
    echo "✅ Assessments API: Valid JSON, " . count($data2['assessments']) . " assessments found\n";
} else {
    echo "❌ Assessments API: Invalid JSON or no assessments data\n";
}

if (json_last_error() === JSON_ERROR_NONE && isset($data3['performance'])) {
    echo "✅ Performance API: Valid JSON\n";
    echo "   Chart labels: " . implode(', ', $data3['performance']['labels'] ?? []) . "\n";
    echo "   Datasets: " . count($data3['performance']['datasets'] ?? []) . " courses\n";
} else {
    echo "❌ Performance API: Invalid JSON or no performance data\n";
}
