<?php
$host = 'localhost';
$dbname = 'course_mark_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Creating marks management tables...\n";

    // Create marks table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS marks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            course_id INT NOT NULL,
            assessment_type ENUM('assignment', 'quiz', 'test', 'final_exam') NOT NULL,
            marks DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            max_marks DECIMAL(5,2) NOT NULL DEFAULT 100.00,
            percentage DECIMAL(5,2) GENERATED ALWAYS AS ((marks / max_marks) * 100) STORED,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by INT NULL,
            updated_by INT NULL,
            UNIQUE KEY unique_student_course_assessment (student_id, course_id, assessment_type)
        )
    ");
    echo "✓ Marks table created\n";

    // Create final_marks table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS final_marks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            course_id INT NOT NULL,
            component_marks DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            final_exam_marks DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            final_marks DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            grade CHAR(2) NOT NULL DEFAULT '',
            gpa_points DECIMAL(3,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            calculated_by INT NULL,
            UNIQUE KEY unique_student_course (student_id, course_id)
        )
    ");
    echo "✓ Final marks table created\n";

    // Insert sample marks
    $pdo->exec("
        INSERT IGNORE INTO marks (student_id, course_id, assessment_type, marks, max_marks, created_by) VALUES
        (4, 1, 'assignment', 85.0, 100.0, 2),
        (4, 1, 'quiz', 92.0, 100.0, 2),
        (4, 1, 'test', 78.0, 100.0, 2),
        (4, 1, 'final_exam', 88.0, 100.0, 2),
        (5, 1, 'assignment', 76.0, 100.0, 2),
        (5, 1, 'quiz', 84.0, 100.0, 2),
        (5, 1, 'test', 82.0, 100.0, 2),
        (5, 1, 'final_exam', 79.0, 100.0, 2),
        (6, 1, 'assignment', 68.0, 100.0, 2),
        (6, 1, 'quiz', 75.0, 100.0, 2),
        (6, 1, 'test', 70.0, 100.0, 2),
        (6, 1, 'final_exam', 72.0, 100.0, 2)
    ");
    echo "✓ Sample marks inserted\n";

    // Calculate and insert final marks for the sample data
    $students = [
        ['student_id' => 4, 'course_id' => 1],
        ['student_id' => 5, 'course_id' => 1],
        ['student_id' => 6, 'course_id' => 1]
    ];

    foreach ($students as $student) {
        // Get marks for this student
        $stmt = $pdo->prepare("
            SELECT assessment_type, percentage 
            FROM marks 
            WHERE student_id = ? AND course_id = ?
        ");
        $stmt->execute([$student['student_id'], $student['course_id']]);
        $marks_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $marks = ['assignment' => 0, 'quiz' => 0, 'test' => 0, 'final_exam' => 0];
        foreach ($marks_data as $mark) {
            $marks[$mark['assessment_type']] = (float)$mark['percentage'];
        }

        // Calculate final marks
        $component_marks = ($marks['assignment'] * 0.25) + ($marks['quiz'] * 0.20) + ($marks['test'] * 0.25);
        $final_marks = ($component_marks * 0.70) + ($marks['final_exam'] * 0.30);

        $grade = 'F';
        $gpa_points = 0.00;
        if ($final_marks >= 80) {
            $grade = 'A';
            $gpa_points = 4.00;
        } elseif ($final_marks >= 70) {
            $grade = 'B';
            $gpa_points = 3.00;
        } elseif ($final_marks >= 60) {
            $grade = 'C';
            $gpa_points = 2.00;
        } elseif ($final_marks >= 50) {
            $grade = 'D';
            $gpa_points = 1.00;
        }

        // Insert final marks
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO final_marks 
            (student_id, course_id, component_marks, final_exam_marks, final_marks, grade, gpa_points, calculated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $student['student_id'],
            $student['course_id'],
            round($component_marks, 2),
            $marks['final_exam'],
            round($final_marks, 2),
            $grade,
            $gpa_points,
            2
        ]);
    }

    echo "✓ Final marks calculated and inserted\n";

    // Check results
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM marks");
    $result = $stmt->fetch();
    echo "📊 Total marks records: " . $result['count'] . "\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM final_marks");
    $result = $stmt->fetch();
    echo "📊 Total final marks records: " . $result['count'] . "\n";

    echo "\n✅ Marks database setup completed successfully!\n";
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
