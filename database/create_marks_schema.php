<?php
$host = 'localhost';
$dbname = 'course_mark_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents('05_marks_schema.sql');

    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }

    echo "\n✅ Marks database schema created successfully!\n";

    // Test query to verify tables were created
    $stmt = $pdo->query("SHOW TABLES LIKE 'marks'");
    if ($stmt->rowCount() > 0) {
        echo "✅ 'marks' table created\n";
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'final_marks'");
    if ($stmt->rowCount() > 0) {
        echo "✅ 'final_marks' table created\n";
    }

    // Check if sample data was inserted
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM marks");
    $result = $stmt->fetch();
    echo "📊 Sample marks inserted: " . $result['count'] . " records\n";
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
