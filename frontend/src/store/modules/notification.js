export default {
  namespaced: true,
  
  state: {
    notifications: []
  },
  
  getters: {
    notifications: state => state.notifications
  },
  
  mutations: {
    ADD_NOTIFICATION(state, notification) {
      // Add unique id to the notification
      const id = new Date().getTime() + Math.floor(Math.random() * 1000);
      state.notifications.push({ ...notification, id });
    },
    
    REMOVE_NOTIFICATION(state, notificationId) {
      state.notifications = state.notifications.filter(notification => 
        notification.id !== notificationId
      );
    },
    
    CLEAR_NOTIFICATIONS(state) {
      state.notifications = [];
    }
  },
  
  actions: {
    add({ commit, dispatch }, notification) {
      // Default notification type is info if not provided
      const type = notification.type || 'info';
      const timeout = notification.timeout || 5000; // 5 seconds default
      
      // Add notification to state
      commit('ADD_NOTIFICATION', { 
        ...notification, 
        type 
      });
      
      // Auto-remove notification after timeout unless specified as persistent
      if (!notification.persistent) {
        setTimeout(() => {
          dispatch('remove', notification.id);
        }, timeout);
      }
    },
    
    remove({ commit }, notificationId) {
      commit('REMOVE_NOTIFICATION', notificationId);
    },
    
    clear({ commit }) {
      commit('CLEAR_NOTIFICATIONS');
    }
  }
};
