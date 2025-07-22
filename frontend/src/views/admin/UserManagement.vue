<template>
  <div class="user-management">
    <h1>User Management</h1>
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Users</h5>
        <button class="btn btn-primary" @click="showAddUserModal = true">
          <i class="bi bi-plus"></i> Add User
        </button>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-search"></i>
            </span>
            <input 
              type="text" 
              class="form-control" 
              v-model="searchQuery" 
              placeholder="Search users..." 
            />
            <select class="form-select" v-model="roleFilter" style="max-width: 150px;">
              <option value="">All Roles</option>
              <option value="admin">Admin</option>
              <option value="lecturer">Lecturer</option>
              <option value="student">Student</option>
              <option value="advisor">Advisor</option>
            </select>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Matric Number</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in filteredUsers" :key="user.id">
                <td>{{ user.name }}</td>
                <td>{{ user.email }}</td>
                <td>
                  <span class="badge" :class="getRoleBadgeClass(user.role)">
                    {{ user.role }}
                  </span>
                </td>
                <td>{{ user.matric_number || '-' }}</td>
                <td>{{ formatDate(user.created_at) }}</td>
                <td>
                  <div class="btn-group">
                    <button 
                      class="btn btn-sm btn-outline-secondary"
                      @click="editUser(user)"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button 
                      class="btn btn-sm btn-outline-danger"
                      @click="confirmDeleteUser(user)"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                    <button 
                      class="btn btn-sm btn-outline-primary"
                      @click="resetPassword(user)"
                    >
                      <i class="bi bi-key"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination controls -->
        <nav aria-label="User pagination">
          <ul class="pagination justify-content-center">
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">Previous</a>
            </li>
            <li v-for="page in totalPages" :key="page" class="page-item" :class="{ active: page === currentPage }">
              <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">Next</a>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal" tabindex="-1" :class="{ 'd-block': showAddUserModal }">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add New User</h5>
            <button type="button" class="btn-close" @click="showAddUserModal = false"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="addUser">
              <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" v-model="newUser.name" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" v-model="newUser.email" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Role</label>
                <select class="form-select" v-model="newUser.role" required>
                  <option value="admin">Admin</option>
                  <option value="lecturer">Lecturer</option>
                  <option value="student">Student</option>
                  <option value="advisor">Advisor</option>
                </select>
              </div>
              <div class="mb-3" v-if="newUser.role === 'student'">
                <label class="form-label">Matric Number</label>
                <input type="text" class="form-control" v-model="newUser.matricNumber">
              </div>
              <div class="mb-3" v-if="newUser.role === 'student'">
                <label class="form-label">PIN (for student login)</label>
                <input type="password" class="form-control" v-model="newUser.pin">
              </div>
              <div class="mb-3" v-if="newUser.role === 'student'">
                <label class="form-label">Advisor</label>
                <select class="form-select" v-model="newUser.advisorId">
                  <option value="">Select Advisor</option>
                  <option 
                    v-for="advisor in advisors" 
                    :key="advisor.id" 
                    :value="advisor.id"
                  >
                    {{ advisor.name }}
                  </option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" v-model="newUser.password" required>
              </div>
              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-secondary me-2" @click="showAddUserModal = false">Cancel</button>
                <button type="submit" class="btn btn-primary">Add User</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show" v-if="showAddUserModal"></div>

    <!-- Edit User Modal -->
    <div class="modal" tabindex="-1" :class="{ 'd-block': showEditUserModal }">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit User</h5>
            <button type="button" class="btn-close" @click="showEditUserModal = false"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="updateUser">
              <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" v-model="editingUser.name" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" v-model="editingUser.email" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Role</label>
                <select class="form-select" v-model="editingUser.role" required>
                  <option value="admin">Admin</option>
                  <option value="lecturer">Lecturer</option>
                  <option value="student">Student</option>
                  <option value="advisor">Advisor</option>
                </select>
              </div>
              <div class="mb-3" v-if="editingUser.role === 'student'">
                <label class="form-label">Matric Number</label>
                <input type="text" class="form-control" v-model="editingUser.matric_number">
              </div>
              <div class="mb-3" v-if="editingUser.role === 'student'">
                <label class="form-label">Advisor</label>
                <select class="form-select" v-model="editingUser.advisor_id">
                  <option value="">Select Advisor</option>
                  <option 
                    v-for="advisor in advisors" 
                    :key="advisor.id" 
                    :value="advisor.id"
                  >
                    {{ advisor.name }}
                  </option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">New Password (leave blank to keep current)</label>
                <input type="password" class="form-control" v-model="editingUser.password">
              </div>
              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-secondary me-2" @click="showEditUserModal = false">Cancel</button>
                <button type="submit" class="btn btn-primary">Update User</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show" v-if="showEditUserModal"></div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" tabindex="-1" :class="{ 'd-block': showDeleteModal }">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Delete</h5>
            <button type="button" class="btn-close" @click="showDeleteModal = false"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete user <strong>{{ userToDelete?.name }}</strong>?</p>
            <p class="text-danger">This action cannot be undone and will remove all associated data.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showDeleteModal = false">Cancel</button>
            <button type="button" class="btn btn-danger" @click="deleteUser">Delete User</button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show" v-if="showDeleteModal"></div>

    <!-- Reset Password Modal -->
    <div class="modal" tabindex="-1" :class="{ 'd-block': showResetPasswordModal }">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Reset Password</h5>
            <button type="button" class="btn-close" @click="showResetPasswordModal = false"></button>
          </div>
          <div class="modal-body">
            <p>Set a new password for <strong>{{ userToResetPassword?.name }}</strong>:</p>
            <div class="mb-3">
              <label class="form-label">New Password</label>
              <input type="password" class="form-control" v-model="newPassword" required>
            </div>
            <div class="mb-3" v-if="userToResetPassword?.role === 'student'">
              <label class="form-label">New PIN (for student login)</label>
              <input type="password" class="form-control" v-model="newPin">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showResetPasswordModal = false">Cancel</button>
            <button type="button" class="btn btn-primary" @click="confirmResetPassword">Reset Password</button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show" v-if="showResetPasswordModal"></div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'UserManagement',
  data() {
    return {
      users: [],
      advisors: [],
      searchQuery: '',
      roleFilter: '',
      currentPage: 1,
      itemsPerPage: 10,
      showAddUserModal: false,
      showEditUserModal: false,
      showDeleteModal: false,
      showResetPasswordModal: false,
      newUser: {
        name: '',
        email: '',
        role: 'student',
        matricNumber: '',
        pin: '',
        advisorId: null,
        password: ''
      },
      editingUser: {
        id: null,
        name: '',
        email: '',
        role: '',
        matric_number: '',
        advisor_id: null,
        password: ''
      },
      userToDelete: null,
      userToResetPassword: null,
      newPassword: '',
      newPin: ''
    };
  },
  computed: {
    filteredUsers() {
      let filtered = this.users;
      
      // Apply search query filter
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(user => 
          user.name.toLowerCase().includes(query) || 
          user.email.toLowerCase().includes(query) ||
          (user.matric_number && user.matric_number.toLowerCase().includes(query))
        );
      }
      
      // Apply role filter
      if (this.roleFilter) {
        filtered = filtered.filter(user => user.role === this.roleFilter);
      }
      
      // Calculate pagination
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      
      return filtered.slice(start, end);
    },
    totalPages() {
      let filtered = this.users;
      
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(user => 
          user.name.toLowerCase().includes(query) || 
          user.email.toLowerCase().includes(query) ||
          (user.matric_number && user.matric_number.toLowerCase().includes(query))
        );
      }
      
      if (this.roleFilter) {
        filtered = filtered.filter(user => user.role === this.roleFilter);
      }
      
      return Math.ceil(filtered.length / this.itemsPerPage);
    }
  },
  methods: {
    formatDate(dateString) {
      const date = new Date(dateString);
      return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
      }).format(date);
    },
    getRoleBadgeClass(role) {
      switch (role) {
        case 'admin':
          return 'bg-danger';
        case 'lecturer':
          return 'bg-primary';
        case 'student':
          return 'bg-success';
        case 'advisor':
          return 'bg-warning';
        default:
          return 'bg-secondary';
      }
    },
    changePage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
      }
    },
    fetchUsers() {
      const token = this.$store.getters['auth/token'];
      
      axios.get(`${process.env.VUE_APP_API_URL}/api/admin/users`, {
        headers: { Authorization: `Bearer ${token}` }
      })
      .then(response => {
        this.users = response.data;
      })
      .catch(error => {
        console.error('Error fetching users:', error);
        this.$toast.error('Failed to load users');
      });
    },
    fetchAdvisors() {
      const token = this.$store.getters['auth/token'];
      
      axios.get(`${process.env.VUE_APP_API_URL}/api/users?role=advisor`, {
        headers: { Authorization: `Bearer ${token}` }
      })
      .then(response => {
        this.advisors = response.data;
      })
      .catch(error => {
        console.error('Error fetching advisors:', error);
      });
    },
    addUser() {
      const token = this.$store.getters['auth/token'];
      
      axios.post(`${process.env.VUE_APP_API_URL}/api/admin/users`, this.newUser, {
        headers: { Authorization: `Bearer ${token}` }
      })
      .then(response => {
        this.$toast.success('User added successfully');
        this.showAddUserModal = false;
        this.fetchUsers();
        
        // Reset form
        this.newUser = {
          name: '',
          email: '',
          role: 'student',
          matricNumber: '',
          pin: '',
          advisorId: null,
          password: ''
        };
      })
      .catch(error => {
        console.error('Error adding user:', error);
        this.$toast.error(error.response?.data?.error || 'Failed to add user');
      });
    },
    editUser(user) {
      this.editingUser = { ...user };
      this.showEditUserModal = true;
    },
    updateUser() {
      const token = this.$store.getters['auth/token'];
      const userId = this.editingUser.id;
      
      // Only send fields that should be updated
      const updateData = {
        name: this.editingUser.name,
        email: this.editingUser.email,
        role: this.editingUser.role
      };
      
      if (this.editingUser.role === 'student') {
        updateData.matricNumber = this.editingUser.matric_number;
        updateData.advisorId = this.editingUser.advisor_id;
      }
      
      if (this.editingUser.password) {
        updateData.password = this.editingUser.password;
      }
      
      axios.put(`${process.env.VUE_APP_API_URL}/api/admin/users/${userId}`, updateData, {
        headers: { Authorization: `Bearer ${token}` }
      })
      .then(response => {
        this.$toast.success('User updated successfully');
        this.showEditUserModal = false;
        this.fetchUsers();
      })
      .catch(error => {
        console.error('Error updating user:', error);
        this.$toast.error(error.response?.data?.error || 'Failed to update user');
      });
    },
    confirmDeleteUser(user) {
      this.userToDelete = user;
      this.showDeleteModal = true;
    },
    deleteUser() {
      const token = this.$store.getters['auth/token'];
      const userId = this.userToDelete.id;
      
      axios.delete(`${process.env.VUE_APP_API_URL}/api/admin/users/${userId}`, {
        headers: { Authorization: `Bearer ${token}` }
      })
      .then(response => {
        this.$toast.success('User deleted successfully');
        this.showDeleteModal = false;
        this.fetchUsers();
      })
      .catch(error => {
        console.error('Error deleting user:', error);
        this.$toast.error(error.response?.data?.error || 'Failed to delete user');
      });
    },
    resetPassword(user) {
      this.userToResetPassword = user;
      this.newPassword = '';
      this.newPin = '';
      this.showResetPasswordModal = true;
    },
    confirmResetPassword() {
      const token = this.$store.getters['auth/token'];
      const userId = this.userToResetPassword.id;
      
      const updateData = {
        password: this.newPassword
      };
      
      if (this.userToResetPassword.role === 'student' && this.newPin) {
        updateData.pin = this.newPin;
      }
      
      axios.put(`${process.env.VUE_APP_API_URL}/api/admin/users/${userId}`, updateData, {
        headers: { Authorization: `Bearer ${token}` }
      })
      .then(response => {
        this.$toast.success('Password reset successfully');
        this.showResetPasswordModal = false;
      })
      .catch(error => {
        console.error('Error resetting password:', error);
        this.$toast.error(error.response?.data?.error || 'Failed to reset password');
      });
    }
  },
  created() {
    this.fetchUsers();
    this.fetchAdvisors();
  }
};
</script>

<style scoped>
.user-management {
  padding: 20px;
}

.modal {
  background-color: rgba(0, 0, 0, 0.5);
}

.badge {
  text-transform: capitalize;
  font-size: 0.8rem;
  padding: 5px 10px;
}
</style>
