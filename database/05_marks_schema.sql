-- Marks Management Database Schema
-- Add these tables to your existing course_mark_management database

-- Table for storing individual component marks
CREATE TABLE IF NOT EXISTS marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    assessment_type ENUM('assignment', 'quiz', 'test', 'final_exam') NOT NULL,
    marks DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    max_marks DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    percentage DECIMAL(5,2) GENERATED ALWAYS AS ((marks / max_marks) * 100) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_by INT NULL,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_student_course_assessment (student_id, course_id, assessment_type)
);

-- Table for storing final calculated marks and grades
CREATE TABLE IF NOT EXISTS final_marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    component_marks DECIMAL(5,2) NOT NULL DEFAULT 0.00, -- Assignment + Quiz + Test combined (70%)
    final_exam_marks DECIMAL(5,2) NOT NULL DEFAULT 0.00, -- Final exam (30%)
    final_marks DECIMAL(5,2) NOT NULL DEFAULT 0.00, -- Calculated final mark
    grade CHAR(2) NOT NULL DEFAULT '',
    gpa_points DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    calculated_by INT NULL,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (calculated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_student_course (student_id, course_id)
);

-- Insert sample courses if they don't exist
INSERT IGNORE INTO courses (id, code, name, academic_year, semester, lecturer_id, created_at) VALUES
(1, 'CS101', 'Introduction to Programming', '2025-2026', 'Fall', 2, NOW()),
(2, 'CS201', 'Data Structures', '2025-2026', 'Fall', 2, NOW()),
(3, 'CS301', 'Database Systems', '2025-2026', 'Fall', 2, NOW());

-- Insert sample enrollments if they don't exist
INSERT IGNORE INTO enrollments (student_id, course_id, academic_year, semester) VALUES
(4, 1, '2025-2026', 'Fall'),
(5, 1, '2025-2026', 'Fall'),
(6, 1, '2025-2026', 'Fall'),
(4, 2, '2025-2026', 'Fall'),
(5, 2, '2025-2026', 'Fall'),
(6, 3, '2025-2026', 'Fall');

-- Insert sample marks for students
INSERT IGNORE INTO marks (student_id, course_id, assessment_type, marks, max_marks, created_by) VALUES
-- CS101 marks
(4, 1, 'assignment', 85.0, 100.0, 2),
(4, 1, 'quiz', 92.0, 100.0, 2),
(4, 1, 'test', 78.0, 100.0, 2),
(4, 1, 'final_exam', 88.0, 100.0, 2),

(5, 1, 'assignment', 76.0, 100.0, 2),
(5, 1, 'quiz', 84.0, 100.0, 2),
(5, 1, 'test', 82.0, 100.0, 2),
(5, 1, 'final_exam', 79.0, 100.0, 2),

(6, 1, 'assignment', 68.0, 100.0, 2),
(6, 1, 'quiz', 75.0, 100.0, 2),
(6, 1, 'test', 70.0, 100.0, 2),
(6, 1, 'final_exam', 72.0, 100.0, 2),

-- CS201 marks
(4, 2, 'assignment', 90.0, 100.0, 2),
(4, 2, 'quiz', 88.0, 100.0, 2),
(4, 2, 'test', 85.0, 100.0, 2),
(4, 2, 'final_exam', 92.0, 100.0, 2),

(5, 2, 'assignment', 82.0, 100.0, 2),
(5, 2, 'quiz', 78.0, 100.0, 2),
(5, 2, 'test', 80.0, 100.0, 2),
(5, 2, 'final_exam', 85.0, 100.0, 2);

