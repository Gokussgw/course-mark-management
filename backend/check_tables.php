<?php
try {
    $pdo = new PDO('sqlite:../database/marks.db');
    $stmt = $pdo->query('SELECT name FROM sqlite_master WHERE type="table"');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo 'Available tables:' . PHP_EOL;
    foreach ($tables as $table) {
        echo '- ' . $table . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
