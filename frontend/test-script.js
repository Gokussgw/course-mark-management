// Real Database CSV Export Test - Updated to use actual API calls
// You can paste this into the browser console on localhost:8085

async function testRealDatabaseCSVExport() {
    console.log('=== Testing Real Database CSV Export ===');
    
    // Step 1: Test login with real credentials
    console.log('1. Testing login...');
    try {
        const loginResponse = await fetch('http://localhost:8080/db-api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                email: 'lecturer1@example.com',
                password: 'password'
            })
        });

        const loginData = await loginResponse.json();
        console.log('Login response:', loginData);

        if (loginData.user) {
            // Save authentication data
            localStorage.setItem('token', loginData.token);
            localStorage.setItem('user', JSON.stringify(loginData.user));
            console.log('✅ Login successful - saved to localStorage');

            // Step 2: Get real courses data
            console.log('2. Fetching lecturer courses...');
            const coursesResponse = await fetch(`http://localhost:8080/marks-api.php?action=lecturer_courses&lecturer_id=${loginData.user.id}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include'
            });

            const coursesData = await coursesResponse.json();
            console.log('Courses API response:', coursesData);

            if (coursesData.courses && coursesData.courses.length > 0) {
                console.log('✅ Found courses:', coursesData.courses.length);
                
                const course = coursesData.courses[0];
                console.log('Testing with course:', course);

                // Step 3: Get real student marks data
                console.log('3. Fetching student marks for course...');
                const marksResponse = await fetch(`http://localhost:8080/marks-api.php?action=course_students_marks&course_id=${course.id}`, {
                    method: 'GET',
                    credentials: 'include'
                });

                const marksData = await marksResponse.json();
                console.log('Marks data response:', marksData);

                // Step 4: Create comprehensive CSV with real data
                console.log('4. Creating comprehensive CSV export...');
                
                if (marksData.students && marksData.students.length > 0) {
                    await createRealDataCSV(course, marksData.students, marksData.assessments || []);
                } else {
                    console.log('❌ No student data found for this course');
                }

            } else {
                console.log('❌ No courses found for this lecturer');
            }

        } else {
            console.log('❌ Login failed:', loginData.message || 'Unknown error');
        }

    } catch (error) {
        console.error('❌ Error during test:', error);
    }
}

