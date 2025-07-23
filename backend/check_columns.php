<?php
try {
    $pdo = new PDO('sqlite:../database/marks.db');
    $stmt = $pdo->query('SELECT * FROM final_marks_custom LIMIT 1');
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($data) {
        echo 'final_marks_custom columns:' . PHP_EOL;
        foreach (array_keys($data) as $column) {
            echo '- ' . $column . PHP_EOL;
        }
        echo PHP_EOL . 'Sample data:' . PHP_EOL;
        print_r($data);
    } else {
        echo 'No data in final_marks_custom table' . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
