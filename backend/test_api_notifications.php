<?php
// Test the actual marks API to see if notifications are triggered

$url = 'http://localhost:8000/marks-api.php';

// Test data - use the same IDs we confirmed exist, but with DIFFERENT marks
$data = [
    'action' => 'save_marks',
    'student_id' => 4,
    'course_id' => 1,
    'lecturer_id' => 2,
    'marks' => [
        'assignment' => 92,  // Changed from 88
        'quiz' => 85,       // Changed from 82
        'test' => 88,       // Changed from 85
        'final_exam' => 93  // Changed from 90
    ]
];

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data),
    ],
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "API Response:\n";
echo $result . "\n\n";

// Check if new notifications were created
$pdo = new PDO('mysql:host=localhost;dbname=course_mark_management;charset=utf8', 'root', '');
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
$stmt->execute([4]); // Student ID 4

echo "Recent notifications for student ID 4:\n";
while ($notification = $stmt->fetch()) {
    echo "- {$notification['created_at']}: {$notification['content']}\n";
}
?>
