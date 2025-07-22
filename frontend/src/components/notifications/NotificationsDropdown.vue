<template>
  <div class="notifications-dropdown">
    <div class="dropdown">
      <button class="btn btn-link nav-link position-relative" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-bell"></i>
        <span v-if="unreadCount > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
          {{ unreadCount > 99 ? '99+' : unreadCount }}
          <span class="visually-hidden">unread notifications</span>
        </span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end notifications-list" aria-labelledby="notificationsDropdown">
        <li class="dropdown-header d-flex justify-content-between align-items-center">
          <span>Notifications</span>
          <button 
            v-if="unreadCount > 0" 
            class="btn btn-sm btn-link p-0 text-decoration-none" 
            @click="markAllAsRead"
          >
            Mark all as read
          </button>
        </li>
        <li v-if="loading" class="text-center py-3">
          <div class="spinner-border spinner-border-sm text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </li>
        <li v-else-if="notifications.length === 0" class="dropdown-item-text text-center text-muted py-3">
          No new notifications
        </li>
        <template v-else>
          <li v-for="notification in notifications" :key="notification.id">
            <a 
              class="dropdown-item notification-item" 
              :class="{ 'unread': !notification.is_read }"
              href="#"
              @click.prevent="handleNotificationClick(notification)"
            >
              <div class="d-flex">
                <div class="notification-icon me-2">
                  <i :class="getNotificationIcon(notification.type)"></i>
                </div>
                <div class="notification-content">
                  <div class="notification-message">{{ notification.content }}</div>
                  <div class="notification-time">{{ formatDate(notification.created_at) }}</div>
                </div>
              </div>
            </a>
          </li>
        </template>
        <li><hr class="dropdown-divider"></li>
        <li class="text-center">
          <router-link class="dropdown-item text-primary" to="/notifications">
            View all notifications
          </router-link>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'NotificationsDropdown',
  data() {
    return {
      notifications: [],
      loading: false,
      refreshInterval: null,
      notificationModal: null
    };
  },
  computed: {
    unreadCount() {
      return this.notifications.filter(notification => !notification.is_read).length;
    }
  },
  mounted() {
    this.fetchNotifications();
    
    // Refresh notifications every 60 seconds
    this.refreshInterval = setInterval(() => {
      this.fetchNotifications();
    }, 60000);
  },
  beforeUnmount() {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
    }
  },
  methods: {
    async fetchNotifications() {
      this.loading = true;
      try {
        const response = await axios.get(`${process.env.VUE_APP_API_URL}/api/notifications`, {
          headers: {
            Authorization: `Bearer ${this.$store.getters['auth/token']}`
          }
        });
        this.notifications = response.data;
      } catch (error) {
        console.error('Failed to fetch notifications:', error);
      } finally {
        this.loading = false;
      }
    },
    async markAsRead(notificationId) {
      try {
        await axios.put(`${process.env.VUE_APP_API_URL}/api/notifications/${notificationId}`, {}, {
          headers: {
            Authorization: `Bearer ${this.$store.getters['auth/token']}`
          }
        });
        
        // Update the notification in the local state
        const notification = this.notifications.find(n => n.id === notificationId);
        if (notification) {
          notification.is_read = 1;
        }
      } catch (error) {
        console.error('Failed to mark notification as read:', error);
      }
    },
    async markAllAsRead() {
      try {
        await axios.put(`${process.env.VUE_APP_API_URL}/api/notifications/read/all`, {}, {
          headers: {
            Authorization: `Bearer ${this.$store.getters['auth/token']}`
          }
        });
        
        // Update all notifications in the local state
        this.notifications.forEach(notification => {
          notification.is_read = 1;
        });
      } catch (error) {
        console.error('Failed to mark all notifications as read:', error);
      }
    },
    handleNotificationClick(notification) {
      // Mark as read
      if (!notification.is_read) {
        this.markAsRead(notification.id);
      }
      
      // Handle different notification types
      switch(notification.type) {
        case 'assessment':
          // Navigate to assessment details
          if (notification.related_id) {
            this.$router.push(`/assessment/${notification.related_id}`);
          }
          break;
        case 'mark':
          // Navigate to mark details
          if (notification.related_id) {
            this.$router.push(`/mark/${notification.related_id}`);
          }
          break;
        case 'course':
          // Navigate to course details
          if (notification.related_id) {
            this.$router.push(`/course/${notification.related_id}`);
          }
          break;
        default:
          // Show modal for general notifications
          this.$store.dispatch('notification/add', {
            type: 'info',
            message: notification.content
          });
      }
    },
    getNotificationIcon(type) {
      switch(type) {
        case 'assessment':
          return 'bi bi-clipboard-check';
        case 'mark':
          return 'bi bi-percent';
        case 'course':
          return 'bi bi-book';
        case 'warning':
          return 'bi bi-exclamation-triangle';
        case 'system':
          return 'bi bi-gear';
        default:
          return 'bi bi-envelope';
      }
    },
    formatDate(dateString) {
      if (!dateString) return '';
      
      const date = new Date(dateString);
      const now = new Date();
      const diffMs = now - date;
      const diffSec = Math.round(diffMs / 1000);
      const diffMin = Math.round(diffSec / 60);
      const diffHour = Math.round(diffMin / 60);
      const diffDay = Math.round(diffHour / 24);
      
      if (diffSec < 60) {
        return 'Just now';
      } else if (diffMin < 60) {
        return `${diffMin}m ago`;
      } else if (diffHour < 24) {
        return `${diffHour}h ago`;
      } else if (diffDay < 7) {
        return `${diffDay}d ago`;
      } else {
        return new Intl.DateTimeFormat('en-GB', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        }).format(date);
      }
    }
  }
};
</script>

<style scoped>
.notifications-dropdown .dropdown-menu {
  width: 320px;
  max-height: 500px;
  overflow-y: auto;
  padding: 0;
}

.notifications-list {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.dropdown-header {
  background-color: #f8f9fa;
  padding: 0.75rem 1rem;
  font-weight: 600;
  border-bottom: 1px solid #dee2e6;
}

.notification-item {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  white-space: normal;
}

.notification-item:hover {
  background-color: #f8f9fa;
}

.notification-item.unread {
  background-color: rgba(13, 110, 253, 0.05);
}

.notification-icon {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background-color: #e9ecef;
  color: #495057;
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-message {
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
  white-space: normal;
  word-break: break-word;
}

.notification-time {
  font-size: 0.75rem;
  color: #6c757d;
}

.dropdown-item-text {
  padding: 0.5rem 1rem;
}
</style>
