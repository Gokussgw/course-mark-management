<template>
  <div class="admin-dashboard">
    <!-- Dashboard Header -->
    <div class="dashboard-header d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0">Admin Dashboard</h1>
      <div class="user-info d-flex align-items-center">
        <span class="me-3">Welcome, {{ userInfo.name }}</span>
        <button class="btn btn-outline-danger btn-sm" @click="logout">
          <i class="fas fa-sign-out-alt me-1"></i>
          Logout
        </button>
      </div>
    </div>
    
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card h-100 bg-primary text-white dashboard-stat">
          <div class="card-body">
            <h5 class="card-title">Users</h5>
            <div class="d-flex align-items-center">
              <div class="dashboard-stat-icon">
                <i class="fas fa-users"></i>
              </div>
              <div class="dashboard-stat-number">
                {{ userStats.total }}
              </div>
            </div>
            <div class="dashboard-stat-details">
              <div>Lecturers: {{ userStats.lecturers }}</div>
              <div>Students: {{ userStats.students }}</div>
              <div>Advisors: {{ userStats.advisors }}</div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="card h-100 bg-success text-white dashboard-stat">
          <div class="card-body">
            <h5 class="card-title">Courses</h5>
            <div class="d-flex align-items-center">
              <div class="dashboard-stat-icon">
                <i class="fas fa-book"></i>
              </div>
              <div class="dashboard-stat-number">
                {{ stats.courses }}
              </div>
            </div>
            <div class="dashboard-stat-details">
              <div>Active: {{ stats.activeCourses }}</div>
              <div>This Semester: {{ stats.currentSemesterCourses }}</div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="card h-100 bg-warning text-white dashboard-stat">
          <div class="card-body">
            <h5 class="card-title">Assessments</h5>
            <div class="d-flex align-items-center">
              <div class="dashboard-stat-icon">
                <i class="fas fa-tasks"></i>
              </div>
              <div class="dashboard-stat-number">
                {{ stats.assessments }}
              </div>
            </div>
            <div class="dashboard-stat-details">
              <div>Upcoming: {{ stats.upcomingAssessments }}</div>
              <div>Completed: {{ stats.completedAssessments }}</div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="card h-100 bg-danger text-white dashboard-stat">
          <div class="card-body">
            <h5 class="card-title">System</h5>
            <div class="d-flex align-items-center">
              <div class="dashboard-stat-icon">
                <i class="fas fa-server"></i>
              </div>
              <div class="dashboard-stat-number">
                <i class="fas fa-check-circle"></i>
              </div>
            </div>
            <div class="dashboard-stat-details">
              <div>Database: Online</div>
              <div>Last Backup: 2 hours ago</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="row">
      <div class="col-md-8">
        <!-- System Health Component -->
        <system-health-monitor />
        
        <div class="card mb-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h5 class="card-title mb-0">User Management</h5>
              <button class="btn btn-primary btn-sm" @click="openAddUserModal">
                <i class="fas fa-plus-circle me-2"></i>
                Add User
              </button>
            </div>
            
            <div class="mb-3 d-flex justify-content-between align-items-center">
              <div class="input-group" style="width: 300px;">
                <span class="input-group-text bg-white border-end-0">
                  <i class="fas fa-search text-muted"></i>
                </span>
                <input 
                  type="text" 
                  class="form-control border-start-0"
                  placeholder="Search users..."
                  v-model="searchQuery"
                >
              </div>
              
              <div class="btn-group">
                <button 
                  class="btn" 
                  :class="roleFilter === 'all' ? 'btn-primary' : 'btn-outline-primary'" 
                  @click="roleFilter = 'all'"
                >
                  All
                </button>
                <button 
                  class="btn" 
                  :class="roleFilter === 'lecturer' ? 'btn-primary' : 'btn-outline-primary'" 
                  @click="roleFilter = 'lecturer'"
                >
                  Lecturers
                </button>
                <button 
                  class="btn" 
                  :class="roleFilter === 'student' ? 'btn-primary' : 'btn-outline-primary'" 
                  @click="roleFilter = 'student'"
                >
                  Students
                </button>
                <button 
                  class="btn" 
                  :class="roleFilter === 'advisor' ? 'btn-primary' : 'btn-outline-primary'" 
                  @click="roleFilter = 'advisor'"
                >
                  Advisors
                </button>
              </div>
            </div>
            
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="user in filteredUsers" :key="user.id">
                    <td>{{ user.name }}</td>
                    <td>{{ user.email }}</td>
                    <td>
                      <span class="badge" :class="getRoleBadgeClass(user.role)">
                        {{ user.role.toUpperCase() }}
                      </span>
                    </td>
                    <td>
                      <span class="badge" :class="user.active ? 'bg-success' : 'bg-danger'">
                        {{ user.active ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm">
                        <button 
                          class="btn btn-outline-primary" 
                          @click="editUser(user)"
                          title="Edit User"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button 
                          class="btn btn-outline-warning" 
                          @click="resetPassword(user)"
                          title="Reset Password"
                        >
                          <i class="fas fa-key"></i>
                        </button>
                        <button 
                          class="btn" 
                          :class="user.active ? 'btn-outline-danger' : 'btn-outline-success'"
                          @click="toggleUserStatus(user)"
                          :title="user.active ? 'Deactivate User' : 'Activate User'"
                        >
                          <i class="fas" :class="user.active ? 'fa-user-slash' : 'fa-user-check'"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-3">
              <div>
                <span class="text-muted">Showing {{ filteredUsers.length }} of {{ users.length }} users</span>
              </div>
              <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                  <li class="page-item"><a class="page-link" href="#">Previous</a></li>
                  <li class="page-item active"><a class="page-link" href="#">1</a></li>
                  <li class="page-item"><a class="page-link" href="#">2</a></li>
                  <li class="page-item"><a class="page-link" href="#">3</a></li>
                  <li class="page-item"><a class="page-link" href="#">Next</a></li>
                </ul>
              </nav>
            </div>
          </div>
        </div>
        
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h5 class="card-title mb-0">System Logs</h5>
              <div>
                <button class="btn btn-outline-secondary btn-sm me-2">
                  <i class="fas fa-filter me-1"></i>
                  Filter
                </button>
                <button class="btn btn-outline-primary btn-sm">
                  <i class="fas fa-download me-1"></i>
                  Export
                </button>
              </div>
            </div>
            
            <div class="table-responsive">
              <table class="table table-sm">
                <thead class="table-light">
                  <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>IP Address</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(log, index) in systemLogs" :key="index">
                    <td>{{ log.time }}</td>
                    <td>{{ log.user }}</td>
                    <td>{{ log.action }}</td>
                    <td>{{ log.ip }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title">Quick Actions</h5>
            
            <div class="list-group">
              <router-link to="/admin/enrollments" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <div>
                  <i class="fas fa-users text-info me-3"></i>
                  Manage Student Enrollments
                </div>
                <i class="fas fa-chevron-right text-muted"></i>
              </router-link>
              <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" @click="openBatchUploadModal">
                <div>
                  <i class="fas fa-upload text-primary me-3"></i>
                  Batch Upload Users
                </div>
                <i class="fas fa-chevron-right text-muted"></i>
              </button>
              <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" @click="openAssignLecturersModal">
                <div>
                  <i class="fas fa-chalkboard-teacher text-success me-3"></i>
                  Assign Lecturers to Courses
                </div>
                <i class="fas fa-chevron-right text-muted"></i>
              </button>
              <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" @click="openBackupModal">
                <div>
                  <i class="fas fa-database text-warning me-3"></i>
                  Backup Database
                </div>
                <i class="fas fa-chevron-right text-muted"></i>
              </button>
              <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" @click="openSystemSettingsModal">
                <div>
                  <i class="fas fa-cog text-secondary me-3"></i>
                  System Settings
                </div>
                <i class="fas fa-chevron-right text-muted"></i>
              </button>
            </div>
          </div>
        </div>
        
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title">System Health</h5>
            
            <div class="mb-3">
              <label class="form-label d-flex justify-content-between">
                <span>Database Storage</span>
                <span>65%</span>
              </label>
              <div class="progress">
                <div class="progress-bar bg-success" role="progressbar" style="width: 65%" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label d-flex justify-content-between">
                <span>File Storage</span>
                <span>42%</span>
              </label>
              <div class="progress">
                <div class="progress-bar bg-success" role="progressbar" style="width: 42%" aria-valuenow="42" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label d-flex justify-content-between">
                <span>CPU Usage</span>
                <span>28%</span>
              </label>
              <div class="progress">
                <div class="progress-bar bg-success" role="progressbar" style="width: 28%" aria-valuenow="28" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div>
            
            <div class="alert alert-success mb-0">
              <i class="fas fa-check-circle me-2"></i>
              All systems operating normally
            </div>
          </div>
        </div>
        
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Upcoming Maintenance</h5>
            <div class="d-flex align-items-center mb-3">
              <div class="maintenance-date bg-light rounded p-2 me-3 text-center">
                <div class="fw-bold">15</div>
                <div class="small">Jul</div>
              </div>
              <div>
                <div class="fw-bold">Database Optimization</div>
                <div class="text-muted small">Scheduled: 02:00 - 04:00 AM</div>
              </div>
            </div>
            
            <div class="d-flex align-items-center">
              <div class="maintenance-date bg-light rounded p-2 me-3 text-center">
                <div class="fw-bold">22</div>
                <div class="small">Jul</div>
              </div>
              <div>
                <div class="fw-bold">System Upgrade</div>
                <div class="text-muted small">Scheduled: 01:00 - 03:00 AM</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Add/Edit User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="userModalLabel">{{ editingUser ? 'Edit User' : 'Add New User' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveUser">
              <div class="mb-3">
                <label for="userName" class="form-label">Name</label>
                <input type="text" class="form-control" id="userName" v-model="userForm.name" required>
              </div>
              <div class="mb-3">
                <label for="userEmail" class="form-label">Email</label>
                <input type="email" class="form-control" id="userEmail" v-model="userForm.email" required>
              </div>
              <div class="mb-3">
                <label for="userRole" class="form-label">Role</label>
                <select class="form-select" id="userRole" v-model="userForm.role" required>
                  <option value="lecturer">Lecturer</option>
                  <option value="student">Student</option>
                  <option value="advisor">Advisor</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              
              <div class="mb-3" v-if="userForm.role === 'student'">
                <label for="matricNumber" class="form-label">Matric Number</label>
                <input type="text" class="form-control" id="matricNumber" v-model="userForm.matricNumber">
              </div>
              
              <div v-if="!editingUser" class="mb-3">
                <label for="userPassword" class="form-label">Password</label>
                <input type="password" class="form-control" id="userPassword" v-model="userForm.password" required>
              </div>
              
              <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="userActive" v-model="userForm.active">
                <label class="form-check-label" for="userActive">Active Account</label>
              </div>
              
              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Reset Password Confirmation Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Reset User Password</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to reset the password for <strong>{{ selectedUser ? selectedUser.name : '' }}</strong>?</p>
            <p>A new temporary password will be generated and sent to their email address.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-warning" @click="confirmResetPassword">Reset Password</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import * as bootstrap from 'bootstrap';
import SystemHealthMonitor from '../../components/admin/SystemHealthMonitor.vue';

export default {
  name: 'AdminDashboard',
  components: {
    SystemHealthMonitor
  },
  data() {
    return {
      users: [
        { id: 1, name: 'Admin User', email: 'admin@example.com', role: 'admin', active: true },
        { id: 2, name: 'Lecturer One', email: 'lecturer1@example.com', role: 'lecturer', active: true },
        { id: 3, name: 'Student One', email: 'student1@example.com', role: 'student', matricNumber: 'A12345', active: true },
        { id: 4, name: 'Advisor One', email: 'advisor1@example.com', role: 'advisor', active: true },
        { id: 5, name: 'Lecturer Two', email: 'lecturer2@example.com', role: 'lecturer', active: true },
        { id: 6, name: 'Student Two', email: 'student2@example.com', role: 'student', matricNumber: 'A12346', active: false },
        { id: 7, name: 'Student Three', email: 'student3@example.com', role: 'student', matricNumber: 'A12347', active: true }
      ],
      systemLogs: [
        { time: '2025-07-12 00:45:23', user: 'admin@example.com', action: 'User login', ip: '192.168.1.1' },
        { time: '2025-07-12 00:30:12', user: 'lecturer1@example.com', action: 'Added new assessment', ip: '192.168.1.2' },
        { time: '2025-07-11 23:15:45', user: 'admin@example.com', action: 'Reset password for student2@example.com', ip: '192.168.1.1' },
        { time: '2025-07-11 22:05:33', user: 'advisor1@example.com', action: 'User login', ip: '192.168.1.3' },
        { time: '2025-07-11 21:45:12', user: 'student1@example.com', action: 'Submitted remark request', ip: '192.168.1.4' }
      ],
      stats: {
        courses: 15,
        activeCourses: 12,
        currentSemesterCourses: 8,
        assessments: 45,
        upcomingAssessments: 12,
        completedAssessments: 33
      },
      searchQuery: '',
      roleFilter: 'all',
      userForm: {
        name: '',
        email: '',
        role: 'student',
        password: '',
        matricNumber: '',
        active: true
      },
      editingUser: false,
      selectedUser: null
    };
  },
  computed: {
    ...mapGetters(['isLoading']),
    ...mapGetters('auth', ['getUser']),
    
    userInfo() {
      return this.getUser || { name: 'Admin' };
    },
    
    userStats() {
      return {
        total: this.users.length,
        lecturers: this.users.filter(u => u.role === 'lecturer').length,
        students: this.users.filter(u => u.role === 'student').length,
        advisors: this.users.filter(u => u.role === 'advisor').length,
        admins: this.users.filter(u => u.role === 'admin').length
      };
    },
    
    filteredUsers() {
      return this.users.filter(user => {
        // Role filter
        if (this.roleFilter !== 'all' && user.role !== this.roleFilter) {
          return false;
        }
        
        // Search query
        if (this.searchQuery && 
            !user.name.toLowerCase().includes(this.searchQuery.toLowerCase()) && 
            !user.email.toLowerCase().includes(this.searchQuery.toLowerCase())) {
          return false;
        }
        
        return true;
      });
    }
  },
  methods: {
    getRoleBadgeClass(role) {
      switch (role) {
        case 'admin': return 'bg-danger';
        case 'lecturer': return 'bg-primary';
        case 'student': return 'bg-info';
        case 'advisor': return 'bg-success';
        default: return 'bg-secondary';
      }
    },
    
    openAddUserModal() {
      this.editingUser = false;
      this.userForm = {
        name: '',
        email: '',
        role: 'student',
        password: '',
        matricNumber: '',
        active: true
      };
      
      // Open modal
      const modal = new bootstrap.Modal(document.getElementById('userModal'));
      modal.show();
    },
    
    editUser(user) {
      this.editingUser = true;
      this.userForm = { ...user };
      
      // Open modal
      const modal = new bootstrap.Modal(document.getElementById('userModal'));
      modal.show();
    },
    
    async saveUser() {
      try {
        // In a real app, this would make an API call
        console.log('Saving user:', this.userForm);
        
        if (this.editingUser) {
          // Update existing user
          const index = this.users.findIndex(u => u.id === this.userForm.id);
          if (index !== -1) {
            this.users[index] = { ...this.userForm };
          }
        } else {
          // Add new user with ID
          const newUser = {
            ...this.userForm,
            id: Math.max(...this.users.map(u => u.id)) + 1
          };
          this.users.push(newUser);
        }
        
        // Close modal
        const modalElement = document.getElementById('userModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        modal.hide();
        
        // Show success message
        alert(`User ${this.editingUser ? 'updated' : 'added'} successfully`);
      } catch (error) {
        console.error('Error saving user:', error);
        alert('Failed to save user. Please try again.');
      }
    },
    
    resetPassword(user) {
      this.selectedUser = user;
      
      // Open confirmation modal
      const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
      modal.show();
    },
    
    async confirmResetPassword() {
      try {
        // In a real app, this would make an API call
        console.log(`Resetting password for user ${this.selectedUser.id}: ${this.selectedUser.email}`);
        
        // Close modal
        const modalElement = document.getElementById('resetPasswordModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        modal.hide();
        
        // Show success message
        alert(`Password reset for ${this.selectedUser.name}. A temporary password has been sent to their email.`);
      } catch (error) {
        console.error('Error resetting password:', error);
        alert('Failed to reset password. Please try again.');
      }
    },
    
    async toggleUserStatus(user) {
      try {
        // In a real app, this would make an API call
        console.log(`Toggling status for user ${user.id}: ${user.email}`);
        
        // Update user status
        const index = this.users.findIndex(u => u.id === user.id);
        if (index !== -1) {
          this.users[index].active = !user.active;
        }
        
        // Show success message
        alert(`User ${user.name} has been ${user.active ? 'deactivated' : 'activated'}.`);
      } catch (error) {
        console.error('Error toggling user status:', error);
        alert('Failed to update user status. Please try again.');
      }
    },
    
    openBatchUploadModal() {
      alert('Batch Upload Users feature would open here');
    },
    
    openAssignLecturersModal() {
      alert('Assign Lecturers to Courses feature would open here');
    },
    
    openBackupModal() {
      alert('Database Backup feature would open here');
    },
    
    openSystemSettingsModal() {
      alert('System Settings feature would open here');
    },

    async logout() {
      if (confirm('Are you sure you want to logout?')) {
        await this.$store.dispatch('auth/logout');
        this.$router.push('/login');
      }
    }
  }
};
</script>

<style scoped>
.admin-dashboard h1 {
  color: #2c3e50;
  font-weight: 700;
}

.card {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border: none;
  border-radius: 10px;
  margin-bottom: 1.5rem;
}

.dashboard-stat {
  position: relative;
  overflow: hidden;
}

.dashboard-stat-icon {
  font-size: 2.5rem;
  opacity: 0.7;
  margin-right: 1rem;
}

.dashboard-stat-number {
  font-size: 2.5rem;
  font-weight: 700;
}

.dashboard-stat-details {
  margin-top: 0.5rem;
  opacity: 0.8;
  font-size: 0.9rem;
}

.table th {
  font-weight: 600;
}

.badge {
  padding: 0.5em 0.85em;
  font-weight: 500;
  text-transform: capitalize;
}

.maintenance-date {
  min-width: 50px;
}

.form-check-input:checked {
  background-color: #2c3e50;
  border-color: #2c3e50;
}

.list-group-item {
  border-left: 0;
  border-right: 0;
  padding: 0.75rem 1rem;
}

.list-group-item:first-child {
  border-top: 0;
}

.list-group-item:last-child {
  border-bottom: 0;
}

.pagination .page-link {
  color: #2c3e50;
}

.pagination .active .page-link {
  background-color: #2c3e50;
  border-color: #2c3e50;
}

.progress {
  height: 0.5rem;
}
</style>
