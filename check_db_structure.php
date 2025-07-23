<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Database Structure Check ===\n";

// Check what tables exist
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Tables: " . implode(', ', $tables) . "\n\n";

// Check final_marks_custom structure
if (in_array('final_marks_custom', $tables)) {
    echo "final_marks_custom table structure:\n";
    $stmt = $pdo->query('DESCRIBE final_marks_custom');
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }

    echo "\nSample data from final_marks_custom for course_id = 5:\n";
    $stmt = $pdo->prepare('SELECT * FROM final_marks_custom WHERE course_id = 5 LIMIT 2');
    $stmt->execute();
    $sample = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($sample) {
        foreach ($sample as $row) {
            echo "Student ID: {$row['student_id']}\n";
            foreach ($row as $key => $value) {
                echo "  $key: $value\n";
            }
            echo "\n";
        }
    } else {
        echo "No data found for course_id = 5\n";
    }
} else {
    echo "final_marks_custom table does not exist\n";
}
