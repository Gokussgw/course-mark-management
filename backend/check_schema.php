<?php
// Database configuration
$host = 'localhost';
$dbname = 'course_mark_management';
$username = 'root';
$password = '';

// Connect to database
$pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

echo "=== CHECKING TABLE STRUCTURE ===\n\n";

// Check assessments table structure
echo "ASSESSMENTS TABLE STRUCTURE:\n";
$stmt = $pdo->query("DESCRIBE assessments");
$columns = $stmt->fetchAll();
foreach ($columns as $column) {
    echo "- {$column['Field']} ({$column['Type']})\n";
}

echo "\nMARKS TABLE STRUCTURE:\n";
$stmt = $pdo->query("DESCRIBE marks");
$columns = $stmt->fetchAll();
foreach ($columns as $column) {
    echo "- {$column['Field']} ({$column['Type']})\n";
}

echo "\nCOURSES TABLE STRUCTURE:\n";
$stmt = $pdo->query("DESCRIBE courses");
$columns = $stmt->fetchAll();
foreach ($columns as $column) {
    echo "- {$column['Field']} ({$column['Type']})\n";
}
