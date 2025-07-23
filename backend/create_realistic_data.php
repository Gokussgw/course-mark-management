<?php
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $host = $_ENV['DB_HOST'];
    $dbname = $_ENV['DB_NAME'];
    $username = $_ENV['DB_USER'];
    $password = $_ENV['DB_PASS'];

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "=== CREATING REALISTIC STUDENT DATA ===" . PHP_EOL;
    
    // More realistic student profiles
    $realistic_students = [
        [
            'name' => 'Emma Thompson',
            'email' => 'emma.thompson@university.edu',
            'matric_number' => 'CS2023001',
            'profile' => 'excellent_consistent' // High achiever across all components
        ],
        [
            'name' => 'James Rodriguez',
            'email' => 'james.rodriguez@university.edu', 
            'matric_number' => 'CS2023002',
            'profile' => 'strong_but_declining' // Started strong but performance declining
        ],
        [
            'name' => 'Sarah Chen',
            'email' => 'sarah.chen@university.edu',
            'matric_number' => 'CS2023003', 
            'profile' => 'average_improving' // Average student showing improvement
        ],
        [
            'name' => 'Michael Johnson',
            'email' => 'michael.johnson@university.edu',
            'matric_number' => 'CS2023004',
            'profile' => 'struggling_assignments' // Good at exams but poor at assignments
        ],
        [
            'name' => 'Priya Patel',
            'email' => 'priya.patel@university.edu',
            'matric_number' => 'CS2023005',
            'profile' => 'exam_anxiety' // Good at coursework but struggles with exams
        ],
        [
            'name' => 'Ahmed Al-Rashid',
            'email' => 'ahmed.alrashid@university.edu',
            'matric_number' => 'CS2023006',
            'profile' => 'inconsistent' // Very inconsistent performance
        ],
        [
            'name' => 'Lisa Wang',
            'email' => 'lisa.wang@university.edu',
            'matric_number' => 'CS2023007',
            'profile' => 'at_risk' // Struggling across all areas
        ],
        [
            'name' => 'Marcus Williams',
            'email' => 'marcus.williams@university.edu',
            'matric_number' => 'CS2023008',
            'profile' => 'comeback_story' // Poor start but strong improvement
        ]
    ];
    
    // Clear existing test data and add realistic students
    echo "Removing old test students..." . PHP_EOL;
    $pdo->exec("DELETE FROM final_marks_custom WHERE student_id IN (4,5,6,7,8,9)");
    $pdo->exec("DELETE FROM enrollments WHERE student_id IN (4,5,6,7,8,9)");
    $pdo->exec("DELETE FROM users WHERE id IN (4,5,6,7,8,9)");
    
    echo "Adding realistic students..." . PHP_EOL;
    foreach ($realistic_students as $student) {
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, matric_number, password, role, advisor_id, created_at) 
            VALUES (?, ?, ?, ?, 'student', 3, NOW())
        ");
        $stmt->execute([
            $student['name'], 
            $student['email'], 
            $student['matric_number'], 
            password_hash('student123', PASSWORD_DEFAULT)
        ]);
        $student_id = $pdo->lastInsertId();
        
        echo "Added: {$student['name']} (ID: $student_id)" . PHP_EOL;
        
        // Enroll in courses
        foreach ([1, 2] as $course_id) {
            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$student_id, $course_id]);
        }
        
        // Generate realistic marks based on profile
        generateRealisticMarks($pdo, $student_id, $student['profile']);
    }
    
    echo PHP_EOL . "=== REALISTIC DATA CREATED SUCCESSFULLY ===" . PHP_EOL;
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}

function generateRealisticMarks($pdo, $student_id, $profile) {
    $courses = [1, 2]; // CS101 and CS201
    
    foreach ($courses as $course_id) {
        $marks = generateMarksForProfile($profile, $course_id);
        
        $stmt = $pdo->prepare("
            INSERT INTO final_marks_custom (
                student_id, course_id, 
                assignment_mark, assignment_percentage,
                quiz_mark, quiz_percentage, 
                test_mark, test_percentage,
                final_exam_mark, final_exam_percentage,
                component_total, final_grade, letter_grade, gpa,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $student_id, $course_id,
            $marks['assignment_mark'], $marks['assignment_percentage'],
            $marks['quiz_mark'], $marks['quiz_percentage'],
            $marks['test_mark'], $marks['test_percentage'], 
            $marks['final_exam_mark'], $marks['final_exam_percentage'],
            $marks['component_total'], $marks['final_grade'], 
            $marks['letter_grade'], $marks['gpa']
        ]);
    }
}

