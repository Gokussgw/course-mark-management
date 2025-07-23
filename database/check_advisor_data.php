<?php
$host = 'localhost';
$dbname = 'course_mark_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Checking advisor-student relationships...\n\n";

    // Check advisor users
    $stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE role = 'advisor'");
    $advisors = $stmt->fetchAll();
    echo "Advisors:\n";
    foreach ($advisors as $advisor) {
        echo "- ID: {$advisor['id']}, Name: {$advisor['name']}, Email: {$advisor['email']}\n";
    }

    echo "\n";

    // Check students with advisors
    $stmt = $pdo->query("SELECT id, name, email, advisor_id FROM users WHERE role = 'student' AND advisor_id IS NOT NULL");
    $students = $stmt->fetchAll();
    echo "Students with advisors:\n";
    foreach ($students as $student) {
        echo "- ID: {$student['id']}, Name: {$student['name']}, Advisor ID: {$student['advisor_id']}\n";
    }

    echo "\n";

    // Check final_marks_custom data for advisee students
    if (!empty($students)) {
        $studentIds = array_column($students, 'id');
        $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';

        $stmt = $pdo->prepare("
            SELECT fm.student_id, u.name as student_name, u.advisor_id, 
                   fm.course_id, c.code as course_code, fm.letter_grade, fm.gpa
            FROM final_marks_custom fm
            JOIN users u ON fm.student_id = u.id
            JOIN courses c ON fm.course_id = c.id
            WHERE fm.student_id IN ($placeholders)
            ORDER BY u.advisor_id, u.name, c.code
        ");
        $stmt->execute($studentIds);
        $marks = $stmt->fetchAll();

        echo "Final marks for advisee students:\n";
        foreach ($marks as $mark) {
            echo "- Student: {$mark['student_name']} (ID: {$mark['student_id']}, Advisor: {$mark['advisor_id']}) - Course: {$mark['course_code']} - Grade: {$mark['letter_grade']} (GPA: {$mark['gpa']})\n";
        }
    }

    echo "\n";

    // Check if we have any advisor with ID 3 (commonly used for testing)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE id = 3 AND role = 'advisor'");
    $stmt->execute();
    $advisorExists = $stmt->fetch();
    echo "Advisor with ID 3 exists: " . ($advisorExists['count'] > 0 ? "YES" : "NO") . "\n";
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
