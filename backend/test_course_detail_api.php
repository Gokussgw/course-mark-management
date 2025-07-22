<?php
// Test the new student course detail API endpoint

echo "Testing Student Course Detail API...\n\n";

$baseUrl = 'http://localhost:8000/marks-api.php';
$studentId = 4;
$courseId = 1;

$url = "$baseUrl?action=student_course_detail&student_id=$studentId&course_id=$courseId";
echo "Testing URL: $url\n\n";

$response = file_get_contents($url);
echo "Response:\n$response\n\n";

// Validate JSON
$data = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✅ Valid JSON response\n";
    echo "Course: " . ($data['course']['code'] ?? 'N/A') . " - " . ($data['course']['name'] ?? 'N/A') . "\n";
    echo "Lecturer: " . ($data['course']['lecturer_name'] ?? 'N/A') . "\n";
    echo "Final Grade: " . ($data['marks']['final_grade'] ?? 'N/A') . "%\n";
    echo "Letter Grade: " . ($data['marks']['letter_grade'] ?? 'N/A') . "\n";
    echo "Class Rank: " . ($data['ranking']['rank_text'] ?? 'N/A') . "\n";
    echo "Assessments: " . count($data['assessments'] ?? []) . " found\n";
} else {
    echo "❌ Invalid JSON response\n";
}
?>