function generateMarksForProfile($profile, $course_id) {
    switch ($profile) {
        case 'excellent_consistent':
            return [
                'assignment_mark' => rand(88, 98), 'assignment_percentage' => rand(88, 98),
                'quiz_mark' => rand(85, 95), 'quiz_percentage' => rand(85, 95),
                'test_mark' => rand(87, 97), 'test_percentage' => rand(87, 97),
                'final_exam_mark' => rand(89, 99), 'final_exam_percentage' => rand(89, 99),
                'component_total' => rand(87, 97), 'final_grade' => rand(87, 97),
                'letter_grade' => 'A', 'gpa' => 4.00
            ];
            
        case 'strong_but_declining':
            $base = $course_id == 1 ? 85 : 75; // Decline from CS101 to CS201
            return [
                'assignment_mark' => $base + rand(-5, 5), 'assignment_percentage' => $base + rand(-5, 5),
                'quiz_mark' => $base + rand(-8, 3), 'quiz_percentage' => $base + rand(-8, 3),
                'test_mark' => $base + rand(-10, 2), 'test_percentage' => $base + rand(-10, 2),
                'final_exam_mark' => $base + rand(-12, 0), 'final_exam_percentage' => $base + rand(-12, 0),
                'component_total' => $base + rand(-8, 2), 'final_grade' => $base + rand(-8, 2),
                'letter_grade' => $course_id == 1 ? 'A' : 'B', 'gpa' => $course_id == 1 ? 4.00 : 3.00
            ];
            
        case 'average_improving':
            $base = $course_id == 1 ? 68 : 75; // Improvement from CS101 to CS201
            return [
                'assignment_mark' => $base + rand(-5, 8), 'assignment_percentage' => $base + rand(-5, 8),
                'quiz_mark' => $base + rand(-3, 10), 'quiz_percentage' => $base + rand(-3, 10),
                'test_mark' => $base + rand(-2, 12), 'test_percentage' => $base + rand(-2, 12),
                'final_exam_mark' => $base + rand(0, 15), 'final_exam_percentage' => $base + rand(0, 15),
                'component_total' => $base + rand(-2, 10), 'final_grade' => $base + rand(-2, 10),
                'letter_grade' => $course_id == 1 ? 'C+' : 'B', 'gpa' => $course_id == 1 ? 2.33 : 3.00
            ];
            
        case 'struggling_assignments':
            return [
                'assignment_mark' => rand(35, 55), 'assignment_percentage' => rand(35, 55),
                'quiz_mark' => rand(45, 65), 'quiz_percentage' => rand(45, 65),
                'test_mark' => rand(70, 85), 'test_percentage' => rand(70, 85),
                'final_exam_mark' => rand(75, 90), 'final_exam_percentage' => rand(75, 90),
                'component_total' => rand(60, 72), 'final_grade' => rand(60, 72),
                'letter_grade' => 'B-', 'gpa' => 2.67
            ];
            
        case 'exam_anxiety':
            return [
                'assignment_mark' => rand(80, 92), 'assignment_percentage' => rand(80, 92),
                'quiz_mark' => rand(78, 88), 'quiz_percentage' => rand(78, 88),
                'test_mark' => rand(75, 85), 'test_percentage' => rand(75, 85),
                'final_exam_mark' => rand(45, 65), 'final_exam_percentage' => rand(45, 65),
                'component_total' => rand(68, 78), 'final_grade' => rand(68, 78),
                'letter_grade' => 'B', 'gpa' => 3.00
            ];
            
        case 'inconsistent':
            return [
                'assignment_mark' => rand(25, 95), 'assignment_percentage' => rand(25, 95),
                'quiz_mark' => rand(30, 90), 'quiz_percentage' => rand(30, 90),
                'test_mark' => rand(40, 85), 'test_percentage' => rand(40, 85),
                'final_exam_mark' => rand(35, 80), 'final_exam_percentage' => rand(35, 80),
                'component_total' => rand(40, 80), 'final_grade' => rand(40, 80),
                'letter_grade' => 'C', 'gpa' => 2.00
            ];
            
        case 'at_risk':
            return [
                'assignment_mark' => rand(25, 45), 'assignment_percentage' => rand(25, 45),
                'quiz_mark' => rand(20, 40), 'quiz_percentage' => rand(20, 40),
                'test_mark' => rand(30, 50), 'test_percentage' => rand(30, 50),
                'final_exam_mark' => rand(35, 55), 'final_exam_percentage' => rand(35, 55),
                'component_total' => rand(28, 48), 'final_grade' => rand(28, 48),
                'letter_grade' => 'F', 'gpa' => 0.00
            ];
            
        case 'comeback_story':
            $base = $course_id == 1 ? 45 : 72; // Major improvement from CS101 to CS201
            return [
                'assignment_mark' => $base + rand(-8, 12), 'assignment_percentage' => $base + rand(-8, 12),
                'quiz_mark' => $base + rand(-5, 15), 'quiz_percentage' => $base + rand(-5, 15),
                'test_mark' => $base + rand(-3, 18), 'test_percentage' => $base + rand(-3, 18),
                'final_exam_mark' => $base + rand(0, 20), 'final_exam_percentage' => $base + rand(0, 20),
                'component_total' => $base + rand(-5, 15), 'final_grade' => $base + rand(-5, 15),
                'letter_grade' => $course_id == 1 ? 'D' : 'B', 'gpa' => $course_id == 1 ? 1.00 : 3.00
            ];
            
        default:
            return [
                'assignment_mark' => rand(60, 80), 'assignment_percentage' => rand(60, 80),
                'quiz_mark' => rand(60, 80), 'quiz_percentage' => rand(60, 80),
                'test_mark' => rand(60, 80), 'test_percentage' => rand(60, 80),
                'final_exam_mark' => rand(60, 80), 'final_exam_percentage' => rand(60, 80),
                'component_total' => rand(60, 80), 'final_grade' => rand(60, 80),
                'letter_grade' => 'B', 'gpa' => 3.00
            ];
    }
}
?>
