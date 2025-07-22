// Notification API service for frontend
export default {
  async getUnreadCount(userId) {
    try {
      const response = await fetch(`http://localhost:8000/marks-api.php?action=unread_notifications&user_id=${userId}`, {
        credentials: 'include'
      });
      const data = await response.json();
      return data.unread_count || 0;
    } catch (error) {
      console.error('Error fetching unread notification count:', error);
      return 0;
    }
  },

  async getRecentNotifications(userId, limit = 10) {
    try {
      const response = await fetch(`http://localhost:8000/marks-api.php?action=recent_notifications&user_id=${userId}&limit=${limit}`, {
        credentials: 'include'
      });
      const data = await response.json();
      return data.notifications || [];
    } catch (error) {
      console.error('Error fetching recent notifications:', error);
      return [];
    }
  },

  async sendCourseAnnouncement(courseId, lecturerId, title, message, includeMarks = false) {
    try {
      const response = await fetch('http://localhost:8000/marks-api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'send_course_announcement',
          course_id: courseId,
          lecturer_id: lecturerId,
          title: title,
          message: message,
          include_marks: includeMarks
        })
      });
      
      const data = await response.json();
      return data;
    } catch (error) {
      console.error('Error sending course announcement:', error);
      throw error;
    }
  },

  async markAsRead(notificationId) {
    try {
      const response = await fetch('http://localhost:8000/marks-api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'mark_notification_read',
          notification_id: notificationId
        })
      });
      const data = await response.json();
      return data.success || false;
    } catch (error) {
      console.error('Error marking notification as read:', error);
      return false;
    }
  },

  formatNotificationTime(timestamp) {
    const now = new Date();
    const notificationTime = new Date(timestamp);
    const diffInMs = now - notificationTime;
    const diffInHours = diffInMs / (1000 * 60 * 60);
    const diffInDays = diffInMs / (1000 * 60 * 60 * 24);

    if (diffInHours < 1) {
      const diffInMinutes = diffInMs / (1000 * 60);
      return `${Math.floor(diffInMinutes)} minutes ago`;
    } else if (diffInHours < 24) {
      return `${Math.floor(diffInHours)} hours ago`;
    } else if (diffInDays < 7) {
      return `${Math.floor(diffInDays)} days ago`;
    } else {
      return notificationTime.toLocaleDateString();
    }
  },

  getNotificationIcon(type) {
    switch (type) {
      case 'mark':
        return 'fas fa-graduation-cap text-primary';
      case 'course':
        return 'fas fa-book text-info';
      case 'warning':
        return 'fas fa-exclamation-triangle text-warning';
      case 'system':
        return 'fas fa-cog text-secondary';
      default:
        return 'fas fa-bell text-primary';
    }
  },

  getNotificationStyle(type) {
    switch (type) {
      case 'mark':
        return 'border-left: 4px solid #007bff;';
      case 'course':
        return 'border-left: 4px solid #17a2b8;';
      case 'warning':
        return 'border-left: 4px solid #ffc107;';
      case 'system':
        return 'border-left: 4px solid #6c757d;';
      default:
        return 'border-left: 4px solid #007bff;';
    }
  }
};
