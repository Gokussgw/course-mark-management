<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');
    echo "Database connection successful\n";
    
    // Test if we can get student 15's data
    $stmt = $pdo->prepare("SELECT id, name, advisor_id, role FROM users WHERE id = 15");
    $stmt->execute();
    $user = $stmt->fetch();
    if ($user) {
        echo "Student 15: {$user['name']}, advisor_id: {$user['advisor_id']}, role: {$user['role']}\n";
    } else {
        echo "Student 15 not found\n";
    }
    
    $stmt = $pdo->prepare("SELECT id, student_id, course_id, gpa, final_grade FROM final_marks_custom WHERE student_id = 15 LIMIT 5");
    $stmt->execute();
    $results = $stmt->fetchAll();
    echo "Found " . count($results) . " final marks for student 15\n";
    
    foreach ($results as $row) {
        echo "Course " . $row['course_id'] . ": GPA = " . $row['gpa'] . ", Final Grade = " . $row['final_grade'] . "\n";
    }
    
    // Test the advisor query
    if ($user && $user['advisor_id']) {
        echo "\nTesting NEW advisor query for advisor ID: {$user['advisor_id']}\n";
        
        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.name,
                u.email,
                u.matric_number,
                u.created_at,
                COALESCE(AVG(fm.gpa), 0) as gpa,
                COUNT(DISTINCT fm.course_id) as enrolled_courses,
                COUNT(DISTINCT fm.id) as completed_credits,
                CASE 
                    WHEN COALESCE(AVG(fm.final_grade), 0) < 50 THEN 'High'
                    WHEN COALESCE(AVG(fm.final_grade), 0) < 65 THEN 'Medium'
                    ELSE 'Low'
                END as risk,
                CASE 
                    WHEN COALESCE(AVG(fm.gpa), 0) >= 3.0 THEN 'Good Standing'
                    WHEN COALESCE(AVG(fm.gpa), 0) >= 2.0 THEN 'Warning'
                    ELSE 'Probation'
                END as status
            FROM users u
            LEFT JOIN final_marks_custom fm ON u.id = fm.student_id
            WHERE u.advisor_id = ? AND u.role = 'student' AND u.id = 15
            GROUP BY u.id, u.name, u.email, u.matric_number, u.created_at
        ");
        $stmt->execute([$user['advisor_id']]);
        $advisee = $stmt->fetch();
        if ($advisee) {
            echo "NEW advisee data:\n";
            echo "  GPA: {$advisee['gpa']}\n";
            echo "  Credits: {$advisee['completed_credits']}\n";
            echo "  Courses: {$advisee['enrolled_courses']}\n";
            echo "  Status: {$advisee['status']}\n";
            echo "  Risk: {$advisee['risk']}\n";
        } else {
            echo "No advisee data found\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
