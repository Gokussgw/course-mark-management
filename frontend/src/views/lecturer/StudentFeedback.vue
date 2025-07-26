<template>
  <div class="student-feedback">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1><i class="fas fa-comments me-2"></i>Student Feedback Management</h1>
      <button class="btn btn-primary" @click="showAddFeedbackModal">
        <i class="fas fa-plus me-2"></i>Add Feedback
      </button>
    </div>

    <!-- Course Selection -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-6">
            <label for="courseSelect" class="form-label fw-bold">Select Course:</label>
            <select 
              id="courseSelect" 
              class="form-select" 
              v-model="selectedCourseId" 
              @change="onCourseChange"
            >
              <option value="">-- Select a Course --</option>
              <option v-for="course in courses" :key="course.id" :value="course.id">
                {{ course.code }} - {{ course.name }}
              </option>
            </select>
          </div>
          <div class="col-md-6 text-end">
            <div class="badge bg-info fs-6" v-if="feedbackList.length > 0">
              {{ feedbackList.length }} Feedback Records
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Feedback List -->
    <div class="card" v-if="selectedCourseId">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="fas fa-list me-2"></i>Student Feedback
        </h5>
      </div>
      <div class="card-body p-0">
        <div v-if="isLoading" class="text-center p-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
        
        <div v-else-if="feedbackList.length === 0" class="text-center p-4 text-muted">
          <i class="fas fa-comment-slash fa-3x mb-3"></i>
          <p>No feedback records found for this course.</p>
          <button class="btn btn-primary" @click="showAddFeedbackModal">
            Add First Feedback
          </button>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Student</th>
                <th>Type</th>
                <th>Subject</th>
                <th>Priority</th>
                <th>Visibility</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="feedback in feedbackList" :key="feedback.id">
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
                <td>
                  <div class="d-flex flex-column">
                    <small :class="feedback.is_visible_to_student ? 'text-success' : 'text-muted'">
                      <i :class="feedback.is_visible_to_student ? 'fas fa-eye' : 'fas fa-eye-slash'"></i>
                      Student
                    </small>
                    <small :class="feedback.is_visible_to_advisor ? 'text-success' : 'text-muted'">
                      <i :class="feedback.is_visible_to_advisor ? 'fas fa-eye' : 'fas fa-eye-slash'"></i>
                      Advisor
                    </small>
                  </div>
                </td>
                <td>{{ formatDate(feedback.created_at) }}</td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button 
                      class="btn btn-outline-info" 
                      @click="viewFeedback(feedback)" 
                      title="View Details"
                    >
                      <i class="fas fa-eye"></i>
                    </button>
                    <button 
                      class="btn btn-outline-warning" 
                      @click="editFeedback(feedback)" 
                      title="Edit"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button 
                      class="btn btn-outline-danger" 
                      @click="deleteFeedback(feedback)" 
                      title="Delete"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add/Edit Feedback Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-comment-dots me-2"></i>
              {{ isEditMode ? 'Edit' : 'Add' }} Student Feedback
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveFeedback">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Student *</label>
                    <select class="form-select" v-model="feedbackForm.student_id" required>
                      <option value="">-- Select Student --</option>
                      <option v-for="student in courseStudents" :key="student.id" :value="student.id">
                        {{ student.name }} ({{ student.matric_number }})
                      </option>
                    </select>
                    <small class="text-muted" v-if="courseStudents.length === 0">
                      No students enrolled in this course
                    </small>
                    <small class="text-muted" v-else>
                      {{ courseStudents.length }} students available
                    </small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Feedback Type *</label>
                    <select class="form-select" v-model="feedbackForm.feedback_type" required>
                      <option value="general">General</option>
                      <option value="performance">Performance</option>
                      <option value="improvement">Improvement</option>
                      <option value="commendation">Commendation</option>
                      <option value="concern">Concern</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-8">
                  <div class="mb-3">
                    <label class="form-label">Subject *</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="feedbackForm.subject" 
                      placeholder="Brief subject line"
                      required
                    >
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="mb-3">
                    <label class="form-label">Priority</label>
                    <select class="form-select" v-model="feedbackForm.priority">
                      <option value="low">Low</option>
                      <option value="medium">Medium</option>
                      <option value="high">High</option>
                      <option value="urgent">Urgent</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Feedback Content *</label>
                <textarea 
                  class="form-control" 
                  v-model="feedbackForm.feedback" 
                  rows="5" 
                  placeholder="Detailed feedback for the student..."
                  required
                ></textarea>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      v-model="feedbackForm.is_visible_to_student" 
                      id="visibleToStudent"
                    >
                    <label class="form-check-label" for="visibleToStudent">
                      Visible to Student
                    </label>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      v-model="feedbackForm.is_visible_to_advisor" 
                      id="visibleToAdvisor"
                    >
                    <label class="form-check-label" for="visibleToAdvisor">
                      Visible to Advisor
                    </label>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="saveFeedback" :disabled="isSaving">
              <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span>
              {{ isEditMode ? 'Update' : 'Add' }} Feedback
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- View Feedback Modal -->
    <div class="modal fade" id="viewFeedbackModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-comment me-2"></i>Feedback Details
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" v-if="selectedFeedback">
            <div class="row">
              <div class="col-md-6">
                <strong>Student:</strong> {{ selectedFeedback.student_name }}
              </div>
              <div class="col-md-6">
                <strong>Course:</strong> {{ selectedFeedback.course_code }}
              </div>
            </div>
            <hr>
            <div class="row">
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
            <hr>
            <div class="mb-3">
              <strong>Subject:</strong>
              <p class="mt-2">{{ selectedFeedback.subject }}</p>
            </div>
            <div class="mb-3">
              <strong>Feedback:</strong>
              <p class="mt-2">{{ selectedFeedback.feedback }}</p>
            </div>
            <div class="row">
              <div class="col-md-6">
                <strong>Visible to Student:</strong> 
                <span :class="selectedFeedback.is_visible_to_student ? 'text-success' : 'text-muted'">
                  <i :class="selectedFeedback.is_visible_to_student ? 'fas fa-check' : 'fas fa-times'"></i>
                  {{ selectedFeedback.is_visible_to_student ? 'Yes' : 'No' }}
                </span>
              </div>
              <div class="col-md-6">
                <strong>Visible to Advisor:</strong> 
                <span :class="selectedFeedback.is_visible_to_advisor ? 'text-success' : 'text-muted'">
                  <i :class="selectedFeedback.is_visible_to_advisor ? 'fas fa-check' : 'fas fa-times'"></i>
                  {{ selectedFeedback.is_visible_to_advisor ? 'Yes' : 'No' }}
                </span>
              </div>
            </div>
            <hr>
            <small class="text-muted">
              <strong>Created:</strong> {{ formatDate(selectedFeedback.created_at) }}
              <br>
              <strong>Last Updated:</strong> {{ formatDate(selectedFeedback.updated_at) }}
            </small>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-warning" @click="editFeedback(selectedFeedback)">
              <i class="fas fa-edit me-2"></i>Edit
            </button>
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
  name: 'StudentFeedback',
  data() {
    return {
      courses: [],
      selectedCourseId: '',
      feedbackList: [],
      courseStudents: [],
      isLoading: false,
      isSaving: false,
      isEditMode: false,
      selectedFeedback: null,
      feedbackForm: {
        id: null,
        student_id: '',
        course_id: '',
        lecturer_id: '',
        feedback_type: 'general',
        subject: '',
        feedback: '',
        priority: 'medium',
        is_visible_to_student: true,
        is_visible_to_advisor: true
      }
    };
  },
  computed: {
    ...mapGetters('auth', ['getUser'])
  },
  mounted() {
    this.loadCourses();
  },
  methods: {
    async loadCourses() {
      console.log('Loading courses...');
      console.log('User data:', this.getUser);
      
      try {
        const userId = this.getUser?.id;
        if (!userId) {
          console.error('No user ID found. User data:', this.getUser);
          this.$store.dispatch('showToast', {
            message: 'Please login first',
            type: 'warning'
          });
          return;
        }

        console.log('Making API call with lecturer_id:', userId);
        const response = await axios.get(`/api/courses?lecturer_id=${userId}`);
        console.log('API response:', response.data);
        
        this.courses = response.data || [];
        console.log('Courses loaded:', this.courses.length);
      } catch (error) {
        console.error('Error loading courses:', error);
        console.error('Error response:', error.response?.data);
        this.$store.dispatch('showToast', {
          message: 'Error loading courses',
          type: 'error'
        });
      }
    },

    onCourseChange() {
      this.loadCourseFeedback();
    },

    async loadCourseFeedback() {
      if (!this.selectedCourseId) {
        this.feedbackList = [];
        return;
      }

      this.isLoading = true;
      try {
        const lecturerId = this.getUser?.id;
        const response = await axios.get(`/feedback-api.php?action=lecturer_feedback&lecturer_id=${lecturerId}&course_id=${this.selectedCourseId}`);
        this.feedbackList = response.data.feedback || [];
        
        // Also load students for this course
        await this.loadCourseStudents();
      } catch (error) {
        console.error('Error loading feedback:', error);
        this.$store.dispatch('showToast', {
          message: 'Error loading feedback',
          type: 'error'
        });
      } finally {
        this.isLoading = false;
      }
    },

    async loadCourseStudents() {
      try {
        console.log('Loading students for course:', this.selectedCourseId);
        const response = await axios.get(`/api/courses/${this.selectedCourseId}/enrollments`);
        console.log('Enrollments response:', response.data);
        
        // Transform enrollment data to student format expected by the dropdown
        this.courseStudents = (response.data.data || []).map(enrollment => ({
          id: enrollment.student_id,
          name: enrollment.student_name,
          email: enrollment.student_email,
          matric_number: enrollment.matric_number
        }));
        
        console.log('Transformed students:', this.courseStudents);
      } catch (error) {
        console.error('Error loading students:', error);
      }
    },

    showAddFeedbackModal() {
      if (!this.selectedCourseId) {
        this.$store.dispatch('showToast', {
          message: 'Please select a course first',
          type: 'warning'
        });
        return;
      }

      this.isEditMode = false;
      this.resetFeedbackForm();
      this.feedbackForm.course_id = this.selectedCourseId;
      this.feedbackForm.lecturer_id = this.getUser?.id;
      
      const modal = new Modal(document.getElementById('feedbackModal'));
      modal.show();
    },

    editFeedback(feedback) {
      this.isEditMode = true;
      this.feedbackForm = { ...feedback };
      
      // Close view modal if open
      const viewModal = Modal.getInstance(document.getElementById('viewFeedbackModal'));
      if (viewModal) {
        viewModal.hide();
      }
      
      // Show edit modal
      const modal = new Modal(document.getElementById('feedbackModal'));
      modal.show();
    },

    viewFeedback(feedback) {
      this.selectedFeedback = feedback;
      const modal = new Modal(document.getElementById('viewFeedbackModal'));
      modal.show();
    },

    async saveFeedback() {
      this.isSaving = true;
      try {
        await (this.isEditMode 
          ? axios.put(`/feedback-api.php?action=update_feedback`, this.feedbackForm)
          : axios.post('/feedback-api.php?action=add_feedback', this.feedbackForm));

        this.$store.dispatch('showToast', {
          message: this.isEditMode ? 'Feedback updated successfully' : 'Feedback added successfully',
          type: 'success'
        });
        
        // Hide modal
        const modal = Modal.getInstance(document.getElementById('feedbackModal'));
        modal.hide();
        
        // Reload feedback list
        await this.loadCourseFeedback();
      } catch (error) {
        console.error('Error saving feedback:', error);
        this.$store.dispatch('showToast', {
          message: 'Error saving feedback',
          type: 'error'
        });
      } finally {
        this.isSaving = false;
      }
    },

    async deleteFeedback(feedback) {
      if (!confirm(`Are you sure you want to delete this feedback for ${feedback.student_name}?`)) {
        return;
      }

      try {
        await axios.delete(`/feedback-api.php?action=delete_feedback&id=${feedback.id}`);
        
        this.$store.dispatch('showToast', {
          message: 'Feedback deleted successfully',
          type: 'success'
        });
        
        await this.loadCourseFeedback();
      } catch (error) {
        console.error('Error deleting feedback:', error);
        this.$store.dispatch('showToast', {
          message: 'Error deleting feedback',
          type: 'error'
        });
      }
    },

    resetFeedbackForm() {
      this.feedbackForm = {
        id: null,
        student_id: '',
        course_id: '',
        lecturer_id: '',
        feedback_type: 'general',
        subject: '',
        feedback: '',
        priority: 'medium',
        is_visible_to_student: true,
        is_visible_to_advisor: true
      };
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
.student-feedback h1 {
  color: #2c3e50;
  font-weight: 700;
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

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
}

.modal-body textarea {
  resize: vertical;
  min-height: 120px;
}

.badge {
  font-size: 0.75rem;
}
</style>
