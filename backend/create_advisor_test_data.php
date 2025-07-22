<?php
try {
    $host = 'localhost';
    $dbname = 'course_mark_management';
    $username = 'root';
    $password = '';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the advisor ID
    $advisorStmt = $pdo->prepare("SELECT id FROM users WHERE email = 'advisor1@example.com'");
    $advisorStmt->execute();
    $advisor = $advisorStmt->fetch(PDO::FETCH_ASSOC);

    if (!$advisor) {
        throw new Exception("Advisor not found");
    }

    $advisorId = $advisor['id'];

    // Add more students under this advisor
    $students = [
        ['name' => 'Alice Johnson', 'email' => 'alice@example.com', 'matric' => 'A12347'],
        ['name' => 'Bob Smith', 'email' => 'bob@example.com', 'matric' => 'A12348'],
        ['name' => 'Carol Davis', 'email' => 'carol@example.com', 'matric' => 'A12349'],
        ['name' => 'David Wilson', 'email' => 'david@example.com', 'matric' => 'A12350']
    ];

    $studentIds = [];

    foreach ($students as $student) {
        // Check if student already exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$student['email']]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            // Insert new student
            $insertStmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, matric_number, pin, advisor_id) 
                VALUES (?, ?, ?, 'student', ?, ?, ?)
            ");
            $insertStmt->execute([
                $student['name'],
                $student['email'],
                '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password: 'password'
                $student['matric'],
                '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // pin: 'password'
                $advisorId
            ]);
            $studentIds[] = $pdo->lastInsertId();
        } else {
            // Update existing student to have advisor
            $updateStmt = $pdo->prepare("UPDATE users SET advisor_id = ? WHERE id = ?");
            $updateStmt->execute([$advisorId, $existing['id']]);
            $studentIds[] = $existing['id'];
        }
    }

    // Get courses
    $courseStmt = $pdo->prepare("SELECT id FROM courses ORDER BY id LIMIT 2");
    $courseStmt->execute();
    $courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($courses)) {
        throw new Exception("No courses found");
    }

    // Enroll students in courses
    foreach ($studentIds as $studentId) {
        foreach ($courses as $course) {
            // Check if enrollment exists
            $checkEnrollStmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
            $checkEnrollStmt->execute([$studentId, $course['id']]);

            if (!$checkEnrollStmt->fetch()) {
                $enrollStmt = $pdo->prepare("
                    INSERT INTO enrollments (student_id, course_id, academic_year, semester, created_at) 
                    VALUES (?, ?, '2025-2026', 'Fall', NOW())
                ");
                $enrollStmt->execute([$studentId, $course['id']]);
            }
        }
    }

    // Get assessments
    $assessmentStmt = $pdo->prepare("SELECT id, course_id, max_mark FROM assessments");
    $assessmentStmt->execute();
    $assessments = $assessmentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Add some marks for variety
    $markRanges = [
        ['min' => 85, 'max' => 95], // High performer
        ['min' => 40, 'max' => 55], // At risk
        ['min' => 65, 'max' => 75], // Average
        ['min' => 45, 'max' => 60]  // Below average
    ];

    foreach ($studentIds as $index => $studentId) {
        $range = $markRanges[$index % count($markRanges)];

        foreach ($assessments as $assessment) {
            // Check if mark exists
            $checkMarkStmt = $pdo->prepare("
                SELECT id FROM marks 
                WHERE student_id = ? AND assessment_id = ?
            ");
            $checkMarkStmt->execute([$studentId, $assessment['id']]);

            if (!$checkMarkStmt->fetch()) {
                $maxMark = $assessment['max_mark'];
                $mark = ($range['min'] / 100) * $maxMark + rand(0, (($range['max'] - $range['min']) / 100) * $maxMark);

                $markStmt = $pdo->prepare("
                    INSERT INTO marks (student_id, assessment_id, mark, created_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                $markStmt->execute([
                    $studentId,
                    $assessment['id'],
                    round($mark, 2)
                ]);
            }
        }
    }

    echo "Sample advisor data created successfully!\n";
    echo "Added " . count($students) . " students under advisor\n";
    echo "Enrolled students in " . count($courses) . " courses\n";
    echo "Added marks for " . count($assessments) . " assessments\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
