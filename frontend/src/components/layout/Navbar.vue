<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
      <router-link class="navbar-brand" to="/">Course Mark Management</router-link>
      
      <button 
        class="navbar-toggler" 
        type="button" 
        data-bs-toggle="collapse" 
        data-bs-target="#navbarContent" 
        aria-controls="navbarContent" 
        aria-expanded="false" 
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>
      
      <div class="collapse navbar-collapse" id="navbarContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <!-- Lecturer navigation -->
          <template v-if="userRole === 'lecturer'">
            <li class="nav-item">
              <router-link class="nav-link" to="/lecturer/dashboard">Dashboard</router-link>
            </li>
            <li class="nav-item dropdown">
              <a 
                class="nav-link dropdown-toggle" 
                href="#" 
                id="coursesDropdown" 
                role="button" 
                data-bs-toggle="dropdown" 
                aria-expanded="false"
              >
                Courses
              </a>
              <ul class="dropdown-menu" aria-labelledby="coursesDropdown">
                <li v-for="course in courses" :key="course.id">
                  <router-link class="dropdown-item" :to="`/lecturer/course/${course.id}`">
                    {{ course.code }} - {{ course.name }}
                  </router-link>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <a class="dropdown-item" href="#" @click.prevent="openCourseModal">
                    <i class="fas fa-plus-circle"></i> Add New Course
                  </a>
                </li>
              </ul>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" to="/lecturer/students">Students</router-link>
            </li>
          </template>
          
          <!-- Student navigation -->
          <template v-else-if="userRole === 'student'">
            <li class="nav-item">
              <router-link class="nav-link" to="/student/dashboard">Dashboard</router-link>
            </li>
            <li class="nav-item dropdown">
              <a 
                class="nav-link dropdown-toggle" 
                href="#" 
                id="studentCoursesDropdown" 
                role="button" 
                data-bs-toggle="dropdown" 
                aria-expanded="false"
              >
                My Courses
              </a>
              <ul class="dropdown-menu" aria-labelledby="studentCoursesDropdown">
                <li v-for="course in courses" :key="course.id">
                  <router-link class="dropdown-item" :to="`/student/course/${course.id}`">
                    {{ course.code }} - {{ course.name }}
                  </router-link>
                </li>
              </ul>
            </li>
          </template>
          
          <!-- Advisor navigation -->
          <template v-else-if="userRole === 'advisor'">
            <li class="nav-item">
              <router-link class="nav-link" to="/advisor/dashboard">Dashboard</router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" to="/advisor/advisees">My Advisees</router-link>
            </li>
          </template>
          
          <!-- Admin navigation -->
          <template v-else-if="userRole === 'admin'">
            <li class="nav-item">
              <router-link class="nav-link" to="/admin/dashboard">Dashboard</router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" to="/admin/users">User Management</router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" to="/admin/logs">System Logs</router-link>
            </li>
          </template>
        </ul>
        
        <div class="d-flex align-items-center">
          <!-- Notifications Dropdown -->
          <notifications-dropdown />
          
          <!-- User info and logout -->
          <div class="dropdown ms-3">
            <a 
              class="dropdown-toggle text-decoration-none text-white d-flex align-items-center" 
              href="#" 
              id="userDropdown" 
              role="button" 
              data-bs-toggle="dropdown" 
              aria-expanded="false"
            >
              <i class="bi bi-person-circle me-2"></i>
              <span>{{ userName }}</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li>
                <router-link class="dropdown-item" to="/profile">
                  <i class="bi bi-person me-2"></i> Profile
                </router-link>
              </li>
              <li>
                <router-link class="dropdown-item" to="/settings">
                  <i class="bi bi-gear me-2"></i> Settings
                </router-link>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item" href="#" @click.prevent="logout">
                  <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Add Course Modal -->
    <div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addCourseModalLabel">Add New Course</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="addCourse">
              <div class="mb-3">
                <label for="courseCode" class="form-label">Course Code</label>
                <input type="text" class="form-control" id="courseCode" v-model="newCourse.code" required>
              </div>
              <div class="mb-3">
                <label for="courseName" class="form-label">Course Name</label>
                <input type="text" class="form-control" id="courseName" v-model="newCourse.name" required>
              </div>
              <div class="mb-3">
                <label for="semester" class="form-label">Semester</label>
                <input type="text" class="form-control" id="semester" v-model="newCourse.semester">
              </div>
              <div class="mb-3">
                <label for="academicYear" class="form-label">Academic Year</label>
                <input type="text" class="form-control" id="academicYear" v-model="newCourse.academic_year">
              </div>
              <button type="submit" class="btn btn-primary">Save Course</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script>
import { mapGetters } from 'vuex';
import bootstrap from 'bootstrap';
import NotificationsDropdown from '../notifications/NotificationsDropdown.vue';

export default {
  components: {
    NotificationsDropdown
  },
  name: 'Navbar',
  data() {
    return {
      courses: [],
      newCourse: {
        code: '',
        name: '',
        semester: '',
        academic_year: ''
      }
    };
  },
  computed: {
    ...mapGetters('auth', ['getUser', 'userRole']),
    userName() {
      return this.getUser ? this.getUser.name : '';
    }
  },
  created() {
    this.loadCourses();
  },
  methods: {
    async loadCourses() {
      try {
        if (this.userRole === 'lecturer') {
          // Load courses that this lecturer teaches
          this.courses = await this.$store.dispatch('courses/fetchCourses', {
            lecturerId: this.getUser.id
          });
        } else if (this.userRole === 'student') {
          // For students, we need to fetch courses they're enrolled in
          // This would require a custom endpoint in the backend
          this.courses = await this.$store.dispatch('courses/fetchCourses');
        }
      } catch (error) {
        console.error('Error loading courses:', error);
      }
    },
    async logout() {
      // Show a notification
      this.$store.dispatch('notification/add', {
        type: 'info',
        message: 'You have been logged out successfully'
      });
      
      // Log the user out
      await this.$store.dispatch('auth/logout');
      
      // Redirect to login page
      this.$router.push('/login');
    },
    openCourseModal() {
      // Reset the form
      this.newCourse = {
        code: '',
        name: '',
        semester: '',
        academic_year: ''
      };
      
      // Open the modal using Bootstrap's JavaScript
      const modal = new bootstrap.Modal(document.getElementById('addCourseModal'));
      modal.show();
    },
    async addCourse() {
      try {
        // Add lecturer_id to the course data
        const courseData = {
          ...this.newCourse,
          lecturer_id: this.getUser.id
        };
        
        await this.$store.dispatch('courses/createCourse', courseData);
        
        // Close the modal using Bootstrap's JavaScript
        const modalElement = document.getElementById('addCourseModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        modal.hide();
        
        // Reload courses
        this.loadCourses();
      } catch (error) {
        console.error('Error adding course:', error);
      }
    }
  }
};
</script>

<style scoped>
.navbar {
  margin-bottom: 20px;
  background-color: #2c3e50 !important;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.navbar-brand {
  font-weight: 700;
  font-size: 1.5rem;
}

.nav-link {
  font-weight: 500;
  transition: color 0.3s ease;
}

.nav-link:hover {
  color: #3498db !important;
}

.dropdown-menu {
  border: none;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border-radius: 0.5rem;
}

.dropdown-item {
  padding: 8px 16px;
  transition: background-color 0.3s ease;
}

.dropdown-item:hover {
  background-color: #f8f9fa;
}
</style>
