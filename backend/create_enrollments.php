<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=course_mark_management;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "Creating test enrollment data...\n";

    // First, get some student IDs and course IDs
    $stmt = $pdo->query('SELECT id FROM users WHERE role = "student" LIMIT 5');
    $students = $stmt->fetchAll();

    $stmt = $pdo->query('SELECT id FROM courses LIMIT 3');
    $courses = $stmt->fetchAll();

    if (empty($students)) {
        echo "No students found in database\n";
        exit;
    }

    if (empty($courses)) {
        echo "No courses found in database\n";
        exit;
    }

    // Clear existing enrollments first
    $pdo->exec('DELETE FROM enrollments WHERE 1=1');
    echo "Cleared existing enrollments\n";

    // Create enrollments
    $stmt = $pdo->prepare('INSERT INTO enrollments (student_id, course_id, academic_year, semester) VALUES (?, ?, ?, ?)');

    $enrollmentData = [
        [$students[0]['id'], $courses[0]['id'], '2025-2026', 'Fall'],
        [$students[1]['id'], $courses[0]['id'], '2025-2026', 'Fall'],
        [$students[0]['id'], $courses[1]['id'], '2025-2026', 'Fall'],
        [$students[2]['id'], $courses[1]['id'], '2025-2026', 'Fall'],
    ];

    // Special focus on course ID 2
    if (isset($courses[1]) && $courses[1]['id'] == 2) {
        $enrollmentData[] = [$students[3]['id'], 2, '2025-2026', 'Fall'];
        if (isset($students[4])) {
            $enrollmentData[] = [$students[4]['id'], 2, '2025-2026', 'Fall'];
        }
    }

    foreach ($enrollmentData as $data) {
        $stmt->execute($data);
        echo "Created enrollment: Student {$data[0]} -> Course {$data[1]}\n";
    }

    echo "\nEnrollment data created successfully!\n";

    // Show course 2 enrollments
    echo "\n=== ENROLLMENTS FOR COURSE 2 ===\n";
    $stmt = $pdo->prepare("
        SELECT 
            e.id as enrollment_id,
            e.academic_year,
            e.semester,
            e.created_at,
            u.id as student_id,
            u.name as student_name,
            u.email as student_email,
            u.matric_number
        FROM enrollments e 
        JOIN users u ON e.student_id = u.id 
        WHERE e.course_id = 2 
        ORDER BY u.name
    ");
    $stmt->execute();
    $course2Enrollments = $stmt->fetchAll();

    if (empty($course2Enrollments)) {
        echo "No enrollments found for course ID 2\n";
    } else {
        foreach ($course2Enrollments as $enrollment) {
            echo "Enrollment ID: {$enrollment['enrollment_id']}, Student: {$enrollment['student_name']}, Email: {$enrollment['student_email']}\n";
        }
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
