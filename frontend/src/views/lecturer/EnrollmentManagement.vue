<template>
  <div class="enrollment-management">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="mb-1">Student Enrollment Management</h1>
        <p class="text-muted mb-0" v-if="course">
          {{ course.code }} - {{ course.name }}
        </p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-success" @click="openEnrollModal">
          <i class="fas fa-user-plus me-2"></i>Enroll Students
        </button>
        <button class="btn btn-outline-primary" @click="openBulkEnrollModal">
          <i class="fas fa-users me-2"></i>Bulk Enroll
        </button>
        <router-link :to="`/lecturer/course/${courseId}`" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left me-2"></i>Back to Course
        </router-link>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h5 class="card-title">Total Enrolled</h5>
                <h2 class="mb-0">{{ enrollments.length }}</h2>
              </div>
              <div class="align-self-center">
                <i class="fas fa-users fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-info text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h5 class="card-title">Available Students</h5>
                <h2 class="mb-0">{{ availableStudents.length }}</h2>
              </div>
              <div class="align-self-center">
                <i class="fas fa-user-plus fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-success text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h5 class="card-title">Academic Year</h5>
                <h2 class="mb-0">{{ course?.academic_year || 'N/A' }}</h2>
              </div>
              <div class="align-self-center">
                <i class="fas fa-calendar fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Enrolled Students Table -->
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Enrolled Students</h5>
          <div class="d-flex gap-2">
            <input 
              type="text" 
              class="form-control form-control-sm" 
              placeholder="Search students..."
              v-model="searchQuery"
              style="width: 250px;"
            >
            <button class="btn btn-outline-success btn-sm" @click="exportEnrollments">
              <i class="fas fa-file-export me-1"></i>Export
            </button>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div v-if="isLoading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div v-else-if="filteredEnrollments.length === 0" class="text-center py-4">
          <i class="fas fa-users fa-3x text-muted mb-3"></i>
          <h6 class="text-muted">No students enrolled</h6>
          <p class="text-muted">Start by enrolling students in this course.</p>
          <button class="btn btn-primary" @click="openEnrollModal">
            <i class="fas fa-user-plus me-2"></i>Enroll Students
          </button>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Student</th>
                <th>Matric Number</th>
                <th>Email</th>
                <th>Semester</th>
                <th>Enrolled Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="enrollment in filteredEnrollments" :key="enrollment.enrollment_id">
                <td>
                  <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm me-3">
                      {{ getStudentInitials(enrollment.student_name) }}
                    </div>
                    <strong>{{ enrollment.student_name }}</strong>
                  </div>
                </td>
                <td>{{ enrollment.matric_number || 'N/A' }}</td>
                <td>{{ enrollment.student_email }}</td>
                <td>{{ enrollment.semester }} {{ enrollment.academic_year }}</td>
                <td>{{ formatDate(enrollment.enrolled_at) }}</td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <router-link 
                      :to="`/lecturer/student/${enrollment.student_id}`" 
                      class="btn btn-outline-info"
                      title="View Student Details"
                    >
                      <i class="fas fa-eye"></i>
                    </router-link>
                    <button 
                      class="btn btn-outline-danger" 
                      @click="confirmRemoveEnrollment(enrollment)"
                      title="Remove from Course"
                    >
                      <i class="fas fa-user-minus"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Single Enrollment Modal -->
    <div class="modal fade" id="enrollModal" tabindex="-1" aria-labelledby="enrollModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="enrollModalLabel">Enroll Student</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="enrollStudent">
              <div class="mb-3">
                <label for="studentSelect" class="form-label">Select Student</label>
                <select class="form-select" id="studentSelect" v-model="enrollForm.studentId" required>
                  <option value="">Choose a student...</option>
                  <option v-for="student in availableStudents" :key="student.id" :value="student.id">
                    {{ student.name }} ({{ student.email }})
                  </option>
                </select>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="academicYear" class="form-label">Academic Year</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      id="academicYear" 
                      v-model="enrollForm.academicYear"
                      placeholder="e.g., 2025-2026"
                      required
                    >
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="semester" class="form-label">Semester</label>
                    <select class="form-select" id="semester" v-model="enrollForm.semester" required>
                      <option value="">Select Semester</option>
                      <option value="Fall">Fall</option>
                      <option value="Spring">Spring</option>
                      <option value="Summer">Summer</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" :disabled="isLoading">
                  <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                  Enroll Student
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Bulk Enrollment Modal -->
    <div class="modal fade" id="bulkEnrollModal" tabindex="-1" aria-labelledby="bulkEnrollModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="bulkEnrollModalLabel">Bulk Enroll Students</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="bulkEnrollStudents">
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="bulkAcademicYear" class="form-label">Academic Year</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    id="bulkAcademicYear" 
                    v-model="bulkEnrollForm.academicYear"
                    placeholder="e.g., 2025-2026"
                    required
                  >
                </div>
                <div class="col-md-6">
                  <label for="bulkSemester" class="form-label">Semester</label>
                  <select class="form-select" id="bulkSemester" v-model="bulkEnrollForm.semester" required>
                    <option value="">Select Semester</option>
                    <option value="Fall">Fall</option>
                    <option value="Spring">Spring</option>
                    <option value="Summer">Summer</option>
                  </select>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Select Students to Enroll</label>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="selectAll"
                      @change="toggleSelectAll"
                      :checked="bulkEnrollForm.studentIds.length === availableStudents.length"
                    >
                    <label class="form-check-label" for="selectAll">
                      Select All ({{ availableStudents.length }} students)
                    </label>
                  </div>
                  <span class="badge bg-primary">{{ bulkEnrollForm.studentIds.length }} selected</span>
                </div>
                
                <div class="student-list" style="max-height: 300px; overflow-y: auto;">
                  <div v-for="student in availableStudents" :key="student.id" class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      :id="`student-${student.id}`"
                      :value="student.id"
                      v-model="bulkEnrollForm.studentIds"
                    >
                    <label class="form-check-label" :for="`student-${student.id}`">
                      <strong>{{ student.name }}</strong> - {{ student.email }}
                      <small class="text-muted d-block">{{ student.matric_number || 'No matric number' }}</small>
                    </label>
                  </div>
                  
                  <div v-if="availableStudents.length === 0" class="text-center py-3 text-muted">
                    No students available for enrollment
                  </div>
                </div>
              </div>
              
              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button 
                  type="submit" 
                  class="btn btn-primary" 
                  :disabled="isLoading || bulkEnrollForm.studentIds.length === 0"
                >
                  <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                  Enroll {{ bulkEnrollForm.studentIds.length }} Students
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Remove Enrollment Confirmation Modal -->
    <div class="modal fade" id="removeEnrollmentModal" tabindex="-1" aria-labelledby="removeEnrollmentModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="removeEnrollmentModalLabel">Remove Student Enrollment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div v-if="enrollmentToRemove">
              <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Warning:</strong> This action will remove the student from the course and delete all their marks.
              </div>
              <p>Are you sure you want to remove <strong>{{ enrollmentToRemove.student_name }}</strong> from this course?</p>
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">{{ enrollmentToRemove.student_name }}</h6>
                  <p class="card-text">
                    <small class="text-muted">{{ enrollmentToRemove.student_email }}</small><br>
                    <small class="text-muted">Enrolled: {{ formatDate(enrollmentToRemove.enrolled_at) }}</small>
                  </p>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button 
              type="button" 
              class="btn btn-danger" 
              @click="removeEnrollment"
              :disabled="isLoading"
            >
              <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
              Remove Student
            </button>
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
  name: 'EnrollmentManagement',
  data() {
    return {
      courseId: null,
      course: null,
      searchQuery: '',
      enrollForm: {
        studentId: '',
        academicYear: '2025-2026',
        semester: 'Fall'
      },
      bulkEnrollForm: {
        studentIds: [],
        academicYear: '2025-2026',
        semester: 'Fall'
      },
      enrollmentToRemove: null
    };
  },
  computed: {
    ...mapGetters('enrollments', ['getEnrollments', 'getAvailableStudents', 'isLoading']),
    ...mapGetters('courses', ['getCourses']),
    
    enrollments() {
      return this.getEnrollments;
    },
    
    availableStudents() {
      return this.getAvailableStudents;
    },
    
    filteredEnrollments() {
      if (!this.searchQuery) return this.enrollments;
      
      const query = this.searchQuery.toLowerCase();
      return this.enrollments.filter(enrollment => 
        enrollment.student_name.toLowerCase().includes(query) ||
        enrollment.student_email.toLowerCase().includes(query) ||
        (enrollment.matric_number && enrollment.matric_number.toLowerCase().includes(query))
      );
    }
  },
  async created() {
    this.courseId = this.$route.params.courseId;
    console.log('EnrollmentManagement component loaded for courseId:', this.courseId);
    
    // Load temporary data for testing
    await this.loadTemporaryData();
  },
  methods: {
    async loadTemporaryData() {
      try {
        // Load temporary course data
        this.course = {
          id: parseInt(this.courseId),
          code: 'CS101',
          name: 'Introduction to Programming',
          semester: 'Fall',
          academic_year: '2025-2026'
        };
        
        // Load temporary enrollment data
        this.$store.commit('enrollments/SET_ENROLLMENTS', [
          {
            enrollment_id: 1,
            student_id: 4,
            student_name: 'Student One',
            student_email: 'student1@example.com',
            matric_number: 'S123456',
            academic_year: '2025-2026',
            semester: 'Fall',
            enrolled_at: '2025-01-15'
          },
          {
            enrollment_id: 2,
            student_id: 5,
            student_name: 'Student Two',
            student_email: 'student2@example.com',
            matric_number: 'S123457',
            academic_year: '2025-2026',
            semester: 'Fall',
            enrolled_at: '2025-01-15'
          }
        ]);
        
        // Load temporary available students
        this.$store.commit('enrollments/SET_AVAILABLE_STUDENTS', [
          {
            id: 6,
            name: 'Student Three',
            email: 'student3@example.com',
            matric_number: 'S123458'
          },
          {
            id: 7,
            name: 'Student Four',
            email: 'student4@example.com',
            matric_number: 'S123459'
          }
        ]);
        
        console.log('Temporary enrollment data loaded');
      } catch (error) {
        console.error('Error loading temporary data:', error);
      }
    },

    async loadData() {
      try {
        // Load course details
        await this.$store.dispatch('courses/fetchCourses');
        this.course = this.getCourses.find(c => c.id == this.courseId);
        
        // Load enrollments and available students
        await Promise.all([
          this.$store.dispatch('enrollments/fetchCourseEnrollments', this.courseId),
          this.$store.dispatch('enrollments/fetchAvailableStudents', this.courseId)
        ]);
      } catch (error) {
        console.error('Error loading enrollment data:', error);
        this.$store.dispatch('showToast', {
          message: 'Error loading enrollment data',
          type: 'error'
        });
      }
    },
    
    getStudentInitials(name) {
      return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase();
    },
    
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
      });
    },
    
    openEnrollModal() {
      this.enrollForm = {
        studentId: '',
        academicYear: this.course?.academic_year || '2025-2026',
        semester: 'Fall'
      };
      
      const modal = new bootstrap.Modal(document.getElementById('enrollModal'));
      modal.show();
    },
    
    openBulkEnrollModal() {
      this.bulkEnrollForm = {
        studentIds: [],
        academicYear: this.course?.academic_year || '2025-2026',
        semester: 'Fall'
      };
      
      const modal = new bootstrap.Modal(document.getElementById('bulkEnrollModal'));
      modal.show();
    },
    
    toggleSelectAll(event) {
      if (event.target.checked) {
        this.bulkEnrollForm.studentIds = this.availableStudents.map(s => s.id);
      } else {
        this.bulkEnrollForm.studentIds = [];
      }
    },
    
    async enrollStudent() {
      try {
        await this.$store.dispatch('enrollments/enrollStudent', {
          courseId: this.courseId,
          studentId: this.enrollForm.studentId,
          academicYear: this.enrollForm.academicYear,
          semester: this.enrollForm.semester
        });
        
        // Close modal
        const modalElement = document.getElementById('enrollModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        modal.hide();
        
        this.$store.dispatch('showToast', {
          message: 'Student enrolled successfully!',
          type: 'success'
        });
      } catch (error) {
        console.error('Error enrolling student:', error);
        this.$store.dispatch('showToast', {
          message: error.message || 'Error enrolling student',
          type: 'error'
        });
      }
    },
    
    async bulkEnrollStudents() {
      try {
        const result = await this.$store.dispatch('enrollments/bulkEnrollStudents', {
          courseId: this.courseId,
          studentIds: this.bulkEnrollForm.studentIds,
          academicYear: this.bulkEnrollForm.academicYear,
          semester: this.bulkEnrollForm.semester
        });
        
        // Close modal
        const modalElement = document.getElementById('bulkEnrollModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        modal.hide();
        
        this.$store.dispatch('showToast', {
          message: result.message,
          type: 'success'
        });
      } catch (error) {
        console.error('Error bulk enrolling students:', error);
        this.$store.dispatch('showToast', {
          message: error.message || 'Error enrolling students',
          type: 'error'
        });
      }
    },
    
    confirmRemoveEnrollment(enrollment) {
      this.enrollmentToRemove = enrollment;
      
      const modal = new bootstrap.Modal(document.getElementById('removeEnrollmentModal'));
      modal.show();
    },
    
    async removeEnrollment() {
      try {
        await this.$store.dispatch('enrollments/removeEnrollment', {
          enrollmentId: this.enrollmentToRemove.enrollment_id,
          courseId: this.courseId
        });
        
        // Close modal
        const modalElement = document.getElementById('removeEnrollmentModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        modal.hide();
        
        this.$store.dispatch('showToast', {
          message: 'Student removed from course successfully!',
          type: 'success'
        });
        
        this.enrollmentToRemove = null;
      } catch (error) {
        console.error('Error removing enrollment:', error);
        this.$store.dispatch('showToast', {
          message: error.message || 'Error removing student',
          type: 'error'
        });
      }
    },
    
    exportEnrollments() {
      if (!this.enrollments.length) {
        this.$store.dispatch('showToast', {
          message: 'No enrollments to export',
          type: 'warning'
        });
        return;
      }
      
      // Create CSV content
      const headers = ['Student Name', 'Matric Number', 'Email', 'Academic Year', 'Semester', 'Enrolled Date'];
      let csvContent = headers.join(',') + '\n';
      
      this.enrollments.forEach(enrollment => {
        const row = [
          `"${enrollment.student_name}"`,
          enrollment.matric_number || 'N/A',
          enrollment.student_email,
          enrollment.academic_year,
          enrollment.semester,
          this.formatDate(enrollment.enrolled_at)
        ];
        csvContent += row.join(',') + '\n';
      });
      
      // Download CSV
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `${this.course?.code || 'course'}_enrollments.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  }
};
</script>

<style scoped>
.enrollment-management h1 {
  color: #2c3e50;
  font-weight: 700;
}

.card {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border: none;
  border-radius: 10px;
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
  font-weight: 600;
  font-size: 14px;
}

.avatar.avatar-sm {
  width: 32px;
  height: 32px;
  font-size: 12px;
}

.student-list {
  border: 1px solid #dee2e6;
  border-radius: 5px;
  padding: 10px;
}

.form-check {
  padding: 8px;
  border-bottom: 1px solid #f8f9fa;
}

.form-check:last-child {
  border-bottom: none;
}

.form-check:hover {
  background-color: #f8f9fa;
}

.table th {
  font-weight: 600;
  color: #2c3e50;
  border-top: none;
}

.btn-group .btn {
  margin-right: 0;
}

.modal-lg {
  max-width: 900px;
}
</style>
