<template>
  <div class="remark-request">
    <h1 class="mb-4">Request Remark</h1>
    
    <div class="row">
      <div class="col-md-8">
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title mb-4">Mark Information</h5>
            
            <div v-if="isLoading" class="text-center my-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
            
            <div v-else>
              <div class="mb-3 row">
                <label class="col-sm-4 col-form-label fw-bold">Course:</label>
                <div class="col-sm-8">
                  <p class="form-control-plaintext">{{ courseInfo.code }} - {{ courseInfo.name }}</p>
                </div>
              </div>
              
              <div class="mb-3 row">
                <label class="col-sm-4 col-form-label fw-bold">Assessment:</label>
                <div class="col-sm-8">
                  <p class="form-control-plaintext">{{ assessmentInfo.name }} ({{ assessmentInfo.type }})</p>
                </div>
              </div>
              
              <div class="mb-3 row">
                <label class="col-sm-4 col-form-label fw-bold">Your Mark:</label>
                <div class="col-sm-8">
                  <p class="form-control-plaintext">
                    {{ markInfo.mark }} / {{ assessmentInfo.max_mark }}
                    ({{ calculatePercentage(markInfo.mark, assessmentInfo.max_mark) }}%)
                  </p>
                </div>
              </div>
              
              <div class="mb-3 row">
                <label class="col-sm-4 col-form-label fw-bold">Class Average:</label>
                <div class="col-sm-8">
                  <p class="form-control-plaintext">
                    {{ classAverage }} / {{ assessmentInfo.max_mark }}
                    ({{ calculatePercentage(classAverage, assessmentInfo.max_mark) }}%)
                  </p>
                </div>
              </div>
              
              <div class="mb-4 row">
                <label class="col-sm-4 col-form-label fw-bold">Lecturer:</label>
                <div class="col-sm-8">
                  <p class="form-control-plaintext">{{ lecturerName }}</p>
                </div>
              </div>
              
              <hr class="my-4">
              
              <form @submit.prevent="submitRequest">
                <div class="mb-3">
                  <label for="justification" class="form-label fw-bold">Justification for Remark Request</label>
                  <textarea 
                    class="form-control" 
                    id="justification" 
                    rows="6"
                    v-model="remarkRequest.justification"
                    placeholder="Please explain why you believe your assessment should be remarked. Be specific about which questions or sections you think were incorrectly marked."
                    required
                  ></textarea>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                  <router-link :to="`/student/course/${courseInfo.id}`" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Back to Course
                  </router-link>
                  
                  <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <div v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                    <i v-else class="fas fa-paper-plane me-2"></i>
                    Submit Request
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-4">Remark Guidelines</h5>
            
            <div class="mb-4">
              <h6><i class="fas fa-info-circle text-primary me-2"></i> About Remark Requests</h6>
              <p class="text-muted small">
                A remark request asks the lecturer to review your assessment marking for potential errors or oversights.
              </p>
            </div>
            
            <div class="mb-4">
              <h6><i class="fas fa-check-circle text-success me-2"></i> Good Justifications</h6>
              <ul class="text-muted small">
                <li>Specific reference to marking criteria</li>
                <li>Clear explanation of potential marking error</li>
                <li>Reference to specific questions/sections</li>
              </ul>
            </div>
            
            <div class="mb-4">
              <h6><i class="fas fa-times-circle text-danger me-2"></i> Poor Justifications</h6>
              <ul class="text-muted small">
                <li>Complaints without specific examples</li>
                <li>"I deserve a better grade"</li>
                <li>"I need a higher mark to pass"</li>
                <li>Comparison to other students' grades</li>
              </ul>
            </div>
            
            <div class="alert alert-warning">
              <i class="fas fa-exclamation-triangle me-2"></i>
              <strong>Important:</strong> Remark requests can result in marks being increased, decreased, or unchanged.
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title" id="successModalLabel">Request Submitted</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="text-center mb-3">
              <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
            </div>
            <p>Your remark request has been submitted successfully. Your lecturer will review your request and respond as soon as possible.</p>
            <p>You can check the status of your request in the course marks page.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <router-link :to="`/student/course/${courseInfo.id}`" class="btn btn-primary">
              Back to Course
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import * as bootstrap from 'bootstrap';

export default {
  name: 'RemarkRequest',
  props: {
    markId: {
      type: [Number, String],
      required: true
    }
  },
  data() {
    return {
      markInfo: {},
      assessmentInfo: {},
      courseInfo: {},
      classAverage: 0,
      lecturerName: '',
      remarkRequest: {
        justification: ''
      },
      submitting: false
    };
  },
  computed: {
    ...mapGetters(['isLoading']),
    ...mapGetters('auth', ['getUser'])
  },
  created() {
    this.loadData();
  },
  methods: {
    async loadData() {
      try {
        // In a real app, these would be separate API calls
        // For demo purposes, we're simulating data
        
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 500));
        
        this.markInfo = {
          id: this.markId,
          mark: 68,
          assessment_id: 1,
          student_id: this.getUser.id,
          created_at: '2025-06-28'
        };
        
        this.assessmentInfo = {
          id: 1,
          name: 'Midterm Examination',
          type: 'midterm',
          max_mark: 100,
          weightage: 30,
          course_id: 1
        };
        
        this.courseInfo = {
          id: 1,
          code: 'CS101',
          name: 'Introduction to Computer Science',
          lecturer_id: 2
        };
        
        this.classAverage = 72.5;
        this.lecturerName = 'Lecturer One';
        
      } catch (error) {
        console.error('Error loading mark data:', error);
      }
    },
    
    calculatePercentage(mark, maxMark) {
      if (!mark || !maxMark) return 0;
      return ((mark / maxMark) * 100).toFixed(1);
    },
    
    async submitRequest() {
      this.submitting = true;
      
      try {
        // In a real app, this would make an API call to save the remark request
        // For demo purposes, we're simulating the API call
        
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // Show success modal
        const modal = new bootstrap.Modal(document.getElementById('successModal'));
        modal.show();
        
        // Reset form
        this.remarkRequest.justification = '';
      } catch (error) {
        console.error('Error submitting remark request:', error);
        alert('Failed to submit request. Please try again.');
      } finally {
        this.submitting = false;
      }
    }
  }
};
</script>

<style scoped>
.remark-request h1 {
  color: #2c3e50;
  font-weight: 700;
}

.card {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border: none;
  border-radius: 10px;
  margin-bottom: 1.5rem;
}

.form-control-plaintext {
  padding-top: calc(0.375rem + 1px);
  padding-bottom: calc(0.375rem + 1px);
  margin-bottom: 0;
}

.modal-header {
  border-top-left-radius: 0.5rem;
  border-top-right-radius: 0.5rem;
}

textarea.form-control:focus {
  box-shadow: 0 0 0 0.25rem rgba(44, 62, 80, 0.25);
  border-color: #2c3e50;
}
</style>
