<?php
// Create a test course for the lecturer
$pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');

$courseData = [
    'code' => 'CS101',
    'name' => 'Introduction to Computer Science', 
    'lecturer_id' => 2, // lecturer1@example.com
    'semester' => 'Fall',
    'academic_year' => '2025-2026'
];

// Check if course already exists
$stmt = $pdo->prepare('SELECT id FROM courses WHERE code = ?');
$stmt->execute([$courseData['code']]);
if ($stmt->fetch()) {
    echo "Course {$courseData['code']} already exists\n";
} else {
    // Create the course
    $stmt = $pdo->prepare('
        INSERT INTO courses (code, name, lecturer_id, semester, academic_year)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $courseData['code'],
        $courseData['name'], 
        $courseData['lecturer_id'],
        $courseData['semester'],
        $courseData['academic_year']
    ]);
    
    $courseId = $pdo->lastInsertId();
    echo "Created course {$courseData['code']} with ID: $courseId\n";
    
    // Add some student enrollments
    $students = [10, 11, 12, 13]; // Student IDs from our database
    foreach ($students as $studentId) {
        $stmt = $pdo->prepare('
            INSERT INTO enrollments (student_id, course_id, academic_year, semester)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([$studentId, $courseId, $courseData['academic_year'], $courseData['semester']]);
    }
    
    echo "Enrolled " . count($students) . " students in the course\n";
}
?>