// Function to create CSV with real database data
async function createRealDataCSV(course, students, assessments) {
    console.log('Creating CSV with real data...');
    console.log('Course:', course);
    console.log('Students:', students.length);
    console.log('Assessments:', assessments.length);
    
    try {
        const csvLines = [];
        
        // Header section with course information
        csvLines.push('Course Information');
        csvLines.push(`Course Code,${course.code || 'N/A'}`);
        csvLines.push(`Course Name,"${(course.name || 'Unknown Course').replace(/"/g, '""')}"`);
        csvLines.push(`Lecturer,${course.lecturer_name || 'Unknown'}`);
        csvLines.push(`Export Date,${new Date().toLocaleDateString()}`);
        csvLines.push(`Export Time,${new Date().toLocaleTimeString()}`);
        csvLines.push(`Total Students,${students.length}`);
        csvLines.push('');
        
        // Determine available assessment types from real data
        const assessmentTypes = new Set();
        students.forEach(student => {
            if (student.marks) {
                Object.keys(student.marks).forEach(type => {
                    assessmentTypes.add(type);
                });
            }
        });
        
        console.log('Found assessment types:', Array.from(assessmentTypes));
        
        // Create dynamic column headers based on real assessment data
        const headers = ['Student Name', 'Matric Number', 'Email'];
        
        // Add columns for each assessment type
        Array.from(assessmentTypes).sort().forEach(type => {
            const displayName = type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            headers.push(`${displayName} (%)`);
            headers.push(`${displayName} Points`);
        });
        
        headers.push('Final Mark', 'Grade');
        csvLines.push(headers.join(','));
        
        // Process each student's data
        let totalClassMarks = 0;
        const typeStats = {};
        
        students.forEach(student => {
            const row = [
                `"${(student.name || 'Unknown').replace(/"/g, '""')}"`,
                student.matric_number || 'N/A',
                student.email || 'N/A'
            ];
            
            let studentTotal = 0;
            
            // Add marks for each assessment type
            Array.from(assessmentTypes).sort().forEach(type => {
                if (student.marks && student.marks[type]) {
                    const mark = student.marks[type];
                    const percentage = mark.max_marks > 0 ? Math.round((mark.obtained / mark.max_marks) * 100 * 10) / 10 : 0;
                    const weightedPoints = mark.weighted || 0;
                    
                    row.push(percentage);
                    row.push(Math.round(weightedPoints * 10) / 10);
                    
                    studentTotal += weightedPoints;
                    
                    // Track statistics
                    if (!typeStats[type]) {
                        typeStats[type] = { total: 0, count: 0 };
                    }
                    typeStats[type].total += weightedPoints;
                    typeStats[type].count++;
                    
                } else {
                    row.push('0');
                    row.push('0');
                }
            });
            
            // Calculate final mark and grade
            const finalMark = Math.round(studentTotal * 10) / 10;
            let grade = 'F';
            if (finalMark >= 80) grade = 'A';
            else if (finalMark >= 70) grade = 'B';
            else if (finalMark >= 60) grade = 'C';
            else if (finalMark >= 50) grade = 'D';
            
            row.push(finalMark);
            row.push(grade);
            
            csvLines.push(row.join(','));
            totalClassMarks += finalMark;
        });
        
        // Add statistics section
        csvLines.push('');
        csvLines.push('Statistics');
        
        if (students.length > 0) {
            const classAverage = Math.round((totalClassMarks / students.length) * 10) / 10;
            csvLines.push(`Class Average,${classAverage}`);
            
            // Add statistics for each assessment type
            Object.keys(typeStats).sort().forEach(type => {
                if (typeStats[type].count > 0) {
                    const avg = Math.round((typeStats[type].total / typeStats[type].count) * 10) / 10;
                    const displayName = type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    csvLines.push(`${displayName} Average,${avg}`);
                }
            });
        }
        
        // Add metadata
        csvLines.push('');
        csvLines.push('Export Details');
        csvLines.push(`Generated by,Course Mark Management System`);
        csvLines.push(`Export ID,${course.code}_${Date.now()}`);
        
        // Create and download the CSV file
        const csvContent = csvLines.join('\\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        
        link.href = url;
        link.download = `${course.code}_Real_Marks_${new Date().toISOString().split('T')[0]}.csv`;
        
        // Add link to page and trigger download
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
        console.log('✅ Real database CSV export successful!');
        console.log('Filename:', `${course.code}_Real_Marks_${new Date().toISOString().split('T')[0]}.csv`);
        console.log('Total lines:', csvLines.length);
        console.log('Students processed:', students.length);
        console.log('Assessment types:', Array.from(assessmentTypes));
        
        // Show first few lines for verification
        console.log('\\nFirst 10 lines of CSV:');
        csvLines.slice(0, 10).forEach((line, index) => {
            console.log(`${index + 1}: ${line}`);
        });
        
        return true;
        
    } catch (error) {
        console.error('❌ Error creating CSV:', error);
        return false;
    }
}

// Enhanced function to test API endpoints
async function testAllAPIEndpoints() {
    console.log('=== Testing All API Endpoints ===');
    
    try {
        // Test 1: Login
        console.log('\\n1. Testing Login API...');
        const loginResponse = await fetch('http://localhost:8080/db-api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ email: 'lecturer1@example.com', password: 'password' })
        });
        const loginData = await loginResponse.json();
        
        if (loginData.user) {
            console.log('✅ Login API working');
            localStorage.setItem('token', loginData.token);
            localStorage.setItem('user', JSON.stringify(loginData.user));
            
            const lecturerId = loginData.user.id;
            
            // Test 2: Lecturer Courses
            console.log('\\n2. Testing Lecturer Courses API...');
            const coursesResponse = await fetch(`http://localhost:8080/marks-api.php?action=lecturer_courses&lecturer_id=${lecturerId}`, {
                credentials: 'include'
            });
            const coursesData = await coursesResponse.json();
            console.log('Courses response:', coursesData);
            
            if (coursesData.courses?.length > 0) {
                console.log('✅ Lecturer Courses API working');
                
                const courseId = coursesData.courses[0].id;
                
                // Test 3: Student Marks
                console.log('\\n3. Testing Student Marks API...');
                const marksResponse = await fetch(`http://localhost:8080/marks-api.php?action=course_students_marks&course_id=${courseId}`, {
                    credentials: 'include'
                });
                const marksData = await marksResponse.json();
                console.log('Marks response:', marksData);
                
                if (marksData.students) {
                    console.log('✅ Student Marks API working');
                    console.log(`Found ${marksData.students.length} students`);
                } else {
                    console.log('⚠️ Student Marks API returned no students');
                }
                
            } else {
                console.log('⚠️ No courses found for lecturer');
            }
            
        } else {
            console.log('❌ Login API failed');
        }
        
    } catch (error) {
        console.error('❌ API test error:', error);
    }
}

// Run the comprehensive test
console.log('Starting comprehensive real database test...');
testRealDatabaseCSVExport();

// Uncomment the line below to also test all API endpoints
testAllAPIEndpoints();
