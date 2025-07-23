<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Computer Networks Course Data Check ===\n\n";

    // First, find the Computer Networks course
    $stmt = $pdo->query('SELECT id, code, name FROM courses WHERE name LIKE "%Network%" OR code LIKE "%401%"');
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($course) {
        echo "Course Found: {$course['code']} - {$course['name']} (ID: {$course['id']})\n\n";

        // Get all students enrolled in this course
        $stmt = $pdo->prepare('
            SELECT 
                u.id, u.name, u.matric_number,
                e.course_id,
                fm.assignment_mark,
                fm.assignment_percentage,
                fm.quiz_mark,
                fm.quiz_percentage,
                fm.test_mark,
                fm.test_percentage,
                fm.final_exam_mark,
                fm.final_exam_percentage,
                fm.component_total,
                fm.final_grade as overall_percentage,
                fm.letter_grade,
                fm.gpa,
                fm.created_at
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            LEFT JOIN final_marks_custom fm ON e.student_id = fm.student_id AND e.course_id = fm.course_id
            WHERE e.course_id = ?
            ORDER BY u.name
        ');
        $stmt->execute([$course['id']]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Students enrolled in {$course['code']}:\n";
        foreach ($students as $student) {
            echo "\n📊 {$student['name']} ({$student['matric_number']}):\n";

            if ($student['assignment_mark']) {
                echo "  Assignment: {$student['assignment_mark']}/100 ({$student['assignment_percentage']}%)\n";
                echo "  Quiz: {$student['quiz_mark']}/100 ({$student['quiz_percentage']}%)\n";
                echo "  Test: {$student['test_mark']}/100 ({$student['test_percentage']}%)\n";
                echo "  Final Exam: {$student['final_exam_mark']}/100 ({$student['final_exam_percentage']}%)\n";
                echo "  Overall: {$student['overall_percentage']}% ({$student['letter_grade']}, GPA: {$student['gpa']})\n";
                echo "  Component Total: {$student['component_total']}%\n";

                // Check for potential issues
                $issues = [];

                // Check if percentages match marks
                if (abs($student['assignment_mark'] - $student['assignment_percentage']) > 5) {
                    $issues[] = "Assignment mark/percentage mismatch";
                }

                if (abs($student['quiz_mark'] - $student['quiz_percentage']) > 5) {
                    $issues[] = "Quiz mark/percentage mismatch";
                }

                if (abs($student['test_mark'] - $student['test_percentage']) > 5) {
                    $issues[] = "Test mark/percentage mismatch";
                }

                if (abs($student['final_exam_mark'] - $student['final_exam_percentage']) > 5) {
                    $issues[] = "Final exam mark/percentage mismatch";
                }

                // Check component total calculation
                $expectedTotal = ($student['assignment_percentage'] * 0.25) +
                    ($student['quiz_percentage'] * 0.15) +
                    ($student['test_percentage'] * 0.30) +
                    ($student['final_exam_percentage'] * 0.30);

                if (abs($expectedTotal - $student['component_total']) > 1) {
                    $issues[] = "Component total calculation error (Expected: " . round($expectedTotal, 2) . "%)";
                }

                if (!empty($issues)) {
                    echo "  ⚠️  ISSUES FOUND:\n";
                    foreach ($issues as $issue) {
                        echo "    - $issue\n";
                    }
                }
            } else {
                echo "  ❌ No marks data found\n";
            }
        }
    } else {
        echo "Computer Networks course not found!\n";
        echo "Available courses:\n";
        $stmt = $pdo->query('SELECT id, code, name FROM courses ORDER BY id');
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($courses as $c) {
            echo "- ID: {$c['id']}, Code: {$c['code']}, Name: {$c['name']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
