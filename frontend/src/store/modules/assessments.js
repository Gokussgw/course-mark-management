import axios from 'axios'

export default {
  namespaced: true,
  state: {
    assessments: [],
    assessment: null
  },
  getters: {
    getAllAssessments: state => state.assessments,
    getAssessment: state => state.assessment,
    getCourseAssessments: state => courseId => {
      return state.assessments.filter(a => a.course_id === parseInt(courseId));
    }
  },
  mutations: {
    SET_ASSESSMENTS(state, assessments) {
      state.assessments = assessments;
    },
    SET_ASSESSMENT(state, assessment) {
      state.assessment = assessment;
    },
    ADD_ASSESSMENT(state, assessment) {
      state.assessments.push(assessment);
    },
    UPDATE_ASSESSMENT(state, updatedAssessment) {
      const index = state.assessments.findIndex(a => a.id === updatedAssessment.id);
      if (index !== -1) {
        state.assessments.splice(index, 1, updatedAssessment);
      }
      if (state.assessment && state.assessment.id === updatedAssessment.id) {
        state.assessment = updatedAssessment;
      }
    },
    DELETE_ASSESSMENT(state, assessmentId) {
      state.assessments = state.assessments.filter(a => a.id !== assessmentId);
      if (state.assessment && state.assessment.id === assessmentId) {
        state.assessment = null;
      }
    }
  },
  actions: {
    async fetchAssessments({ commit, dispatch }, filter = {}) {
      try {
        dispatch('setLoading', true, { root: true });
        
        let url = '/api/assessments';
        const params = {};
        
        if (filter.courseId) {
          params.course_id = filter.courseId;
        }
        
        const response = await axios.get(url, { params });
        commit('SET_ASSESSMENTS', response.data);
        return response.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to fetch assessments';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async fetchAssessment({ commit, dispatch }, assessmentId) {
      try {
        dispatch('setLoading', true, { root: true });
        
        const response = await axios.get(`/api/assessments/${assessmentId}`);
        commit('SET_ASSESSMENT', response.data);
        return response.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to fetch assessment';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async createAssessment({ commit, dispatch }, assessmentData) {
      try {
        dispatch('setLoading', true, { root: true });
        
        const response = await axios.post('/api/assessments', assessmentData);
        // Fetch the newly created assessment
        const newAssessmentId = response.data.assessmentId;
        const newAssessmentResponse = await axios.get(`/api/assessments/${newAssessmentId}`);
        
        commit('ADD_ASSESSMENT', newAssessmentResponse.data);
        dispatch('showToast', {
          message: 'Assessment created successfully',
          type: 'success'
        }, { root: true });
        
        return newAssessmentResponse.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to create assessment';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async updateAssessment({ commit, dispatch }, { assessmentId, assessmentData }) {
      try {
        dispatch('setLoading', true, { root: true });
        
        await axios.put(`/api/assessments/${assessmentId}`, assessmentData);
        // Fetch the updated assessment
        const response = await axios.get(`/api/assessments/${assessmentId}`);
        
        commit('UPDATE_ASSESSMENT', response.data);
        dispatch('showToast', {
          message: 'Assessment updated successfully',
          type: 'success'
        }, { root: true });
        
        return response.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to update assessment';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async deleteAssessment({ commit, dispatch }, assessmentId) {
      try {
        dispatch('setLoading', true, { root: true });
        
        await axios.delete(`/api/assessments/${assessmentId}`);
        
        commit('DELETE_ASSESSMENT', assessmentId);
        dispatch('showToast', {
          message: 'Assessment deleted successfully',
          type: 'success'
        }, { root: true });
        
        return true;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to delete assessment';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    }
  }
};
