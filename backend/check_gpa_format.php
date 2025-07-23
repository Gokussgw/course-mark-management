<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=course_mark_management;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // First check table structure
    echo "=== Table Structure ===\n";
    $stmt = $pdo->query('DESCRIBE final_marks_custom');
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }

    echo "\n=== Sample Data ===\n";
    $stmt = $pdo->query('SELECT student_id, course_id, final_grade, gpa FROM final_marks_custom LIMIT 5');
    $results = $stmt->fetchAll();

    foreach ($results as $row) {
        echo "Student ID: {$row['student_id']}, Course ID: {$row['course_id']}, Final Grade: {$row['final_grade']}, GPA: {$row['gpa']}\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
