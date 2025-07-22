<?php
// Script to create lecturer feedback table
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Creating lecturer feedback table...\n";

try {
    $host = $_ENV['DB_HOST'];
    $dbname = $_ENV['DB_NAME'];
    $username = $_ENV['DB_USER'];
    $password = $_ENV['DB_PASS'];

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/../database/lecturer_feedback.sql');

    // Execute the SQL
    $pdo->exec($sql);

    echo "✅ Lecturer feedback table created successfully!\n";

    // Verify the table was created
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM lecturer_feedback");
    $result = $stmt->fetch();
    echo "📊 Sample feedback records: {$result['count']}\n";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
