<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "course_mark_management";

try {
    // Create connection
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Checking existing marks management tables...\n";

    // Check if marks table exists
    $marksTableExists = $pdo->query("SHOW TABLES LIKE 'marks'")->rowCount() > 0;
    $assessmentsTableExists = $pdo->query("SHOW TABLES LIKE 'assessments'")->rowCount() > 0;

    if ($marksTableExists && $assessmentsTableExists) {
        echo "✓ Marks and assessments tables already exist\n";
    } else {
        echo "❌ Required tables not found. Please run the main schema.sql first\n";
        exit;
    }

    // Create our custom component marks table for simplified marks management
    $createComponentMarksTable = "
        CREATE TABLE IF NOT EXISTS component_marks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            course_id INT NOT NULL,
            assignment_mark DECIMAL(5,2) DEFAULT 0.00,
            quiz_mark DECIMAL(5,2) DEFAULT 0.00,
            test_mark DECIMAL(5,2) DEFAULT 0.00,
            assignment_percentage DECIMAL(5,2) GENERATED ALWAYS AS (assignment_mark * 0.25) STORED,
            quiz_percentage DECIMAL(5,2) GENERATED ALWAYS AS (quiz_mark * 0.20) STORED,
            test_percentage DECIMAL(5,2) GENERATED ALWAYS AS (test_mark * 0.25) STORED,
            component_total DECIMAL(5,2) GENERATED ALWAYS AS (assignment_percentage + quiz_percentage + test_percentage) STORED,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            UNIQUE KEY unique_student_course (student_id, course_id)
        )
    ";

    $pdo->exec($createComponentMarksTable);
    echo "✓ Component marks table created\n";

    // Create final marks table for exam and total grades  
    $createFinalMarksTable = "
        CREATE TABLE IF NOT EXISTS final_marks_custom (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            course_id INT NOT NULL,
            final_exam_mark DECIMAL(5,2) DEFAULT 0.00,
            final_exam_percentage DECIMAL(5,2) DEFAULT 0.00,
            component_total DECIMAL(5,2) DEFAULT 0.00,
            final_grade DECIMAL(5,2) DEFAULT 0.00,
            letter_grade VARCHAR(2) DEFAULT 'F',
            gpa DECIMAL(3,2) DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            UNIQUE KEY unique_student_course (student_id, course_id)
        )
    ";

    $pdo->exec($createFinalMarksTable);
    echo "✓ Final marks table created\n";

    // Insert sample marks data
    echo "Adding sample marks data...\n";

    // Get enrolled students and their courses
    $enrolledStudents = $pdo->query("
        SELECT DISTINCT e.student_id, e.course_id, u.name as student_name, c.name as course_name 
        FROM enrollments e 
        INNER JOIN users u ON e.student_id = u.id 
        INNER JOIN courses c ON e.course_id = c.id 
        WHERE u.role = 'student'
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($enrolledStudents)) {
        echo "❌ No enrolled students found for sample data\n";
        exit;
    }

    // Insert sample component marks
    foreach ($enrolledStudents as $enrollment) {
        // Generate random marks
        $assignmentMark = rand(60, 95);
        $quizMark = rand(65, 90);
        $testMark = rand(55, 85);
        $finalExamMark = rand(50, 90);

        // Insert component marks
        $insertComponentMark = $pdo->prepare("
            INSERT INTO component_marks (student_id, course_id, assignment_mark, quiz_mark, test_mark) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                assignment_mark = VALUES(assignment_mark),
                quiz_mark = VALUES(quiz_mark),
                test_mark = VALUES(test_mark)
        ");
        $insertComponentMark->execute([
            $enrollment['student_id'],
            $enrollment['course_id'],
            $assignmentMark,
            $quizMark,
            $testMark
        ]);

        // Calculate component total (70%)
        $componentTotal = ($assignmentMark * 0.25) + ($quizMark * 0.20) + ($testMark * 0.25);

        // Calculate letter grade and GPA
        $finalGrade = $componentTotal + ($finalExamMark * 0.30);
        $letterGrade = 'F';
        $gpa = 0.00;

        if ($finalGrade >= 80) {
            $letterGrade = 'A';
            $gpa = 4.00;
        } elseif ($finalGrade >= 75) {
            $letterGrade = 'A-';
            $gpa = 3.67;
        } elseif ($finalGrade >= 70) {
            $letterGrade = 'B+';
            $gpa = 3.33;
        } elseif ($finalGrade >= 65) {
            $letterGrade = 'B';
            $gpa = 3.00;
        } elseif ($finalGrade >= 60) {
            $letterGrade = 'B-';
            $gpa = 2.67;
        } elseif ($finalGrade >= 55) {
            $letterGrade = 'C+';
            $gpa = 2.33;
        } elseif ($finalGrade >= 50) {
            $letterGrade = 'C';
            $gpa = 2.00;
        }

        // Insert final marks record
        $insertFinalMark = $pdo->prepare("
            INSERT INTO final_marks_custom (student_id, course_id, final_exam_mark, final_exam_percentage, component_total, final_grade, letter_grade, gpa) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                final_exam_mark = VALUES(final_exam_mark),
                final_exam_percentage = VALUES(final_exam_percentage),
                component_total = VALUES(component_total),
                final_grade = VALUES(final_grade),
                letter_grade = VALUES(letter_grade),
                gpa = VALUES(gpa)
        ");
        $insertFinalMark->execute([
            $enrollment['student_id'],
            $enrollment['course_id'],
            $finalExamMark,
            $finalExamMark * 0.30, // Calculate exam percentage
            $componentTotal,
            $finalGrade,
            $letterGrade,
            $gpa
        ]);

        echo "✓ Added marks for {$enrollment['student_name']} in {$enrollment['course_name']}\n";
    }

    echo "✓ Sample marks data added successfully\n";
    echo "✅ Marks management setup complete!\n";

    // Show summary
    $componentCount = $pdo->query("SELECT COUNT(*) as count FROM component_marks")->fetch(PDO::FETCH_ASSOC)['count'];
    $finalCount = $pdo->query("SELECT COUNT(*) as count FROM final_marks_custom")->fetch(PDO::FETCH_ASSOC)['count'];

    echo "\n📊 Summary:\n";
    echo "   - Component marks records: $componentCount\n";
    echo "   - Final marks records: $finalCount\n";
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