-- Calculate and insert final marks
INSERT IGNORE INTO final_marks (student_id, course_id, component_marks, final_exam_marks, final_marks, grade, gpa_points, calculated_by)
SELECT 
    m.student_id,
    m.course_id,
    -- Component marks calculation (Assignment 25% + Quiz 20% + Test 25% = 70%)
    ROUND(
        (COALESCE(assignment.percentage, 0) * 0.25) + 
        (COALESCE(quiz.percentage, 0) * 0.20) + 
        (COALESCE(test.percentage, 0) * 0.25), 2
    ) as component_marks,
    -- Final exam marks (30%)
    COALESCE(final_exam.percentage, 0) as final_exam_marks,
    -- Final marks calculation
    ROUND(
        ((COALESCE(assignment.percentage, 0) * 0.25) + 
         (COALESCE(quiz.percentage, 0) * 0.20) + 
         (COALESCE(test.percentage, 0) * 0.25)) * 0.70 +
        (COALESCE(final_exam.percentage, 0) * 0.30), 2
    ) as final_marks,
    -- Grade calculation
    CASE 
        WHEN ROUND(
            ((COALESCE(assignment.percentage, 0) * 0.25) + 
             (COALESCE(quiz.percentage, 0) * 0.20) + 
             (COALESCE(test.percentage, 0) * 0.25)) * 0.70 +
            (COALESCE(final_exam.percentage, 0) * 0.30), 2
        ) >= 80 THEN 'A'
        WHEN ROUND(
            ((COALESCE(assignment.percentage, 0) * 0.25) + 
             (COALESCE(quiz.percentage, 0) * 0.20) + 
             (COALESCE(test.percentage, 0) * 0.25)) * 0.70 +
            (COALESCE(final_exam.percentage, 0) * 0.30), 2
        ) >= 70 THEN 'B'
        WHEN ROUND(
            ((COALESCE(assignment.percentage, 0) * 0.25) + 
             (COALESCE(quiz.percentage, 0) * 0.20) + 
             (COALESCE(test.percentage, 0) * 0.25)) * 0.70 +
            (COALESCE(final_exam.percentage, 0) * 0.30), 2
        ) >= 60 THEN 'C'
        WHEN ROUND(
            ((COALESCE(assignment.percentage, 0) * 0.25) + 
             (COALESCE(quiz.percentage, 0) * 0.20) + 
             (COALESCE(test.percentage, 0) * 0.25)) * 0.70 +
            (COALESCE(final_exam.percentage, 0) * 0.30), 2
        ) >= 50 THEN 'D'
        ELSE 'F'
    END as grade,
    -- GPA points
    CASE 
        WHEN ROUND(
            ((COALESCE(assignment.percentage, 0) * 0.25) + 
             (COALESCE(quiz.percentage, 0) * 0.20) + 
             (COALESCE(test.percentage, 0) * 0.25)) * 0.70 +
            (COALESCE(final_exam.percentage, 0) * 0.30), 2
        ) >= 80 THEN 4.00
        WHEN ROUND(
            ((COALESCE(assignment.percentage, 0) * 0.25) + 
             (COALESCE(quiz.percentage, 0) * 0.20) + 
             (COALESCE(test.percentage, 0) * 0.25)) * 0.70 +
            (COALESCE(final_exam.percentage, 0) * 0.30), 2
        ) >= 70 THEN 3.00
        WHEN ROUND(
            ((COALESCE(assignment.percentage, 0) * 0.25) + 
             (COALESCE(quiz.percentage, 0) * 0.20) + 
             (COALESCE(test.percentage, 0) * 0.25)) * 0.70 +
            (COALESCE(final_exam.percentage, 0) * 0.30), 2
        ) >= 60 THEN 2.00
        WHEN ROUND(
            ((COALESCE(assignment.percentage, 0) * 0.25) + 
             (COALESCE(quiz.percentage, 0) * 0.20) + 
             (COALESCE(test.percentage, 0) * 0.25)) * 0.70 +
            (COALESCE(final_exam.percentage, 0) * 0.30), 2
        ) >= 50 THEN 1.00
        ELSE 0.00
    END as gpa_points,
    2 as calculated_by
FROM (
    SELECT DISTINCT student_id, course_id 
    FROM marks 
) m
LEFT JOIN marks assignment ON m.student_id = assignment.student_id AND m.course_id = assignment.course_id AND assignment.assessment_type = 'assignment'
LEFT JOIN marks quiz ON m.student_id = quiz.student_id AND m.course_id = quiz.course_id AND quiz.assessment_type = 'quiz'
LEFT JOIN marks test ON m.student_id = test.student_id AND m.course_id = test.course_id AND test.assessment_type = 'test'
LEFT JOIN marks final_exam ON m.student_id = final_exam.student_id AND m.course_id = final_exam.course_id AND final_exam.assessment_type = 'final_exam';
