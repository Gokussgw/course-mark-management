// Test CSV export functionality
// Paste this into browser console on localhost:8082 after logging in

async function testCSVExport() {
    console.log('=== Testing CSV Export Functionality ===');
    
    // Check if user is logged in
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || 'null');
    
    console.log('Auth check:', { token: !!token, user: user });
    
    if (!user) {
        console.log('❌ Please log in first');
        return;
    }
    
    // Test server-side export
    console.log('1. Testing server-side CSV export...');
    try {
        const exportUrl = `http://localhost/marks-api.php?action=export_marks_csv&course_id=1&lecturer_id=${user.id}`;
        console.log('Export URL:', exportUrl);
        
        const response = await fetch(exportUrl, {
            method: 'GET',
            credentials: 'include'
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', Object.fromEntries(response.headers));
        
        if (response.ok) {
            const contentType = response.headers.get('content-type');
            console.log('Content-Type:', contentType);
            
            if (contentType && contentType.includes('text/csv')) {
                console.log('✅ CSV export successful!');
                
                // Create download link
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'test_export.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                console.log('✅ File download initiated');
            } else {
                const text = await response.text();
                console.log('❌ Not a CSV response:', text);
            }
        } else {
            const errorText = await response.text();
            console.log('❌ Export failed:', response.status, errorText);
        }
    } catch (error) {
        console.error('❌ Export error:', error);
    }
    
    // Test client-side data availability
    console.log('2. Testing client-side data...');
    try {
        const coursesResponse = await fetch(`http://localhost/marks-api.php?action=lecturer_courses&lecturer_id=${user.id}`, {
            method: 'GET',
            credentials: 'include'
        });
        
        const coursesData = await coursesResponse.json();
        console.log('Courses data:', coursesData);
        
        if (coursesData.courses && coursesData.courses.length > 0) {
            const courseId = coursesData.courses[0].id;
            console.log('Testing with course ID:', courseId);
            
            const studentsResponse = await fetch(`http://localhost/marks-api.php?action=course_students_marks&course_id=${courseId}`, {
                method: 'GET',
                credentials: 'include'
            });
            
            const studentsData = await studentsResponse.json();
            console.log('Students data:', studentsData);
            
            if (studentsData.students && studentsData.students.length > 0) {
                console.log('✅ Client-side data available for export');
            } else {
                console.log('❌ No student data available');
            }
        } else {
            console.log('❌ No courses available');
        }
    } catch (error) {
        console.error('❌ Data fetch error:', error);
    }
}

// Run the test
testCSVExport();
