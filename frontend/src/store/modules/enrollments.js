import axios from 'axios';

const state = {
  enrollments: [],
  availableStudents: [],
  studentEnrollments: [],
  isLoading: false,
  error: null
};

const getters = {
  getEnrollments: (state) => state.enrollments,
  getAvailableStudents: (state) => state.availableStudents,
  getStudentEnrollments: (state) => state.studentEnrollments,
  isLoading: (state) => state.isLoading,
  getError: (state) => state.error
};

const mutations = {
  SET_LOADING(state, loading) {
    state.isLoading = loading;
  },
  SET_ERROR(state, error) {
    state.error = error;
  },
  SET_ENROLLMENTS(state, enrollments) {
    state.enrollments = enrollments;
  },
  SET_AVAILABLE_STUDENTS(state, students) {
    state.availableStudents = students;
  },
  SET_STUDENT_ENROLLMENTS(state, enrollments) {
    state.studentEnrollments = enrollments;
  },
  ADD_ENROLLMENT(state, enrollment) {
    state.enrollments.push(enrollment);
    // Remove student from available students if they were there
    state.availableStudents = state.availableStudents.filter(
      student => student.id !== enrollment.student_id
    );
  },
  REMOVE_ENROLLMENT(state, enrollmentId) {
    state.enrollments = state.enrollments.filter(
      enrollment => enrollment.enrollment_id !== enrollmentId
    );
  },
  CLEAR_ENROLLMENTS(state) {
    state.enrollments = [];
    state.availableStudents = [];
    state.studentEnrollments = [];
  }
};

const actions = {
  // Fetch enrollments for a specific course
  async fetchCourseEnrollments({ commit }, courseId) {
    commit('SET_LOADING', true);
    commit('SET_ERROR', null);
    
    try {
      const token = localStorage.getItem('token');
      const response = await axios.get(`/api/courses/${courseId}/enrollments`, {
        headers: { Authorization: `Bearer ${token}` }
      });
      
      if (response.data.success) {
        commit('SET_ENROLLMENTS', response.data.data);
      } else {
        commit('SET_ERROR', response.data.message);
      }
    } catch (error) {
      commit('SET_ERROR', error.response?.data?.message || 'Failed to fetch enrollments');
      throw error;
    } finally {
      commit('SET_LOADING', false);
    }
  },

  // Fetch available students for enrollment
  async fetchAvailableStudents({ commit }, courseId) {
    commit('SET_LOADING', true);
    commit('SET_ERROR', null);
    
    try {
      const token = localStorage.getItem('token');
      const response = await axios.get(`/api/courses/${courseId}/available-students`, {
        headers: { Authorization: `Bearer ${token}` }
      });
      
      if (response.data.success) {
        commit('SET_AVAILABLE_STUDENTS', response.data.data);
      } else {
        commit('SET_ERROR', response.data.message);
      }
    } catch (error) {
      commit('SET_ERROR', error.response?.data?.message || 'Failed to fetch available students');
      throw error;
    } finally {
      commit('SET_LOADING', false);
    }
  },

  // Enroll a single student
  async enrollStudent({ commit, dispatch }, { courseId, studentId, academicYear, semester }) {
    commit('SET_LOADING', true);
    commit('SET_ERROR', null);
    
    try {
      const token = localStorage.getItem('token');
      const response = await axios.post(`/api/courses/${courseId}/enroll`, {
        student_id: studentId,
        academic_year: academicYear,
        semester: semester
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });
      
      if (response.data.success) {
        // Refresh enrollments to get updated data
        await dispatch('fetchCourseEnrollments', courseId);
        await dispatch('fetchAvailableStudents', courseId);
        return response.data;
      } else {
        commit('SET_ERROR', response.data.message);
        throw new Error(response.data.message);
      }
    } catch (error) {
      const errorMessage = error.response?.data?.message || 'Failed to enroll student';
      commit('SET_ERROR', errorMessage);
      throw new Error(errorMessage);
    } finally {
      commit('SET_LOADING', false);
    }
  },

  // Bulk enroll students
  async bulkEnrollStudents({ commit, dispatch }, { courseId, studentIds, academicYear, semester }) {
    commit('SET_LOADING', true);
    commit('SET_ERROR', null);
    
    try {
      const token = localStorage.getItem('token');
      const response = await axios.post(`/api/courses/${courseId}/bulk-enroll`, {
        student_ids: studentIds,
        academic_year: academicYear,
        semester: semester
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });
      
      if (response.data.success) {
        // Refresh enrollments to get updated data
        await dispatch('fetchCourseEnrollments', courseId);
        await dispatch('fetchAvailableStudents', courseId);
        return response.data;
      } else {
        commit('SET_ERROR', response.data.message);
        throw new Error(response.data.message);
      }
    } catch (error) {
      const errorMessage = error.response?.data?.message || 'Failed to bulk enroll students';
      commit('SET_ERROR', errorMessage);
      throw new Error(errorMessage);
    } finally {
      commit('SET_LOADING', false);
    }
  },

  // Remove student enrollment
  async removeEnrollment({ commit, dispatch }, { enrollmentId, courseId }) {
    commit('SET_LOADING', true);
    commit('SET_ERROR', null);
    
    try {
      const token = localStorage.getItem('token');
      const response = await axios.delete(`/api/enrollments/${enrollmentId}`, {
        headers: { Authorization: `Bearer ${token}` }
      });
      
      if (response.data.success) {
        commit('REMOVE_ENROLLMENT', enrollmentId);
        // Refresh available students
        await dispatch('fetchAvailableStudents', courseId);
        return response.data;
      } else {
        commit('SET_ERROR', response.data.message);
        throw new Error(response.data.message);
      }
    } catch (error) {
      const errorMessage = error.response?.data?.message || 'Failed to remove enrollment';
      commit('SET_ERROR', errorMessage);
      throw new Error(errorMessage);
    } finally {
      commit('SET_LOADING', false);
    }
  },

  // Fetch student's enrollment history
  async fetchStudentEnrollments({ commit }, studentId) {
    commit('SET_LOADING', true);
    commit('SET_ERROR', null);
    
    try {
      const token = localStorage.getItem('token');
      const response = await axios.get(`/api/students/${studentId}/enrollments`, {
        headers: { Authorization: `Bearer ${token}` }
      });
      
      if (response.data.success) {
        commit('SET_STUDENT_ENROLLMENTS', response.data.data);
      } else {
        commit('SET_ERROR', response.data.message);
      }
    } catch (error) {
      commit('SET_ERROR', error.response?.data?.message || 'Failed to fetch student enrollments');
      throw error;
    } finally {
      commit('SET_LOADING', false);
    }
  },

  // Clear all enrollment data
  clearEnrollments({ commit }) {
    commit('CLEAR_ENROLLMENTS');
  }
};

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
};
