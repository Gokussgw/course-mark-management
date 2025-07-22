<template>
  <div class="student-feedback-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1><i class="fas fa-comments me-2"></i>My Feedback</h1>
      <div class="badge bg-info fs-6" v-if="feedbackList.length > 0">
        {{ feedbackList.length }} Feedback Records
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center p-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-3 text-muted">Loading your feedback...</p>
    </div>

    <!-- No Feedback State -->
    <div v-else-if="feedbackList.length === 0" class="text-center p-5">
      <i class="fas fa-comment-slash fa-4x text-muted mb-3"></i>
      <h3 class="text-muted">No Feedback Yet</h3>
      <p class="text-muted">Your lecturers haven't provided any feedback yet. Check back later!</p>
    </div>

    <!-- Feedback List -->
    <div v-else class="row">
      <div v-for="feedback in feedbackList" :key="feedback.id" class="col-md-6 col-lg-4 mb-4">
        <div class="card feedback-card h-100" :class="getFeedbackCardClass(feedback.feedback_type)">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <span class="badge" :class="getFeedbackTypeBadgeClass(feedback.feedback_type)">
                {{ formatFeedbackType(feedback.feedback_type) }}
              </span>
              <span class="badge ms-2" :class="getPriorityBadgeClass(feedback.priority)">
                {{ feedback.priority.toUpperCase() }}
              </span>
            </div>
            <small class="text-muted">{{ formatDate(feedback.created_at) }}</small>
          </div>
          <div class="card-body">
            <h6 class="card-title">{{ feedback.subject }}</h6>
            <p class="card-text">{{ feedback.feedback }}</p>
            <div class="mt-3">
              <small class="text-muted">
                <strong>Course:</strong> {{ feedback.course_code }} - {{ feedback.course_name }}
              </small>
              <br>
              <small class="text-muted">
                <strong>From:</strong> {{ feedback.lecturer_name }}
              </small>
            </div>
          </div>
          <div class="card-footer">
            <button 
              class="btn btn-outline-primary btn-sm" 
              @click="viewFeedbackDetails(feedback)"
            >
              <i class="fas fa-eye me-1"></i>View Details
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Feedback Stats -->
    <div v-if="feedbackList.length > 0" class="row mt-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Feedback Summary</h5>
          </div>
          <div class="card-body">
            <div class="row text-center">
              <div class="col-md-3">
                <div class="stat-item">
                  <h4 class="text-primary">{{ totalFeedback }}</h4>
                  <p class="text-muted mb-0">Total Feedback</p>
                </div>
              </div>
              <div class="col-md-3">
                <div class="stat-item">
                  <h4 class="text-success">{{ commendationCount }}</h4>
                  <p class="text-muted mb-0">Commendations</p>
                </div>
              </div>
              <div class="col-md-3">
                <div class="stat-item">
                  <h4 class="text-warning">{{ improvementCount }}</h4>
                  <p class="text-muted mb-0">Improvements</p>
                </div>
              </div>
              <div class="col-md-3">
                <div class="stat-item">
                  <h4 class="text-danger">{{ concernCount }}</h4>
                  <p class="text-muted mb-0">Concerns</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Feedback Details Modal -->
    <div class="modal fade" id="feedbackDetailsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-comment me-2"></i>Feedback Details
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" v-if="selectedFeedback">
            <div class="row mb-3">
              <div class="col-md-6">
                <strong>Course:</strong> {{ selectedFeedback.course_code }} - {{ selectedFeedback.course_name }}
              </div>
              <div class="col-md-6">
                <strong>Lecturer:</strong> {{ selectedFeedback.lecturer_name }}
              </div>
            </div>
            
            <div class="row mb-3">
              <div class="col-md-6">
                <strong>Type:</strong> 
                <span class="badge ms-2" :class="getFeedbackTypeBadgeClass(selectedFeedback.feedback_type)">
                  {{ formatFeedbackType(selectedFeedback.feedback_type) }}
                </span>
              </div>
              <div class="col-md-6">
                <strong>Priority:</strong> 
                <span class="badge ms-2" :class="getPriorityBadgeClass(selectedFeedback.priority)">
                  {{ selectedFeedback.priority.toUpperCase() }}
                </span>
              </div>
            </div>

            <div class="mb-3">
              <strong>Subject:</strong>
              <p class="mt-2 p-3 bg-light rounded">{{ selectedFeedback.subject }}</p>
            </div>

            <div class="mb-3">
              <strong>Feedback:</strong>
              <div class="mt-2 p-3 border rounded feedback-content">
                {{ selectedFeedback.feedback }}
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <small class="text-muted">
                  <strong>Received:</strong> {{ formatDate(selectedFeedback.created_at) }}
                </small>
              </div>
              <div class="col-md-6">
                <small class="text-muted">
                  <strong>Last Updated:</strong> {{ formatDate(selectedFeedback.updated_at) }}
                </small>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { Modal } from 'bootstrap';
