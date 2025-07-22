-- Course Mark Management Database Schema

-- Drop tables if they exist
DROP TABLE IF EXISTS system_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS remark_requests;
DROP TABLE IF EXISTS marks;
DROP TABLE IF EXISTS assessments;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS advisor_notes;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('lecturer', 'student', 'advisor', 'admin') NOT NULL,
    matric_number VARCHAR(20) UNIQUE,
    pin VARCHAR(255),
    advisor_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (advisor_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create courses table
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    lecturer_id INT,
    semester VARCHAR(20),
    academic_year VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create enrollments table
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE (student_id, course_id, academic_year, semester)
);

-- Create advisor notes
CREATE TABLE advisor_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    advisor_id INT NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (advisor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create assessments table
CREATE TABLE assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('quiz', 'assignment', 'midterm', 'final_exam', 'other') NOT NULL,
    weightage DECIMAL(5,2) NOT NULL, -- Percentage of total mark
    max_mark DECIMAL(10,2) NOT NULL,
    is_final_exam BOOLEAN DEFAULT FALSE,
    date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Create marks table
CREATE TABLE marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    assessment_id INT NOT NULL,
    mark DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
    UNIQUE (student_id, assessment_id)
);

-- Create remark requests table
CREATE TABLE remark_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mark_id INT NOT NULL,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    justification TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'resolved') DEFAULT 'pending',
    lecturer_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mark_id) REFERENCES marks(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Create notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    content TEXT NOT NULL,
    related_id INT,
    sender_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create system logs table
CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert sample data for testing
INSERT INTO users (name, email, password, role) 
VALUES ('Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT INTO users (name, email, password, role) 
VALUES ('Lecturer One', 'lecturer1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer');

INSERT INTO users (name, email, password, role) 
VALUES ('Advisor One', 'advisor1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'advisor');

INSERT INTO users (name, email, password, role, matric_number, pin) 
VALUES ('Student One', 'student1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'A12345', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO users (name, email, password, role, matric_number, pin) 
VALUES ('Student Two', 'student2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'A12346', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample courses
INSERT INTO courses (code, name, lecturer_id, semester, academic_year) 
VALUES ('CS101', 'Introduction to Computer Science', (SELECT id FROM users WHERE email = 'lecturer1@example.com'), 'Fall', '2025-2026');

INSERT INTO courses (code, name, lecturer_id, semester, academic_year) 
VALUES ('CS201', 'Data Structures and Algorithms', (SELECT id FROM users WHERE email = 'lecturer1@example.com'), 'Fall', '2025-2026');

-- Enroll students in courses
INSERT INTO enrollments (student_id, course_id, academic_year, semester) 
VALUES ((SELECT id FROM users WHERE email = 'student1@example.com'), 
        (SELECT id FROM courses WHERE code = 'CS101'), 
        '2025-2026', 'Fall');

INSERT INTO enrollments (student_id, course_id, academic_year, semester) 
VALUES ((SELECT id FROM users WHERE email = 'student1@example.com'), 
        (SELECT id FROM courses WHERE code = 'CS201'), 
        '2025-2026', 'Fall');

INSERT INTO enrollments (student_id, course_id, academic_year, semester) 
VALUES ((SELECT id FROM users WHERE email = 'student2@example.com'), 
        (SELECT id FROM courses WHERE code = 'CS101'), 
        '2025-2026', 'Fall');

-- Insert sample assessments
INSERT INTO assessments (course_id, name, type, weightage, max_mark, is_final_exam, date) 
VALUES ((SELECT id FROM courses WHERE code = 'CS101'), 
        'Assignment 1', 'assignment', 10.00, 100.00, FALSE, '2025-09-15');

INSERT INTO assessments (course_id, name, type, weightage, max_mark, is_final_exam, date) 
VALUES ((SELECT id FROM courses WHERE code = 'CS101'), 
        'Midterm Exam', 'midterm', 30.00, 100.00, FALSE, '2025-10-15');

INSERT INTO assessments (course_id, name, type, weightage, max_mark, is_final_exam, date) 
VALUES ((SELECT id FROM courses WHERE code = 'CS101'), 
        'Final Exam', 'final_exam', 60.00, 100.00, TRUE, '2025-12-15');

INSERT INTO assessments (course_id, name, type, weightage, max_mark, is_final_exam, date) 
VALUES ((SELECT id FROM courses WHERE code = 'CS201'), 
        'Assignment 1', 'assignment', 15.00, 100.00, FALSE, '2025-09-20');

INSERT INTO assessments (course_id, name, type, weightage, max_mark, is_final_exam, date) 
VALUES ((SELECT id FROM courses WHERE code = 'CS201'), 
        'Final Project', 'assignment', 25.00, 100.00, FALSE, '2025-11-30');

INSERT INTO assessments (course_id, name, type, weightage, max_mark, is_final_exam, date) 
VALUES ((SELECT id FROM courses WHERE code = 'CS201'), 
        'Final Exam', 'final_exam', 60.00, 100.00, TRUE, '2025-12-20');

-- Insert sample marks
INSERT INTO marks (student_id, assessment_id, mark) 
VALUES ((SELECT id FROM users WHERE email = 'student1@example.com'), 
        (SELECT id FROM assessments WHERE name = 'Assignment 1' AND course_id = (SELECT id FROM courses WHERE code = 'CS101')), 
        85.00);

INSERT INTO marks (student_id, assessment_id, mark) 
VALUES ((SELECT id FROM users WHERE email = 'student1@example.com'), 
        (SELECT id FROM assessments WHERE name = 'Midterm Exam' AND course_id = (SELECT id FROM courses WHERE code = 'CS101')), 
        78.00);

INSERT INTO marks (student_id, assessment_id, mark) 
VALUES ((SELECT id FROM users WHERE email = 'student2@example.com'), 
        (SELECT id FROM assessments WHERE name = 'Assignment 1' AND course_id = (SELECT id FROM courses WHERE code = 'CS101')), 
        92.00);

-- Insert sample advisor notes
INSERT INTO advisor_notes (student_id, advisor_id, note) 
VALUES ((SELECT id FROM users WHERE email = 'student1@example.com'), 
        (SELECT id FROM users WHERE email = 'advisor1@example.com'), 
        'Student has shown improvement in programming skills but needs to work on time management.');

-- Insert sample remark request
INSERT INTO remark_requests (mark_id, student_id, course_id, justification, status) 
VALUES ((SELECT id FROM marks 
          WHERE student_id = (SELECT id FROM users WHERE email = 'student1@example.com') 
          AND assessment_id = (SELECT id FROM assessments WHERE name = 'Midterm Exam' AND course_id = (SELECT id FROM courses WHERE code = 'CS101'))), 
        (SELECT id FROM users WHERE email = 'student1@example.com'), 
        (SELECT id FROM courses WHERE code = 'CS101'), 
        'I believe my answer to question 4 deserves more marks as it correctly addresses all the requirements.', 
        'pending');

-- Insert sample notifications
INSERT INTO notifications (user_id, type, content, related_id, sender_id) 
VALUES ((SELECT id FROM users WHERE email = 'student1@example.com'), 
        'grade_posted', 
        'Your grade for CS101 Assignment 1 has been posted.', 
        (SELECT id FROM marks 
          WHERE student_id = (SELECT id FROM users WHERE email = 'student1@example.com') 
          AND assessment_id = (SELECT id FROM assessments WHERE name = 'Assignment 1' AND course_id = (SELECT id FROM courses WHERE code = 'CS101'))), 
        (SELECT id FROM users WHERE email = 'lecturer1@example.com'));

INSERT INTO notifications (user_id, type, content, related_id, sender_id) 
VALUES ((SELECT id FROM users WHERE email = 'lecturer1@example.com'), 
        'remark_request', 
        'Student One has requested a remark for CS101 Midterm Exam.', 
        (SELECT id FROM remark_requests 
          WHERE student_id = (SELECT id FROM users WHERE email = 'student1@example.com') 
          AND course_id = (SELECT id FROM courses WHERE code = 'CS101')), 
        (SELECT id FROM users WHERE email = 'student1@example.com'));

-- Insert sample system logs
INSERT INTO system_logs (action, description, user_id) 
VALUES ('user_login', 
        'User login successful', 
        (SELECT id FROM users WHERE email = 'admin@example.com'));

INSERT INTO system_logs (action, description, user_id) 
VALUES ('user_create', 
        'Created new student account: Student Two', 
        (SELECT id FROM users WHERE email = 'admin@example.com'));
