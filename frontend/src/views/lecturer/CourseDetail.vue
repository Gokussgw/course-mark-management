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
                  <th>Assessments:</th>
                  <td>{{ courseAssessments.length }}</td>
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
            <h5 class="mb-0">Assessment Breakdown</h5>
            <button class="btn btn-sm btn-primary" @click="navigateToCreateAssessment">
              <i class="fas fa-plus-circle me-2"></i> Add Assessment
            </button>
          </div>
          <div class="card-body">
            <div v-if="courseAssessments.length === 0" class="text-center py-4">
              <p class="mb-3">No assessments have been added to this course yet.</p>
              <button class="btn btn-primary" @click="navigateToCreateAssessment">
                <i class="fas fa-plus-circle me-2"></i> Create First Assessment
              </button>
            </div>
            <div v-else>
              <div class="mb-4">
                <div class="progress" style="height: 30px;">
                  <div v-for="assessment in courseAssessments" 
                       :key="assessment.id"
                       class="progress-bar" 
                       :class="getAssessmentTypeClass(assessment.type)"
                       :style="`width: ${assessment.weightage}%`"
                       :title="`${assessment.name} (${assessment.weightage}%)`">
                    {{ assessment.weightage }}%
                  </div>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Type</th>
                      <th>Weight</th>
                      <th>Due Date</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="assessment in courseAssessments" :key="assessment.id">
                      <td>
                        <strong>{{ assessment.name }}</strong>
                        <div class="small text-muted">{{ assessment.description }}</div>
                      </td>
                      <td>
                        <span class="badge" :class="getAssessmentTypeBadgeClass(assessment.type)">
                          {{ assessment.type }}
                        </span>
                      </td>
                      <td>{{ assessment.weightage }}%</td>
                      <td>{{ formatDate(assessment.due_date) }}</td>
                      <td>
                        <span class="badge" :class="getAssessmentStatusBadgeClass(assessment)">
                          {{ getAssessmentStatus(assessment) }}
                        </span>
                      </td>
                      <td>
                        <div class="btn-group btn-group-sm">
                          <button class="btn btn-outline-primary" @click="navigateToEnterMarks(assessment)">
                            <i class="fas fa-calculator"></i>
                          </button>
                          <button class="btn btn-outline-secondary" @click="navigateToEditAssessment(assessment)">
                            <i class="fas fa-edit"></i>
                          </button>
                          <button class="btn btn-outline-danger" @click="confirmDeleteAssessment(assessment)">
                            <i class="fas fa-trash"></i>
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
                          :style="`width: ${getStudentProgress(student.id)}%`"
                          :aria-valuenow="getStudentProgress(student.id)" 
                          aria-valuemin="0" 
                          aria-valuemax="100">
                          {{ getStudentProgress(student.id) }}%
                        </div>
                      </div>
                    </td>
                    <td>
                      <span 
                        :class="getMarkClass(getStudentCurrentMark(student.id))">
                        {{ getStudentCurrentMark(student.id) }}
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

    <!-- Delete Assessment Confirmation Modal -->
    <div class="modal fade" id="deleteAssessmentModal" tabindex="-1" aria-labelledby="deleteAssessmentModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteAssessmentModalLabel">Confirm Deletion</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to delete the assessment "{{ assessmentToDelete?.name }}"? This action cannot be undone.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" @click="deleteAssessment">Delete</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapGetters, mapActions } from 'vuex'

