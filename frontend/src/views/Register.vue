<template>
  <div class="register-container">
    <div class="card">
      <div class="card-header bg-primary text-white">
        <h2 class="mb-0">Register</h2>
      </div>
      <div class="card-body">
        <form @submit.prevent="handleRegister">
          <div class="alert alert-danger" v-if="error">
            {{ error }}
          </div>

          <!-- User Information -->
          <div class="form-group mb-3">
            <label for="fullName">Full Name</label>
            <input
              type="text"
              id="fullName"
              v-model="user.fullName"
              class="form-control"
              required
            />
          </div>

          <div class="form-group mb-3">
            <label for="email">Email</label>
            <input
              type="email"
              id="email"
              v-model="user.email"
              class="form-control"
              required
            />
          </div>

          <!-- Role Selection -->
          <div class="form-group mb-3">
            <label for="role">Role</label>
            <select id="role" v-model="user.role" class="form-control" required>
              <option value="">Select your role</option>
              <option value="student">Student</option>
              <option value="lecturer">Lecturer</option>
              <option value="advisor">Academic Advisor</option>
            </select>
          </div>

          <!-- Student-specific fields -->
          <div v-if="user.role === 'student'" class="student-fields">
            <div class="form-group mb-3">
              <label for="studentId">Student ID</label>
              <input
                type="text"
                id="studentId"
                v-model="user.studentId"
                class="form-control"
                required
              />
            </div>
            
            <div class="form-group mb-3">
              <label for="program">Program</label>
              <input
                type="text"
                id="program"
                v-model="user.program"
                class="form-control"
                required
              />
            </div>
          </div>

          <!-- Lecturer-specific fields -->
          <div v-if="user.role === 'lecturer'" class="lecturer-fields">
            <div class="form-group mb-3">
              <label for="department">Department</label>
              <input
                type="text"
                id="department"
                v-model="user.department"
                class="form-control"
                required
              />
            </div>
          </div>

          <!-- Password fields -->
          <div class="form-group mb-3">
            <label for="password">Password</label>
            <input
              type="password"
              id="password"
              v-model="user.password"
              class="form-control"
              required
            />
          </div>

          <div class="form-group mb-3">
            <label for="confirmPassword">Confirm Password</label>
            <input
              type="password"
              id="confirmPassword"
              v-model="user.confirmPassword"
              class="form-control"
              required
            />
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <router-link to="/login" class="text-decoration-none">Already have an account? Login</router-link>
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
              {{ loading ? 'Registering...' : 'Register' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions } from 'vuex'

export default {
  name: 'Register',
  data() {
    return {
      user: {
        fullName: '',
        email: '',
        password: '',
        confirmPassword: '',
        role: '',
        studentId: '',
        program: '',
        department: ''
      },
      loading: false,
      error: null
    }
  },
  methods: {
    ...mapActions('auth', ['register']),
    async handleRegister() {
      // Reset error
      this.error = null

      // Validate passwords match
      if (this.user.password !== this.user.confirmPassword) {
        this.error = 'Passwords do not match';
        return;
      }

      // Start loading
      this.loading = true;

      try {
        // Call register action from auth store
        await this.register(this.user);
        // Redirect to login page after successful registration
        this.$router.push('/login');
      } catch (err) {
        this.error = err.message || 'Failed to register. Please try again.';
      } finally {
        this.loading = false;
      }
    }
  }
}
</script>

<style scoped>
.register-container {
  max-width: 600px;
  margin: 2rem auto;
  padding: 0 1rem;
}

.card {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
</style>
