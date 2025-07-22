<template>
  <div class="admin-enrollment-management">
    <!-- Dashboard Header -->
    <div class="dashboard-header d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0">Student Enrollment Management</h1>
      <div class="user-info d-flex align-items-center">
        <span class="me-3">Welcome, {{ userInfo.name }}</span>
        <button class="btn btn-outline-danger btn-sm" @click="logout">
          <i class="fas fa-sign-out-alt me-1"></i>
          Logout
        </button>
      </div>
    </div>

    <!-- Stats Overview -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h5 class="card-title">Total Students</h5>
                <h2 class="mb-0">{{ totalStudents }}</h2>
              </div>
              <div class="align-self-center">
                <i class="fas fa-user-graduate fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-success text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h5 class="card-title">Total Courses</h5>
                <h2 class="mb-0">{{ totalCourses }}</h2>
              </div>
              <div class="align-self-center">
                <i class="fas fa-book fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-info text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h5 class="card-title">Total Enrollments</h5>
                <h2 class="mb-0">{{ totalEnrollments }}</h2>
              </div>
              <div class="align-self-center">
                <i class="fas fa-users fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-warning text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h5 class="card-title">Current Semester</h5>
                <h2 class="mb-0">Fall 2025</h2>
              </div>
              <div class="align-self-center">
                <i class="fas fa-calendar fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Course Selection and Management -->
    <div class="row">
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Course Enrollments</h5>
              <div class="d-flex gap-2">
                <select class="form-select form-select-sm" v-model="selectedCourseId" @change="loadCourseEnrollments" style="width: 250px;">
                  <option value="">Select a course...</option>
                  <option v-for="course in courses" :key="course.id" :value="course.id">
                    {{ course.code }} - {{ course.name }}
                  </option>
                </select>
                <button class="btn btn-success btn-sm" @click="openBulkEnrollModal" :disabled="!selectedCourseId">
                  <i class="fas fa-users me-1"></i>Bulk Enroll
                </button>
              </div>
            </div>
          </div>
          <div class="card-body">
            <div v-if="!selectedCourseId" class="text-center py-4">
              <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
              <h6 class="text-muted">Select a course to manage enrollments</h6>
              <p class="text-muted">Choose a course from the dropdown above to view and manage student enrollments.</p>
            </div>

            <div v-else-if="isLoading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>

            <div v-else-if="currentCourseEnrollments.length === 0" class="text-center py-4">
              <i class="fas fa-users fa-3x text-muted mb-3"></i>
              <h6 class="text-muted">No students enrolled</h6>
              <p class="text-muted">This course has no enrolled students yet.</p>
              <button class="btn btn-primary" @click="openBulkEnrollModal">
                <i class="fas fa-user-plus me-2"></i>Enroll Students
              </button>
            </div>

            <div v-else>
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                  <span class="badge bg-primary me-2">{{ currentCourseEnrollments.length }} enrolled</span>
                  <span class="text-muted">{{ selectedCourse?.code }} - {{ selectedCourse?.name }}</span>
                </div>
                <input 
                  type="text" 
                  class="form-control form-control-sm" 
                  placeholder="Search students..."
                  v-model="searchQuery"
                  style="width: 200px;"
                >
              </div>

              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead>
                    <tr>
                      <th>Student</th>
                      <th>Matric Number</th>
                      <th>Email</th>
                      <th>Academic Year</th>
                      <th>Semester</th>
                      <th>Enrolled Date</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="enrollment in filteredCourseEnrollments" :key="enrollment.enrollment_id">
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
                      <td>{{ enrollment.academic_year }}</td>
                      <td>{{ enrollment.semester }}</td>
                      <td>{{ formatDate(enrollment.enrolled_at) }}</td>
                      <td>
                        <div class="btn-group btn-group-sm">
                          <button 
                            class="btn btn-outline-info"
                            @click="viewStudentDetails(enrollment.student_id)"
                            title="View Student Details"
                          >
                            <i class="fas fa-eye"></i>
                          </button>
                          <button 
                            class="btn btn-outline-warning"
                            @click="openTransferStudentModal(enrollment)"
                            title="Transfer to Another Course"
                          >
                            <i class="fas fa-exchange-alt"></i>
                          </button>
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
        </div>
      </div>

      <div class="col-md-4">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Quick Actions</h5>
          </div>
          <div class="card-body">
            <div class="d-grid gap-2">
              <button class="btn btn-primary" @click="openStudentEnrollmentHistoryModal">
                <i class="fas fa-history me-2"></i>View Student History
              </button>
              <button class="btn btn-info" @click="exportAllEnrollments">
                <i class="fas fa-file-export me-2"></i>Export All Enrollments
              </button>
              <button class="btn btn-warning" @click="openTransferStudentModal">
                <i class="fas fa-exchange-alt me-2"></i>Transfer Student
              </button>
              <router-link to="/admin/dashboard" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
              </router-link>
            </div>
          </div>
        </div>

        <div class="card mt-3">
          <div class="card-header">
            <h5 class="mb-0">Course Statistics</h5>
          </div>
          <div class="card-body">
            <div v-if="selectedCourse">
              <div class="stat-item">
                <strong>Course Code:</strong> {{ selectedCourse.code }}
              </div>
              <div class="stat-item">
                <strong>Course Name:</strong> {{ selectedCourse.name }}
              </div>
              <div class="stat-item">
                <strong>Lecturer:</strong> {{ getLecturerName(selectedCourse.lecturer_id) }}
              </div>
              <div class="stat-item">
                <strong>Academic Year:</strong> {{ selectedCourse.academic_year || 'N/A' }}
              </div>
              <div class="stat-item">
                <strong>Semester:</strong> {{ selectedCourse.semester || 'N/A' }}
              </div>
              <div class="stat-item">
                <strong>Enrolled Students:</strong> {{ currentCourseEnrollments.length }}
              </div>
            </div>
            <div v-else class="text-muted text-center">
              Select a course to view statistics
            </div>
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
            <div v-if="selectedCourse" class="alert alert-info">
              <strong>Course:</strong> {{ selectedCourse.code }} - {{ selectedCourse.name }}
            </div>
            
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
                      id="selectAllStudents"
                      @change="toggleSelectAllStudents"
                      :checked="bulkEnrollForm.studentIds.length === availableStudents.length && availableStudents.length > 0"
                    >
                    <label class="form-check-label" for="selectAllStudents">
                      Select All ({{ availableStudents.length }} available)
                    </label>
                  </div>
                  <span class="badge bg-primary">{{ bulkEnrollForm.studentIds.length }} selected</span>
                </div>
                
                <div class="student-list" style="max-height: 300px; overflow-y: auto;">
                  <div v-for="student in availableStudents" :key="student.id" class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      :id="`bulk-student-${student.id}`"
                      :value="student.id"
                      v-model="bulkEnrollForm.studentIds"
                    >
                    <label class="form-check-label" :for="`bulk-student-${student.id}`">
                      <strong>{{ student.name }}</strong> - {{ student.email }}
                      <small class="text-muted d-block">{{ student.matric_number || 'No matric number' }}</small>
                      <small class="text-muted">{{ student.enrollment_count }} current enrollments</small>
                    </label>
                  </div>
                  
                  <div v-if="availableStudents.length === 0" class="text-center py-3 text-muted">
                    <div v-if="isLoading">Loading available students...</div>
                    <div v-else>No students available for enrollment</div>
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
              <p>Are you sure you want to remove <strong>{{ enrollmentToRemove.student_name }}</strong> from <strong>{{ selectedCourse?.code }}</strong>?</p>
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

    <!-- Transfer Student Modal -->
    <div class="modal fade" id="transferStudentModal" tabindex="-1" aria-labelledby="transferStudentModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="transferStudentModalLabel">Transfer Student</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div v-if="transferForm.student">
              <div class="mb-3">
                <label class="form-label">Student to Transfer:</label>
                <div class="card">
                  <div class="card-body py-2">
                    <strong>{{ transferForm.student.student_name }}</strong>
                    <br>
                    <small class="text-muted">{{ transferForm.student.student_email }}</small>
                    <br>
                    <small class="text-muted">Matric: {{ transferForm.student.matric_number }}</small>
                  </div>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">From Course:</label>
                <div class="card">
                  <div class="card-body py-2">
                    <strong>{{ selectedCourse.code }} - {{ selectedCourse.name }}</strong>
                  </div>
                </div>
              </div>
              
              <div class="mb-3">
                <label for="targetCourse" class="form-label">Transfer to Course:</label>
                <select 
                  id="targetCourse" 
                  v-model="transferForm.targetCourseId" 
                  class="form-select"
                  required
                >
                  <option value="">Select target course...</option>
                  <option 
                    v-for="course in availableTargetCourses" 
                    :key="course.id" 
                    :value="course.id"
                  >
                    {{ course.code }} - {{ course.name }}
                  </option>
                </select>
              </div>
              
              <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                This will remove the student from the current course and enroll them in the selected target course.
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button 
              type="button" 
              class="btn btn-warning" 
              @click="transferStudent"
              :disabled="isLoading || !transferForm.targetCourseId"
            >
              <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
              Transfer Student
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
  name: 'AdminEnrollmentManagement',
  data() {
    return {
      selectedCourseId: '',
      searchQuery: '',
      currentCourseEnrollments: [],
      allStudents: [],
      bulkEnrollForm: {
        studentIds: [],
        academicYear: '2025-2026',
        semester: 'Fall'
      },
      enrollmentToRemove: null,
      transferForm: {
        student: null,
        targetCourseId: ''
      },
      transferredStudents: new Map(), // Store transferred students by target course ID
      transferredOutStudents: new Map() // Store students transferred OUT of each course
    };
  },
  computed: {
    ...mapGetters('auth', ['getUser']),
    ...mapGetters('courses', ['getCourses']),
    ...mapGetters('enrollments', ['getAvailableStudents', 'isLoading']),
    ...mapGetters('users', ['getUsers']),
    
    userInfo() {
      return this.getUser || { name: 'Admin' };
    },
    
    courses() {
      return this.getCourses || [];
    },
    
    availableStudents() {
      return this.getAvailableStudents || [];
    },
    
    selectedCourse() {
      return this.courses?.find(c => c.id == this.selectedCourseId) || null;
    },
    
    filteredCourseEnrollments() {
      if (!this.searchQuery) return this.currentCourseEnrollments || [];
      
      const query = this.searchQuery.toLowerCase();
      return (this.currentCourseEnrollments || []).filter(enrollment => 
        enrollment.student_name.toLowerCase().includes(query) ||
        enrollment.student_email.toLowerCase().includes(query) ||
        (enrollment.matric_number && enrollment.matric_number.toLowerCase().includes(query))
      );
    },
    
    totalStudents() {
      return this.allStudents?.length || 0;
    },
    
    totalCourses() {
      return this.courses?.length || 0;
    },
    
    totalEnrollments() {
      return this.allStudents?.reduce((total, student) => total + (student.enrollment_count || 0), 0) || 0;
    },
    
    availableTargetCourses() {
      // Return all courses except the currently selected one
      return this.courses?.filter(course => course.id != this.selectedCourseId) || [];
    }
  },
  async created() {
    // Load any previously transferred students from localStorage
    this.loadTransferredStudents();
    await this.loadTemporaryData();
  },
  methods: {
    async loadTemporaryData() {
      try {
        // Load temporary courses
        this.$store.commit('courses/SET_COURSES', [
          {
            id: 1,
            code: 'CS101',
            name: 'Introduction to Programming',
            academic_year: '2025-2026',
            semester: 'Fall',
            lecturer_id: 2,
            lecturer_name: 'Lecturer One'
          },
          {
            id: 2,
            code: 'CS201',
            name: 'Data Structures',
            academic_year: '2025-2026',
            semester: 'Fall',
            lecturer_id: 2,
            lecturer_name: 'Lecturer One'
          }
        ]);
        
        // Load temporary students
        this.allStudents = [
          {
            id: 4,
            name: 'Student One',
            email: 'student1@example.com',
            role: 'student',
            matric_number: 'S123456',
            enrollment_count: 2
          },
          {
            id: 5,
            name: 'Student Two',
            email: 'student2@example.com',
            role: 'student',
            matric_number: 'S123457',
            enrollment_count: 1
          },
          {
            id: 6,
            name: 'Student Three',
            email: 'student3@example.com',
            role: 'student',
            matric_number: 'S123458',
            enrollment_count: 0
          }
        ];
        
        // Load temporary users (including lecturers) into the store
        this.$store.commit('users/SET_USERS', [
          {
            id: 1,
            name: 'Admin User',
            email: 'admin@example.com',
            role: 'admin'
          },
          {
            id: 2,
            name: 'Lecturer One',
            email: 'lecturer1@example.com',
            role: 'lecturer'
          },
          {
            id: 3,
            name: 'Advisor One',
            email: 'advisor1@example.com',
            role: 'advisor'
          },
          {
            id: 4,
            name: 'Student One',
            email: 'student1@example.com',
            role: 'student',
            matric_number: 'S123456'
          },
          {
            id: 5,
            name: 'Student Two',
            email: 'student2@example.com',
            role: 'student',
            matric_number: 'S123457'
          },
          {
            id: 6,
            name: 'Student Three',
            email: 'student3@example.com',
            role: 'student',
            matric_number: 'S123458'
          }
        ]);
        
        console.log('Temporary admin enrollment data loaded');
      } catch (error) {
        console.error('Error loading temporary data:', error);
      }
    },

    async loadInitialData() {
      try {
        // Load courses and all students
        await Promise.all([
          this.$store.dispatch('courses/fetchCourses'),
          this.loadAllStudents()
        ]);
      } catch (error) {
        console.error('Error loading initial data:', error);
        this.$store.dispatch('showToast', {
          message: 'Error loading data',
          type: 'error'
        });
      }
    },
    
    async loadAllStudents() {
      try {
        const response = await this.$store.dispatch('users/fetchUsers');
        this.allStudents = response.filter(user => user.role === 'student');
      } catch (error) {
        console.error('Error loading students:', error);
      }
    },
    
    async loadCourseEnrollments() {
      if (!this.selectedCourseId) {
        this.currentCourseEnrollments = [];
        return;
      }
      
      try {
        console.log('Loading enrollments for course:', this.selectedCourseId);
        
        // Load temporary course enrollments based on selected course
        if (this.selectedCourseId == 1) {
          this.currentCourseEnrollments = [
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
              enrolled_at: '2025-01-16'
            }
          ];
          
          console.log('Set enrollments for CS101:', this.currentCourseEnrollments);
          
          // Set available students for this course
          this.$store.commit('enrollments/SET_AVAILABLE_STUDENTS', [
            {
              id: 6,
              name: 'Student Three',
              email: 'student3@example.com',
              matric_number: 'S123458',
              enrollment_count: 0
            }
          ]);
        } else if (this.selectedCourseId == 2) {
          this.currentCourseEnrollments = [
            {
              enrollment_id: 3,
              student_id: 5,
              student_name: 'Student Two',
              student_email: 'student2@example.com',
              matric_number: 'S123457',
              academic_year: '2025-2026',
              semester: 'Fall',
              enrolled_at: '2025-01-17'
            }
          ];
          
          console.log('Set enrollments for CS201:', this.currentCourseEnrollments);
          
          // Set available students for this course
          this.$store.commit('enrollments/SET_AVAILABLE_STUDENTS', [
            {
              id: 4,
              name: 'Student One',
              email: 'student1@example.com',
              matric_number: 'S123456',
              enrollment_count: 1
            },
            {
              id: 6,
              name: 'Student Three',
              email: 'student3@example.com',
              matric_number: 'S123458',
              enrollment_count: 0
            }
          ]);
        } else {
          this.currentCourseEnrollments = [];
          console.log('No enrollments for course:', this.selectedCourseId);
        }
        
        // Filter out students that have been transferred OUT of this course
        const currentCourseId = parseInt(this.selectedCourseId);
        if (this.transferredOutStudents && this.transferredOutStudents.has(currentCourseId)) {
          const transferredOutIds = this.transferredOutStudents.get(currentCourseId);
          this.currentCourseEnrollments = this.currentCourseEnrollments.filter(
            enrollment => !transferredOutIds.includes(enrollment.enrollment_id)
          );
        }
        
        // Add any transferred students to this course
        if (this.transferredStudents && this.transferredStudents.has(parseInt(this.selectedCourseId))) {
          const transferredToThisCourse = this.transferredStudents.get(parseInt(this.selectedCourseId));
          this.currentCourseEnrollments = this.currentCourseEnrollments.concat(transferredToThisCourse);
        }
        
        console.log('Course enrollments loaded for course:', this.selectedCourseId);
      } catch (error) {
        console.error('Error loading course enrollments:', error);
        this.$store.dispatch('showToast', {
          message: 'Error loading course enrollments',
          type: 'error'
        });
      }
    },

    async loadCourseEnrollmentsOriginal() {
      if (!this.selectedCourseId) {
        this.currentCourseEnrollments = [];
        return;
      }
      
      try {
        await Promise.all([
          this.$store.dispatch('enrollments/fetchCourseEnrollments', this.selectedCourseId),
          this.$store.dispatch('enrollments/fetchAvailableStudents', this.selectedCourseId)
        ]);
        
        this.currentCourseEnrollments = this.$store.getters['enrollments/getEnrollments'];
      } catch (error) {
        console.error('Error loading course enrollments:', error);
        this.$store.dispatch('showToast', {
          message: 'Error loading course enrollments',
          type: 'error'
        });
      }
    },
    
    getLecturerName(lecturerId) {
      if (!this.getUsers || !Array.isArray(this.getUsers)) {
        return 'Unknown Lecturer';
      }
      const lecturer = this.getUsers.find(u => u.id === lecturerId && u.role === 'lecturer');
      return lecturer ? lecturer.name : 'Unknown Lecturer';
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
    
    openBulkEnrollModal() {
      this.bulkEnrollForm = {
        studentIds: [],
        academicYear: this.selectedCourse?.academic_year || '2025-2026',
        semester: 'Fall'
      };
      
      const modal = new bootstrap.Modal(document.getElementById('bulkEnrollModal'));
      modal.show();
    },
    
    toggleSelectAllStudents(event) {
      if (event.target.checked) {
        this.bulkEnrollForm.studentIds = this.availableStudents.map(s => s.id);
      } else {
        this.bulkEnrollForm.studentIds = [];
      }
    },
    
    async bulkEnrollStudents() {
      try {
        const result = await this.$store.dispatch('enrollments/bulkEnrollStudents', {
          courseId: this.selectedCourseId,
          studentIds: this.bulkEnrollForm.studentIds,
          academicYear: this.bulkEnrollForm.academicYear,
          semester: this.bulkEnrollForm.semester
        });
        
        // Close modal
        const modalElement = document.getElementById('bulkEnrollModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        modal.hide();
        
        // Refresh enrollments
        await this.loadCourseEnrollments();
        
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
          courseId: this.selectedCourseId
        });
        
        // Close modal
        const modalElement = document.getElementById('removeEnrollmentModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        modal.hide();
        
        // Refresh enrollments
        await this.loadCourseEnrollments();
        
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
    
    viewStudentDetails(studentId) {
      // Navigate to student details page
      this.$router.push(`/admin/student/${studentId}`);
    },
    
    openStudentEnrollmentHistoryModal() {
      // This could open a modal to search and view student enrollment history
      this.$store.dispatch('showToast', {
        message: 'Student enrollment history feature coming soon',
        type: 'info'
      });
    },
    
    openTransferStudentModal(student = null) {
      if (!student) {
        this.$store.dispatch('showToast', {
          message: 'Please select a student to transfer from the enrollment list',
          type: 'warning'
        });
        return;
      }
      
      if (!this.selectedCourseId) {
        this.$store.dispatch('showToast', {
          message: 'Please select a course first',
          type: 'warning'
        });
        return;
      }
      
      // Set up transfer form
      this.transferForm.student = student;
      this.transferForm.targetCourseId = '';
      
      // Show modal
      const modal = new bootstrap.Modal(document.getElementById('transferStudentModal'));
      modal.show();
    },
    
    async transferStudent() {
      if (!this.transferForm.student || !this.transferForm.targetCourseId) {
        this.$store.dispatch('showToast', {
          message: 'Please select a target course',
          type: 'error'
        });
        return;
      }
      
      try {
        // Find target course info
        const targetCourse = this.courses.find(c => c.id == this.transferForm.targetCourseId);
        const studentName = this.transferForm.student.student_name;
        
        // Remove from current course
        const studentIndex = this.currentCourseEnrollments.findIndex(
          e => e.enrollment_id === this.transferForm.student.enrollment_id
        );
        
        if (studentIndex !== -1) {
          // Store the enrollment data before removing it
          const enrollmentToTransfer = { ...this.currentCourseEnrollments[studentIndex] };
          
          // Track student transferred OUT of current course
          const currentCourseId = parseInt(this.selectedCourseId);
          if (!this.transferredOutStudents.has(currentCourseId)) {
            this.transferredOutStudents.set(currentCourseId, []);
          }
          this.transferredOutStudents.get(currentCourseId).push(enrollmentToTransfer.enrollment_id);
          
          // Remove from current course enrollments
          this.currentCourseEnrollments.splice(studentIndex, 1);
          
          // Update the enrollment data for the target course
          enrollmentToTransfer.course_id = this.transferForm.targetCourseId;
          enrollmentToTransfer.course_code = targetCourse.code;
          enrollmentToTransfer.course_name = targetCourse.name;
          enrollmentToTransfer.lecturer_id = targetCourse.lecturer_id;
          enrollmentToTransfer.enrolled_at = new Date().toISOString();
          
          // Store the transfer in a temporary data structure for persistence
          const targetCourseId = parseInt(this.transferForm.targetCourseId);
          
          // Store by target course ID
          if (!this.transferredStudents.has(targetCourseId)) {
            this.transferredStudents.set(targetCourseId, []);
          }
          this.transferredStudents.get(targetCourseId).push(enrollmentToTransfer);
          
          // Save to localStorage for persistence
          this.saveTransferredStudents();
          
          // Update the loadEnrollments method to include transferred students
          // Refresh the current course view to reflect the removal
          await this.loadCourseEnrollments();
        }
        
        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('transferStudentModal'));
        modal.hide();
        
        // Clear form
        this.transferForm.student = null;
        this.transferForm.targetCourseId = '';
        
        // Show success message
        this.$store.dispatch('showToast', {
          message: `Student ${studentName} has been transferred to ${targetCourse?.code || 'target course'}`,
          type: 'success'
        });
        
      } catch (error) {
        console.error('Error transferring student:', error);
        this.$store.dispatch('showToast', {
          message: 'Error transferring student. Please try again.',
          type: 'error'
        });
      }
    },
    
    exportAllEnrollments() {
      if (!this.currentCourseEnrollments.length) {
        this.$store.dispatch('showToast', {
          message: 'No enrollments to export',
          type: 'warning'
        });
        return;
      }
      
      // Create CSV content
      const headers = ['Course Code', 'Course Name', 'Student Name', 'Matric Number', 'Email', 'Academic Year', 'Semester', 'Enrolled Date'];
      let csvContent = headers.join(',') + '\n';
      
      this.currentCourseEnrollments.forEach(enrollment => {
        const row = [
          this.selectedCourse?.code || 'N/A',
          `"${this.selectedCourse?.name || 'N/A'}"`,
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
      link.setAttribute('download', `${this.selectedCourse?.code || 'course'}_enrollments.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },

    async logout() {
      if (confirm('Are you sure you want to logout?')) {
        await this.$store.dispatch('auth/logout');
        this.$router.push('/login');
      }
    },

    // Persistence methods for transferred students
    saveTransferredStudents() {
      try {
        const transfersObj = {};
        this.transferredStudents.forEach((students, courseId) => {
          transfersObj[courseId] = students;
        });
        
        const transfersOutObj = {};
        this.transferredOutStudents.forEach((enrollmentIds, courseId) => {
          transfersOutObj[courseId] = enrollmentIds;
        });
        
        localStorage.setItem('enrollmentTransfers', JSON.stringify({
          transfersIn: transfersObj,
          transfersOut: transfersOutObj
        }));
      } catch (error) {
        console.error('Error saving transferred students:', error);
      }
    },

    loadTransferredStudents() {
      try {
        const saved = localStorage.getItem('enrollmentTransfers');
        if (saved) {
          const data = JSON.parse(saved);
          
          // Load transfers IN
          this.transferredStudents = new Map();
          if (data.transfersIn) {
            Object.keys(data.transfersIn).forEach(courseId => {
              this.transferredStudents.set(parseInt(courseId), data.transfersIn[courseId]);
            });
          }
          
          // Load transfers OUT
          this.transferredOutStudents = new Map();
          if (data.transfersOut) {
            Object.keys(data.transfersOut).forEach(courseId => {
              this.transferredOutStudents.set(parseInt(courseId), data.transfersOut[courseId]);
            });
          }
        }
      } catch (error) {
        console.error('Error loading transferred students:', error);
        this.transferredStudents = new Map();
        this.transferredOutStudents = new Map();
      }
    }
  }
};
</script>

<style scoped>
.admin-enrollment-management h1 {
  color: #2c3e50;
  font-weight: 700;
}

.card {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border: none;
  border-radius: 10px;
  margin-bottom: 1.5rem;
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

.stat-item {
  margin-bottom: 10px;
  padding-bottom: 10px;
  border-bottom: 1px solid #f8f9fa;
}

.stat-item:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.table th {
  font-weight: 600;
  color: #2c3e50;
  border-top: none;
}

.btn-group .btn {
  margin-right: 0;
}
</style>
