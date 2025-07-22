-- Course Mark Management Database Schema - Part 4: Marks and Other Data

-- Insert sample marks
INSERT INTO marks (student_id, assessment_id, mark) 
SELECT 
    (SELECT id FROM users WHERE email = 'student1@example.com'),
    (SELECT id FROM assessments WHERE name = 'Assignment 1' AND course_id = (SELECT id FROM courses WHERE code = 'CS101')),
    85.00;

INSERT INTO marks (student_id, assessment_id, mark) 
SELECT 
    (SELECT id FROM users WHERE email = 'student1@example.com'),
    (SELECT id FROM assessments WHERE name = 'Midterm Exam' AND course_id = (SELECT id FROM courses WHERE code = 'CS101')),
    78.00;

INSERT INTO marks (student_id, assessment_id, mark) 
SELECT 
    (SELECT id FROM users WHERE email = 'student2@example.com'),
    (SELECT id FROM assessments WHERE name = 'Assignment 1' AND course_id = (SELECT id FROM courses WHERE code = 'CS101')),
    92.00;

-- Insert sample advisor notes
INSERT INTO advisor_notes (student_id, advisor_id, note) 
SELECT 
    (SELECT id FROM users WHERE email = 'student1@example.com'),
    (SELECT id FROM users WHERE email = 'advisor1@example.com'),
    'Student has shown improvement in programming skills but needs to work on time management.';

-- Insert sample remark request
INSERT INTO remark_requests (mark_id, student_id, course_id, justification, status) 
SELECT 
    (SELECT id FROM marks 
     WHERE student_id = (SELECT id FROM users WHERE email = 'student1@example.com') 
     AND assessment_id = (SELECT id FROM assessments WHERE name = 'Midterm Exam' AND course_id = (SELECT id FROM courses WHERE code = 'CS101'))),
    (SELECT id FROM users WHERE email = 'student1@example.com'),
    (SELECT id FROM courses WHERE code = 'CS101'),
    'I believe my answer to question 4 deserves more marks as it correctly addresses all the requirements.',
    'pending';

-- Insert sample notifications
INSERT INTO notifications (user_id, type, content, related_id, sender_id) 
SELECT 
    (SELECT id FROM users WHERE email = 'student1@example.com'),
    'grade_posted',
    'Your grade for CS101 Assignment 1 has been posted.',
    (SELECT id FROM marks 
     WHERE student_id = (SELECT id FROM users WHERE email = 'student1@example.com') 
     AND assessment_id = (SELECT id FROM assessments WHERE name = 'Assignment 1' AND course_id = (SELECT id FROM courses WHERE code = 'CS101'))),
    (SELECT id FROM users WHERE email = 'lecturer1@example.com');

INSERT INTO notifications (user_id, type, content, related_id, sender_id) 
SELECT 
    (SELECT id FROM users WHERE email = 'lecturer1@example.com'),
    'remark_request',
    'Student One has requested a remark for CS101 Midterm Exam.',
    (SELECT id FROM remark_requests 
     WHERE student_id = (SELECT id FROM users WHERE email = 'student1@example.com') 
     AND course_id = (SELECT id FROM courses WHERE code = 'CS101')),
    (SELECT id FROM users WHERE email = 'student1@example.com');

-- Insert sample system logs
INSERT INTO system_logs (action, description, user_id) 
SELECT 
    'user_login',
    'User login successful',
    (SELECT id FROM users WHERE email = 'admin@example.com');

INSERT INTO system_logs (action, description, user_id) 
SELECT 
    'user_create',
    'Created new student account: Student Two',
    (SELECT id FROM users WHERE email = 'admin@example.com');
