# Course Management for Lecturers

## Overview

Lecturers can now fully manage their courses with Create, Read, Update, and Delete (CRUD) operations through the lecturer dashboard.

## Features

### 1. **View Courses**

- Access your courses from the main dashboard
- See course details including code, name, semester, and academic year
- View the number of assessments for each course
- Click on any course to view detailed information

### 2. **Add New Course**

- Click the "Add Course" button on the dashboard
- Fill in the required information:
  - **Course Code** (required): e.g., "CS101"
  - **Course Name** (required): e.g., "Introduction to Computer Science"
  - **Semester** (optional): Fall, Spring, or Summer
  - **Academic Year** (optional): e.g., "2025-2026"
- Course codes must be unique across the system

### 3. **Edit Existing Course**

- Click the edit button (pencil icon) next to any course
- Modify any course information
- Changes are saved immediately
- Course codes must remain unique

### 4. **Delete Course**

- Click the delete button (trash icon) next to any course
- Confirm the deletion in the popup dialog
- **Warning**: Deleting a course will remove all associated assessments and marks
- Courses with enrolled students cannot be deleted until students are unenrolled

### 5. **Export Course Data**

- Use the "Export CSV" button to download your course list
- Includes all course information and assessment counts
- Useful for record-keeping and reporting

## Security & Permissions

### Lecturer Permissions

- Lecturers can only manage courses assigned to them
- Cannot edit or delete courses belonging to other lecturers
- Automatically assigned as the lecturer when creating new courses

### Data Protection

- All course operations require authentication
- JWT tokens validate user identity and permissions
- Database constraints prevent unauthorized access

## User Interface

### Dashboard Course List

Each course in the list shows:

- Course code and name
- Semester and academic year
- Number of assessments
- Quick action buttons (Edit/Delete)
- Link to detailed course view

### Course Form Modal

The add/edit form includes:

- Clear field labels and placeholders
- Dropdown for semester selection
- Real-time validation
- Cancel and save options

### Delete Confirmation

Before deleting a course:

- Shows course details
- Lists potential data loss (assessments, marks)
- Requires explicit confirmation
- Cannot proceed if students are enrolled

## API Integration

### Backend Endpoints

- `GET /api/courses` - List lecturer's courses
- `POST /api/courses` - Create new course
- `PUT /api/courses/{id}` - Update course
- `DELETE /api/courses/{id}` - Delete course

### Error Handling

- Validation errors display helpful messages
- Network errors are handled gracefully
- Success messages confirm completed actions
- Loading states prevent double-submissions

## Best Practices

### Course Codes

- Use consistent naming conventions (e.g., CS101, MATH201)
- Keep codes short but descriptive
- Include department prefix when applicable

### Course Names

- Use clear, descriptive titles
- Avoid special characters that might cause issues
- Consider including course level or type

### Academic Information

- Always specify semester and academic year
- Use standard semester names: Fall, Spring, Summer
- Format academic years consistently (e.g., "2025-2026")

### Data Management

- Regularly export course data for backup
- Review and update course information each semester
- Remove old courses that are no longer needed (after proper archival)

## Troubleshooting

### Common Issues

**"Course code already exists"**

- Each course code must be unique
- Check if the code is already in use
- Consider adding year or section identifiers

**"Cannot delete course with enrolled students"**

- Remove all student enrollments first
- Or contact an administrator for assistance

**"Unauthorized to update this course"**

- You can only edit courses assigned to you
- Contact an administrator if course ownership is incorrect

**Form validation errors**

- Ensure all required fields are filled
- Check that course codes follow proper format
- Verify semester selection from dropdown

### Getting Help

- Check the system logs for detailed error messages
- Contact technical support for persistent issues
- Refer to user manual for additional guidance

## Future Enhancements

### Planned Features

- Bulk course creation from CSV import
- Course templates for recurring courses
- Student enrollment management
- Course analytics and reporting
- Integration with university course catalog

### Feedback

If you have suggestions for improving course management:

- Contact the development team
- Submit feature requests through the help desk
- Participate in user feedback sessions
