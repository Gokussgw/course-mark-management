import { createStore } from 'vuex'
import auth from './modules/auth'
import courses from './modules/courses'
import assessments from './modules/assessments'
import marks from './modules/marks'
import users from './modules/users'
import enrollments from './modules/enrollments'
import notification from './modules/notification'

export default createStore({
  state: {
    loading: false,
    error: null,
    toast: {
      show: false,
      message: '',
      type: 'success' // success, error, warning, info
    }
  },
  getters: {
    isLoading: state => state.loading,
    getError: state => state.error,
    getToast: state => state.toast
  },
  mutations: {
    SET_LOADING(state, loading) {
      state.loading = loading;
    },
    SET_ERROR(state, error) {
      state.error = error;
    },
    SHOW_TOAST(state, { message, type }) {
      state.toast.show = true;
      state.toast.message = message;
      state.toast.type = type || 'success';
    },
    HIDE_TOAST(state) {
      state.toast.show = false;
    }
  },
  actions: {
    setLoading({ commit }, loading) {
      commit('SET_LOADING', loading);
    },
    setError({ commit }, error) {
      commit('SET_ERROR', error);
    },
    showToast({ commit }, { message, type }) {
      commit('SHOW_TOAST', { message, type });
      // Auto-hide the toast after 3 seconds
      setTimeout(() => {
        commit('HIDE_TOAST');
      }, 3000);
    },
    hideToast({ commit }) {
      commit('HIDE_TOAST');
    }
  },
  modules: {
    auth,
    courses,
    assessments,
    marks,
    users,
    enrollments,
    notification
  }
});
