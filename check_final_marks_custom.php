<?php
require_once 'backend/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable('backend');
$dotenv->load();

try {
    $pdo = new PDO('mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);

    echo "=== FINAL_MARKS_CUSTOM TABLE STRUCTURE ===\n";
    $stmt = $pdo->query('DESCRIBE final_marks_custom');
    while ($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }

    echo "\n=== SAMPLE DATA FROM FINAL_MARKS_CUSTOM ===\n";
    $stmt = $pdo->query('SELECT * FROM final_marks_custom LIMIT 10');
    $data = $stmt->fetchAll();
    foreach ($data as $row) {
        print_r($row);
        echo "\n";
    }

    echo "\n=== COUNT OF RECORDS ===\n";
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM final_marks_custom');
    $count = $stmt->fetch();
    echo "Total records: " . $count['total'] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
