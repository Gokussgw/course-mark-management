<?php
// Test the updated student_course_detail API with final_marks_custom data

// Configuration
$base_url = 'http://localhost:8000';

// First, let's login to get a token
echo "=== Testing Final Marks API ===\n";

// Login
$login_data = [
    'email' => 'student1@example.com',
    'password' => 'password'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . '/api/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($login_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$login_response = curl_exec($ch);
$login_result = json_decode($login_response, true);

if (!$login_result || !isset($login_result['token'])) {
    echo "Login failed: " . $login_response . "\n";
    exit(1);
}

$token = $login_result['token'];
echo "Login successful, got token\n";

// Test student_course_detail API with real data from final_marks_custom
$course_detail_data = [
    'student_id' => 4, // Based on our database check, student ID 4 has data
    'course_id' => 1   // Course ID 1
];

curl_setopt($ch, CURLOPT_URL, $base_url . '/api/marks/student_course_detail');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($course_detail_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);

$course_response = curl_exec($ch);
curl_close($ch);

echo "\n=== Course Detail Response ===\n";
echo $course_response . "\n";

// Parse and display structured data
$course_result = json_decode($course_response, true);

if ($course_result && $course_result['success']) {
    echo "\n=== Structured Output ===\n";
    echo "Course: " . $course_result['course']['code'] . " - " . $course_result['course']['name'] . "\n";
    echo "Lecturer: " . $course_result['course']['lecturer_name'] . "\n";
    echo "Performance: " . $course_result['performance']['overall_percentage'] . "% (Letter: " . $course_result['performance']['letter_grade'] . ", GPA: " . $course_result['performance']['gpa'] . ")\n";
    echo "Ranking: " . $course_result['ranking']['position'] . " out of " . $course_result['ranking']['total_students'] . " students\n";
    
    echo "\nAssessments:\n";
    foreach ($course_result['assessments'] as $assessment) {
        echo "- " . $assessment['assessment_name'] . ": " . $assessment['mark'] . "/100 (Weight: " . $assessment['weightage'] . "%)\n";
    }
} else {
    echo "API call failed or returned error\n";
}
?>
