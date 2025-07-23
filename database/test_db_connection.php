<?php
$host = 'localhost';
$dbname = 'course_mark_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Database connection successful\n";

    // Test the query used in adviseeReports.php
    $advisorId = 3;
    $stmt = $pdo->prepare('
        SELECT u.id, u.name, u.email, u.advisor_id 
        FROM users u 
        WHERE u.role = "student" AND u.advisor_id = ?
    ');
    $stmt->execute([$advisorId]);
    $basicAdvisees = $stmt->fetchAll();
    
    echo "✅ Basic advisees query successful: " . count($basicAdvisees) . " students found\n";
    
    // Test the complex query
    $stmt = $pdo->prepare('
        SELECT 
            u.id,
            u.name,
            u.email,
            u.matric_number,
            u.created_at as enrollment_date,
            COUNT(DISTINCT e.course_id) as total_courses,
            COUNT(DISTINCT fm.id) as completed_courses,
            AVG(fm.gpa) as overall_gpa
        FROM users u
        LEFT JOIN enrollments e ON u.id = e.student_id
        LEFT JOIN final_marks_custom fm ON u.id = fm.student_id AND e.course_id = fm.course_id
        WHERE u.advisor_id = ? AND u.role = "student"
        GROUP BY u.id
        ORDER BY u.name
        LIMIT 1
    ');
    $stmt->execute([$advisorId]);
    $testAdvisee = $stmt->fetch();
    
    if ($testAdvisee) {
        echo "✅ Complex advisees query successful\n";
        echo "Sample advisee: " . $testAdvisee['name'] . "\n";
    } else {
        echo "❌ Complex advisees query returned no results\n";
    }

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ General error: " . $e->getMessage() . "\n";
}
?>
