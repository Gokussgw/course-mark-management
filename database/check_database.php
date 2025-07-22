<?php
$pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Available Tables:\n";
foreach($tables as $table) {
    echo "- $table\n";
}

echo "\nChecking final_marks_custom table structure:\n";
$stmt = $pdo->query('DESCRIBE final_marks_custom');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($columns as $col) {
    echo $col['Field'] . ' | ' . $col['Type'] . "\n";
}

echo "\nSample data from final_marks_custom:\n";
$stmt = $pdo->query('SELECT * FROM final_marks_custom LIMIT 3');
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($data as $row) {
    print_r($row);
}
?>