import { mapGetters } from 'vuex';

export default {
  name: 'StudentFeedbackView',
  data() {
    return {
      feedbackList: [],
      isLoading: true,
      selectedFeedback: null
    };
  },
  computed: {
    ...mapGetters('auth', ['getUser']),
    totalFeedback() {
      return this.feedbackList.length;
    },
    commendationCount() {
      return this.feedbackList.filter(f => f.feedback_type === 'commendation').length;
    },
    improvementCount() {
      return this.feedbackList.filter(f => f.feedback_type === 'improvement').length;
    },
    concernCount() {
      return this.feedbackList.filter(f => f.feedback_type === 'concern').length;
    }
  },
  mounted() {
    this.loadStudentFeedback();
  },
  methods: {
    async loadStudentFeedback() {
      this.isLoading = true;
      try {
        const studentId = this.getUser?.id;
        if (!studentId) {
          this.$store.dispatch('showToast', {
            message: 'Please login first',
            type: 'warning'
          });
          return;
        }

        const response = await axios.get(`http://localhost:8080/feedback-api.php?action=student_feedback&student_id=${studentId}`);
        this.feedbackList = response.data.feedback || [];
      } catch (error) {
        console.error('Error loading feedback:', error);
        this.$store.dispatch('showToast', {
          message: 'Error loading your feedback',
          type: 'error'
        });
      } finally {
        this.isLoading = false;
      }
    },

    viewFeedbackDetails(feedback) {
      this.selectedFeedback = feedback;
      const modal = new Modal(document.getElementById('feedbackDetailsModal'));
      modal.show();
    },

    getFeedbackCardClass(type) {
      const classes = {
        'commendation': 'border-success',
        'improvement': 'border-warning',
        'concern': 'border-danger',
        'performance': 'border-info',
        'general': 'border-secondary'
      };
      return classes[type] || 'border-secondary';
    },

    getFeedbackTypeBadgeClass(type) {
      const classes = {
        'general': 'bg-secondary',
        'performance': 'bg-success',
        'improvement': 'bg-warning text-dark',
        'commendation': 'bg-info',
        'concern': 'bg-danger'
      };
      return classes[type] || 'bg-secondary';
    },

    getPriorityBadgeClass(priority) {
      const classes = {
        'low': 'bg-light text-dark',
        'medium': 'bg-info',
        'high': 'bg-warning text-dark',
        'urgent': 'bg-danger'
      };
      return classes[priority] || 'bg-info';
    },

    formatFeedbackType(type) {
      return type.charAt(0).toUpperCase() + type.slice(1);
    },

    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
  }
};
</script>

<style scoped>
.student-feedback-view h1 {
  color: #2c3e50;
  font-weight: 700;
}

.feedback-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.feedback-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.feedback-card.border-success {
  border-left: 4px solid #28a745 !important;
}

.feedback-card.border-warning {
  border-left: 4px solid #ffc107 !important;
}

.feedback-card.border-danger {
  border-left: 4px solid #dc3545 !important;
}

.feedback-card.border-info {
  border-left: 4px solid #17a2b8 !important;
}

.feedback-card.border-secondary {
  border-left: 4px solid #6c757d !important;
}

.stat-item {
  padding: 1rem;
  border-radius: 8px;
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
}

.stat-item h4 {
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.feedback-content {
  background-color: #f8f9fa;
  line-height: 1.6;
  white-space: pre-wrap;
}

.badge {
  font-size: 0.75rem;
}

.card {
  border-radius: 10px;
}

.card-header {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  border-bottom: 1px solid #dee2e6;
}
</style>
