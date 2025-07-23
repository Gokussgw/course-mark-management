<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Checking final_marks_custom table structure ===\n\n";

$stmt = $pdo->query('DESCRIBE final_marks_custom');
$columns = $stmt->fetchAll();
foreach ($columns as $column) {
    echo $column['Field'] . ' - ' . $column['Type'] . "\n";
}

echo "\n=== Sample data from final_marks_custom ===\n";
$stmt = $pdo->query('SELECT assignment_mark, assignment_percentage, quiz_mark, quiz_percentage, test_mark, test_percentage FROM final_marks_custom LIMIT 5');
$samples = $stmt->fetchAll();
foreach ($samples as $i => $sample) {
    echo "Row " . ($i + 1) . ":\n";
    echo "  Assignment: {$sample['assignment_mark']} (raw) / {$sample['assignment_percentage']}% (percentage)\n";
    echo "  Quiz: {$sample['quiz_mark']} (raw) / {$sample['quiz_percentage']}% (percentage)\n";
    echo "  Test: {$sample['test_mark']} (raw) / {$sample['test_percentage']}% (percentage)\n\n";
}

echo "=== Issue Identified ===\n";
echo "The comprehensive API is using percentage columns that contain raw score percentages.\n";
echo "These are different from the raw marks used in individual reports.\n";
echo "The percentage values are very low, causing all students to appear at risk.\n";
