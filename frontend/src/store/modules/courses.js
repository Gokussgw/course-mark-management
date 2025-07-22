import axios from 'axios'

export default {
  namespaced: true,
  state: {
    courses: [],
    course: null
  },
  getters: {
    getAllCourses: state => state.courses,
    getCourses: state => state.courses,
    getCourse: state => state.course
  },
  mutations: {
    SET_COURSES(state, courses) {
      state.courses = courses;
    },
    SET_COURSE(state, course) {
      state.course = course;
    },
    ADD_COURSE(state, course) {
      state.courses.push(course);
    },
    UPDATE_COURSE(state, updatedCourse) {
      const index = state.courses.findIndex(c => c.id === updatedCourse.id);
      if (index !== -1) {
        state.courses.splice(index, 1, updatedCourse);
      }
      if (state.course && state.course.id === updatedCourse.id) {
        state.course = updatedCourse;
      }
    },
    DELETE_COURSE(state, courseId) {
      state.courses = state.courses.filter(c => c.id !== courseId);
      if (state.course && state.course.id === courseId) {
        state.course = null;
      }
    }
  },
  actions: {
    async fetchCourses({ commit, dispatch }, filter = {}) {
      try {
        dispatch('setLoading', true, { root: true });
        
        let url = '/api/courses';
        const params = {};
        
        if (filter.lecturerId) {
          params.lecturer_id = filter.lecturerId;
        }
        
        const response = await axios.get(url, { params });
        commit('SET_COURSES', response.data);
        return response.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to fetch courses';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async fetchCourse({ commit, dispatch }, courseId) {
      try {
        dispatch('setLoading', true, { root: true });
        
        const response = await axios.get(`/api/courses/${courseId}`);
        commit('SET_COURSE', response.data);
        return response.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to fetch course';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async createCourse({ commit, dispatch }, courseData) {
      try {
        dispatch('setLoading', true, { root: true });
        
        const response = await axios.post('/api/courses', courseData);
        // Fetch the newly created course with complete data
        const newCourseId = response.data.courseId;
        const newCourseResponse = await axios.get(`/api/courses/${newCourseId}`);
        
        commit('ADD_COURSE', newCourseResponse.data);
        dispatch('showToast', {
          message: 'Course created successfully',
          type: 'success'
        }, { root: true });
        
        return newCourseResponse.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to create course';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async updateCourse({ commit, dispatch }, { courseId, courseData }) {
      try {
        dispatch('setLoading', true, { root: true });
        
        await axios.put(`/api/courses/${courseId}`, courseData);
        // Fetch the updated course
        const response = await axios.get(`/api/courses/${courseId}`);
        
        commit('UPDATE_COURSE', response.data);
        dispatch('showToast', {
          message: 'Course updated successfully',
          type: 'success'
        }, { root: true });
        
        return response.data;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to update course';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    },
    
    async deleteCourse({ commit, dispatch }, courseId) {
      try {
        dispatch('setLoading', true, { root: true });
        
        await axios.delete(`/api/courses/${courseId}`);
        
        commit('DELETE_COURSE', courseId);
        dispatch('showToast', {
          message: 'Course deleted successfully',
          type: 'success'
        }, { root: true });
        
        return true;
      } catch (error) {
        const errorMsg = error.response?.data?.error || 'Failed to delete course';
        dispatch('setError', errorMsg, { root: true });
        throw new Error(errorMsg);
      } finally {
        dispatch('setLoading', false, { root: true });
      }
    }
  }
};
