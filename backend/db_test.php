<?php
// Database connection test
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Testing database connection...\n";

try {
    // Connect to the database
    $host = $_ENV['DB_HOST'];
    $dbname = $_ENV['DB_NAME'];
    $username = $_ENV['DB_USER'];
    $password = $_ENV['DB_PASS'];

    echo "Connecting to MySQL: Host=$host, DB=$dbname, User=$username\n";

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $pdo = new PDO($dsn, $username, $password, $options);

    echo "Connection successful!\n";

    // Check if users exist
    $stmt = $pdo->query('SELECT id, name, email, role FROM users');
    $users = $stmt->fetchAll();

    if (count($users) > 0) {
        echo "Found " . count($users) . " users in the database:\n";
        foreach ($users as $user) {
            echo "ID: {$user['id']}, Name: {$user['name']}, Email: {$user['email']}, Role: {$user['role']}\n";
        }
    } else {
        echo "No users found in the database. You may need to import the schema.sql file.\n";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";

    // Check if database exists
    try {
        $rootDsn = "mysql:host=$host;charset=utf8mb4";
        $rootPdo = new PDO($rootDsn, $username, $password, $options);

        echo "Can connect to MySQL but database '$dbname' might not exist.\n";
        echo "Try creating it with: CREATE DATABASE $dbname;\n";
    } catch (PDOException $rootError) {
        echo "Cannot connect to MySQL server at all: " . $rootError->getMessage() . "\n";
    }
}
