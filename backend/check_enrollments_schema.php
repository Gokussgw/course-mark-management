<?php
$pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');
$stmt = $pdo->query('DESCRIBE enrollments');
echo "Enrollments table structure:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%s | %s | %s\n", 
        str_pad($row['Field'], 20),
        str_pad($row['Type'], 20), 
        $row['Null'] === 'YES' ? 'NULL' : 'NOT NULL'
    );
}
?>
