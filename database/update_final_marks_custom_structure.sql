-- Migration script to update final_marks_custom table structure
-- This will add individual Assignment, Quiz, and Test columns instead of just component_total

-- First, let's backup the existing data structure
-- Note: Run this script carefully in a production environment

-- Add new columns for individual component marks
ALTER TABLE final_marks_custom 
ADD COLUMN assignment_mark DECIMAL(5,2) DEFAULT 0.00 AFTER course_id,
ADD COLUMN assignment_percentage DECIMAL(5,2) DEFAULT 0.00 AFTER assignment_mark,
ADD COLUMN quiz_mark DECIMAL(5,2) DEFAULT 0.00 AFTER assignment_percentage,
ADD COLUMN quiz_percentage DECIMAL(5,2) DEFAULT 0.00 AFTER quiz_mark,
ADD COLUMN test_mark DECIMAL(5,2) DEFAULT 0.00 AFTER quiz_percentage,
ADD COLUMN test_percentage DECIMAL(5,2) DEFAULT 0.00 AFTER test_mark;

-- Update existing records to split component_total into individual components if data exists
-- This is a best-effort migration - you may need to adjust based on your data
UPDATE final_marks_custom 
SET 
    assignment_mark = ROUND(component_total * (25/70), 2),
    assignment_percentage = ROUND((component_total * (25/70)) * 100 / 100, 2),
    quiz_mark = ROUND(component_total * (20/70), 2), 
    quiz_percentage = ROUND((component_total * (20/70)) * 100 / 100, 2),
    test_mark = ROUND(component_total * (25/70), 2),
    test_percentage = ROUND((component_total * (25/70)) * 100 / 100, 2)
WHERE component_total > 0;

-- Optional: You can drop the component_total column if you no longer need it
-- Uncomment the following line if you want to remove the old column:
-- ALTER TABLE final_marks_custom DROP COLUMN component_total;

-- Add some indexes for better performance
CREATE INDEX idx_final_marks_student_course ON final_marks_custom(student_id, course_id);
CREATE INDEX idx_final_marks_assignment ON final_marks_custom(assignment_mark);
CREATE INDEX idx_final_marks_quiz ON final_marks_custom(quiz_mark);
CREATE INDEX idx_final_marks_test ON final_marks_custom(test_mark);
