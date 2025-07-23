<?php
require_once '../backend/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable('../backend');
$dotenv->load();

// Database connection
function getDbConnection()
{
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'course_mark_management';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

try {
    $pdo = getDbConnection();

    echo "Users table structure:\n";
    $stmt = $pdo->query('DESCRIBE users');
    while ($row = $stmt->fetch()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }

    echo "\nSample users with advisor role:\n";
    $stmt = $pdo->query('SELECT * FROM users WHERE role = "advisor" LIMIT 3');
    while ($row = $stmt->fetch()) {
        print_r($row);
        echo "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
