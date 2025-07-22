<?php
// Test script to check real database data and create CSV export function
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== Testing Real Database Data ===\n";

try {
    // Connect to the database
    $host = $_ENV['DB_HOST'];
    $dbname = $_ENV['DB_NAME'];
    $username = $_ENV['DB_USER'];
    $password = $_ENV['DB_PASS'];

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $pdo = new PDO($dsn, $username, $password, $options);
    echo "✅ Database connected successfully!\n\n";

    // 1. Check courses
    echo "1. Checking courses...\n";
    $stmt = $pdo->query('SELECT * FROM courses');
    $courses = $stmt->fetchAll();

    if (count($courses) > 0) {
        echo "Found " . count($courses) . " courses:\n";
        foreach ($courses as $course) {
            echo "  - ID: {$course['id']}, Code: {$course['code']}, Name: {$course['name']}\n";
        }
    } else {
        echo "❌ No courses found. Need to insert sample data.\n";

        // Insert sample courses
        echo "Inserting sample courses...\n";
        $insertCourse = $pdo->prepare('INSERT INTO courses (code, name, lecturer_id) VALUES (?, ?, ?)');

        $sampleCourses = [
            ['CS101', 'Introduction to Computer Science', 2],
            ['CS102', 'Data Structures', 2],
            ['MATH101', 'Calculus I', 2]
        ];

        foreach ($sampleCourses as $course) {
            $insertCourse->execute($course);
        }

        echo "✅ Sample courses inserted!\n";

        // Re-fetch courses
        $stmt = $pdo->query('SELECT * FROM courses');
        $courses = $stmt->fetchAll();
    }
    echo "\n";

    // 2. Check students
    echo "2. Checking students...\n";
    $stmt = $pdo->query('SELECT * FROM users WHERE role = "student"');
    $students = $stmt->fetchAll();

    if (count($students) > 0) {
        echo "Found " . count($students) . " students:\n";
        foreach ($students as $student) {
            echo "  - ID: {$student['id']}, Name: {$student['name']}, Email: {$student['email']}\n";
        }
    } else {
        echo "❌ No students found. Need to insert sample students.\n";

        // Insert sample students
        echo "Inserting sample students...\n";
        $insertUser = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');

        $sampleStudents = [
            ['Alice Johnson', 'alice@student.com', password_hash('password', PASSWORD_DEFAULT), 'student'],
            ['Bob Smith', 'bob@student.com', password_hash('password', PASSWORD_DEFAULT), 'student'],
            ['Carol Davis', 'carol@student.com', password_hash('password', PASSWORD_DEFAULT), 'student'],
            ['David Wilson', 'david@student.com', password_hash('password', PASSWORD_DEFAULT), 'student'],
            ['Eva Brown', 'eva@student.com', password_hash('password', PASSWORD_DEFAULT), 'student']
        ];

        foreach ($sampleStudents as $student) {
            $insertUser->execute($student);
        }

        echo "✅ Sample students inserted!\n";

        // Re-fetch students
        $stmt = $pdo->query('SELECT * FROM users WHERE role = "student"');
        $students = $stmt->fetchAll();
    }
    echo "\n";

    // 3. Check enrollments
    echo "3. Checking enrollments...\n";
    $stmt = $pdo->query('SELECT * FROM enrollments');
    $enrollments = $stmt->fetchAll();

    if (count($enrollments) === 0 && count($courses) > 0 && count($students) > 0) {
        echo "No enrollments found. Creating sample enrollments...\n";

        $insertEnrollment = $pdo->prepare('INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)');

        // Enroll all students in first course
        $firstCourse = $courses[0];
        foreach ($students as $student) {
            $insertEnrollment->execute([$student['id'], $firstCourse['id']]);
        }

        echo "✅ Sample enrollments created!\n";

        // Re-fetch enrollments
        $stmt = $pdo->query('SELECT * FROM enrollments');
        $enrollments = $stmt->fetchAll();
    }

    echo "Found " . count($enrollments) . " enrollments\n\n";

    // 4. Check assessments
    echo "4. Checking assessments...\n";
    $stmt = $pdo->query('SELECT * FROM assessments');
    $assessments = $stmt->fetchAll();

    if (count($assessments) === 0 && count($courses) > 0) {
        echo "No assessments found. Creating sample assessments...\n";

        $insertAssessment = $pdo->prepare('INSERT INTO assessments (course_id, name, type, weightage, max_mark) VALUES (?, ?, ?, ?, ?)');

        $firstCourse = $courses[0];
        $sampleAssessments = [
            [$firstCourse['id'], 'Assignment 1', 'assignment', 25.0, 100],
            [$firstCourse['id'], 'Quiz 1', 'quiz', 20.0, 100],
            [$firstCourse['id'], 'Test 1', 'midterm', 25.0, 100],
            [$firstCourse['id'], 'Final Exam', 'final_exam', 30.0, 100]
        ];

        foreach ($sampleAssessments as $assessment) {
            $insertAssessment->execute($assessment);
        }

        echo "✅ Sample assessments created!\n";

        // Re-fetch assessments
        $stmt = $pdo->query('SELECT * FROM assessments');
        $assessments = $stmt->fetchAll();
    }

    echo "Found " . count($assessments) . " assessments\n\n";

    // 5. Check marks
    echo "5. Checking marks...\n";
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM marks');
    $markCount = $stmt->fetch()['count'];

    if ($markCount === 0 && count($assessments) > 0 && count($students) > 0) {
        echo "No marks found. Creating sample marks...\n";

        $insertMark = $pdo->prepare('INSERT INTO marks (student_id, assessment_id, mark) VALUES (?, ?, ?)');

        // Create random marks for each student in each assessment
        foreach ($students as $student) {
            foreach ($assessments as $assessment) {
                // Generate random marks between 60-95
                $marks = rand(60, 95);
                $insertMark->execute([$student['id'], $assessment['id'], $marks]);
            }
        }

        echo "✅ Sample marks created!\n";
        $markCount = count($students) * count($assessments);
    }

    echo "Found $markCount marks in database\n\n";

    // 6. Create comprehensive data export function
    echo "6. Creating comprehensive data export...\n";

    if (count($courses) > 0) {
        $firstCourse = $courses[0];
        $courseId = $firstCourse['id'];

        echo "Exporting data for course: {$firstCourse['code']} - {$firstCourse['name']}\n";

        // Get complete data with calculations
        $sql = "
            SELECT 
                u.id as student_id,
                u.name as student_name,
                u.email as student_email,
                c.code as course_code,
                c.name as course_name,
                a.name as assessment_name,
                a.type as assessment_type,
                a.weightage as assessment_weight,
                a.max_mark as assessment_max_marks,
                m.mark as marks_obtained,
                ROUND((m.mark / a.max_mark) * a.weightage, 2) as weighted_marks
            FROM users u
            JOIN enrollments e ON u.id = e.student_id
            JOIN courses c ON e.course_id = c.id
            JOIN assessments a ON c.id = a.course_id
            LEFT JOIN marks m ON u.id = m.student_id AND a.id = m.assessment_id
            WHERE c.id = ? AND u.role = 'student'
            ORDER BY u.name, a.type, a.name
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$courseId]);
        $results = $stmt->fetchAll();

        // Process data for CSV export
        $studentData = [];
        $assessmentTypes = [];

        foreach ($results as $row) {
            $studentId = $row['student_id'];
            $assessmentType = $row['assessment_type'];

            if (!isset($studentData[$studentId])) {
                $studentData[$studentId] = [
                    'name' => $row['student_name'],
                    'email' => $row['student_email'],
                    'marks' => [],
                    'total_weighted' => 0
                ];
            }

            $studentData[$studentId]['marks'][$assessmentType] = [
                'obtained' => $row['marks_obtained'] ?? 0,
                'max' => $row['assessment_max_marks'],
                'weight' => $row['assessment_weight'],
                'weighted' => $row['weighted_marks'] ?? 0
            ];

            $studentData[$studentId]['total_weighted'] += $row['weighted_marks'] ?? 0;

            if (!in_array($assessmentType, $assessmentTypes)) {
                $assessmentTypes[] = $assessmentType;
            }
        }

        // Generate CSV content
        $csvLines = [];

        // Header information
        $csvLines[] = "Course Information";
        $csvLines[] = "Course Code," . $firstCourse['code'];
        $csvLines[] = "Course Name," . $firstCourse['name'];
        $csvLines[] = "Export Date," . date('Y-m-d H:i:s');
        $csvLines[] = "Total Students," . count($studentData);
        $csvLines[] = "";

        // Column headers
        $headers = ["Student Name", "Email"];
        foreach ($assessmentTypes as $type) {
            $headers[] = ucfirst(str_replace('_', ' ', $type)) . " (%)";
            $headers[] = ucfirst(str_replace('_', ' ', $type)) . " Weighted";
        }
        $headers[] = "Final Mark";
        $headers[] = "Grade";
        $csvLines[] = implode(",", $headers);

        // Student data rows
        foreach ($studentData as $student) {
            $row = [$student['name'], $student['email']];

            foreach ($assessmentTypes as $type) {
                if (isset($student['marks'][$type])) {
                    $mark = $student['marks'][$type];
                    $percentage = $mark['max'] > 0 ? round(($mark['obtained'] / $mark['max']) * 100, 1) : 0;
                    $row[] = $percentage;
                    $row[] = round($mark['weighted'], 2);
                } else {
                    $row[] = "0";
                    $row[] = "0";
                }
            }

            $finalMark = round($student['total_weighted'], 2);
            $grade = $finalMark >= 80 ? 'A' : ($finalMark >= 70 ? 'B' : ($finalMark >= 60 ? 'C' : ($finalMark >= 50 ? 'D' : 'F')));

            $row[] = $finalMark;
            $row[] = $grade;

            $csvLines[] = implode(",", $row);
        }

        // Statistics
        $csvLines[] = "";
        $csvLines[] = "Statistics";

        if (count($studentData) > 0) {
            $totalMarks = array_sum(array_column($studentData, 'total_weighted'));
            $averageMark = round($totalMarks / count($studentData), 2);
            $csvLines[] = "Class Average," . $averageMark;

            foreach ($assessmentTypes as $type) {
                $typeTotal = 0;
                $typeCount = 0;
                foreach ($studentData as $student) {
                    if (isset($student['marks'][$type])) {
                        $typeTotal += $student['marks'][$type]['weighted'];
                        $typeCount++;
                    }
                }
                if ($typeCount > 0) {
                    $typeAverage = round($typeTotal / $typeCount, 2);
                    $csvLines[] = ucfirst(str_replace('_', ' ', $type)) . " Average," . $typeAverage;
                }
            }
        }

        // Save CSV file
        $csvContent = implode("\n", $csvLines);
        $filename = "exports/" . $firstCourse['code'] . "_marks_" . date('Y-m-d_H-i-s') . ".csv";

        // Create exports directory if it doesn't exist
        if (!is_dir('exports')) {
            mkdir('exports', 0755, true);
        }

        file_put_contents($filename, $csvContent);

        echo "✅ CSV export created: $filename\n";
        echo "Sample data (first 5 lines):\n";
        $lines = explode("\n", $csvContent);
        for ($i = 0; $i < min(5, count($lines)); $i++) {
            echo "  " . $lines[$i] . "\n";
        }

        echo "\nTotal lines in CSV: " . count($lines) . "\n";
        echo "File size: " . number_format(strlen($csvContent)) . " bytes\n";
    }
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test completed ===\n";
