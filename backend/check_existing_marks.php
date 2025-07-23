<?php
$pdo = new PDO('mysql:host=localhost;dbname=course_mark_management;charset=utf8', 'root', '');

echo "Current marks for Student ID 4, Course ID 1:\n";
$stmt = $pdo->prepare("SELECT * FROM final_marks_custom WHERE student_id = ? AND course_id = ?");
$stmt->execute([4, 1]);
$marks = $stmt->fetch();

if ($marks) {
    echo "Assignment: {$marks['assignment_mark']}\n";
    echo "Quiz: {$marks['quiz_mark']}\n";
    echo "Test: {$marks['test_mark']}\n";
    echo "Final Exam: {$marks['final_exam_mark']}\n";
    echo "Updated at: {$marks['updated_at']}\n";
} else {
    echo "No marks found\n";
}
