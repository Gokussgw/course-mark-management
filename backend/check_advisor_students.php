<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check advisor ID
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute(['advisor1@example.com']);
    $advisor = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Advisor ID: " . ($advisor['id'] ?? 'not found') . "\n";

    if ($advisor) {
        // Check students under this advisor
        $stmt2 = $pdo->prepare('SELECT id, name, email, advisor_id FROM users WHERE role = "student" AND advisor_id = ?');
        $stmt2->execute([$advisor['id']]);
        $students = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        echo "Students under advisor:\n";
        foreach ($students as $student) {
            echo "- ID: {$student['id']}, Name: {$student['name']}, Email: {$student['email']}\n";
        }

        if (empty($students)) {
            echo "No students found under advisor\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
