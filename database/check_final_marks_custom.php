<?php
$host = 'localhost';
$dbname = 'course_mark_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Checking database structure...\n\n";

    // Check if final_marks_custom exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'final_marks_custom'");
    if ($stmt->rowCount() > 0) {
        echo "✅ final_marks_custom table exists\n";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE final_marks_custom");
        $columns = $stmt->fetchAll();
        echo "\nColumns in final_marks_custom:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']}) {$column['Null']} {$column['Default']}\n";
        }
        
        // Check sample data
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM final_marks_custom");
        $result = $stmt->fetch();
        echo "\nRecords in final_marks_custom: " . $result['count'] . "\n";
        
        if ($result['count'] > 0) {
            $stmt = $pdo->query("SELECT * FROM final_marks_custom LIMIT 1");
            $sample = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "\nSample record:\n";
            foreach ($sample as $key => $value) {
                echo "- $key: $value\n";
            }
        }
    } else {
        echo "❌ final_marks_custom table does NOT exist\n";
        
        // Check other tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll();
        echo "\nExisting tables:\n";
        foreach ($tables as $table) {
            echo "- " . $table[0] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
