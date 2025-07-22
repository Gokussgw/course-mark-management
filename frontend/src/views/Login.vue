<template>
  <div class="login-page">
    <div class="login-container">
      <div class="login-branding">
        <div class="logo-container">
          <i class="fas fa-graduation-cap logo-icon"></i>
        </div>
        <h1 class="app-name">Course Mark Management</h1>
        <p class="app-tagline">Streamline your academic journey</p>
      </div>

      <div class="login-form-container">
        <div class="login-header">
          <h2>Welcome Back</h2>
          <p>Sign in to your account</p>
        </div>
        
        <div class="alert alert-danger" v-if="error">
          <i class="fas fa-exclamation-circle me-2"></i>{{ error }}
        </div>

        <!-- Unified Login Form -->
        <div class="login-form">
          <form @submit.prevent="handleLogin">
            <div class="input-group">
              <span class="input-icon"><i class="fas fa-envelope"></i></span>
              <input 
                type="email" 
                class="form-input" 
                placeholder="Email Address" 
                v-model="loginData.email" 
                required
                autocomplete="email"
              >
            </div>
            
            <div class="input-group">
              <span class="input-icon"><i class="fas fa-lock"></i></span>
              <input 
                :type="showPassword ? 'text' : 'password'" 
                class="form-input" 
                placeholder="Password" 
                v-model="loginData.password" 
                required
                autocomplete="current-password"
              >
              <span class="password-toggle" @click="togglePassword">
                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
              </span>
            </div>
            
            <div class="remember-forgot">
              <div class="remember-me">
                <input type="checkbox" id="remember" v-model="rememberMe">
                <label for="remember">Remember me</label>
              </div>
              <a href="#" class="forgot-password">Forgot password?</a>
            </div>
            
            <button 
              type="submit" 
              class="login-button"
              :disabled="isLoading"
            >
              <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
              <span>Sign In</span>
            </button>
          </form>
        </div>

        <div class="register-link">
          <p>Don't have an account? <router-link to="/register">Register</router-link></p>
        </div>

        <!-- Role Information -->
        <div class="role-info">
          <p class="text-muted small">
            <i class="fas fa-info-circle me-1"></i>
            All users (students, lecturers, advisors, and admins) use email and password to sign in
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';

export default {
  name: 'Login',
  data() {
    return {
      loginData: {
        email: '',
        password: ''
      },
      error: '',
      showPassword: false,
      rememberMe: false
    };
  },
  computed: {
    ...mapGetters(['isLoading'])
  },
  methods: {
    togglePassword() {
      this.showPassword = !this.showPassword;
    },
    
    async handleLogin() {
      this.error = '';
      
      try {
        const user = await this.$store.dispatch('auth/login', this.loginData);
        
        // Redirect based on user role
        this.redirectUserToRolePage(user.role);
      } catch (error) {
        this.error = error.message || 'Invalid email or password';
      }
    },

    redirectUserToRolePage(role) {
      switch(role) {
        case 'student':
          this.$router.push('/student/dashboard');
          break;
        case 'lecturer':
          this.$router.push('/lecturer/dashboard');
          break;
        case 'advisor':
          this.$router.push('/advisor/dashboard');
          break;
        case 'admin':
          this.$router.push('/admin/dashboard');
          break;
        default:
          this.error = 'Invalid user role';
          break;
      }
    }
  },
  
  mounted() {
    // Clear any existing auth state when component loads
    this.$store.dispatch('auth/logout');
  }
};
</script>

<style scoped>
.login-page {
  display: flex;
  min-height: 100vh;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1e5799 0%, #2989d8 50%, #207cca 100%);
  padding: 20px;
}

.login-container {
  display: flex;
  flex-direction: row;
  width: 100%;
  max-width: 1000px;
  min-height: 550px;
  background-color: #fff;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  overflow: hidden;
}

.login-branding {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #0f4c81 0%, #236ab9 100%);
  color: white;
  padding: 40px;
  position: relative;
}

.logo-container {
  width: 90px;
  height: 90px;
  border-radius: 50%;
  background-color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
}

.logo-icon {
  font-size: 40px;
  color: #0f4c81;
}

.app-name {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 16px;
  text-align: center;
}

.app-tagline {
  font-size: 14px;
  opacity: 0.8;
  margin-bottom: 30px;
  text-align: center;
}

.login-form-container {
  flex: 1.2;
  display: flex;
  flex-direction: column;
  padding: 40px;
  position: relative;
}

.login-header {
  margin-bottom: 30px;
  text-align: center;
}

.login-header h2 {
  color: #333;
  margin-bottom: 8px;
  font-size: 24px;
  font-weight: 600;
}

.login-header p {
  color: #666;
  margin: 0;
  font-size: 14px;
}

.alert {
  margin-bottom: 20px;
  border-radius: 8px;
  padding: 12px 16px;
}

.alert-danger {
  background-color: #fee2e2;
  color: #dc2626;
  border-left: 4px solid #dc2626;
}

.input-group {
  position: relative;
  margin-bottom: 24px;
}

.input-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
}

.form-input {
  width: 100%;
  padding: 14px 14px 14px 45px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 15px;
  transition: all 0.3s;
  background-color: #f9f9f9;
}

.form-input:focus {
  border-color: #0f4c81;
  box-shadow: 0 0 0 3px rgba(15, 76, 129, 0.1);
  background-color: white;
}

.password-toggle {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
  cursor: pointer;
  transition: all 0.2s;
}

.password-toggle:hover {
  color: #333;
}

.remember-forgot {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  font-size: 14px;
}

.remember-me {
  display: flex;
  align-items: center;
}

.remember-me input {
  margin-right: 8px;
}

.forgot-password {
  color: #0f4c81;
  text-decoration: none;
  transition: all 0.2s;
}

.forgot-password:hover {
  text-decoration: underline;
}

.login-button {
  width: 100%;
  padding: 14px;
  background-color: #0f4c81;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
}

.login-button:hover {
  background-color: #0d3d69;
}

.login-button:disabled {
  background-color: #7a9cbf;
  cursor: not-allowed;
}

.register-link {
  text-align: center;
  font-size: 14px;
  color: #666;
  margin-bottom: 20px;
}

.register-link a {
  color: #0f4c81;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s;
}

.register-link a:hover {
  text-decoration: underline;
}

.role-info {
  text-align: center;
  margin-top: auto;
}

.role-info p {
  margin: 0;
  padding: 10px;
  background-color: #f8f9fa;
  border-radius: 6px;
  border-left: 3px solid #0f4c81;
}

/* Responsive design */
@media (max-width: 768px) {
  .login-container {
    flex-direction: column;
    max-width: 500px;
  }
  
  .login-branding {
    padding: 30px 20px;
  }
  
  .login-form-container {
    padding: 30px 20px;
  }
}
</style>
