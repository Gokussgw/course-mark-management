// Complete CSV Export Debug Test
// Run this in browser console after logging in and navigating to marks management

async function debugCSVExport() {
    console.log('=== Complete CSV Export Debug ===');
    
    // 1. Check authentication
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || 'null');
    console.log('Auth Status:', { 
        hasToken: !!token, 
        user: user?.name, 
        userId: user?.id,
        role: user?.role 
    });
    
    if (!user || user.role !== 'lecturer') {
        console.log('❌ Please log in as a lecturer first');
        return;
    }
    
    // 2. Check Vue app state (if available)
    if (window.Vue && window.Vue.version) {
        console.log('Vue app detected:', window.Vue.version);
    }
    
    // 3. Test courses API
    console.log('--- Testing Courses API ---');
    try {
        const coursesResponse = await fetch(`http://localhost/marks-api.php?action=lecturer_courses&lecturer_id=${user.id}`, {
            method: 'GET',
            credentials: 'include'
        });
        
        if (!coursesResponse.ok) {
            throw new Error(`Courses API failed: ${coursesResponse.status}`);
        }
        
        const coursesData = await coursesResponse.json();
        console.log('Courses API Response:', coursesData);
        
        if (!coursesData.courses || coursesData.courses.length === 0) {
            console.log('❌ No courses found for lecturer');
            return;
        }
        
        const courseId = coursesData.courses[0].id;
        console.log('Using course ID:', courseId);
        
        // 4. Test students marks API
        console.log('--- Testing Students Marks API ---');
        const studentsResponse = await fetch(`http://localhost/marks-api.php?action=course_students_marks&course_id=${courseId}`, {
            method: 'GET',
            credentials: 'include'
        });
        
        if (!studentsResponse.ok) {
            throw new Error(`Students API failed: ${studentsResponse.status}`);
        }
        
        const studentsData = await studentsResponse.json();
        console.log('Students API Response:', studentsData);
        
        // 5. Test Server-side CSV Export
        console.log('--- Testing Server-side CSV Export ---');
        const exportUrl = `http://localhost/marks-api.php?action=export_marks_csv&course_id=${courseId}&lecturer_id=${user.id}`;
        console.log('Export URL:', exportUrl);
        
        const exportResponse = await fetch(exportUrl, {
            method: 'GET',
            credentials: 'include',
            headers: { 'Accept': 'text/csv' }
        });
        
        console.log('Export Response Status:', exportResponse.status);
        console.log('Export Response Headers:', Object.fromEntries(exportResponse.headers));
        
        if (exportResponse.ok) {
            const contentType = exportResponse.headers.get('content-type');
            if (contentType && contentType.includes('text/csv')) {
                console.log('✅ Server-side export successful!');
                
                // Download the file
                const blob = await exportResponse.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `debug_export_${Date.now()}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                console.log('✅ File downloaded successfully');
                
                // Also log first few lines
                const text = await blob.text();
                console.log('CSV Preview (first 200 chars):', text.substring(0, 200));
            } else {
                const errorText = await exportResponse.text();
                console.log('❌ Not CSV response:', errorText);
            }
        } else {
            const errorText = await exportResponse.text();
            console.log('❌ Export failed:', errorText);
        }
        
        // 6. Test Client-side Export Simulation
        console.log('--- Testing Client-side Export Simulation ---');
        if (studentsData.students && studentsData.students.length > 0) {
            const csvData = [];
            
            // Course info
            const course = coursesData.courses[0];
            csvData.push(['Course Code', course.code]);
            csvData.push(['Course Name', course.name]);
            csvData.push(['Export Date', new Date().toLocaleDateString()]);
            csvData.push([]);
            
            // Headers
            csvData.push(['Student Name', 'Matric Number', 'Assignment', 'Quiz', 'Test', 'Final Exam', 'Final Mark', 'Grade']);
            
            // Student data
            studentsData.students.forEach(student => {
                csvData.push([
                    student.name,
                    student.matric_number || 'N/A',
                    student.marks?.assignment || '',
                    student.marks?.quiz || '',
                    student.marks?.test || '',
                    student.marks?.final_exam || '',
                    student.marks?.final_mark || '',
                    student.marks?.grade || ''
                ]);
            });
            
            const csvString = csvData.map(row => row.join(',')).join('\\n');
            console.log('✅ Client-side CSV generated');
            console.log('CSV Preview:', csvString.substring(0, 200));
            
            // Download client-side CSV
            const blob = new Blob([csvString], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `client_export_${Date.now()}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            console.log('✅ Client-side file downloaded');
        } else {
            console.log('❌ No student data for client-side export');
        }
        
        console.log('=== Debug Complete ===');
        
    } catch (error) {
        console.error('❌ Debug Error:', error);
    }
}

// Run the debug
debugCSVExport();