export default {
  name: 'CourseDetail',
  data() {
    return {
      courseId: null,
      studentSearchQuery: '',
      assessmentToDelete: null,
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
    ...mapGetters({
      getCourseAssessments: 'assessments/getCourseAssessments'
    }),
    courseAssessments() {
      return this.getCourseAssessments(this.courseId) || [];
    },
    enrolledStudents() {
      // This would be populated from a store getter once implemented
      return this.$store.state.users.courseStudents || [];
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
      
      // Fetch course assessments
      await this.fetchAssessments({ courseId: this.courseId });
      
      // Fetch enrolled students
      await this.fetchCourseStudents(this.courseId);
      
      // Fetch marks for this course
      await this.fetchMarks({ courseId: this.courseId });
      
      // Initialize edit form data
      this.initializeEditForm();
    } catch (error) {
      console.error('Error loading course details:', error);
    }
  },
  methods: {
    ...mapActions({
      fetchCourse: 'courses/fetchCourse',
      fetchAssessments: 'assessments/fetchAssessments',
      fetchMarks: 'marks/fetchMarks',
      updateCourse: 'courses/updateCourse',
      deleteAssessmentAction: 'assessments/deleteAssessment'
    }),
    async fetchCourseStudents(courseId) {
      try {
        // This action would need to be implemented in the users store module
        await this.$store.dispatch('users/fetchCourseStudents', courseId);
      } catch (error) {
        console.error('Error fetching course students:', error);
      }
    },
    formatDate(dateString) {
      if (!dateString) return 'Not set';
      return new Date(dateString).toLocaleDateString();
    },
    getAssessmentTypeClass(type) {
      const types = {
        'exam': 'bg-danger',
        'midterm': 'bg-warning',
        'quiz': 'bg-info',
        'assignment': 'bg-success',
        'project': 'bg-primary',
        'lab': 'bg-secondary'
      };
      return types[type.toLowerCase()] || 'bg-secondary';
    },
    getAssessmentTypeBadgeClass(type) {
      const types = {
        'exam': 'bg-danger',
        'midterm': 'bg-warning',
        'quiz': 'bg-info',
        'assignment': 'bg-success',
        'project': 'bg-primary',
        'lab': 'bg-secondary'
      };
      return types[type.toLowerCase()] || 'bg-secondary';
    },
    getAssessmentStatus(assessment) {
      const today = new Date();
      const dueDate = new Date(assessment.due_date);
      
      if (assessment.marks_entered) {
        return 'Marked';
      } else if (dueDate < today) {
        return 'Overdue';
      } else if (dueDate.getTime() - today.getTime() < 7 * 24 * 60 * 60 * 1000) {
        return 'Upcoming';
      } else {
        return 'Scheduled';
      }
    },
    getAssessmentStatusBadgeClass(assessment) {
      const status = this.getAssessmentStatus(assessment);
      const classes = {
        'Marked': 'bg-success',
        'Overdue': 'bg-danger',
        'Upcoming': 'bg-warning',
        'Scheduled': 'bg-info'
      };
      return classes[status] || 'bg-secondary';
    },
    getStudentProgress(studentId) {
      // Calculate what percentage of assessments the student has completed
      const studentMarks = this.$store.state.marks.marks.filter(mark => 
        mark.student_id === studentId && mark.course_id === parseInt(this.courseId)
      );
      
      if (this.courseAssessments.length === 0) return 0;
      
      return Math.round((studentMarks.length / this.courseAssessments.length) * 100);
    },
    getStudentCurrentMark(studentId) {
      // Calculate the current weighted average for this student
      const studentMarks = this.$store.state.marks.marks.filter(mark => 
        mark.student_id === studentId && mark.course_id === parseInt(this.courseId)
      );
      
      if (studentMarks.length === 0) return 'N/A';
      
      let weightedSum = 0;
      let totalWeight = 0;
      
      studentMarks.forEach(mark => {
        const assessment = this.courseAssessments.find(a => a.id === mark.assessment_id);
        if (assessment) {
          weightedSum += (mark.mark / assessment.max_mark) * assessment.weightage;
          totalWeight += assessment.weightage;
        }
      });
      
      if (totalWeight === 0) return 'N/A';
      
      const averageMark = Math.round((weightedSum / totalWeight) * 100);
      return `${averageMark}%`;
    },
    getMarkClass(markString) {
      if (markString === 'N/A') return 'text-muted';
      
      const mark = parseInt(markString);
      if (isNaN(mark)) return 'text-muted';
      
      if (mark >= 70) return 'text-success fw-bold';
      if (mark >= 50) return 'text-warning';
      return 'text-danger';
    },
    navigateToCreateAssessment() {
      this.$router.push({ 
        name: 'CreateAssessment',
        query: { courseId: this.courseId }
      });
    },
    navigateToEditAssessment(assessment) {
      this.$router.push({ 
        name: 'EditAssessment', 
        params: { id: assessment.id }
      });
    },
    navigateToEnterMarks(assessment) {
      // This route would need to be added to the router
      this.$router.push({ 
        name: 'EnterMarks', 
        params: { id: assessment.id }
      });
    },
    navigateToStudentDetail(student) {
      this.$router.push({ 
        name: 'StudentDetail', 
        params: { id: student.id },
        query: { courseId: this.courseId }
      });
    },
    navigateToStudentMarks(student) {
      // This route would need to be added to the router
      this.$router.push({ 
        name: 'StudentMarks', 
        params: { studentId: student.id, courseId: this.courseId }
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
    confirmDeleteAssessment(assessment) {
      this.assessmentToDelete = assessment;
      // In a real implementation, we would use Bootstrap's modal methods
      // $('#deleteAssessmentModal').modal('show');
    },
    async deleteAssessment() {
      if (!this.assessmentToDelete) return;
      
      try {
        await this.deleteAssessmentAction(this.assessmentToDelete.id);
        // Close modal
        // $('#deleteAssessmentModal').modal('hide');
        this.$store.dispatch('showToast', {
          message: 'Assessment deleted successfully',
          type: 'success'
        });
        this.assessmentToDelete = null;
      } catch (error) {
        console.error('Error deleting assessment:', error);
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
