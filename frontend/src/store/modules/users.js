import axios from 'axios'

export default {
  namespaced: true,
  state: {
    users: [],
    user: null,
    students: [],
    lecturers: [],
    advisors: [],
    advisees: []
  },
  getters: {
    getAllUsers: state => state.users,
    getUsers: state => state.users,
    getUser: state => state.user,
    getAllStudents: state => state.students,
    getAllLecturers: state => state.lecturers,
    getAllAdvisors: state => state.advisors,
    getAllAdvisees: state => state.advisees
  },
  mutations: {
    SET_USERS(state, users) {
      state.users = users;
    },
    SET_USER(state, user) {
      state.user = user;
    },
    SET_STUDENTS(state, students) {
      state.students = students;
    },
    SET_LECTURERS(state, lecturers) {
      state.lecturers = lecturers;
    },
    SET_ADVISORS(state, advisors) {
      state.advisors = advisors;
    },
    SET_ADVISEES(state, advisees) {
      state.advisees = advisees;
    },
    ADD_USER(state, user) {
      state.users.push(user);
      // Also add to specific role arrays
      if (user.role === 'student') state.students.push(user);
      if (user.role === 'lecturer') state.lecturers.push(user);
      if (user.role === 'advisor') state.advisors.push(user);
    },
    UPDATE_USER(state, updatedUser) {
      // Update in users array
      const index = state.users.findIndex(u => u.id === updatedUser.id);
      if (index !== -1) {
        state.users.splice(index, 1, updatedUser);
      }
      
      // Update in specific role arrays
      if (updatedUser.role === 'student') {
        const studentIndex = state.students.findIndex(s => s.id === updatedUser.id);
        if (studentIndex !== -1) {
          state.students.splice(studentIndex, 1, updatedUser);
        } else {
          state.students.push(updatedUser);
        }
      }
      
      if (updatedUser.role === 'lecturer') {
        const lecturerIndex = state.lecturers.findIndex(l => l.id === updatedUser.id);
        if (lecturerIndex !== -1) {
          state.lecturers.splice(lecturerIndex, 1, updatedUser);
        } else {
          state.lecturers.push(updatedUser);
        }
      }
      
      if (updatedUser.role === 'advisor') {
        const advisorIndex = state.advisors.findIndex(a => a.id === updatedUser.id);
        if (advisorIndex !== -1) {
          state.advisors.splice(advisorIndex, 1, updatedUser);
        } else {
          state.advisors.push(updatedUser);
        }
      }
      
      // If current user is being viewed
      if (state.user && state.user.id === updatedUser.id) {
        state.user = updatedUser;
      }
    },
    DELETE_USER(state, userId) {
      state.users = state.users.filter(u => u.id !== userId);
      state.students = state.students.filter(u => u.id !== userId);
      state.lecturers = state.lecturers.filter(u => u.id !== userId);
      state.advisors = state.advisors.filter(u => u.id !== userId);
      state.advisees = state.advisees.filter(u => u.id !== userId);
      
      if (state.user && state.user.id === userId) {
        state.user = null;
      }
    }
  },
  actions: {
    async fetchUsers({ commit, dispatch }) {
      try {
        dispatch('setLoading', true, { root: true });
        
        const response = await axios.get('/api/users');
        commit('SET_USERS', response.data);
        return response.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to fetch users';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async fetchUsersByRole({ commit, dispatch }, role) {
      try {
        dispatch('setLoading', true, { root: true });
        
        const response = await axios.get('/api/users', { params: { role } });
        
        if (role === 'student') {
          commit('SET_STUDENTS', response.data);
        } else if (role === 'lecturer') {
          commit('SET_LECTURERS', response.data);
        } else if (role === 'advisor') {
          commit('SET_ADVISORS', response.data);
        }
        
        return response.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || `Failed to fetch ${role}s`;
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async fetchUser({ commit, dispatch }, userId) {
      try {
        dispatch('setLoading', true, { root: true });
        
        const response = await axios.get(`/api/users/${userId}`);
        commit('SET_USER', response.data);
        return response.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to fetch user';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async createUser({ commit, dispatch }, userData) {
      try {
        dispatch('setLoading', true, { root: true });
        
        // Use the register endpoint to create a user
        const response = await axios.post('/api/auth/register', userData);
        const newUserId = response.data.userId;
        
        // Fetch the new user's details
        const userResponse = await axios.get(`/api/users/${newUserId}`);
        
        commit('ADD_USER', userResponse.data);
        dispatch('showToast', {
          message: 'User created successfully',
          type: 'success'
        }, { root: true });
        
        return userResponse.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to create user';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async updateUser({ commit, dispatch }, { userId, userData }) {
      try {
        dispatch('setLoading', true, { root: true });
        
        await axios.put(`/api/users/${userId}`, userData);
        
        // Fetch updated user data
        const response = await axios.get(`/api/users/${userId}`);
        
        commit('UPDATE_USER', response.data);
        dispatch('showToast', {
          message: 'User updated successfully',
          type: 'success'
        }, { root: true });
        
        return response.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to update user';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async deleteUser({ commit, dispatch }, userId) {
      try {
        dispatch('setLoading', true, { root: true });
        
        await axios.delete(`/api/users/${userId}`);
        
        commit('DELETE_USER', userId);
        dispatch('showToast', {
          message: 'User deleted successfully',
          type: 'success'
        }, { root: true });
        
        return true;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to delete user';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    // Fetch students assigned to an advisor
    async fetchAdviseesByAdvisor({ commit, dispatch }, advisorId) {
      try {
        dispatch('setLoading', true, { root: true });
        
        // This is a placeholder - you would need to add this endpoint to your backend
        const response = await axios.get(`/api/advisors/${advisorId}/advisees`);
        
        commit('SET_ADVISEES', response.data);
        return response.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to fetch advisees';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },

    // Fetch a specific advisee by ID
    async fetchAdviseeById({ dispatch }, studentId) {
      try {
        dispatch('setLoading', true, { root: true });
        
        // Use the advisor dashboard API to get advisee data
        const token = localStorage.getItem('token');
        const response = await axios.get(`http://localhost:8080/advisor-dashboard-api.php?action=advisees`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        // Find the specific student in the advisees list
        const advisee = response.data.advisees.find(student => student.id == studentId);
        if (!advisee) {
          throw new Error('Student not found or not under your advisory');
        }
        
        // Add default properties if missing
        advisee.advisorNotes = advisee.advisorNotes || [];
        advisee.totalCreditsRequired = advisee.totalCreditsRequired || 120;
        
        return advisee;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to fetch advisee data';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    }
  }
};
