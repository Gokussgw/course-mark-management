<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

echo "=== Admin Dashboard Real Data Check ===\n\n";

echo "CURRENT STATUS: Admin dashboard is using MOCK DATA (hardcoded values)\n\n";

echo "AVAILABLE REAL DATA from backend API (/api/admin/stats):\n\n";

try {
    // Count users by role
    $stmt = $pdo->query('
        SELECT role, COUNT(*) as count 
        FROM users 
        GROUP BY role
    ');
    $usersByRole = $stmt->fetchAll();
    
    echo "👥 Users by Role:\n";
    $totalUsers = 0;
    foreach ($usersByRole as $role) {
        echo "   {$role['role']}: {$role['count']}\n";
        $totalUsers += $role['count'];
    }
    echo "   Total Users: $totalUsers\n\n";

    // Count courses
    $stmt = $pdo->query('SELECT COUNT(*) FROM courses');
    $totalCourses = $stmt->fetchColumn();
    echo "📚 Total Courses: $totalCourses\n";

    // Count assessments
    $stmt = $pdo->query('SELECT COUNT(*) FROM assessments');
    $totalAssessments = $stmt->fetchColumn();
    echo "📝 Total Assessments: $totalAssessments\n";

    // Count marks
    $stmt = $pdo->query('SELECT COUNT(*) FROM marks');
    $totalMarks = $stmt->fetchColumn();
    echo "📊 Total Marks: $totalMarks\n";
    
    // Count from final_marks_custom (which has real data)
    $stmt = $pdo->query('SELECT COUNT(*) FROM final_marks_custom');
    $totalFinalMarks = $stmt->fetchColumn();
    echo "📈 Total Final Marks: $totalFinalMarks\n";

    // Count remark requests
    $stmt = $pdo->query('SELECT COUNT(*) FROM remark_requests');
    $totalRemarkRequests = $stmt->fetchColumn();
    echo "🔄 Total Remark Requests: $totalRemarkRequests\n";

    // Count enrollments
    $stmt = $pdo->query('SELECT COUNT(*) FROM enrollments');
    $totalEnrollments = $stmt->fetchColumn();
    echo "📋 Total Enrollments: $totalEnrollments\n\n";

    echo "COMPARISON:\n";
    echo "Frontend (Mock Data)    | Backend (Real Data)\n";
    echo "─────────────────────── | ──────────────────\n";
    echo "Users: 7                | Users: $totalUsers\n";
    echo "Courses: 15             | Courses: $totalCourses\n";
    echo "Assessments: 45         | Assessments: $totalAssessments\n";
    echo "Active Courses: 12      | (No backend query)\n";
    echo "Upcoming Assessments: 12| (No backend query)\n\n";

    echo "🔧 TO FIX: Update admin Dashboard.vue to:\n";
    echo "1. Call /api/admin/stats endpoint on component mount\n";
    echo "2. Replace hardcoded stats with real API data\n";
    echo "3. Call /api/admin/users endpoint for real user list\n";
    echo "4. Implement real system logs API if needed\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
