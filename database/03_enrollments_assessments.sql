-- Course Mark Management Database Schema - Part 3: Enrollments and Assessments

-- Enroll students in courses
INSERT INTO enrollments (student_id, course_id, academic_year, semester) 
SELECT 
    (SELECT id FROM users WHERE email = 'student1@example.com'),
    (SELECT id FROM courses WHERE code = 'CS101'),
    '2025-2026', 'Fall';

INSERT INTO enrollments (student_id, course_id, academic_year, semester) 
SELECT 
    (SELECT id FROM users WHERE email = 'student1@example.com'),
    (SELECT id FROM courses WHERE code = 'CS201'),
    '2025-2026', 'Fall';

INSERT INTO enrollments (student_id, course_id, academic_year, semester) 
SELECT 
    (SELECT id FROM users WHERE email = 'student2@example.com'),
    (SELECT id FROM courses WHERE code = 'CS101'),
    '2025-2026', 'Fall';

-- Insert sample assessments
INSERT INTO assessments (course_id, name, type, weightage, max_mark, is_final_exam, date) 
SELECT 
    (SELECT id FROM courses WHERE code = 'CS101'),
    'Assignment 1', 'assignment', 10.00, 100.00, FALSE, '2025-09-15';

INSERT INTO assessments (course_id, name, type, weightage, max_mark, is_final_exam, date) 
SELECT 
    (SELECT id FROM courses WHERE code = 'CS101'),
    'Midterm Exam', 'midterm', 30.00, 100.00, FALSE, '2025-10-15';

INSERT INTO assessments (course_id, name, type, weightage, max_mark, is_final_exam, date) 
SELECT 
    (SELECT id FROM courses WHERE code = 'CS101'),
    'Final Exam', 'final_exam', 60.00, 100.00, TRUE, '2025-12-15';

INSERT INTO assessments (course_id, name, type, weightage, max_mark, is_final_exam, date) 
SELECT 
    (SELECT id FROM courses WHERE code = 'CS201'),
    'Assignment 1', 'assignment', 15.00, 100.00, FALSE, '2025-09-20';

INSERT INTO assessments (course_id, name, type, weightage, max_mark, is_final_exam, date) 
SELECT 
    (SELECT id FROM courses WHERE code = 'CS201'),
    'Final Project', 'assignment', 25.00, 100.00, FALSE, '2025-11-30';

INSERT INTO assessments (course_id, name, type, weightage, max_mark, is_final_exam, date) 
SELECT 
    (SELECT id FROM courses WHERE code = 'CS201'),
    'Final Exam', 'final_exam', 60.00, 100.00, TRUE, '2025-12-20';
