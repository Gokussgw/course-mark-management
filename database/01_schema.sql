-- Course Mark Management Database Schema - Part 1: Tables

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
