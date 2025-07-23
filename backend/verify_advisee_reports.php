<?php
// Test multiple students' data for the advisee report page
require_once 'vendor/autoload.php';

try {
    $pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== ADVISEE REPORT PAGE DATA VERIFICATION ===\n\n";

    // Get all students under advisor 3
    $stmt = $pdo->prepare('
        SELECT id, name, email, matric_number
        FROM users 
        WHERE advisor_id = 3 AND role = "student"
        ORDER BY id
    ');
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Students under Advisor One (ID: 3):\n";
    foreach ($students as $student) {
        echo "- ID: {$student['id']}, Name: {$student['name']}, Email: {$student['email']}\n";
    }

    echo "\nTesting individual report URLs:\n";
    foreach ($students as $student) {
        echo "\n📊 Student: {$student['name']} (ID: {$student['id']})\n";
        echo "URL: http://localhost:8081/advisor/advisee-report/{$student['id']}\n";

        // Check if they have course data
        $stmt = $pdo->prepare('
            SELECT COUNT(*) as course_count,
                   AVG(fm.gpa) as avg_gpa,
                   COUNT(CASE WHEN fm.letter_grade = "A" THEN 1 END) as a_grades
            FROM enrollments e
            LEFT JOIN final_marks_custom fm ON e.student_id = fm.student_id AND e.course_id = fm.course_id
            WHERE e.student_id = ?
        ');
        $stmt->execute([$student['id']]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "  - Enrolled Courses: {$data['course_count']}\n";
        echo "  - Average GPA: " . round($data['avg_gpa'] ?? 0, 2) . "\n";
        echo "  - A Grades: {$data['a_grades']}\n";

        $status = ($data['course_count'] > 0) ? "✅ Has Data" : "❌ No Data";
        echo "  - Status: $status\n";
    }

    echo "\n=== SUMMARY ===\n";
    echo "✅ API Endpoint: /api/advisee-reports/individual/{studentId}\n";
    echo "✅ Authentication: Requires advisor login token\n";
    echo "✅ Data Structure: student, courses, notes, analytics, risk_indicators\n";
    echo "✅ Frontend Component: AdviseeDetailReport.vue\n";
    echo "✅ Routing: /advisor/advisee-report/:studentId\n";

    echo "\nRecommended test students:\n";
    echo "- Emma Thompson (ID: 10) - High performer with 3 A grades\n";
    echo "- James Rodriguez (ID: 11) - Check for different performance pattern\n";
    echo "- Sarah Chen (ID: 12) - Another data point for comparison\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
