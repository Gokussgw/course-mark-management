<?php
// Test the fixed SQL query 

$host = 'localhost';
$dbname = 'course_mark_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Testing fixed class rankings SQL query...\n\n";

    // Use the new approach with direct integer insertion
    $limit = intval(20);
    $offset = intval(0);

    // Ensure positive values for security
    $limit = max(1, min(100, $limit));  // Between 1 and 100
    $offset = max(0, $offset);  // Non-negative

    $stmt = $pdo->prepare("
        WITH student_performance AS (
            SELECT 
                u.id,
                u.name,
                u.matric_number,
                COALESCE(AVG(fm.gpa), 0) as gpa,
                COUNT(DISTINCT fm.course_id) as courses_taken,
                AVG(fm.final_grade) as average_grade
            FROM users u
            LEFT JOIN final_marks_custom fm ON u.id = fm.student_id
            WHERE u.role = 'student'
            GROUP BY u.id, u.name, u.matric_number
        ),
        ranked_students AS (
            SELECT 
                *,
                RANK() OVER (ORDER BY gpa DESC, average_grade DESC) as overall_rank,
                COUNT(*) OVER() as total_students
            FROM student_performance
        )
        SELECT 
            *
        FROM ranked_students 
        ORDER BY overall_rank
        LIMIT $limit OFFSET $offset
    ");

    $stmt->execute();
    $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "✅ Query executed successfully!\n";
    echo "Number of students returned: " . count($rankings) . "\n\n";

    if (!empty($rankings)) {
        echo "Top 3 students:\n";
        for ($i = 0; $i < min(3, count($rankings)); $i++) {
            $student = $rankings[$i];
            echo ($i + 1) . ". {$student['name']} (Matric: {$student['matric_number']}, GPA: {$student['gpa']}, Rank: {$student['overall_rank']})\n";
        }
    } else {
        echo "No student data found.\n";
    }

    echo "\n✅ LIMIT/OFFSET syntax error has been fixed!\n";
} catch (PDOException $e) {
    echo "❌ SQL Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
