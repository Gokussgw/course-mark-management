<template>
  <div class="dashboard">
    <!-- Dashboard Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="mb-1">Lecturer Dashboard</h1>
        <p class="text-muted mb-0">Welcome back, {{ getUser.name }}</p>
      </div>
      <div class="d-flex align-items-center gap-3">
        <router-link 
          to="/lecturer/marks" 
          class="btn btn-success"
          title="Manage Marks"
        >
          <i class="fas fa-graduation-cap me-2"></i>Manage Marks
        </router-link>
        <router-link 
          to="/lecturer/feedback" 
          class="btn btn-info"
          title="Student Feedback"
        >
          <i class="fas fa-comments me-2"></i>Student Feedback
        </router-link>
        <div class="user-info text-end">
          <small class="text-muted d-block">{{ getUser.email }}</small>
          <small class="badge bg-primary">{{ getUser.role }}</small>
        </div>
        <button class="btn btn-outline-danger" @click="logout" title="Logout">
          <i class="fas fa-sign-out-alt me-2"></i>Logout
        </button>
      </div>
    </div>
    
    <div class="row">
      <div class="col-md-6 mb-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="card-title mb-0">My Courses</h5>
              <div class="btn-group">
                <button class="btn btn-success btn-sm" @click="openAddCourseModal">
                  <i class="fas fa-plus me-2"></i>Add Course
                </button>
                <button class="btn btn-outline-secondary btn-sm" @click="exportCoursesToCSV">
                  <i class="fas fa-file-export me-2"></i>Export CSV
                </button>
              </div>
            </div>
            <p class="card-text text-muted">Manage and view courses you are teaching this semester</p>
            
            <div v-if="isLoading" class="text-center">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
            
            <div v-else-if="courses.length === 0" class="text-center py-4">
              <i class="fas fa-book-open text-muted mb-3" style="font-size: 3rem;"></i>
              <h6 class="text-muted">No courses yet</h6>
              <p class="text-muted small">Start by creating your first course to manage assessments and student marks.</p>
              <button class="btn btn-primary" @click="openAddCourseModal">
                <i class="fas fa-plus-circle me-2"></i> Create Your First Course
              </button>
            </div>
            
            <div v-else class="list-group">
              <div v-for="course in courses" :key="course.id" class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                  <div class="flex-grow-1">
                    <router-link 
                      :to="`/lecturer/course/${course.id}`" 
                      class="text-decoration-none"
                    >
                      <strong>{{ course.code }}</strong> - {{ course.name }}
                      <div class="text-muted small">
                        {{ course.semester || 'No semester' }} | {{ course.academic_year || 'No year' }}
                      </div>
                    </router-link>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary rounded-pill">
                      {{ getAssessmentCountForCourse(course.id) }} assessments
                    </span>
                    <div class="btn-group" role="group">
                      <router-link 
                        :to="`/lecturer/course/${course.id}/enrollments`" 
                        class="btn btn-sm btn-outline-info"
                        title="Manage Enrollments"
                      >
                        <i class="fas fa-users"></i>
                      </router-link>
                      <router-link 
                        :to="`/lecturer/course/${course.id}/marks`" 
                        class="btn btn-sm btn-outline-success"
                        title="Manage Marks"
                      >
                        <i class="fas fa-graduation-cap"></i>
                      </router-link>
                      <router-link 
                        :to="`/lecturer/breakdown/${course.id}`" 
                        class="btn btn-sm btn-outline-warning"
                        title="View Mark Breakdown"
                      >
                        <i class="fas fa-chart-bar"></i>
                      </router-link>
                      <button 
                        class="btn btn-sm btn-outline-primary" 
                        @click="editCourse(course)"
                        title="Edit Course"
                      >
                        <i class="fas fa-edit"></i>
                      </button>
                      <button 
                        class="btn btn-sm btn-outline-danger" 
                        @click="confirmDeleteCourse(course)"
                        title="Delete Course"
                      >
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6 mb-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Recent Activity</h5>
            <p class="card-text text-muted mb-4">Latest updates and actions</p>
            
            <div class="timeline">
              <div class="timeline-item" v-for="(activity, index) in recentActivities" :key="index">
                <div class="timeline-item-icon">
                  <i :class="activity.icon"></i>
                </div>
                <div class="timeline-item-content">
                  <h6>{{ activity.title }}</h6>
                  <p class="text-muted mb-0">{{ activity.description }}</p>
                  <small class="text-muted">{{ activity.time }}</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Analytics Charts Row -->
    <div class="row">
      <div class="col-md-6 mb-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Assessment Types</h5>
            <p class="card-text text-muted mb-3">Distribution of assessment types across courses</p>
            <AssessmentChart :assessments="assessments" :height="250" />
          </div>
        </div>
      </div>
      
      <div class="col-md-6 mb-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Mark Distribution</h5>
            <p class="card-text text-muted mb-3">Overview of student performance</p>
            <MarkDistributionChart :mark-data="markDistribution" :height="250" />
          </div>
        </div>
      </div>
    </div>
    
    <div class="row">
      <div class="col-md-12 mb-4">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="card-title mb-0">Assessment Overview</h5>
              <button class="btn btn-sm btn-success" @click="exportAssessmentsToCSV">
                <i class="fas fa-file-export me-2"></i>Export Marks
              </button>
            </div>
            <p class="card-text text-muted mb-4">Current status of assessments across all courses</p>
            
            <div v-if="isLoading" class="text-center">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
            
            <table v-else class="table table-hover">
              <thead>
                <tr>
                  <th>Course</th>
                  <th>Assessment</th>
                  <th>Type</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="assessment in upcomingAssessments" :key="assessment.id">
                  <td>{{ getCourseNameById(assessment.course_id) }}</td>
                  <td>{{ assessment.name }}</td>
                  <td>
                    <span class="badge" :class="getAssessmentTypeBadgeClass(assessment.type)">
                      {{ assessment.type }}
                    </span>
                  </td>
                  <td>{{ formatDate(assessment.date) }}</td>
                  <td>
                    <span class="badge bg-warning" v-if="isPending(assessment)">Pending</span>
                    <span class="badge bg-success" v-else>Completed</span>
                  </td>
                  <td>
                    <div class="btn-group btn-group-sm" role="group">
                      <router-link :to="`/lecturer/assessment/edit/${assessment.id}`" class="btn btn-outline-primary">
                        <i class="fas fa-edit"></i>
                      </router-link>
                      <router-link :to="`/lecturer/course/${assessment.course_id}`" class="btn btn-outline-info">
                        <i class="fas fa-eye"></i>
                      </router-link>
                      <button 
                        class="btn btn-outline-success"
                        @click="openNotificationModal(assessment)"
                        title="Notify Students"
                      >
                        <i class="fas fa-bell"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                
                <tr v-if="upcomingAssessments.length === 0">
                  <td colspan="6" class="text-center">No upcoming assessments</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Add/Edit Course Modal -->
    <div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addCourseModalLabel">
              {{ isEditMode ? 'Edit Course' : 'Add New Course' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveCourse">
              <div class="mb-3">
                <label for="courseCode" class="form-label">Course Code</label>
                <input 
                  type="text" 
                  class="form-control" 
                  id="courseCode" 
                  v-model="newCourse.code" 
                  required
                  placeholder="e.g., CS101"
                >
              </div>
              <div class="mb-3">
                <label for="courseName" class="form-label">Course Name</label>
                <input 
                  type="text" 
                  class="form-control" 
                  id="courseName" 
                  v-model="newCourse.name" 
                  required
                  placeholder="e.g., Introduction to Computer Science"
                >
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="semester" class="form-label">Semester</label>
                    <select class="form-control" id="semester" v-model="newCourse.semester">
                      <option value="">Select Semester</option>
                      <option value="Fall">Fall</option>
                      <option value="Spring">Spring</option>
                      <option value="Summer">Summer</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="academicYear" class="form-label">Academic Year</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      id="academicYear" 
                      v-model="newCourse.academic_year"
                      placeholder="e.g., 2025-2026"
                    >
                  </div>
                </div>
              </div>
              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" :disabled="isLoading">
                  <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                  {{ isEditMode ? 'Update Course' : 'Save Course' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Delete Course Confirmation Modal -->
    <div class="modal fade" id="deleteCourseModal" tabindex="-1" aria-labelledby="deleteCourseModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteCourseModalLabel">Confirm Delete Course</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div v-if="courseToDelete">
              <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Warning:</strong> This action cannot be undone.
              </div>
              <p>Are you sure you want to delete the following course?</p>
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">{{ courseToDelete.code }} - {{ courseToDelete.name }}</h6>
                  <p class="card-text text-muted small">
                    {{ courseToDelete.semester || 'No semester' }} | {{ courseToDelete.academic_year || 'No year' }}
                  </p>
                  <p class="card-text">
                    <span class="badge bg-primary">
                      {{ getAssessmentCountForCourse(courseToDelete.id) }} assessments
                    </span>
                  </p>
                </div>
              </div>
              <p class="text-danger mt-3">
                <strong>Note:</strong> Deleting this course will also remove all associated assessments and marks.
              </p>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button 
              type="button" 
              class="btn btn-danger" 
              @click="deleteCourse"
              :disabled="isLoading"
            >
              <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
              Delete Course
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Notify Students Modal -->
    <div class="modal fade" id="notifyStudentsModal" tabindex="-1" aria-labelledby="notifyStudentsModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="notifyStudentsModalLabel">Notify Students</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div v-if="selectedAssessment">
              <p><strong>Course:</strong> {{ getCourseNameById(selectedAssessment.course_id) }}</p>
              <p><strong>Assessment:</strong> {{ selectedAssessment.name }} ({{ selectedAssessment.type }})</p>
              
              <form @submit.prevent="sendNotification">
                <div class="mb-3">
                  <label for="notificationTitle" class="form-label">Notification Title</label>
                  <input type="text" class="form-control" id="notificationTitle" v-model="notification.title" required>
                </div>
                <div class="mb-3">
                  <label for="notificationMessage" class="form-label">Message</label>
                  <textarea class="form-control" id="notificationMessage" rows="4" v-model="notification.message" required></textarea>
                </div>
                <div class="form-check mb-3">
                  <input class="form-check-input" type="checkbox" id="includeMarks" v-model="notification.includeMarks">
                  <label class="form-check-label" for="includeMarks">
                    Include marks in notification
                  </label>
                </div>
                <button type="submit" class="btn btn-primary">Send Notification</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import AssessmentChart from '@/components/charts/AssessmentChart.vue';
import MarkDistributionChart from '@/components/charts/MarkDistributionChart.vue';
import * as bootstrap from 'bootstrap';

export default {
  name: 'LecturerDashboard',
  components: {
    AssessmentChart,
    MarkDistributionChart
  },
  data() {
    return {
      courses: [],
      assessments: [],
      marks: [],
      selectedAssessment: null,
      notification: {
        title: '',
        message: '',
        includeMarks: true
      },
      markDistribution: {
        gradeA: 4,
        gradeB: 7,
        gradeC: 10,
        gradeD: 5,
        gradeF: 2
      },
      recentActivities: [
        {
          title: 'Marks Updated',
          description: 'You updated marks for Database Systems assignment',
          time: '2 hours ago',
          icon: 'fas fa-pen text-primary'
        },
        {
          title: 'New Assessment Added',
          description: 'Web Development - Midterm Exam',
          time: '1 day ago',
          icon: 'fas fa-plus-circle text-success'
        },
        {
          title: 'Student Request',
          description: 'John Smith requested a mark review',
          time: '2 days ago',
          icon: 'fas fa-question-circle text-warning'
        }
      ],
      newCourse: {
        code: '',
        name: '',
        semester: '',
        academic_year: ''
      },
      editingCourse: null,
      isEditMode: false,
      courseToDelete: null
    };
  },
  computed: {
    ...mapGetters(['isLoading']),
    ...mapGetters('auth', ['getUser']),
    
    upcomingAssessments() {
      return this.assessments
        .filter(assessment => !assessment.is_graded)
        .sort((a, b) => new Date(a.date) - new Date(b.date))
        .slice(0, 5);
    }
  },
  created() {
    this.loadData();
  },
  methods: {
    async loadData() {
      try {
        // Fetch courses for the current lecturer
        this.courses = await this.$store.dispatch('courses/fetchCourses', {
          lecturerId: this.getUser.id
        });
        
        // Fetch assessments for all courses
        const courseIds = this.courses.map(course => course.id);
        
        for (const courseId of courseIds) {
          const courseAssessments = await this.$store.dispatch('assessments/fetchAssessments', {
            courseId
          });
          this.assessments = [...this.assessments, ...courseAssessments];
        }
      } catch (error) {
        console.error('Error loading dashboard data:', error);
      }
    },
    
    getAssessmentCountForCourse(courseId) {
      return this.assessments.filter(a => a.course_id === courseId).length;
    },
    
    getCourseNameById(courseId) {
      const course = this.courses.find(c => c.id === courseId);
      return course ? `${course.code} - ${course.name}` : 'Unknown Course';
    },
    
    getAssessmentTypeBadgeClass(type) {
      switch (type) {
        case 'quiz': return 'bg-info';
        case 'assignment': return 'bg-primary';
        case 'midterm': return 'bg-warning';
        case 'final_exam': return 'bg-danger';
        default: return 'bg-secondary';
      }
    },
    
    formatDate(dateString) {
      if (!dateString) return 'Not scheduled';
      
      const date = new Date(dateString);
      return date.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
      });
    },
    
    isPending() {
      // Simple logic to determine if an assessment is pending
      // In a real app, this would check if all students have marks for this assessment
      return true;
    },
    
    openAddCourseModal() {
      // Reset the form for adding new course
      this.newCourse = {
        code: '',
        name: '',
        semester: '',
        academic_year: ''
      };
      this.isEditMode = false;
      this.editingCourse = null;
      
      // Open the modal using Bootstrap's JavaScript
      const modal = new bootstrap.Modal(document.getElementById('addCourseModal'));
      modal.show();
    },

    editCourse(course) {
      // Populate the form with course data for editing
      this.newCourse = {
        code: course.code,
        name: course.name,
        semester: course.semester || '',
        academic_year: course.academic_year || ''
      };
      this.isEditMode = true;
      this.editingCourse = course;
      
      // Open the modal using Bootstrap's JavaScript
      const modal = new bootstrap.Modal(document.getElementById('addCourseModal'));
      modal.show();
    },

    async saveCourse() {
      try {
        if (this.isEditMode) {
          // Update existing course
          await this.$store.dispatch('courses/updateCourse', {
            courseId: this.editingCourse.id,
            courseData: this.newCourse
          });
          this.$store.dispatch('showToast', { 
            message: 'Course updated successfully!', 
            type: 'success' 
          });
        } else {
          // Create new course
          const courseData = {
            ...this.newCourse,
            lecturer_id: this.getUser.id
          };
          await this.$store.dispatch('courses/createCourse', courseData);
          this.$store.dispatch('showToast', { 
            message: 'Course created successfully!', 
            type: 'success' 
          });
        }
        
        // Close the modal
        const modalElement = document.getElementById('addCourseModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        modal.hide();
        
        // Reload courses
        this.loadData();
      } catch (error) {
        console.error('Error saving course:', error);
        this.$store.dispatch('showToast', { 
          message: 'Error saving course. Please try again.', 
          type: 'error' 
        });
      }
    },

    confirmDeleteCourse(course) {
      this.courseToDelete = course;
      
      // Open the delete confirmation modal
      const modal = new bootstrap.Modal(document.getElementById('deleteCourseModal'));
      modal.show();
    },

    async deleteCourse() {
      try {
        await this.$store.dispatch('courses/deleteCourse', this.courseToDelete.id);
        
        // Close the modal
        const modalElement = document.getElementById('deleteCourseModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        modal.hide();
        
        this.$store.dispatch('showToast', { 
          message: 'Course deleted successfully!', 
          type: 'success' 
        });
        
        // Reload courses
        this.loadData();
        
        // Clear the course to delete
        this.courseToDelete = null;
      } catch (error) {
        console.error('Error deleting course:', error);
        this.$store.dispatch('showToast', { 
          message: 'Error deleting course. Please try again.', 
          type: 'error' 
        });
      }
    },

    async addCourse() {
      // This method is deprecated in favor of saveCourse
      await this.saveCourse();
    },
    
    exportCoursesToCSV() {
      if (!this.courses.length) {
        alert('No courses to export');
        return;
      }
      
      // Convert courses data to CSV format
      const headers = ['ID', 'Code', 'Name', 'Semester', 'Academic Year', 'Number of Assessments'];
      let csvContent = headers.join(',') + '\n';
      
      this.courses.forEach(course => {
        const assessmentCount = this.getAssessmentCountForCourse(course.id);
        const row = [
          course.id,
          course.code,
          `"${course.name}"`,  // Quotes to handle commas in names
          course.semester || 'Not specified',
          course.academic_year || 'Not specified',
          assessmentCount
        ];
        csvContent += row.join(',') + '\n';
      });
      
      // Create and trigger download
      this.downloadCSV(csvContent, 'courses.csv');
    },
    
    exportAssessmentsToCSV() {
      if (!this.assessments.length) {
        alert('No assessments to export');
        return;
      }
      
      // Convert assessments data to CSV format
      const headers = ['ID', 'Course', 'Name', 'Type', 'Date', 'Max Mark', 'Weightage', 'Status'];
      let csvContent = headers.join(',') + '\n';
      
      this.assessments.forEach(assessment => {
        const row = [
          assessment.id,
          `"${this.getCourseNameById(assessment.course_id)}"`,
          `"${assessment.name}"`,
          assessment.type,
          assessment.date || 'Not scheduled',
          assessment.max_mark,
          `${assessment.weightage}%`,
          this.isPending(assessment) ? 'Pending' : 'Completed'
        ];
        csvContent += row.join(',') + '\n';
      });
      
      // Create and trigger download
      this.downloadCSV(csvContent, 'assessments.csv');
    },
    
    downloadCSV(csvContent, fileName) {
      // Create a Blob with the CSV content
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      
      // Create a temporary link and trigger download
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', fileName);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },
    
    openNotificationModal(assessment) {
      // Set the selected assessment
      this.selectedAssessment = assessment;
      
      // Pre-fill notification fields
      const courseName = this.getCourseNameById(assessment.course_id);
      this.notification = {
        title: `${assessment.name} Update`,
        message: `Dear Students,\n\nThe marks for ${assessment.name} in ${courseName} have been updated. Please login to view your results.\n\nRegards,\n${this.getUser.name}`,
        includeMarks: true
      };
      
      // Open the modal
      const modal = new bootstrap.Modal(document.getElementById('notifyStudentsModal'));
      modal.show();
    },
    
    async sendNotification() {
      try {
        // In a real implementation, this would make an API call to send notifications
        // to all students enrolled in the course with the assessment
        
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // Show success message
        alert(`Notification sent to students in ${this.getCourseNameById(this.selectedAssessment.course_id)}`);
        
        // Add to recent activities
        const newActivity = {
          title: 'Students Notified',
          description: `${this.notification.title} - ${this.getCourseNameById(this.selectedAssessment.course_id)}`,
          time: 'Just now',
          icon: 'fas fa-bell text-success'
        };
        this.recentActivities.unshift(newActivity);
        if (this.recentActivities.length > 5) {
          this.recentActivities.pop();
        }
        
        // Close the modal
        const modalElement = document.getElementById('notifyStudentsModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        modal.hide();
      } catch (error) {
        console.error('Error sending notification:', error);
        alert('Failed to send notification. Please try again.');
      }
    },

    logout() {
      if (confirm('Are you sure you want to logout?')) {
        this.$store.dispatch('auth/logout');
        this.$router.push('/login');
      }
    }
  }
};
</script>

<style scoped>
.dashboard h1 {
  color: #2c3e50;
  font-weight: 700;
}

.card {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border: none;
  border-radius: 10px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
}

.card-title {
  color: #2c3e50;
  font-weight: 600;
}

.list-group-item {
  border-left: 0;
  border-right: 0;
  padding: 15px;
  transition: background-color 0.2s ease;
}

.list-group-item:first-child {
  border-top: 0;
}

.list-group-item:last-child {
  border-bottom: 0;
}

.timeline {
  position: relative;
  padding-left: 30px;
}

.timeline-item {
  position: relative;
  margin-bottom: 25px;
}

.timeline-item:last-child {
  margin-bottom: 0;
}

.timeline-item:before {
  content: '';
  position: absolute;
  left: -30px;
  top: 0;
  bottom: -25px;
  width: 2px;
  background-color: #e9ecef;
}

.timeline-item:last-child:before {
  bottom: 0;
}

.timeline-item-icon {
  position: absolute;
  left: -39px;
  top: 0;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background-color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
}

.timeline-item-icon i {
  font-size: 14px;
}

.timeline-item-content h6 {
  margin-bottom: 5px;
  font-weight: 600;
}

.badge {
  padding: 6px 10px;
  font-weight: 500;
  text-transform: capitalize;
}

.table th {
  font-weight: 600;
  color: #2c3e50;
}
</style>
