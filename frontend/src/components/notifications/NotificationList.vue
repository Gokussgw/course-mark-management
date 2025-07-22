<template>
  <div class="notification-container">
    <transition-group name="notification-fade">
      <div 
        v-for="notification in notifications" 
        :key="notification.id"
        class="notification"
        :class="'notification-' + notification.type"
      >
        <div class="notification-content">
          <div class="notification-icon">
            <i :class="getIcon(notification.type)"></i>
          </div>
          <div class="notification-message">
            {{ notification.message }}
          </div>
          <button 
            class="notification-close"
            @click="close(notification.id)"
          >
            <i class="bi bi-x"></i>
          </button>
        </div>
      </div>
    </transition-group>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';

export default {
  name: 'NotificationList',
  
  computed: {
    ...mapGetters({
      notifications: 'notification/notifications'
    })
  },
  
  methods: {
    close(id) {
      this.$store.dispatch('notification/remove', id);
    },
    
    getIcon(type) {
      switch(type) {
        case 'success': 
          return 'bi bi-check-circle-fill';
        case 'danger':
        case 'error': 
          return 'bi bi-exclamation-circle-fill';
        case 'warning': 
          return 'bi bi-exclamation-triangle-fill';
        case 'info':
        default: 
          return 'bi bi-info-circle-fill';
      }
    }
  }
};
</script>

<style scoped>
.notification-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  width: 350px;
  max-width: 90vw;
}

.notification {
  margin-bottom: 10px;
  padding: 15px;
  border-radius: 4px;
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16);
  animation: slideIn 0.3s ease-out;
}

.notification-content {
  display: flex;
  align-items: center;
}

.notification-icon {
  margin-right: 15px;
  font-size: 1.2rem;
}

.notification-message {
  flex-grow: 1;
  font-size: 0.95rem;
}

.notification-close {
  background: transparent;
  border: none;
  color: inherit;
  opacity: 0.7;
  cursor: pointer;
  padding: 0;
  font-size: 1.2rem;
}

.notification-close:hover {
  opacity: 1;
}

/* Notification types */
.notification-success {
  background-color: #d4edda;
  border-left: 4px solid #28a745;
  color: #155724;
}

.notification-error, .notification-danger {
  background-color: #f8d7da;
  border-left: 4px solid #dc3545;
  color: #721c24;
}

.notification-warning {
  background-color: #fff3cd;
  border-left: 4px solid #ffc107;
  color: #856404;
}

.notification-info {
  background-color: #d1ecf1;
  border-left: 4px solid #17a2b8;
  color: #0c5460;
}

/* Animation */
@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.notification-fade-enter-active, 
.notification-fade-leave-active {
  transition: all 0.3s ease;
}

.notification-fade-enter-from,
.notification-fade-leave-to {
  transform: translateX(100%);
  opacity: 0;
}
</style>
