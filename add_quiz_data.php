<?php
require_once 'backend/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable('backend');
$dotenv->load();

try {
    $pdo = new PDO('mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
    
    echo "=== ALL ASSESSMENTS ===\n";
    $stmt = $pdo->query('SELECT * FROM assessments ORDER BY course_id, date');
    $assessments = $stmt->fetchAll();
    foreach ($assessments as $assessment) {
        echo "Course {$assessment['course_id']}: {$assessment['name']} ({$assessment['type']}) - {$assessment['weightage']}% - Date: {$assessment['date']}\n";
    }
    
    // Let's add a quiz to course 1 to show variety
    echo "\n=== ADDING A QUIZ TO CS101 ===\n";
    $stmt = $pdo->prepare("
        INSERT INTO assessments (course_id, name, type, weightage, max_mark, is_final_exam, date) 
        VALUES (1, 'Quiz 1', 'quiz', 5.00, 50.00, 0, '2025-09-30')
    ");
    $stmt->execute();
    
    $quizId = $pdo->lastInsertId();
    echo "Added Quiz 1 with ID: $quizId\n";
    
    // Add a mark for this quiz for student 4
    $stmt = $pdo->prepare("
        INSERT INTO marks (student_id, assessment_id, mark) 
        VALUES (4, ?, 42.00)
    ");
    $stmt->execute([$quizId]);
    
    echo "Added mark for student 4: 42/50 on Quiz 1\n";
    
    // Update the weightages to make them add up to 100%
    // Assignment: 10% -> 10%
    // Quiz: 5%
    // Midterm: 30% -> 25%  
    // Final: 60% -> 60%
    $stmt = $pdo->prepare("UPDATE assessments SET weightage = 25.00 WHERE id = 2"); // Midterm
    $stmt->execute();
    
    echo "Updated Midterm weightage to 25%\n";
    echo "Total weightage: Assignment(10%) + Quiz(5%) + Midterm(25%) + Final(60%) = 100%\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
