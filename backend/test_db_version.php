<?php
$pdo = new PDO('mysql:host=localhost;dbname=course_mark_management;charset=utf8', 'root', '');
$stmt = $pdo->query('SELECT VERSION() as version');
$result = $stmt->fetch();
echo "Database Version: " . $result['version'] . "\n";

// Let's also test a simple LIMIT query
echo "\nTesting simple LIMIT query...\n";
try {
    $limit = 5;
    $offset = 0;

    // Test with direct values first
    $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'student' ORDER BY id LIMIT 5 OFFSET 0");
    $results = $stmt->fetchAll();
    echo "✅ Direct LIMIT/OFFSET works, returned " . count($results) . " rows\n";

    // Test with prepared statement and integer parameters
    echo "\nTesting prepared statement with integer parameters...\n";
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'student' ORDER BY id LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    $results = $stmt->fetchAll();
    echo "✅ Prepared statement with integers works, returned " . count($results) . " rows\n";

    // Test with string parameters (this should cause the error)
    echo "\nTesting prepared statement with string parameters...\n";
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'student' ORDER BY id LIMIT ? OFFSET ?");
    $stmt->execute(["5", "0"]);  // Pass as strings
    $results = $stmt->fetchAll();
    echo "✅ Prepared statement with strings works, returned " . count($results) . " rows\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
