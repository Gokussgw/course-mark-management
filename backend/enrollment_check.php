<?php
require_once 'vendor/autoload.php';

try {
    $pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== COURSE ENROLLMENT CHECK ===\n\n";

    // Get all courses
    $courses = $pdo->query('SELECT id, code, name FROM courses ORDER BY id')->fetchAll();
    echo "Total courses in database: " . count($courses) . "\n\n";

    // Check each course for enrollments
    foreach ($courses as $course) {
        $stmt = $pdo->prepare('
            SELECT COUNT(*) as count, 
                   GROUP_CONCAT(u.name ORDER BY u.name) as student_names
            FROM enrollments e 
            LEFT JOIN users u ON e.student_id = u.id 
            WHERE e.course_id = ?
        ');
        $stmt->execute([$course['id']]);
        $result = $stmt->fetch();

        echo "Course {$course['id']}: {$course['code']} - {$course['name']}\n";
        echo "  Students enrolled: {$result['count']}\n";

        if ($result['count'] > 0) {
            echo "  Students: " . ($result['student_names'] ?: 'Unknown') . "\n";
        } else {
            echo "  ❌ NO STUDENTS ENROLLED\n";
        }
        echo "\n";
    }

    // Summary
    $summary = $pdo->query('
        SELECT 
            COUNT(DISTINCT course_id) as courses_with_students,
            COUNT(*) as total_enrollments,
            COUNT(DISTINCT student_id) as unique_students
        FROM enrollments
    ')->fetch();

    echo "=== SUMMARY ===\n";
    echo "Courses with students: {$summary['courses_with_students']} / " . count($courses) . "\n";
    echo "Total enrollments: {$summary['total_enrollments']}\n";
    echo "Unique students enrolled: {$summary['unique_students']}\n";

    // Check for courses without enrollments
    $empty_courses = $pdo->query('
        SELECT c.id, c.code, c.name 
        FROM courses c 
        LEFT JOIN enrollments e ON c.id = e.course_id 
        WHERE e.course_id IS NULL
        ORDER BY c.id
    ')->fetchAll();

    if (count($empty_courses) > 0) {
        echo "\n❌ COURSES WITHOUT STUDENTS:\n";
        foreach ($empty_courses as $course) {
            echo "  - Course {$course['id']}: {$course['code']} - {$course['name']}\n";
        }
    } else {
        echo "\n✅ All courses have at least one student enrolled!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
