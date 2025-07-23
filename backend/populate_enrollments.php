<?php
require_once 'vendor/autoload.php';

try {
    $pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Adding Enrollment Data for Empty Courses ===\n\n";

    // Get all students
    $students = $pdo->query('SELECT id, name FROM users WHERE role = "student" ORDER BY id')->fetchAll();
    $student_count = count($students);
    echo "Available students: $student_count\n";

    // Define courses that need enrollments (from our check)
    $empty_courses = [3, 4, 5, 6, 7]; // CS301, CS302, CS401, CS402, MATH201

    foreach ($empty_courses as $course_id) {
        // Get course info
        $stmt = $pdo->prepare('SELECT code, name FROM courses WHERE id = ?');
        $stmt->execute([$course_id]);
        $course = $stmt->fetch();

        echo "\nAdding students to Course $course_id: {$course['code']} - {$course['name']}\n";

        // Randomly select 3-4 students for each course
        $num_students = rand(3, 4);
        $selected_students = array_rand($students, $num_students);

        // Ensure it's always an array
        if (!is_array($selected_students)) {
            $selected_students = [$selected_students];
        }

        $enrolled_count = 0;
        foreach ($selected_students as $student_index) {
            $student = $students[$student_index];

            // Check if already enrolled
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND course_id = ?');
            $stmt->execute([$student['id'], $course_id]);

            if ($stmt->fetchColumn() == 0) {
                // Add enrollment
                $stmt = $pdo->prepare('INSERT INTO enrollments (student_id, course_id, academic_year, semester, created_at) VALUES (?, ?, "2024/2025", "Semester 1", NOW())');
                $stmt->execute([$student['id'], $course_id]);
                $enrolled_count++;
                echo "  - Added {$student['name']} (ID: {$student['id']})\n";
            } else {
                echo "  - {$student['name']} already enrolled\n";
            }
        }

        echo "  ✅ Enrolled $enrolled_count students in {$course['code']}\n";
    }

    echo "\n=== Updated Enrollment Summary ===\n";

    // Check final status
    $stmt = $pdo->query('
        SELECT c.id, c.code, c.name, COUNT(e.student_id) as enrollment_count
        FROM courses c
        LEFT JOIN enrollments e ON c.id = e.course_id
        GROUP BY c.id, c.code, c.name
        ORDER BY c.id
    ');
    $results = $stmt->fetchAll();

    foreach ($results as $course) {
        echo "Course {$course['id']}: {$course['code']} - {$course['name']}: {$course['enrollment_count']} students\n";
    }

    // Final summary
    $stmt = $pdo->query('
        SELECT 
            COUNT(DISTINCT course_id) as courses_with_students,
            COUNT(*) as total_enrollments,
            COUNT(DISTINCT student_id) as unique_students
        FROM enrollments
    ');
    $summary = $stmt->fetch();

    $total_courses = count($results);
    $courses_without_students = $total_courses - $summary['courses_with_students'];

    echo "\n=== FINAL SUMMARY ===\n";
    echo "✅ Courses with students: {$summary['courses_with_students']} / $total_courses\n";
    echo "✅ Total enrollments: {$summary['total_enrollments']}\n";
    echo "✅ Unique students enrolled: {$summary['unique_students']}\n";

    if ($courses_without_students == 0) {
        echo "\n🎉 SUCCESS: All courses now have enrolled students!\n";
    } else {
        echo "\n⚠️  $courses_without_students course(s) still without students\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
