<?php
require_once '../backend/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable('../backend');
$dotenv->load();

// Database connection
function getDbConnection()
{
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'course_mark_management';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

function calculateGPA($letterGrade)
{
    switch ($letterGrade) {
        case 'A+':
        case 'A':
            return 4.0;
        case 'A-':
            return 3.7;
        case 'B+':
            return 3.3;
        case 'B':
            return 3.0;
        case 'B-':
            return 2.7;
        case 'C+':
            return 2.3;
        case 'C':
            return 2.0;
        case 'C-':
            return 1.7;
        case 'D+':
            return 1.3;
        case 'D':
            return 1.0;
        case 'F':
            return 0.0;
        default:
            return 0.0;
    }
}

function getLetterGrade($percentage)
{
    if ($percentage >= 90) return 'A';
    if ($percentage >= 85) return 'A-';
    if ($percentage >= 80) return 'B+';
    if ($percentage >= 75) return 'B';
    if ($percentage >= 70) return 'B-';
    if ($percentage >= 65) return 'C+';
    if ($percentage >= 60) return 'C';
    if ($percentage >= 55) return 'C-';
    if ($percentage >= 50) return 'D+';
    if ($percentage >= 45) return 'D';
    return 'F';
}

try {
    $pdo = getDbConnection();

    echo "Completing the academic data...\n\n";

    // First, add more courses
    echo "Adding additional courses...\n";
    $additionalCourses = [
        ['CS302', 'Software Engineering', '2025-2026', 'Spring'],
        ['CS401', 'Computer Networks', '2025-2026', 'Fall'],
        ['CS402', 'Machine Learning', '2025-2026', 'Spring'],
        ['MATH201', 'Linear Algebra', '2025-2026', 'Fall']
    ];

    foreach ($additionalCourses as $course) {
        $stmt = $pdo->prepare('
            INSERT IGNORE INTO courses (code, name, academic_year, semester, lecturer_id, created_at) 
            VALUES (?, ?, ?, ?, 2, NOW())
        ');
        $stmt->execute($course);
        echo "Added course: " . $course[0] . " - " . $course[1] . "\n";
    }

    // Get all courses
    $stmt = $pdo->query('SELECT id, code, name FROM courses ORDER BY id');
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all students with advisor_id = 3
    $stmt = $pdo->query('SELECT id, name FROM users WHERE role = "student" AND advisor_id = 3 ORDER BY id');
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nAdding enrollments for new courses...\n";
    // Add enrollments for all students to all courses
    foreach ($students as $student) {
        foreach ($courses as $course) {
            $stmt = $pdo->prepare('
                INSERT IGNORE INTO enrollments (student_id, course_id, academic_year, semester, created_at) 
                VALUES (?, ?, "2025-2026", "Fall", NOW())
            ');
            $stmt->execute([$student['id'], $course['id']]);
        }
    }

    echo "Enrollments completed.\n\n";

    // Define student performance patterns for comprehensive data
    $studentProfiles = [
        'Emma Thompson' => [ // Excellent performer
            'base_performance' => 90,
            'variation' => 5,
            'trend' => 'stable'
        ],
        'James Rodriguez' => [ // Declining performance
            'base_performance' => 75,
            'variation' => 15,
            'trend' => 'declining'
        ],
        'Sarah Chen' => [ // Improving performance
            'base_performance' => 65,
            'variation' => 10,
            'trend' => 'improving'
        ],
        'Michael Johnson' => [ // Struggling with assignments
            'base_performance' => 60,
            'variation' => 20,
            'trend' => 'stable'
        ],
        'Priya Patel' => [ // Good but inconsistent
            'base_performance' => 70,
            'variation' => 15,
            'trend' => 'stable'
        ],
        'Ahmed Al-Rashid' => [ // Average performer
            'base_performance' => 68,
            'variation' => 12,
            'trend' => 'stable'
        ],
        'Lisa Wang' => [ // At risk - very low performance
            'base_performance' => 35,
            'variation' => 15,
            'trend' => 'declining'
        ],
        'Marcus Williams' => [ // Comeback story - improving
            'base_performance' => 55,
            'variation' => 20,
            'trend' => 'improving'
        ]
    ];

    echo "Generating comprehensive marks data...\n";

    foreach ($students as $student) {
        $profile = $studentProfiles[$student['name']];
        $courseCount = 0;

        foreach ($courses as $course) {
            $courseCount++;

            // Check if record already exists
            $stmt = $pdo->prepare('SELECT id FROM final_marks_custom WHERE student_id = ? AND course_id = ?');
            $stmt->execute([$student['id'], $course['id']]);
            if ($stmt->fetch()) {
                echo "Skipping existing record for " . $student['name'] . " in " . $course['code'] . "\n";
                continue;
            }

            // Generate marks based on student profile and course progression
            $trendMultiplier = 1.0;
            if ($profile['trend'] === 'improving') {
                $trendMultiplier = 0.8 + ($courseCount * 0.1); // Starts lower, improves
            } elseif ($profile['trend'] === 'declining') {
                $trendMultiplier = 1.2 - ($courseCount * 0.1); // Starts higher, declines
            }

            $basePerf = $profile['base_performance'] * $trendMultiplier;
            $variation = $profile['variation'];

            // Generate component marks with realistic patterns
            $assignmentMark = max(0, min(100, $basePerf + rand(-$variation, $variation)));
            $quizMark = max(0, min(100, $basePerf + rand(-$variation / 2, $variation / 2)));
            $testMark = max(0, min(100, $basePerf + rand(-$variation, $variation)));
            $finalExamMark = max(0, min(100, $basePerf + rand(-$variation * 1.5, $variation)));

            // Calculate percentages (realistic weightings)
            $assignmentPercentage = ($assignmentMark / 100) * 25; // 25%
            $quizPercentage = ($quizMark / 100) * 15; // 15%
            $testPercentage = ($testMark / 100) * 30; // 30%
            $finalExamPercentage = ($finalExamMark / 100) * 30; // 30%

            $componentTotal = $assignmentPercentage + $quizPercentage + $testPercentage;
            $finalGrade = $componentTotal + $finalExamPercentage;

            $letterGrade = getLetterGrade($finalGrade);
            $gpa = calculateGPA($letterGrade);

            // Insert the record
            $stmt = $pdo->prepare('
                INSERT INTO final_marks_custom (
                    student_id, course_id, 
                    assignment_mark, assignment_percentage,
                    quiz_mark, quiz_percentage,
                    test_mark, test_percentage,
                    final_exam_mark, final_exam_percentage,
                    component_total, final_grade, letter_grade, gpa,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ');

            $stmt->execute([
                $student['id'],
                $course['id'],
                $assignmentMark,
                $assignmentPercentage,
                $quizMark,
                $quizPercentage,
                $testMark,
                $testPercentage,
                $finalExamMark,
                $finalExamPercentage,
                $componentTotal,
                $finalGrade,
                $letterGrade,
                $gpa
            ]);

            echo "Added marks for " . $student['name'] . " in " . $course['code'] . " - Grade: $letterGrade ($finalGrade%)\n";
        }
    }

    echo "\nData completion summary:\n";
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM final_marks_custom');
    echo "Total records: " . $stmt->fetch()['total'] . "\n";

    $stmt = $pdo->query('SELECT COUNT(DISTINCT student_id) as students FROM final_marks_custom');
    echo "Students with marks: " . $stmt->fetch()['students'] . "\n";

    $stmt = $pdo->query('SELECT COUNT(DISTINCT course_id) as courses FROM final_marks_custom');
    echo "Courses with marks: " . $stmt->fetch()['courses'] . "\n";

    echo "\nStudent completion rates:\n";
    $stmt = $pdo->query('
        SELECT 
            u.name,
            COUNT(fm.id) as completed_courses,
            COUNT(DISTINCT c.id) as total_available_courses,
            ROUND((COUNT(fm.id) / COUNT(DISTINCT c.id)) * 100, 1) as completion_rate
        FROM users u
        CROSS JOIN courses c
        LEFT JOIN final_marks_custom fm ON u.id = fm.student_id AND c.id = fm.course_id
        WHERE u.role = "student" AND u.advisor_id = 3
        GROUP BY u.id, u.name
        ORDER BY u.name
    ');

    while ($row = $stmt->fetch()) {
        echo $row['name'] . ": " . $row['completed_courses'] . "/" . $row['total_available_courses'] . " courses (" . $row['completion_rate'] . "%)\n";
    }

    echo "\nData completion successful! 🎉\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
