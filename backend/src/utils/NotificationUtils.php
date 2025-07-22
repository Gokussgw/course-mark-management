<?php

/**
 * Notification Utility Functions
 * Handles sending notifications for marks updates and other academic events
 */

function sendMarkUpdateNotification($pdo, $courseId, $studentId, $lecturerId, $componentType, $newMark) {
    try {
        // Get course and student information
        $courseStmt = $pdo->prepare("
            SELECT c.code, c.name as course_name, u.name as lecturer_name
            FROM courses c 
            JOIN users u ON c.lecturer_id = u.id 
            WHERE c.id = ?
        ");
        $courseStmt->execute([$courseId]);
        $course = $courseStmt->fetch();

        $studentStmt = $pdo->prepare("
            SELECT name, email 
            FROM users 
            WHERE id = ? AND role = 'student'
        ");
        $studentStmt->execute([$studentId]);
        $student = $studentStmt->fetch();

        if (!$course || !$student) {
            return false;
        }

        // Format component name
        $componentNames = [
            'assignment' => 'Assignment',
            'quiz' => 'Quiz', 
            'test' => 'Test',
            'final_exam' => 'Final Exam'
        ];
        $componentName = $componentNames[$componentType] ?? ucfirst($componentType);

        // Send notification to student
        $studentContent = "Your {$componentName} mark for {$course['code']} - {$course['course_name']} has been updated. New mark: {$newMark}%. Please check your academic dashboard for details.";
        
        $studentNotification = $pdo->prepare("
            INSERT INTO notifications (user_id, type, content, related_id, sender_id, created_at) 
            VALUES (?, 'mark', ?, ?, ?, NOW())
        ");
        $studentNotification->execute([$studentId, $studentContent, $courseId, $lecturerId]);

        // Send notification to lecturer (confirmation)
        $lecturerContent = "Mark update confirmed for {$student['name']} in {$course['code']} - {$componentName}: {$newMark}%. Student has been notified automatically.";
        
        $lecturerNotification = $pdo->prepare("
            INSERT INTO notifications (user_id, type, content, related_id, sender_id, created_at) 
            VALUES (?, 'mark', ?, ?, ?, NOW())
        ");
        $lecturerNotification->execute([$lecturerId, $lecturerContent, $courseId, $lecturerId]);

        return true;
    } catch (PDOException $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

function sendBulkMarkUpdateNotification($pdo, $courseId, $lecturerId, $componentType, $studentsUpdated) {
    try {
        // Get course information
        $courseStmt = $pdo->prepare("
            SELECT c.code, c.name as course_name, u.name as lecturer_name
            FROM courses c 
            JOIN users u ON c.lecturer_id = u.id 
            WHERE c.id = ?
        ");
        $courseStmt->execute([$courseId]);
        $course = $courseStmt->fetch();

        if (!$course) {
            return false;
        }

        // Format component name
        $componentNames = [
            'assignment' => 'Assignment',
            'quiz' => 'Quiz', 
            'test' => 'Test',
            'final_exam' => 'Final Exam'
        ];
        $componentName = $componentNames[$componentType] ?? ucfirst($componentType);

        // Send notifications to all students in the course
        $studentStmt = $pdo->prepare("
            SELECT DISTINCT u.id, u.name, u.email
            FROM users u
            JOIN enrollments e ON u.id = e.student_id
            WHERE e.course_id = ? AND u.role = 'student'
        ");
        $studentStmt->execute([$courseId]);
        $students = $studentStmt->fetchAll();

        $notificationsSent = 0;
        foreach ($students as $student) {
            $studentContent = "New {$componentName} marks have been released for {$course['code']} - {$course['course_name']}. Please check your academic dashboard to view your results.";
            
            $studentNotification = $pdo->prepare("
                INSERT INTO notifications (user_id, type, content, related_id, sender_id, created_at) 
                VALUES (?, 'mark', ?, ?, ?, NOW())
            ");
            $studentNotification->execute([$student['id'], $studentContent, $courseId, $lecturerId]);
            $notificationsSent++;
        }

        // Send confirmation to lecturer
        $lecturerContent = "Bulk mark update completed for {$course['code']} - {$componentName}. {$notificationsSent} students have been automatically notified of their new marks.";
        
        $lecturerNotification = $pdo->prepare("
            INSERT INTO notifications (user_id, type, content, related_id, sender_id, created_at) 
            VALUES (?, 'mark', ?, ?, ?, NOW())
        ");
        $lecturerNotification->execute([$lecturerId, $lecturerContent, $courseId, $lecturerId]);

        return $notificationsSent;
    } catch (PDOException $e) {
        error_log("Bulk notification error: " . $e->getMessage());
        return false;
    }
}

function sendCourseAnnouncementNotification($pdo, $courseId, $lecturerId, $title, $message, $includeMarks = false) {
    try {
        // Get course information
        $courseStmt = $pdo->prepare("
            SELECT c.code, c.name as course_name, u.name as lecturer_name
            FROM courses c 
            JOIN users u ON c.lecturer_id = u.id 
            WHERE c.id = ?
        ");
        $courseStmt->execute([$courseId]);
        $course = $courseStmt->fetch();

        if (!$course) {
            return false;
        }

        // Get enrolled students
        $studentStmt = $pdo->prepare("
            SELECT DISTINCT u.id, u.name, u.email
            FROM users u
            JOIN enrollments e ON u.id = e.student_id
            WHERE e.course_id = ? AND u.role = 'student'
        ");
        $studentStmt->execute([$courseId]);
        $students = $studentStmt->fetchAll();

        $notificationsSent = 0;
        foreach ($students as $student) {
            $content = "{$title}\n\n{$message}\n\nCourse: {$course['code']} - {$course['course_name']}\nFrom: {$course['lecturer_name']}";
            
            if ($includeMarks) {
                $content .= "\n\nPlease check your academic dashboard for updated marks.";
            }
            
            $studentNotification = $pdo->prepare("
                INSERT INTO notifications (user_id, type, content, related_id, sender_id, created_at) 
                VALUES (?, 'course', ?, ?, ?, NOW())
            ");
            $studentNotification->execute([$student['id'], $content, $courseId, $lecturerId]);
            $notificationsSent++;
        }

        // Send confirmation to lecturer
        $lecturerContent = "Course announcement sent successfully to {$notificationsSent} students in {$course['code']} - {$course['course_name']}.";
        
        $lecturerNotification = $pdo->prepare("
            INSERT INTO notifications (user_id, type, content, related_id, sender_id, created_at) 
            VALUES (?, 'course', ?, ?, ?, NOW())
        ");
        $lecturerNotification->execute([$lecturerId, $lecturerContent, $courseId, $lecturerId]);

        return $notificationsSent;
    } catch (PDOException $e) {
        error_log("Course announcement notification error: " . $e->getMessage());
        return false;
    }
}

function getUnreadNotificationCount($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        error_log("Unread notification count error: " . $e->getMessage());
        return 0;
    }
}

function getRecentNotifications($pdo, $userId, $limit = 10) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                n.*,
                sender.name as sender_name
            FROM notifications n
            LEFT JOIN users sender ON n.sender_id = sender.id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Recent notifications error: " . $e->getMessage());
        return [];
    }
}

?>
