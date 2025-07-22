<template>
  <div class="marks-management">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item">
              <router-link to="/lecturer/dashboard">Dashboard</router-link>
            </li>
            <li class="breadcrumb-item">
              <router-link :to="`/lecturer/course/${courseId}`">{{ course?.code }}</router-link>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Marks Management</li>
          </ol>
        </nav>
        <h1 class="mb-2">Marks Management</h1>
        <p class="text-muted">
          {{ course?.code }} - {{ course?.name }} | {{ course?.semester }}
        </p>
      </div>
      <div class="btn-group">
        <button class="btn btn-success" @click="saveAllMarks" :disabled="isLoading">
          <i class="fas fa-save me-2"></i>Save All Changes
        </button>
        <button class="btn btn-outline-primary" @click="exportMarksCSV" :disabled="isLoading || !courseId">
          <i class="fas fa-file-export me-2"></i>Export Marks CSV
        </button>
      </div>
    </div>

    <!-- Course Selection -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <label for="courseSelect" class="form-label">Select Course:</label>
            <select 
              id="courseSelect" 
              v-model="courseId" 
              @change="loadCourseData"
              class="form-select"
            >
              <option value="">Select a course...</option>
              <option 
                v-for="course in lecturerCourses" 
                :key="course.id" 
                :value="course.id"
              >
                {{ course.code }} - {{ course.name }}
              </option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Assessment Breakdown:</label>
            <div class="d-flex gap-3">
              <div class="badge bg-primary p-2">
                <i class="fas fa-tasks me-2"></i>Components: 70%
              </div>
              <div class="badge bg-warning p-2">
                <i class="fas fa-graduation-cap me-2"></i>Final Exam: 30%
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="isLoading" class="text-center my-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else-if="!courseId" class="text-center my-5">
      <i class="fas fa-book-open text-muted mb-3" style="font-size: 3rem;"></i>
      <h5 class="text-muted">Select a Course</h5>
      <p class="text-muted">Choose a course from the dropdown above to manage student marks.</p>
    </div>

    <div v-else-if="students.length === 0" class="text-center my-5">
      <i class="fas fa-users text-muted mb-3" style="font-size: 3rem;"></i>
      <h5 class="text-muted">No Students Enrolled</h5>
      <p class="text-muted">There are no students enrolled in this course yet.</p>
    </div>

    <!-- Marks Table -->
    <div v-else class="card">
      <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Student Marks</h5>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-success" @click="calculateAllFinalMarks">
              <i class="fas fa-calculator me-2"></i>Calculate Final Marks
            </button>
            <button class="btn btn-sm btn-outline-info" @click="showMarkingGuide">
              <i class="fas fa-info-circle me-2"></i>Marking Guide
            </button>
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th rowspan="2" class="align-middle">Student</th>
                <th rowspan="2" class="align-middle">Matric No.</th>
                <th colspan="3" class="text-center bg-primary text-white">Components (70%)</th>
                <th rowspan="2" class="align-middle bg-warning text-dark">Final Exam (30%)</th>
                <th rowspan="2" class="align-middle bg-success text-white">Final Mark</th>
                <th rowspan="2" class="align-middle">Grade</th>
                <th rowspan="2" class="align-middle">Actions</th>
              </tr>
              <tr>
                <th class="bg-primary text-white">Assignment</th>
                <th class="bg-primary text-white">Quiz</th>
                <th class="bg-primary text-white">Test</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="student in students" :key="student.id">
                <td>
                  <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm me-3">
                      {{ getStudentInitials(student.name) }}
                    </div>
                    <div>
                      <strong>{{ student.name }}</strong>
                      <br>
                      <small class="text-muted">{{ student.email }}</small>
                    </div>
                  </div>
                </td>
                <td>{{ student.matric_number || 'N/A' }}</td>
                
                <!-- Component Marks (70%) -->
                <td>
                  <input 
                    type="number" 
                    class="form-control form-control-sm"
                    v-model.number="student.marks.assignment"
                    @input="calculateFinalMark(student)"
                    min="0" 
                    max="100"
                    placeholder="0-100"
                  >
                </td>
                <td>
                  <input 
                    type="number" 
                    class="form-control form-control-sm"
                    v-model.number="student.marks.quiz"
                    @input="calculateFinalMark(student)"
                    min="0" 
                    max="100"
                    placeholder="0-100"
                  >
                </td>
                <td>
                  <input 
                    type="number" 
                    class="form-control form-control-sm"
                    v-model.number="student.marks.test"
                    @input="calculateFinalMark(student)"
                    min="0" 
                    max="100"
                    placeholder="0-100"
                  >
                </td>
                
                <!-- Final Exam (30%) -->
                <td>
                  <input 
                    type="number" 
                    class="form-control form-control-sm"
                    v-model.number="student.marks.final_exam"
                    @input="calculateFinalMark(student)"
                    min="0" 
                    max="100"
                    placeholder="0-100"
                  >
                </td>
                
                <!-- Final Mark -->
                <td>
                  <span class="badge bg-success fs-6">
                    {{ student.marks.final_mark ? student.marks.final_mark.toFixed(1) : 'N/A' }}
                  </span>
                </td>
                
                <!-- Grade -->
                <td>
                  <span class="badge" :class="getGradeBadgeClass(student.marks.grade)">
                    {{ student.marks.grade || 'N/A' }}
                  </span>
                </td>
                
                <!-- Actions -->
                <td>
                  <div class="btn-group btn-group-sm">
                    <button 
                      class="btn btn-outline-primary"
                      @click="viewStudentDetails(student)"
                      title="View Details"
                    >
                      <i class="fas fa-eye"></i>
                    </button>
                    <button 
                      class="btn btn-outline-success"
                      @click="saveStudentMarks(student)"
                      title="Save Marks"
                    >
                      <i class="fas fa-save"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Marking Guide Modal -->
    <div class="modal fade" id="markingGuideModal" tabindex="-1" aria-labelledby="markingGuideModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="markingGuideModalLabel">Marking Guide</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <h6>Assessment Breakdown</h6>
                <ul class="list-group list-group-flush">
                  <li class="list-group-item d-flex justify-content-between">
                    <span>Assignment</span>
                    <span class="badge bg-primary">25%</span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between">
                    <span>Quiz</span>
                    <span class="badge bg-primary">20%</span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between">
                    <span>Test</span>
                    <span class="badge bg-primary">25%</span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between">
                    <span><strong>Components Total</strong></span>
                    <span class="badge bg-primary"><strong>70%</strong></span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between">
                    <span><strong>Final Exam</strong></span>
                    <span class="badge bg-warning text-dark"><strong>30%</strong></span>
                  </li>
                </ul>
              </div>
              <div class="col-md-6">
                <h6>Grading Scale</h6>
                <ul class="list-group list-group-flush">
                  <li class="list-group-item d-flex justify-content-between">
                    <span>A</span>
                    <span>80-100</span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between">
                    <span>B</span>
                    <span>70-79</span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between">
                    <span>C</span>
                    <span>60-69</span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between">
                    <span>D</span>
                    <span>50-59</span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between">
                    <span>F</span>
                    <span>0-49</span>
                  </li>
                </ul>
              </div>
            </div>
            <div class="mt-3">
              <h6>Calculation Formula</h6>
              <div class="alert alert-info">
                <strong>Final Grade = (Assignment × 25%) + (Quiz × 20%) + (Test × 25%) + (Final Exam × 30%)</strong>
                <br><small class="text-muted">Components (Assignment + Quiz + Test) contribute 70% total, Final Exam contributes 30%</small>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import * as bootstrap from 'bootstrap';

