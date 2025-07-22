-- Create advisor_notes table for advisor dashboard
CREATE TABLE IF NOT EXISTS advisor_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    advisor_id INT NOT NULL,
    student_id INT NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (advisor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_advisor_notes_advisor (advisor_id),
    INDEX idx_advisor_notes_student (student_id)
);

-- Insert sample advisor notes
INSERT INTO advisor_notes (advisor_id, student_id, note, created_at) VALUES 
(
    (SELECT id FROM users WHERE email = 'advisor1@example.com'),
    (SELECT id FROM users WHERE email = 'student1@example.com'),
    'Student shows improvement in recent assignments. Discussed time management strategies.',
    '2025-01-15 10:30:00'
),
(
    (SELECT id FROM users WHERE email = 'advisor1@example.com'),
    (SELECT id FROM users WHERE email = 'student2@example.com'),
    'Concerned about attendance. Scheduled follow-up meeting to discuss academic challenges.',
    '2025-01-10 14:15:00'
);
