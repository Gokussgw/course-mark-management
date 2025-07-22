<?php
$pdo = new PDO('mysql:host=localhost;dbname=course_mark_management;charset=utf8', 'root', '');
$stmt = $pdo->query('SHOW TABLES LIKE "notifications"');
if ($stmt->rowCount() > 0) {
    echo "Notifications table exists\n";
    $desc = $pdo->query('DESCRIBE notifications');
    while ($row = $desc->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo "Notifications table does NOT exist\n";
    // Let's also check all tables
    echo "\nAll tables in database:\n";
    $all_tables = $pdo->query('SHOW TABLES');
    while ($table = $all_tables->fetch()) {
        echo $table[0] . "\n";
    }
}
?>
