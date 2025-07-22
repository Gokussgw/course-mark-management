<template>
  <div class="course-detail">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item">
              <router-link to="/lecturer/dashboard">Dashboard</router-link>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              {{ course?.code }} - {{ course?.name }}
            </li>
          </ol>
        </nav>
        <h1 class="mb-2">{{ course?.name }}</h1>
        <p class="text-muted">
          {{ course?.code }} | {{ course?.semester || 'No semester specified' }}
        </p>
      </div>
      <div>
        <div class="btn-group">
          <router-link 
            :to="`/lecturer/course/${courseId}/marks`" 
            class="btn btn-success"
          >
            <i class="fas fa-graduation-cap me-2"></i> Manage Marks
          </router-link>
          <button class="btn btn-outline-primary" @click="exportCourseData">
            <i class="fas fa-file-export me-2"></i> Export Data
          </button>
          <button class="btn btn-outline-primary" @click="openEditCourseModal">
            <i class="fas fa-edit me-2"></i> Edit Course
          </button>
        </div>
      </div>
    </div>

    <div v-if="isLoading" class="text-center my-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else class="row">
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Course Information</h5>
          </div>
          <div class="card-body">
            <table class="table table-sm">
              <tbody>
                <tr>
                  <th>Code:</th>
                  <td>{{ course?.code }}</td>
                </tr>
                <tr>
                  <th>Name:</th>
                  <td>{{ course?.name }}</td>
                </tr>
                <tr>
                  <th>Semester:</th>
                  <td>{{ course?.semester || 'Not specified' }}</td>
                </tr>
                <tr>
                  <th>Credits:</th>
                  <td>{{ course?.credits || 'Not specified' }}</td>
                </tr>
                <tr>
                  <th>Students:</th>
                  <td>{{ enrolledStudents.length }}</td>
                </tr>
                <tr>
                  <th>Students with Marks:</th>
                  <td>{{ studentsWithMarks }}</td>
                </tr>
                <tr>
                  <th>Class Average:</th>
                  <td>{{ courseBreakdown.class_average || 'N/A' }}%</td>
                </tr>
              </tbody>
            </table>
            <div v-if="course?.description" class="mt-3">
              <h6>Description</h6>
              <p>{{ course.description }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-8 mb-4">
        <div class="card h-100">
          <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Component Breakdown</h5>
            <router-link :to="`/lecturer/breakdown/${courseId}`" class="btn btn-sm btn-primary">
              <i class="fas fa-chart-bar me-2"></i> View Detailed Analytics
            </router-link>
          </div>
          <div class="card-body">
            <div v-if="isLoadingBreakdown" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading component data...</span>
              </div>
            </div>
            <div v-else>
              <!-- Component Weightage Progress Bar -->
              <div class="mb-4">
                <div class="progress" style="height: 30px;">
                  <div class="progress-bar bg-success" 
                       style="width: 25%"
                       title="Assignment (25%)">
                    Assignment 25%
                  </div>
                  <div class="progress-bar bg-info" 
                       style="width: 20%"
                       title="Quiz (20%)">
                    Quiz 20%
                  </div>
                  <div class="progress-bar bg-warning" 
                       style="width: 25%"
                       title="Test (25%)">
                    Test 25%
                  </div>
                  <div class="progress-bar bg-danger" 
                       style="width: 30%"
                       title="Final Exam (30%)">
                    Final Exam 30%
                  </div>
                </div>
              </div>
              
              <!-- Component Statistics -->
              <div class="row">
                <div class="col-md-6 mb-3">
                  <div class="card border-light">
                    <div class="card-body p-3">
                      <h6 class="card-title text-success">
                        <i class="fas fa-file-alt me-2"></i>Assignment (25%)
                      </h6>
                      <div class="d-flex justify-content-between">
                        <span>Class Average:</span>
                        <strong>{{ courseBreakdown.assignment?.average || 'N/A' }}%</strong>
                      </div>
                      <div class="d-flex justify-content-between">
                        <span>Highest Score:</span>
                        <strong>{{ courseBreakdown.assignment?.highest || 'N/A' }}%</strong>
                      </div>
                      <div class="d-flex justify-content-between">
                        <span>Students Completed:</span>
                        <strong>{{ courseBreakdown.assignment?.completed || 0 }}/{{ enrolledStudents.length }}</strong>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="col-md-6 mb-3">
                  <div class="card border-light">
                    <div class="card-body p-3">
                      <h6 class="card-title text-info">
                        <i class="fas fa-question-circle me-2"></i>Quiz (20%)
                      </h6>
                      <div class="d-flex justify-content-between">
                        <span>Class Average:</span>
                        <strong>{{ courseBreakdown.quiz?.average || 'N/A' }}%</strong>
                      </div>
                      <div class="d-flex justify-content-between">
                        <span>Highest Score:</span>
                        <strong>{{ courseBreakdown.quiz?.highest || 'N/A' }}%</strong>
                      </div>
                      <div class="d-flex justify-content-between">
                        <span>Students Completed:</span>
                        <strong>{{ courseBreakdown.quiz?.completed || 0 }}/{{ enrolledStudents.length }}</strong>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="col-md-6 mb-3">
                  <div class="card border-light">
                    <div class="card-body p-3">
                      <h6 class="card-title text-warning">
                        <i class="fas fa-clipboard-check me-2"></i>Test (25%)
                      </h6>
                      <div class="d-flex justify-content-between">
                        <span>Class Average:</span>
                        <strong>{{ courseBreakdown.test?.average || 'N/A' }}%</strong>
                      </div>
                      <div class="d-flex justify-content-between">
                        <span>Highest Score:</span>
                        <strong>{{ courseBreakdown.test?.highest || 'N/A' }}%</strong>
                      </div>
                      <div class="d-flex justify-content-between">
                        <span>Students Completed:</span>
                        <strong>{{ courseBreakdown.test?.completed || 0 }}/{{ enrolledStudents.length }}</strong>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="col-md-6 mb-3">
                  <div class="card border-light">
                    <div class="card-body p-3">
                      <h6 class="card-title text-danger">
                        <i class="fas fa-graduation-cap me-2"></i>Final Exam (30%)
                      </h6>
                      <div class="d-flex justify-content-between">
                        <span>Class Average:</span>
                        <strong>{{ courseBreakdown.final_exam?.average || 'N/A' }}%</strong>
                      </div>
                      <div class="d-flex justify-content-between">
                        <span>Highest Score:</span>
                        <strong>{{ courseBreakdown.final_exam?.highest || 'N/A' }}%</strong>
                      </div>
                      <div class="d-flex justify-content-between">
                        <span>Students Completed:</span>
                        <strong>{{ courseBreakdown.final_exam?.completed || 0 }}/{{ enrolledStudents.length }}</strong>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12 mb-4">
        <div class="card">
          <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Enrolled Students</h5>
            <div class="input-group w-25">
              <input 
                type="text" 
                class="form-control" 
                placeholder="Search students..." 
                v-model="studentSearchQuery"
              >
              <button class="btn btn-outline-secondary" type="button">
                <i class="fas fa-search"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <div v-if="enrolledStudents.length === 0" class="text-center py-4">
              <p>No students are enrolled in this course yet.</p>
            </div>
            <div v-else class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Overall Progress</th>
                    <th>Current Mark</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="student in filteredStudents" :key="student.id">
                    <td>{{ student.student_id || 'N/A' }}</td>
                    <td>{{ student.name }}</td>
                    <td>{{ student.email }}</td>
                    <td>
                      <div class="progress">
                        <div 
                          class="progress-bar bg-success" 
                          role="progressbar"
                          :style="`width: ${getStudentCompletionProgress(student.id)}%`"
                          :aria-valuenow="getStudentCompletionProgress(student.id)" 
                          aria-valuemin="0" 
                          aria-valuemax="100">
                          {{ getStudentCompletionProgress(student.id) }}%
                        </div>
                      </div>
                    </td>
                    <td>
                      <span 
                        :class="getMarkClass(getStudentFinalGrade(student.id))">
                        {{ getStudentFinalGrade(student.id) }}
                      </span>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" @click="navigateToStudentDetail(student)">
                          <i class="fas fa-user me-1"></i> Details
                        </button>
                        <button class="btn btn-outline-secondary" @click="navigateToStudentMarks(student)">
                          <i class="fas fa-calculator me-1"></i> Marks
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Course Modal -->
    <div class="modal fade" id="editCourseModal" tabindex="-1" aria-labelledby="editCourseModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editCourseModalLabel">Edit Course</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Course edit form will go here -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="saveCourseChanges">Save Changes</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapActions } from 'vuex'

