// Test Feedback API Script
// Run this in the browser console on localhost:8082

async function testFeedbackAPI() {
    console.log('=== Testing Feedback API ===');
    
    try {
        // Step 1: Test login
        console.log('1. Testing login...');
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

        if (loginData.user && loginData.user.role === 'lecturer') {
            console.log('✅ Login successful');
            
            // Save to localStorage for the app
            localStorage.setItem('token', loginData.token);
            localStorage.setItem('user', JSON.stringify(loginData.user));
            
            const lecturerId = loginData.user.id;
            
            // Step 2: Test lecturer courses API
            console.log('2. Testing lecturer courses...');
            const coursesResponse = await fetch(`http://localhost:8080/marks-api.php?action=lecturer_courses&lecturer_id=${lecturerId}`, {
                credentials: 'include'
            });
            const coursesData = await coursesResponse.json();
            console.log('Courses response:', coursesData);
            
            if (coursesData.courses && coursesData.courses.length > 0) {
                console.log('✅ Courses API working');
                const courseId = coursesData.courses[0].id;
                
                // Step 3: Test feedback API
                console.log('3. Testing feedback API...');
                const feedbackResponse = await fetch(`http://localhost:8080/feedback-api.php?action=lecturer_feedback&lecturer_id=${lecturerId}&course_id=${courseId}`, {
                    credentials: 'include'
                });
                const feedbackData = await feedbackResponse.json();
                console.log('Feedback response:', feedbackData);
                
                if (feedbackData.feedback !== undefined) {
                    console.log('✅ Feedback API working');
                    console.log(`Found ${feedbackData.feedback.length} feedback records`);
                } else {
                    console.log('❌ Feedback API failed:', feedbackData);
                }
                
                // Step 4: Test students for course API
                console.log('4. Testing students for course...');
                const studentsResponse = await fetch(`http://localhost:8080/marks-api.php?action=course_students_marks&course_id=${courseId}`, {
                    credentials: 'include'
                });
                const studentsData = await studentsResponse.json();
                console.log('Students response:', studentsData);
                
                if (studentsData.students) {
                    console.log('✅ Students API working');
                    console.log(`Found ${studentsData.students.length} students`);
                } else {
                    console.log('❌ Students API failed:', studentsData);
                }
                
            } else {
                console.log('❌ No courses found');
            }
            
        } else {
            console.log('❌ Login failed or user is not a lecturer');
        }
        
    } catch (error) {
        console.error('❌ Test error:', error);
    }
}

// Run the test
console.log('Starting Feedback API test...');
testFeedbackAPI();
