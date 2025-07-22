-- Add lecturer feedback table for student feedback/remarks system
-- This allows lecturers to add feedback for students in their courses

DROP TABLE IF EXISTS lecturer_feedback;

CREATE TABLE lecturer_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    lecturer_id INT NOT NULL,
    feedback_type ENUM('general', 'performance', 'improvement', 'commendation', 'concern') NOT NULL DEFAULT 'general',
    subject VARCHAR(255) NOT NULL,
    feedback TEXT NOT NULL,
    is_visible_to_student BOOLEAN DEFAULT TRUE,
    is_visible_to_advisor BOOLEAN DEFAULT TRUE,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add indexes for better performance
CREATE INDEX idx_lecturer_feedback_student ON lecturer_feedback(student_id);
CREATE INDEX idx_lecturer_feedback_course ON lecturer_feedback(course_id);
CREATE INDEX idx_lecturer_feedback_lecturer ON lecturer_feedback(lecturer_id);
CREATE INDEX idx_lecturer_feedback_created ON lecturer_feedback(created_at);

-- Insert sample feedback data for testing
INSERT INTO lecturer_feedback (student_id, course_id, lecturer_id, feedback_type, subject, feedback, priority) VALUES
(4, 1, 2, 'performance', 'Excellent Progress in Assignments', 'Student One has shown outstanding performance in recent assignments. Keep up the good work!', 'medium'),
(4, 1, 2, 'improvement', 'Need to Focus on Final Exam Preparation', 'Please allocate more time for final exam preparation. Consider forming study groups.', 'high'),
(5, 1, 2, 'concern', 'Attendance Issues', 'Student Two has missed several classes. Please see me during office hours to discuss.', 'high'),
(5, 1, 2, 'commendation', 'Great Participation in Class', 'Excellent class participation and thoughtful questions. This shows good engagement with the material.', 'medium');
