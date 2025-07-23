<?php
// Test the actual marks API to see if notifications are triggered

$url = 'http://localhost:8000/marks-api.php';

// Test data - use the same IDs we confirmed exist, with DIFFERENT marks to trigger notifications
$data = [
    'action' => 'save_marks',
    'student_id' => 4,
    'course_id' => 1,
    'lecturer_id' => 2,
    'marks' => [
        'assignment' => 95,  // Changed significantly to trigger notification
        'quiz' => 89,       // Changed significantly to trigger notification
        'test' => 91,       // Changed significantly to trigger notification
        'final_exam' => 96  // Changed significantly to trigger notification
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