export default {
  name: 'MarksManagement',
  data() {
    return {
      courseId: this.$route.params.courseId || '',
      course: null,
      students: [],
      lecturerCourses: [],
      isLoading: false,
      unsavedChanges: false
    };
  },
  computed: {
    ...mapGetters('auth', ['getUser', 'userId']),
    currentUser() {
      return this.getUser;
    }
  },
  async created() {
    console.log('MarksManagement component created');
    console.log('Initial auth state:', {
      user: this.getUser,
      userId: this.userId,
      isAuthenticated: this.$store.getters['auth/isAuthenticated']
    });
    
    // Wait a bit for auth to be initialized if needed
    await this.$nextTick();
    
    await this.loadLecturerCourses();
    if (this.courseId) {
      await this.loadCourseData();
    }
  },
  methods: {
    async loadLecturerCourses() {
      try {
        console.log('Loading lecturer courses...');
        console.log('User ID:', this.userId);
        console.log('Current user:', this.currentUser);
        
        if (!this.userId) {
          throw new Error('User not authenticated');
        }
        
        const lecturerId = this.userId;
        
        const response = await fetch(`http://localhost:8080/marks-api.php?action=lecturer_courses&lecturer_id=${lecturerId}`, {
          method: 'GET',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json'
          }
        });

        console.log('API Response status:', response.status);

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('API Response data:', data);
        
        if (data.error) {
          throw new Error(data.error);
        }

        this.lecturerCourses = data.courses || [];
        console.log('Loaded lecturer courses:', this.lecturerCourses);

      } catch (error) {
        console.error('Error loading lecturer courses:', error);
        this.$store.dispatch('showToast', {
          message: 'Error loading courses: ' + error.message,
          type: 'error'
        });
        this.lecturerCourses = [];
      }
    },

    async loadCourseData() {
      if (!this.courseId) return;
      
      this.isLoading = true;
      try {
        // Find the selected course
        this.course = this.lecturerCourses.find(c => c.id == this.courseId);
        
        // Load students for this course with their marks
        await this.loadStudentsWithMarks();
        
      } catch (error) {
        console.error('Error loading course data:', error);
        this.$store.dispatch('showToast', {
          message: 'Error loading course data',
          type: 'error'
        });
      } finally {
        this.isLoading = false;
      }
    },

    async loadStudentsWithMarks() {
      try {
        // Make API call to get students with marks for this course
        const response = await fetch(`http://localhost:8080/marks-api.php?action=course_students_marks&course_id=${this.courseId}`, {
          method: 'GET',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json'
          }
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.error) {
          throw new Error(data.error);
        }

        // Map the API response to the expected format
        this.students = data.students.map(student => ({
          id: student.student_id,
          name: student.student_name,
          email: student.email || 'N/A',
          matric_number: student.matric_number,
          marks: {
            assignment: student.assignment_mark || 0,
            quiz: student.quiz_mark || 0,
            test: student.test_mark || 0,
            final_exam: student.final_exam_mark || 0,
            final_mark: student.final_grade || 0,
            grade: student.letter_grade || 'F'
          }
        }));

        // Calculate final marks for all students to ensure they're up to date
        this.students.forEach(student => {
          this.calculateFinalMark(student);
        });

        console.log('Loaded students with marks:', this.students);

      } catch (error) {
        console.error('Error loading students with marks:', error);
        this.$store.dispatch('showToast', {
          message: 'Error loading student marks: ' + error.message,
          type: 'error'
        });
        
        // Fallback to empty array
        this.students = [];
      }
    },

    calculateFinalMark(student) {
      const marks = student.marks;
      
      // Check if all marks are entered
      const assignment = marks.assignment || 0;
      const quiz = marks.quiz || 0;
      const test = marks.test || 0;
      const finalExam = marks.final_exam || 0;
      
      // Calculate final mark: Components (70%) + Final Exam (30%)
      // Components breakdown: Assignment (25%) + Quiz (20%) + Test (25%)
      const componentMark = (assignment * 0.25) + (quiz * 0.20) + (test * 0.25);
      const finalMark = componentMark + (finalExam * 0.30);
      
      marks.final_mark = finalMark;
      marks.grade = this.calculateGrade(finalMark);
      
      this.unsavedChanges = true;
    },

    calculateGrade(finalMark) {
      if (finalMark >= 80) return 'A';
      if (finalMark >= 70) return 'B';
      if (finalMark >= 60) return 'C';
      if (finalMark >= 50) return 'D';
      return 'F';
    },

    calculateAllFinalMarks() {
      this.students.forEach(student => {
        this.calculateFinalMark(student);
      });
      
      this.$store.dispatch('showToast', {
        message: 'Final marks calculated for all students',
        type: 'success'
      });
    },

    async saveStudentMarks(student) {
      try {
        if (!this.userId) {
          throw new Error('User not authenticated');
        }
        
        const lecturerId = this.userId;
        
        const requestData = {
          action: 'save_marks',
          student_id: student.id,
          course_id: this.courseId,
          lecturer_id: lecturerId,
          marks: {
            assignment: student.marks.assignment || 0,
            quiz: student.marks.quiz || 0,
            test: student.marks.test || 0,
            final_exam: student.marks.final_exam || 0
          }
        };

        const response = await fetch('http://localhost:8080/marks-api.php', {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(requestData)
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.error) {
          throw new Error(data.error);
        }

        console.log('Marks saved successfully for student:', student.name);
        
        this.$store.dispatch('showToast', {
          message: `Marks saved for ${student.name}`,
          type: 'success'
        });

      } catch (error) {
        console.error('Error saving student marks:', error);
        this.$store.dispatch('showToast', {
          message: `Error saving marks for ${student.name}: ${error.message}`,
          type: 'error'
        });
        throw error; // Re-throw so saveAllMarks can handle it
      }
    },

    async saveAllMarks() {
      if (!this.unsavedChanges) {
        this.$store.dispatch('showToast', {
          message: 'No changes to save',
          type: 'info'
        });
        return;
      }

      this.isLoading = true;
      try {
        // Save all student marks to API
        for (const student of this.students) {
          await this.saveStudentMarks(student);
        }
        
        this.unsavedChanges = false;
        this.$store.dispatch('showToast', {
          message: 'All marks saved successfully',
          type: 'success'
        });
      } catch (error) {
        console.error('Error saving all marks:', error);
        this.$store.dispatch('showToast', {
          message: 'Error saving marks',
          type: 'error'
        });
      } finally {
        this.isLoading = false;
      }
    },

    async exportMarksCSV() {
      console.log('Starting enhanced CSV export with real database integration...');
      
      // Enhanced validation checks
      if (!this.courseId) {
        this.$store.dispatch('showToast', {
          message: 'Please select a course first',
          type: 'warning'
        });
        return;
      }

      if (!this.userId) {
        this.$store.dispatch('showToast', {
          message: 'User authentication required',
          type: 'error'
        });
        return;
      }

      if (!this.students || this.students.length === 0) {
        this.$store.dispatch('showToast', {
          message: 'No student data available to export',
          type: 'warning'
        });
        return;
      }

      this.exportingCSV = true;

      try {
        console.log('Creating enhanced CSV export with comprehensive data...');
        
        // Generate filename with enhanced naming
        const timestamp = new Date().toISOString().split('T')[0];
        const timeString = new Date().toISOString().split('T')[1].split('.')[0].replace(/:/g, '-');
        const filename = `${this.course?.code || 'Course'}_Complete_Marks_Export_${timestamp}_${timeString}.csv`;
        
        // Enhanced helper function to escape CSV values
        const escapeCSV = (value) => {
          if (value === null || value === undefined) return '';
          const stringValue = String(value);
          if (stringValue.includes(',') || stringValue.includes('"') || stringValue.includes('\n') || stringValue.includes('\r')) {
            return '"' + stringValue.replace(/"/g, '""') + '"';
          }
          return stringValue;
        };

        // Build comprehensive CSV content
        const csvLines = [];
        
        // Enhanced header section
        csvLines.push('COURSE MARK MANAGEMENT SYSTEM - COMPREHENSIVE EXPORT');
        csvLines.push(''); // Empty line
        
        // Detailed course information
        csvLines.push('COURSE INFORMATION');
        csvLines.push(`Course Code,${escapeCSV(this.course?.code || 'N/A')}`);
        csvLines.push(`Course Name,${escapeCSV(this.course?.name || 'N/A')}`);
        csvLines.push(`Academic Year,${escapeCSV(this.course?.academic_year || new Date().getFullYear())}`);
        csvLines.push(`Semester,${escapeCSV(this.course?.semester || 'Current')}`);
        csvLines.push(`Lecturer,${escapeCSV(this.course?.lecturer_name || this.$store.state.user?.name || 'Unknown')}`);
        csvLines.push(`Export Date,${new Date().toLocaleDateString()}`);
        csvLines.push(`Export Time,${new Date().toLocaleTimeString()}`);
        csvLines.push(`Total Students Enrolled,${this.students.length}`);
        csvLines.push(`Export Generated By,${escapeCSV(this.$store.state.user?.name || 'System')}`);
        csvLines.push(''); // Empty line
        
        // Assessment structure and weightings
        csvLines.push('ASSESSMENT STRUCTURE');
        csvLines.push('Component,Weight (%), Max Raw Score,Points Contribution');
        csvLines.push('Assignment,25%,100,25 points');
        csvLines.push('Quiz,20%,100,20 points');
        csvLines.push('Test,25%,100,25 points');
        csvLines.push('Final Exam,30%,100,30 points');
        csvLines.push('TOTAL,100%,400,100 points');
        csvLines.push(''); // Empty line
        
        // Grading scale information
        csvLines.push('GRADING SCALE');
        csvLines.push('Letter Grade,Points Range,Description');
        csvLines.push('A,80.0 - 100.0,Excellent');
        csvLines.push('B,70.0 - 79.9,Good');
        csvLines.push('C,60.0 - 69.9,Satisfactory');
        csvLines.push('D,50.0 - 59.9,Pass');
        csvLines.push('F,0.0 - 49.9,Fail');
        csvLines.push(''); // Empty line
        
        // Detailed student marks table with calculations
        csvLines.push('DETAILED STUDENT MARKS');
        csvLines.push('Student Name,Matric Number,Email,Assignment Raw,Assignment Points,Quiz Raw,Quiz Points,Test Raw,Test Points,Final Exam Raw,Final Exam Points,Total Points,Final Mark (%),Letter Grade,Academic Status');
        
        // Process each student with enhanced calculations and statistics tracking
        let totalClassPoints = 0;
        let studentsWithMarks = 0;
        const gradeDistribution = { A: 0, B: 0, C: 0, D: 0, F: 0 };
        const componentStats = {
          assignment: { totalRaw: 0, totalPoints: 0, count: 0, highest: 0, lowest: 100 },
          quiz: { totalRaw: 0, totalPoints: 0, count: 0, highest: 0, lowest: 100 },
          test: { totalRaw: 0, totalPoints: 0, count: 0, highest: 0, lowest: 100 },
          final_exam: { totalRaw: 0, totalPoints: 0, count: 0, highest: 0, lowest: 100 }
        };

        this.students.forEach(student => {
          const marks = student.marks || {};
          
          // Calculate individual component scores and points
          const assignmentRaw = parseFloat(marks.assignment) || 0;
          const assignmentPoints = (assignmentRaw * 25) / 100; // 25% weighting
          
          const quizRaw = parseFloat(marks.quiz) || 0;
          const quizPoints = (quizRaw * 20) / 100; // 20% weighting
          
          const testRaw = parseFloat(marks.test) || 0;
          const testPoints = (testRaw * 25) / 100; // 25% weighting
          
          const finalExamRaw = parseFloat(marks.final_exam) || 0;
          const finalExamPoints = (finalExamRaw * 30) / 100; // 30% weighting
          
          // Calculate total points and final percentage
          const totalPoints = assignmentPoints + quizPoints + testPoints + finalExamPoints;
          const finalMark = Math.round(totalPoints * 100) / 100; // Round to 2 decimal places
          
          // Determine letter grade and academic status
          let letterGrade = 'F';
          let academicStatus = 'Fail';
          
          if (finalMark >= 80) {
            letterGrade = 'A';
            academicStatus = 'Excellent';
          } else if (finalMark >= 70) {
            letterGrade = 'B';
            academicStatus = 'Good';
          } else if (finalMark >= 60) {
            letterGrade = 'C';
            academicStatus = 'Satisfactory';
          } else if (finalMark >= 50) {
            letterGrade = 'D';
            academicStatus = 'Pass';
          }
          
          // Update overall statistics
          if (finalMark > 0 || assignmentRaw > 0 || quizRaw > 0 || testRaw > 0 || finalExamRaw > 0) {
            totalClassPoints += finalMark;
            studentsWithMarks++;
          }
          
          gradeDistribution[letterGrade]++;
          
          // Update component statistics
          const components = [
            { name: 'assignment', raw: assignmentRaw, points: assignmentPoints },
            { name: 'quiz', raw: quizRaw, points: quizPoints },
            { name: 'test', raw: testRaw, points: testPoints },
            { name: 'final_exam', raw: finalExamRaw, points: finalExamPoints }
          ];
          
          components.forEach(comp => {
            if (comp.raw > 0) {
              const stats = componentStats[comp.name];
              stats.totalRaw += comp.raw;
              stats.totalPoints += comp.points;
              stats.count++;
              stats.highest = Math.max(stats.highest, comp.raw);
              stats.lowest = Math.min(stats.lowest, comp.raw);
            }
          });
          
          // Create detailed student row
          const row = [
            escapeCSV(student.name || 'Unknown'),
            escapeCSV(student.matric_number || 'N/A'),
            escapeCSV(student.email || 'N/A'),
            assignmentRaw > 0 ? assignmentRaw.toFixed(1) : '',
            assignmentPoints.toFixed(2),
            quizRaw > 0 ? quizRaw.toFixed(1) : '',
            quizPoints.toFixed(2),
            testRaw > 0 ? testRaw.toFixed(1) : '',
            testPoints.toFixed(2),
            finalExamRaw > 0 ? finalExamRaw.toFixed(1) : '',
            finalExamPoints.toFixed(2),
            totalPoints.toFixed(2),
            finalMark.toFixed(2),
            letterGrade,
            academicStatus
          ];
          
          csvLines.push(row.join(','));
        });

        // Add comprehensive statistics and analysis
        csvLines.push(''); // Empty line
        csvLines.push('CLASS PERFORMANCE STATISTICS');
        
        if (studentsWithMarks > 0) {
          const classAverage = totalClassPoints / studentsWithMarks;
          const validFinalMarks = this.students
            .map(s => {
              const marks = s.marks || {};
              const total = ((parseFloat(marks.assignment) || 0) * 0.25) + 
                           ((parseFloat(marks.quiz) || 0) * 0.20) + 
                           ((parseFloat(marks.test) || 0) * 0.25) + 
                           ((parseFloat(marks.final_exam) || 0) * 0.30);
              return total;
            })
            .filter(mark => mark > 0);
          
          if (validFinalMarks.length > 0) {
            const highest = Math.max(...validFinalMarks);
            const lowest = Math.min(...validFinalMarks);
            const median = validFinalMarks.sort((a, b) => a - b)[Math.floor(validFinalMarks.length / 2)];
            
            csvLines.push(`Total Students,${this.students.length}`);
            csvLines.push(`Students with Marks,${studentsWithMarks}`);
            csvLines.push(`Class Average,${classAverage.toFixed(2)}`);
            csvLines.push(`Highest Mark,${highest.toFixed(2)}`);
            csvLines.push(`Lowest Mark,${lowest.toFixed(2)}`);
            csvLines.push(`Median Mark,${median.toFixed(2)}`);
          }
          
          // Grade distribution analysis
          csvLines.push(''); 
          csvLines.push('GRADE DISTRIBUTION ANALYSIS');
          csvLines.push('Letter Grade,Student Count,Percentage of Class,Performance Level');
          
          Object.entries(gradeDistribution).forEach(([grade, count]) => {
            const percentage = this.students.length > 0 ? (count / this.students.length * 100).toFixed(1) : '0.0';
            let performanceLevel = '';
            
            switch(grade) {
              case 'A': performanceLevel = 'Excellent Performance'; break;
              case 'B': performanceLevel = 'Good Performance'; break;
              case 'C': performanceLevel = 'Satisfactory Performance'; break;
              case 'D': performanceLevel = 'Minimum Pass'; break;
              case 'F': performanceLevel = 'Needs Improvement'; break;
            }
            
            csvLines.push(`${grade},${count},${percentage}%,${performanceLevel}`);
          });
          
          // Component performance analysis
          csvLines.push('');
          csvLines.push('COMPONENT PERFORMANCE ANALYSIS');
          csvLines.push('Assessment Component,Students Assessed,Raw Score Average,Points Average,Highest Score,Lowest Score,Performance Rating');
          
          Object.entries(componentStats).forEach(([component, stats]) => {
            if (stats.count > 0) {
              const displayName = component.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
              const rawAverage = (stats.totalRaw / stats.count).toFixed(2);
              const pointsAverage = (stats.totalPoints / stats.count).toFixed(2);
              
              let performanceRating = 'Needs Improvement';
              const avgRaw = stats.totalRaw / stats.count;
              if (avgRaw >= 80) performanceRating = 'Excellent';
              else if (avgRaw >= 70) performanceRating = 'Good';
              else if (avgRaw >= 60) performanceRating = 'Satisfactory';
              else if (avgRaw >= 50) performanceRating = 'Fair';
              
              csvLines.push(`${displayName},${stats.count},${rawAverage},${pointsAverage},${stats.highest.toFixed(1)},${stats.lowest.toFixed(1)},${performanceRating}`);
            }
          });
        }

        // Add export metadata and technical information
        csvLines.push('');
        csvLines.push('EXPORT METADATA');
        csvLines.push(`Export Format,CSV (Comma Separated Values)`);
        csvLines.push(`Character Encoding,UTF-8`);
        csvLines.push(`System Version,Course Mark Management System v1.0`);
        csvLines.push(`Database Connection,Real-time MySQL Database`);
        csvLines.push(`Export Timestamp,${new Date().toISOString()}`);
        csvLines.push(`Unique Export ID,${this.course?.code || 'COURSE'}_${Date.now()}`);
        csvLines.push(`Total Data Rows,${csvLines.length + 2}`);
        csvLines.push(`File Size (estimated),${(csvLines.join('\n').length * 1.1).toFixed(0)} bytes`);

        // Create final CSV content
        const csvContent = csvLines.join('\n');
        
        // Enhanced logging
        console.log('✅ Enhanced CSV content generated successfully');
        console.log('📊 Export Statistics:');
        console.log(`  - Total lines: ${csvLines.length}`);
        console.log(`  - Content size: ${csvContent.length} characters`);
        console.log(`  - Students processed: ${this.students.length}`);
        console.log(`  - Students with marks: ${studentsWithMarks}`);
        console.log(`  - File name: ${filename}`);
        
        // Create blob and download
        const blob = new Blob([csvContent], { 
          type: 'text/csv;charset=utf-8;' 
        });
        
        console.log(`📁 Blob created: ${blob.size} bytes`);
        
        // Create download link and trigger download
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.style.display = 'none';
        
        document.body.appendChild(link);
        console.log('⬇️ Triggering download...');
        link.click();
        document.body.removeChild(link);
        
        // Clean up URL object
        setTimeout(() => {
          window.URL.revokeObjectURL(url);
        }, 100);
        
        console.log('✅ Enhanced CSV export completed successfully');
        
        // Show success message
        this.$store.dispatch('showToast', {
          message: `Enhanced marks export completed! Downloaded: ${filename}`,
          type: 'success'
        });
        
      } catch (error) {
        console.error('❌ Enhanced CSV export error:', error);
        this.$store.dispatch('showToast', {
          message: 'Error during enhanced export: ' + error.message,
          type: 'error'
        });
      } finally {
        this.exportingCSV = false;
      }
    },

    showMarkingGuide() {
      const modal = new bootstrap.Modal(document.getElementById('markingGuideModal'));
      modal.show();
    },

    viewStudentDetails(student) {
      this.$router.push(`/lecturer/student/${student.id}`);
    },

    getStudentInitials(name) {
      return name.split(' ').map(n => n.charAt(0)).join('').toUpperCase();
    },

    getGradeBadgeClass(grade) {
      const gradeClasses = {
        'A': 'bg-success',
        'B': 'bg-info',
        'C': 'bg-warning text-dark',
        'D': 'bg-secondary',
        'F': 'bg-danger'
      };
      return gradeClasses[grade] || 'bg-light text-dark';
    }
  }
};
</script>

<style scoped>
.marks-management h1 {
  color: #2c3e50;
  font-weight: 700;
}

.avatar {
  width: 40px;
  height: 40px;
  background: linear-gradient(45deg, #007bff, #0056b3);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 0.9rem;
}

.table input.form-control-sm {
  width: 80px;
  text-align: center;
}

.table th {
  font-weight: 600;
  border-top: none;
}

.table td {
  vertical-align: middle;
}

.card {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border: none;
  border-radius: 10px;
}

.breadcrumb-item a {
  text-decoration: none;
  color: #007bff;
}

.breadcrumb-item a:hover {
  color: #0056b3;
}

.badge.fs-6 {
  font-size: 0.9rem !important;
}
</style>
