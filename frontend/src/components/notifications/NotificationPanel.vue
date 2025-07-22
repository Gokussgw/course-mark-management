<template>
  <div class="notification-panel">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">
        <i class="fas fa-bell me-2"></i>Notifications
        <span v-if="unreadCount > 0" class="badge bg-danger ms-2">{{ unreadCount }}</span>
      </h5>
      <button 
        class="btn btn-sm btn-outline-primary"
        @click="refreshNotifications"
        :disabled="loading"
      >
        <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
      </button>
    </div>
    
    <div v-if="loading && notifications.length === 0" class="text-center py-3">
      <div class="spinner-border spinner-border-sm text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
    
    <div v-else-if="notifications.length === 0" class="text-center py-4 text-muted">
      <i class="fas fa-bell-slash mb-2" style="font-size: 2rem;"></i>
      <p class="mb-0">No notifications yet</p>
    </div>
    
    <div v-else class="notification-list">
      <div 
        v-for="notification in notifications" 
        :key="notification.id"
        class="notification-item mb-3 p-3 rounded"
        :class="{ 'unread': !notification.is_read }"
        :style="getNotificationStyle(notification.type)"
        @click="handleNotificationClick(notification)"
      >
        <div class="d-flex align-items-start">
          <div class="notification-icon me-3">
            <i :class="getNotificationIcon(notification.type)"></i>
          </div>
          <div class="notification-content flex-grow-1">
            <div class="notification-text" v-html="formatNotificationContent(notification.content)"></div>
            <div class="notification-meta mt-2">
              <small class="text-muted">
                <i class="fas fa-clock me-1"></i>
                {{ formatTime(notification.created_at) }}
              </small>
              <span v-if="notification.sender_name" class="text-muted ms-3">
                <i class="fas fa-user me-1"></i>
                {{ notification.sender_name }}
              </span>
            </div>
          </div>
          <div class="notification-actions">
            <button 
              v-if="!notification.is_read"
              class="btn btn-sm btn-outline-primary"
              @click.stop="markAsRead(notification.id)"
              title="Mark as read"
            >
              <i class="fas fa-check"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <div v-if="notifications.length >= 10" class="text-center pt-2">
      <button class="btn btn-sm btn-link text-muted" @click="loadMore">
        View older notifications
      </button>
    </div>
  </div>
</template>

<script>
import NotificationService from '@/services/NotificationService.js';

export default {
  name: 'NotificationPanel',
  props: {
    userId: {
      type: [Number, String],
      required: true
    },
    autoRefresh: {
      type: Boolean,
      default: true
    },
    refreshInterval: {
      type: Number,
      default: 30000 // 30 seconds
    }
  },
  data() {
    return {
      notifications: [],
      unreadCount: 0,
      loading: false,
      refreshTimer: null
    };
  },
  async created() {
    await this.loadNotifications();
    
    if (this.autoRefresh) {
      this.startAutoRefresh();
    }
  },
  beforeUnmount() {
    if (this.refreshTimer) {
      clearInterval(this.refreshTimer);
    }
  },
  methods: {
    async loadNotifications() {
      this.loading = true;
      try {
        const [notifications, unreadCount] = await Promise.all([
          NotificationService.getRecentNotifications(this.userId),
          NotificationService.getUnreadCount(this.userId)
        ]);
        
        this.notifications = notifications;
        this.unreadCount = unreadCount;
      } catch (error) {
        console.error('Error loading notifications:', error);
      } finally {
        this.loading = false;
      }
    },
    
    async refreshNotifications() {
      await this.loadNotifications();
    },
    
    async markAsRead(notificationId) {
      try {
        const success = await NotificationService.markAsRead(notificationId);
        if (success) {
          // Update local state
          const notification = this.notifications.find(n => n.id === notificationId);
          if (notification) {
            notification.is_read = true;
            this.unreadCount = Math.max(0, this.unreadCount - 1);
          }
        }
      } catch (error) {
        console.error('Error marking notification as read:', error);
      }
    },
    
    handleNotificationClick(notification) {
      if (!notification.is_read) {
        this.markAsRead(notification.id);
      }
      
      // Emit event for parent component to handle navigation
      this.$emit('notification-clicked', notification);
    },
    
    startAutoRefresh() {
      this.refreshTimer = setInterval(() => {
        this.loadNotifications();
      }, this.refreshInterval);
    },
    
    loadMore() {
      // Implement pagination if needed
      this.$emit('load-more');
    },
    
    formatNotificationContent(content) {
      // Convert line breaks to HTML breaks and make it safe
      return content.replace(/\n/g, '<br>');
    },
    
    formatTime(timestamp) {
      return NotificationService.formatNotificationTime(timestamp);
    },
    
    getNotificationIcon(type) {
      return NotificationService.getNotificationIcon(type);
    },
    
    getNotificationStyle(type) {
      return NotificationService.getNotificationStyle(type);
    }
  }
};
</script>

<style scoped>
.notification-panel {
  max-height: 500px;
  overflow-y: auto;
}

.notification-list {
  max-height: 400px;
  overflow-y: auto;
}

.notification-item {
  background-color: #f8f9fa;
  border: 1px solid #e9ecef;
  cursor: pointer;
  transition: all 0.2s ease;
}

.notification-item:hover {
  background-color: #e9ecef;
  transform: translateX(2px);
}

.notification-item.unread {
  background-color: #fff3cd;
  border-color: #ffeaa7;
}

.notification-icon {
  width: 30px;
  text-align: center;
}

.notification-content {
  line-height: 1.4;
}

.notification-text {
  font-size: 0.9rem;
  color: #333;
}

.notification-meta {
  font-size: 0.8rem;
}

.notification-actions {
  opacity: 0;
  transition: opacity 0.2s ease;
}

.notification-item:hover .notification-actions {
  opacity: 1;
}

/* Custom scrollbar for notification list */
.notification-list::-webkit-scrollbar {
  width: 6px;
}

.notification-list::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.notification-list::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 3px;
}

.notification-list::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>
