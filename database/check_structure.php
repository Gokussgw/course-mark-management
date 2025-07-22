<?php
$pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');
$stmt = $pdo->query('DESCRIBE final_marks_custom');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Final Marks Custom Table Structure:\n";
echo "Field | Type | Null | Default\n";
echo "------|------|------|--------\n";

foreach ($columns as $col) {
    echo $col['Field'] . ' | ' . $col['Type'] . ' | ' . $col['Null'] . ' | ' . $col['Default'] . PHP_EOL;
}
