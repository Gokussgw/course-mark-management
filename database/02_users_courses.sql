-- Course Mark Management Database Schema - Part 2: Sample Data

-- Insert sample users
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

-- Update advisor relationships
UPDATE users SET advisor_id = (SELECT id FROM (SELECT id FROM users WHERE email = 'advisor1@example.com') AS t)
WHERE email IN ('student1@example.com', 'student2@example.com');

-- Insert sample courses
INSERT INTO courses (code, name, lecturer_id, semester, academic_year) 
VALUES ('CS101', 'Introduction to Computer Science', 
        (SELECT id FROM users WHERE email = 'lecturer1@example.com'), 
        'Fall', '2025-2026');

INSERT INTO courses (code, name, lecturer_id, semester, academic_year) 
VALUES ('CS201', 'Data Structures and Algorithms', 
        (SELECT id FROM users WHERE email = 'lecturer1@example.com'), 
        'Fall', '2025-2026');