export default {
  name: 'CourseDetail',
  data() {
    return {
      courseId: null,
      studentSearchQuery: '',
      isLoadingBreakdown: false,
      courseBreakdown: {},
      studentsMarks: [],
      editFormData: {
        code: '',
        name: '',
        semester: '',
        credits: '',
        description: ''
      }
    }
  },
  computed: {
    ...mapState({
      isLoading: state => state.loading,
      course: state => state.courses.course,
    }),
    enrolledStudents() {
      // This would be populated from a store getter once implemented
      return this.$store.state.users.courseStudents || [];
    },
    studentsWithMarks() {
      return this.studentsMarks.filter(student => 
        student.assignment_mark !== null || 
        student.quiz_mark !== null || 
        student.test_mark !== null || 
        student.final_exam_mark !== null
      ).length;
    },
    filteredStudents() {
      if (!this.studentSearchQuery) {
        return this.enrolledStudents;
      }
      
      const query = this.studentSearchQuery.toLowerCase();
      return this.enrolledStudents.filter(student => 
        student.name.toLowerCase().includes(query) || 
        student.email.toLowerCase().includes(query) ||
        (student.student_id && student.student_id.toString().includes(query))
      );
    }
  },
  async created() {
    this.courseId = this.$route.params.id;
    try {
      // Fetch course details
      await this.fetchCourse(this.courseId);
      
      // Fetch enrolled students
      await this.fetchCourseStudents(this.courseId);
      
      // Fetch component breakdown data
      await this.fetchCourseBreakdown();
      
      // Fetch students marks from final_marks_custom
      await this.fetchStudentsMarks();
      
      // Initialize edit form data
      this.initializeEditForm();
    } catch (error) {
      console.error('Error loading course details:', error);
    }
  },
  methods: {
    ...mapActions({
      fetchCourse: 'courses/fetchCourse',
      updateCourse: 'courses/updateCourse'
    }),
    async fetchCourseStudents(courseId) {
      try {
        const response = await fetch(`http://localhost:8080/marks-api.php?action=course_students_list&course_id=${courseId}`);
        const data = await response.json();
        
        if (data.success) {
          // Store in Vuex or local data
          this.$store.commit('users/setCourseStudents', data.students || []);
        } else {
          console.error('Failed to fetch course students:', data.message);
        }
      } catch (error) {
        console.error('Error fetching course students:', error);
      }
    },
    async fetchCourseBreakdown() {
      try {
        this.isLoadingBreakdown = true;
        const response = await fetch(`http://localhost:8080/breakdown-api.php?action=course_breakdown&course_id=${this.courseId}`);
        const data = await response.json();
        
        // Check if the response has an error property (API error response)
        if (data.error) {
          console.error('Failed to fetch course breakdown:', data.error);
        } else {
          // The API returns data directly, not wrapped in a data property
          // Create component breakdown from the response
          const componentBreakdown = {};
          if (data.component_breakdown) {
            data.component_breakdown.forEach(comp => {
              componentBreakdown[comp.type] = {
                average: Math.round(comp.average_percentage || 0),
                highest: Math.round(comp.average_percentage || 0), // Using average as highest for now
                completed: comp.submissions || 0
              };
            });
          }
          
          // Calculate highest scores from student data
          if (data.students) {
            data.students.forEach(student => {
              if (student.marks) {
                Object.keys(student.marks).forEach(type => {
                  if (componentBreakdown[type]) {
                    const percentage = parseFloat(student.marks[type].percentage || 0);
                    if (percentage > componentBreakdown[type].highest) {
                      componentBreakdown[type].highest = Math.round(percentage);
                    }
                  }
                });
              }
            });
          }
          
          this.courseBreakdown = {
            ...componentBreakdown,
            class_average: Math.round(data.statistics?.class_average || 0)
          };
        }
      } catch (error) {
        console.error('Error fetching course breakdown:', error);
      } finally {
        this.isLoadingBreakdown = false;
      }
    },
    async fetchStudentsMarks() {
      try {
        const response = await fetch(`http://localhost:8080/marks-api.php?action=course_students_with_marks&course_id=${this.courseId}`);
        const data = await response.json();
        
        if (data.success) {
          this.studentsMarks = data.students || [];
        } else {
          console.error('Failed to fetch students marks:', data.message);
        }
      } catch (error) {
        console.error('Error fetching students marks:', error);
      }
    },
    formatDate(dateString) {
      if (!dateString) return 'Not set';
      return new Date(dateString).toLocaleDateString();
    },
    getStudentCompletionProgress(studentId) {
      // Calculate completion progress based on how many components the student has completed
      const studentData = this.studentsMarks.find(s => s.id === studentId);
      if (!studentData) return 0;
      
      let completedComponents = 0;
      if (studentData.assignment_mark !== null) completedComponents++;
      if (studentData.quiz_mark !== null) completedComponents++;
      if (studentData.test_mark !== null) completedComponents++;
      if (studentData.final_exam_mark !== null) completedComponents++;
      
      return Math.round((completedComponents / 4) * 100);
    },
    getStudentFinalGrade(studentId) {
      // Get final grade from final_marks_custom data
      const studentData = this.studentsMarks.find(s => s.id === studentId);
      if (!studentData) return 'N/A';
      
      return studentData.final_grade || 'N/A';
    },
    getMarkClass(markString) {
      if (markString === 'N/A') return 'text-muted';
      
      // Handle grade strings (A+, A, B+, etc.)
      if (typeof markString === 'string' && markString.match(/^[A-F][+-]?$/)) {
        const gradeOrder = ['F', 'D', 'D+', 'C-', 'C', 'C+', 'B-', 'B', 'B+', 'A-', 'A', 'A+'];
        const gradeIndex = gradeOrder.indexOf(markString);
        if (gradeIndex >= 8) return 'text-success fw-bold'; // B+ and above
        if (gradeIndex >= 4) return 'text-warning'; // C and above
        return 'text-danger'; // Below C
      }
      
      // Handle percentage strings
      const mark = parseInt(markString);
      if (isNaN(mark)) return 'text-muted';
      
      if (mark >= 70) return 'text-success fw-bold';
      if (mark >= 50) return 'text-warning';
      return 'text-danger';
    },
    navigateToStudentDetail(student) {
      this.$router.push({ 
        name: 'StudentDetail', 
        params: { id: student.id },
        query: { courseId: this.courseId }
      });
    },
    navigateToStudentMarks(student) {
      // Navigate to marks management for specific student
      this.$router.push({ 
        path: `/lecturer/course/${this.courseId}/marks`,
        query: { studentId: student.id }
      });
    },
    openEditCourseModal() {
      this.initializeEditForm();
      // In a real implementation, we would use Bootstrap's modal methods
      // $('#editCourseModal').modal('show');
    },
    initializeEditForm() {
      if (this.course) {
        this.editFormData = {
          code: this.course.code || '',
          name: this.course.name || '',
          semester: this.course.semester || '',
          credits: this.course.credits || '',
          description: this.course.description || ''
        };
      }
    },
    async saveCourseChanges() {
      try {
        await this.updateCourse({
          id: this.courseId,
          ...this.editFormData
        });
        // Close modal
        // $('#editCourseModal').modal('hide');
        this.$store.dispatch('showToast', {
          message: 'Course updated successfully',
          type: 'success'
        });
      } catch (error) {
        console.error('Error updating course:', error);
      }
    },
    exportCourseData() {
      // This would initiate a download of course data in CSV format
      // Implementation would need to call an API endpoint
      this.$store.dispatch('showToast', {
        message: 'Exporting course data...',
        type: 'info'
      });
    }
  }
}
</script>

<style scoped>
.course-detail h1 {
  font-size: 2rem;
  font-weight: 600;
}

.progress {
  height: 20px;
}

.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
  background-color: rgba(0, 0, 0, 0.03);
  border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.table th {
  font-weight: 600;
}

.badge {
  font-weight: 500;
  padding: 0.35em 0.65em;
}

/* Timeline styling for recent activities */
.timeline {
  position: relative;
  padding-left: 40px;
}

.timeline-item {
  position: relative;
  margin-bottom: 20px;
}

.timeline-item:before {
  content: "";
  position: absolute;
  left: -30px;
  top: 0;
  height: 100%;
  width: 2px;
  background-color: #e9ecef;
}

.timeline-item:last-child:before {
  height: 50%;
}

.timeline-item-icon {
  position: absolute;
  left: -38px;
  top: 0;
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #fff;
  border: 2px solid #007bff;
  border-radius: 50%;
}

.timeline-item-icon i {
  font-size: 8px;
  color: #007bff;
}
</style>
