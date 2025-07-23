<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');
    $stmt = $pdo->query('DESCRIBE enrollments');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Enrollments table structure:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . ' | ' . $col['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
