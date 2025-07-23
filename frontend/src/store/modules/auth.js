import axios from 'axios'

export default {
  namespaced: true,
  state: {
    token: localStorage.getItem('token') || null,
    user: JSON.parse(localStorage.getItem('user')) || null
  },
  getters: {
    isAuthenticated: state => !!state.token,
    getUser: state => state.user,
    getToken: state => state.token,
    userRole: state => state.user ? state.user.role : null,
    userId: state => state.user ? state.user.id : null
  },
  mutations: {
    SET_TOKEN(state, token) {
      state.token = token;
    },
    SET_USER(state, user) {
      state.user = user;
    },
    CLEAR_AUTH(state) {
      state.token = null;
      state.user = null;
    }
  },
  actions: {
    async login({ commit, dispatch }, credentials) {
      try {
        dispatch('setLoading', true, { root: true });
        
        // Use the proper auth endpoint with correct backend port
        const response = await axios.post('http://localhost:8000/api/auth/login', {
          email: credentials.email,
          password: credentials.password
        });
        
        console.log('Login response:', response.data); // Debug log
        const { token, user } = response.data;
        
        console.log('Extracted token:', token); // Debug log
        console.log('Extracted user:', user); // Debug log
        
        // Save to localStorage
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
        
        console.log('Saved to localStorage - token:', localStorage.getItem('token')); // Debug log
        console.log('Saved to localStorage - user:', localStorage.getItem('user')); // Debug log
        
        // Update state
        commit('SET_TOKEN', token);
        commit('SET_USER', user);
        
        console.log('Store updated successfully'); // Debug log
        
        return user;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Login failed';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async register({ dispatch }, userData) {
      try {
        dispatch('setLoading', true, { root: true });
        const response = await axios.post('http://localhost:8000/api/auth/register', userData);
        return response.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Registration failed';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async logout({ commit }) {
      try {
        // Clear localStorage first
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        
        // Update state
        commit('CLEAR_AUTH');
        
        console.log('Logout successful'); // Debug log
      } catch (error) {
        console.error('Error during logout:', error);
        // Even if there's an error, clear the auth state
        commit('CLEAR_AUTH');
      }
    },
    
    checkAuth({ commit }) {
      try {
        console.log('=== checkAuth called ==='); // Debug log
        const token = localStorage.getItem('token');
        const userStr = localStorage.getItem('user');
        
        console.log('Checking auth - token:', token);
        console.log('Checking auth - user string:', userStr);
        
        if (token && userStr) {
          const user = JSON.parse(userStr);
          console.log('Parsed user:', user);
          commit('SET_TOKEN', token);
          commit('SET_USER', user);
          console.log('Auth state restored from localStorage'); // Debug log
        } else {
          console.log('No auth data found in localStorage');
        }
      } catch (error) {
        console.error('Error checking auth:', error);
        // Clear potentially corrupted data
        localStorage.removeItem('token');
        localStorage.removeItem('user');
      }
    }
  }
};
