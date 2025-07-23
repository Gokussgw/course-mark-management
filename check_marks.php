<?php
require_once 'backend/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable('backend');
$dotenv->load();

try {
    $pdo = new PDO('mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);

    echo "=== MARKS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query('DESCRIBE marks');
    while ($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }

    echo "\n=== SAMPLE MARKS ===\n";
    $stmt = $pdo->query('SELECT * FROM marks LIMIT 3');
    $marks = $stmt->fetchAll();
    foreach ($marks as $mark) {
        print_r($mark);
        echo "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
