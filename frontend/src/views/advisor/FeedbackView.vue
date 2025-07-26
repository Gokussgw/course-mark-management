<template>
  <div class="advisor-feedback-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1><i class="fas fa-user-friends me-2"></i>Advisee Feedback Overview</h1>
      <div class="badge bg-info fs-6" v-if="feedbackList.length > 0">
        {{ feedbackList.length }} Feedback Records
      </div>
    </div>

    <!-- Advisee Selection -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-6">
            <label for="adviseeSelect" class="form-label fw-bold">Filter by Advisee:</label>
            <select 
              id="adviseeSelect" 
              class="form-select" 
              v-model="selectedAdviseeId" 
              @change="filterFeedback"
            >
              <option value="">-- All Advisees --</option>
              <option v-for="advisee in advisees" :key="advisee.id" :value="advisee.id">
                {{ advisee.name }} ({{ advisee.matric_number }})
              </option>
            </select>
          </div>
          <div class="col-md-6">
            <label for="feedbackTypeFilter" class="form-label fw-bold">Filter by Type:</label>
            <select 
              id="feedbackTypeFilter" 
              class="form-select" 
              v-model="selectedFeedbackType" 
              @change="filterFeedback"
            >
              <option value="">-- All Types --</option>
              <option value="general">General</option>
              <option value="performance">Performance</option>
              <option value="improvement">Improvement</option>
              <option value="commendation">Commendation</option>
              <option value="concern">Concern</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center p-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-3 text-muted">Loading advisee feedback...</p>
    </div>

    <!-- No Feedback State -->
    <div v-else-if="filteredFeedback.length === 0" class="text-center p-5">
      <i class="fas fa-comment-slash fa-4x text-muted mb-3"></i>
      <h3 class="text-muted">No Feedback Found</h3>
      <p class="text-muted">
        {{ selectedAdviseeId ? 'This advisee has no feedback yet.' : 'Your advisees haven\'t received any feedback yet.' }}
      </p>
    </div>

    <!-- Feedback List -->
    <div v-else>
      <!-- Summary Cards -->
      <div class="row mb-4">
        <div class="col-md-3">
          <div class="card text-center stat-card bg-primary text-white">
            <div class="card-body">
              <i class="fas fa-comments fa-2x mb-2"></i>
              <h4>{{ filteredFeedback.length }}</h4>
              <p class="mb-0">Total Feedback</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center stat-card bg-success text-white">
            <div class="card-body">
              <i class="fas fa-thumbs-up fa-2x mb-2"></i>
              <h4>{{ commendationCount }}</h4>
              <p class="mb-0">Commendations</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center stat-card bg-warning text-dark">
            <div class="card-body">
              <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
              <h4>{{ concernCount }}</h4>
              <p class="mb-0">Concerns</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center stat-card bg-info text-white">
            <div class="card-body">
              <i class="fas fa-chart-line fa-2x mb-2"></i>
              <h4>{{ improvementCount }}</h4>
              <p class="mb-0">Improvements</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Feedback Table -->
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Advisee Feedback Details
          </h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Student</th>
                  <th>Course</th>
                  <th>Lecturer</th>
                  <th>Type</th>
                  <th>Subject</th>
                  <th>Priority</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="feedback in filteredFeedback" :key="feedback.id">
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="avatar me-3">
                        {{ getStudentInitials(feedback.student_name) }}
                      </div>
                      <div>
                        <strong>{{ feedback.student_name }}</strong>
                        <br>
                        <small class="text-muted">{{ feedback.matric_number }}</small>
                      </div>
                    </div>
                  </td>
                  <td>
                    <strong>{{ feedback.course_code }}</strong>
                    <br>
                    <small class="text-muted">{{ feedback.course_name }}</small>
                  </td>
                  <td>{{ feedback.lecturer_name }}</td>
                  <td>
                    <span class="badge" :class="getFeedbackTypeBadgeClass(feedback.feedback_type)">
                      {{ formatFeedbackType(feedback.feedback_type) }}
                    </span>
                  </td>
                  <td>
                    <strong>{{ feedback.subject }}</strong>
                    <br>
                    <small class="text-muted">{{ truncateText(feedback.feedback, 50) }}</small>
                  </td>
                  <td>
                    <span class="badge" :class="getPriorityBadgeClass(feedback.priority)">
                      {{ feedback.priority.toUpperCase() }}
                    </span>
                  </td>
                  <td>{{ formatDate(feedback.created_at) }}</td>
                  <td>
                    <button 
                      class="btn btn-outline-info btn-sm" 
                      @click="viewFeedbackDetails(feedback)" 
                      title="View Details"
                    >
                      <i class="fas fa-eye"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
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
                <strong>Student:</strong> {{ selectedFeedback.student_name }} ({{ selectedFeedback.matric_number }})
              </div>
              <div class="col-md-6">
                <strong>Course:</strong> {{ selectedFeedback.course_code }} - {{ selectedFeedback.course_name }}
              </div>
            </div>
            
            <div class="row mb-3">
              <div class="col-md-6">
                <strong>Lecturer:</strong> {{ selectedFeedback.lecturer_name }}
              </div>
              <div class="col-md-6">
                <strong>Type:</strong> 
                <span class="badge ms-2" :class="getFeedbackTypeBadgeClass(selectedFeedback.feedback_type)">
                  {{ formatFeedbackType(selectedFeedback.feedback_type) }}
                </span>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-6">
                <strong>Priority:</strong> 
                <span class="badge ms-2" :class="getPriorityBadgeClass(selectedFeedback.priority)">
                  {{ selectedFeedback.priority.toUpperCase() }}
                </span>
              </div>
              <div class="col-md-6">
                <strong>Visibility:</strong>
                <div class="mt-1">
                  <span class="badge bg-success me-1" v-if="selectedFeedback.is_visible_to_student">
                    <i class="fas fa-eye me-1"></i>Student
                  </span>
                  <span class="badge bg-info" v-if="selectedFeedback.is_visible_to_advisor">
                    <i class="fas fa-eye me-1"></i>Advisor
                  </span>
                </div>
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
                  <strong>Created:</strong> {{ formatDate(selectedFeedback.created_at) }}
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
  name: 'AdvisorFeedbackView',
  data() {
    return {
      feedbackList: [],
      advisees: [],
      isLoading: true,
      selectedFeedback: null,
      selectedAdviseeId: '',
      selectedFeedbackType: ''
    };
  },
  computed: {
    ...mapGetters('auth', ['getUser']),
    filteredFeedback() {
      let filtered = this.feedbackList;
      
      if (this.selectedAdviseeId) {
        filtered = filtered.filter(f => f.student_id == this.selectedAdviseeId);
      }
      
      if (this.selectedFeedbackType) {
        filtered = filtered.filter(f => f.feedback_type === this.selectedFeedbackType);
      }
      
      return filtered;
    },
    commendationCount() {
      return this.filteredFeedback.filter(f => f.feedback_type === 'commendation').length;
    },
    improvementCount() {
      return this.filteredFeedback.filter(f => f.feedback_type === 'improvement').length;
    },
    concernCount() {
      return this.filteredFeedback.filter(f => f.feedback_type === 'concern').length;
    }
  },
  mounted() {
    this.loadAdviseesFeedback();
  },
  methods: {
    async loadAdviseesFeedback() {
      this.isLoading = true;
      try {
        let advisorUser = this.getUser;
        
        // If user is undefined, try to restore from localStorage
        if (!advisorUser && localStorage.getItem('token') && localStorage.getItem('user')) {
          try {
            await this.$store.dispatch('auth/checkAuth');
            advisorUser = this.getUser;
          } catch (e) {
            console.error('Error in checkAuth:', e);
          }
          
          // If still undefined, try parsing directly from localStorage
          if (!advisorUser) {
            try {
              advisorUser = JSON.parse(localStorage.getItem('user'));
            } catch (e) {
              console.error('Error parsing user from localStorage:', e);
            }
          }
        }
        
        const advisorId = advisorUser?.id;
        if (!advisorId) {
          this.$store.dispatch('showToast', {
            message: 'Please login first',
            type: 'warning'
          });
          return;
        }

        console.log('Loading feedback for advisor ID:', advisorId);

        // Load advisees first
        const adviseesResponse = await axios.get(`http://localhost:3000/advisor-dashboard-api.php?action=advisees`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        });
        console.log('Advisees response:', adviseesResponse.data);
        this.advisees = adviseesResponse.data.advisees || [];

        // Load feedback for all advisees
        const feedbackResponse = await axios.get(`http://localhost:3000/feedback-api.php?action=advisor_feedback&advisor_id=${advisorId}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        });
        console.log('Feedback response:', feedbackResponse.data);
        this.feedbackList = feedbackResponse.data.feedback || [];
      } catch (error) {
        console.error('Error loading advisees feedback:', error);
        this.$store.dispatch('showToast', {
          message: 'Error loading advisees feedback',
          type: 'error'
        });
      } finally {
        this.isLoading = false;
      }
    },

    filterFeedback() {
      // Computed property will handle filtering
    },

    viewFeedbackDetails(feedback) {
      this.selectedFeedback = feedback;
      const modal = new Modal(document.getElementById('feedbackDetailsModal'));
      modal.show();
    },

    getStudentInitials(name) {
      return name.split(' ').map(n => n.charAt(0)).join('').toUpperCase();
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
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    truncateText(text, length) {
      return text.length > length ? text.substring(0, length) + '...' : text;
    }
  }
};
</script>

<style scoped>
.advisor-feedback-view h1 {
  color: #2c3e50;
  font-weight: 700;
}

.stat-card {
  transition: transform 0.2s ease;
  border-radius: 10px;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.avatar {
  width: 40px;
  height: 40px;
  background: linear-gradient(45deg, #007bff, #0056b3);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 0.9rem;
}

.card {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border: none;
  border-radius: 10px;
}

.table th {
  font-weight: 600;
  border-top: none;
}

.table td {
  vertical-align: middle;
}

.feedback-content {
  background-color: #f8f9fa;
  line-height: 1.6;
  white-space: pre-wrap;
}

.badge {
  font-size: 0.75rem;
}
</style>
