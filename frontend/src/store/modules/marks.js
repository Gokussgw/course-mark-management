import axios from 'axios'

export default {
  namespaced: true,
  state: {
    marks: [],
    mark: null,
    studentSummary: null,
    courseStatistics: null
  },
  getters: {
    getAllMarks: state => state.marks,
    getMark: state => state.mark,
    getStudentSummary: state => state.studentSummary,
    getCourseStatistics: state => state.courseStatistics,
    
    getStudentCourseMarks: state => (studentId, courseId) => {
      return state.marks.filter(
        m => m.student_id === parseInt(studentId) && m.course_id === parseInt(courseId)
      );
    },
    
    getAssessmentMarks: state => assessmentId => {
      return state.marks.filter(m => m.assessment_id === parseInt(assessmentId));
    }
  },
  mutations: {
    SET_MARKS(state, marks) {
      state.marks = marks;
    },
    SET_MARK(state, mark) {
      state.mark = mark;
    },
    ADD_MARK(state, mark) {
      state.marks.push(mark);
    },
    UPDATE_MARK(state, updatedMark) {
      const index = state.marks.findIndex(m => m.id === updatedMark.id);
      if (index !== -1) {
        state.marks.splice(index, 1, updatedMark);
      }
      if (state.mark && state.mark.id === updatedMark.id) {
        state.mark = updatedMark;
      }
    },
    DELETE_MARK(state, markId) {
      state.marks = state.marks.filter(m => m.id !== markId);
      if (state.mark && state.mark.id === markId) {
        state.mark = null;
      }
    },
    SET_STUDENT_SUMMARY(state, summary) {
      state.studentSummary = summary;
    },
    SET_COURSE_STATISTICS(state, statistics) {
      state.courseStatistics = statistics;
    }
  },
  actions: {
    async fetchMarks({ commit, dispatch }, filter = {}) {
      try {
        dispatch('setLoading', true, { root: true });
        
        const params = {};
        if (filter.studentId) params.student_id = filter.studentId;
        if (filter.assessmentId) params.assessment_id = filter.assessmentId;
        if (filter.courseId) params.course_id = filter.courseId;
        
        const response = await axios.get('/api/marks', { params });
        commit('SET_MARKS', response.data);
        return response.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to fetch marks';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async fetchMark({ commit, dispatch }, markId) {
      try {
        dispatch('setLoading', true, { root: true });
        
        const response = await axios.get(`/api/marks/${markId}`);
        commit('SET_MARK', response.data);
        return response.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to fetch mark';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async createMark({ commit, dispatch }, markData) {
      try {
        dispatch('setLoading', true, { root: true });
        
        const response = await axios.post('/api/marks', markData);
        // Fetch the newly created mark
        const newMarkId = response.data.markId;
        const newMarkResponse = await axios.get(`/api/marks/${newMarkId}`);
        
        commit('ADD_MARK', newMarkResponse.data);
        dispatch('showToast', {
          message: 'Mark added successfully',
          type: 'success'
        }, { root: true });
        
        return newMarkResponse.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to add mark';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async updateMark({ commit, dispatch }, { markId, markData }) {
      try {
        dispatch('setLoading', true, { root: true });
        
        await axios.put(`/api/marks/${markId}`, markData);
        // Fetch the updated mark
        const response = await axios.get(`/api/marks/${markId}`);
        
        commit('UPDATE_MARK', response.data);
        dispatch('showToast', {
          message: 'Mark updated successfully',
          type: 'success'
        }, { root: true });
        
        return response.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to update mark';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async deleteMark({ commit, dispatch }, markId) {
      try {
        dispatch('setLoading', true, { root: true });
        
        await axios.delete(`/api/marks/${markId}`);
        
        commit('DELETE_MARK', markId);
        dispatch('showToast', {
          message: 'Mark deleted successfully',
          type: 'success'
        }, { root: true });
        
        return true;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to delete mark';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async fetchStudentCourseSummary({ commit, dispatch }, { studentId, courseId }) {
      try {
        dispatch('setLoading', true, { root: true });
        
        const response = await axios.get(`/api/marks/summary/student/${studentId}/course/${courseId}`);
        commit('SET_STUDENT_SUMMARY', response.data);
        return response.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to fetch student summary';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async fetchCourseStatistics({ commit, dispatch }, courseId) {
      try {
        dispatch('setLoading', true, { root: true });
        
        const response = await axios.get(`/api/marks/statistics/course/${courseId}`);
        commit('SET_COURSE_STATISTICS', response.data);
        return response.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to fetch course statistics';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    }
  }
};
