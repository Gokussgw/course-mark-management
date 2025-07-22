<?php
// Test mark update and notification sending

$host = 'localhost';
$dbname = 'course_mark_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Include notification utilities
    require_once __DIR__ . '/src/utils/NotificationUtils.php';

    echo "Testing notification system...\n\n";

    // First, let's check what courses and students exist
    echo "Available courses:\n";
    $courses = $pdo->query("SELECT id, code, name FROM courses LIMIT 5");
    while ($course = $courses->fetch()) {
        echo "ID: {$course['id']}, Code: {$course['code']}, Name: {$course['name']}\n";
    }

    echo "\nAvailable students:\n";
    $students = $pdo->query("SELECT id, name, email FROM users WHERE role = 'student' LIMIT 5");
    while ($student = $students->fetch()) {
        echo "ID: {$student['id']}, Name: {$student['name']}, Email: {$student['email']}\n";
    }

    echo "\nAvailable lecturers:\n";
    $lecturers = $pdo->query("SELECT id, name, email FROM users WHERE role = 'lecturer' LIMIT 5");
    while ($lecturer = $lecturers->fetch()) {
        echo "ID: {$lecturer['id']}, Name: {$lecturer['name']}, Email: {$lecturer['email']}\n";
    }

    // Test sending a notification
    echo "\n--- Testing notification sending ---\n";
    
    // Get first available course, student, and lecturer
    $courseResult = $pdo->query("SELECT id FROM courses LIMIT 1")->fetch();
    $studentResult = $pdo->query("SELECT id FROM users WHERE role = 'student' LIMIT 1")->fetch();
    $lecturerResult = $pdo->query("SELECT id FROM users WHERE role = 'lecturer' LIMIT 1")->fetch();

    if ($courseResult && $studentResult && $lecturerResult) {
        $courseId = $courseResult['id'];
        $studentId = $studentResult['id'];
        $lecturerId = $lecturerResult['id'];

        echo "Testing with Course ID: $courseId, Student ID: $studentId, Lecturer ID: $lecturerId\n";

        // Test the notification function directly
        $result = sendMarkUpdateNotification($pdo, $courseId, $studentId, $lecturerId, 'assignment', 85);
        
        if ($result) {
            echo "✅ Notification sent successfully!\n";
            
            // Check if notification was actually inserted
            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$studentId]);
            $notification = $stmt->fetch();
            
            if ($notification) {
                echo "✅ Notification found in database:\n";
                echo "  Type: {$notification['type']}\n";
                echo "  Content: {$notification['content']}\n";
                echo "  Created: {$notification['created_at']}\n";
            } else {
                echo "❌ No notification found in database\n";
            }
        } else {
            echo "❌ Failed to send notification\n";
        }
    } else {
        echo "❌ Missing required data (course, student, or lecturer)\n";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
