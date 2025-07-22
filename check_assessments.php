<?php
require_once 'backend/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable('backend');
$dotenv->load();

try {
    $pdo = new PDO('mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
    
    echo "=== ASSESSMENTS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query('DESCRIBE assessments');
    while ($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    echo "\n=== SAMPLE ASSESSMENTS ===\n";
    $stmt = $pdo->query('SELECT * FROM assessments LIMIT 3');
    $assessments = $stmt->fetchAll();
    foreach ($assessments as $assessment) {
        print_r($assessment);
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
