<template>
  <div class="mark-simulation">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item">
              <router-link to="/student/dashboard">Dashboard</router-link>
            </li>
            <li class="breadcrumb-item">
              <router-link :to="`/student/course/${courseId}`">
                {{ course?.code }} - {{ course?.name }}
              </router-link>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              Grade Simulator
            </li>
          </ol>
        </nav>
        <h1 class="mb-2">Grade Simulator</h1>
        <p class="text-muted">
          {{ course?.code }} | {{ course?.name }}
        </p>
      </div>
    </div>

    <div v-if="isLoading" class="text-center my-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else-if="!course" class="alert alert-danger">
      Course not found or you don't have access to this course.
    </div>

    <div v-else class="row">
      <div class="col-md-8 mb-4">
        <div class="card">
          <div class="card-header bg-light">
            <h5 class="mb-0">Assessment Marks</h5>
            <p class="text-muted small mb-0">
              Enter hypothetical marks for assessments to simulate your final grade
            </p>
          </div>
          <div class="card-body">
            <div class="alert alert-info mb-4">
              <i class="fas fa-info-circle me-2"></i>
              Enter the marks you expect to get for assessments that haven't been graded yet. Your actual marks are pre-filled where available.
            </div>

            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Assessment</th>
                    <th>Type</th>
                    <th>Weight</th>
                    <th>Status</th>
                    <th>Mark</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="assessment in sortedAssessments" :key="assessment.id">
                    <td>
                      <strong>{{ assessment.name }}</strong>
                    </td>
                    <td>
                      <span class="badge" :class="getAssessmentTypeBadgeClass(assessment.type)">
                        {{ assessment.type }}
                      </span>
                    </td>
                    <td>{{ assessment.weightage }}%</td>
                    <td>
                      <span 
                        class="badge" 
                        :class="getStatusBadge(assessment)"
                      >
                        {{ getAssessmentStatus(assessment) }}
                      </span>
                    </td>
                    <td style="width: 180px;">
                      <div v-if="hasMarkForAssessment(assessment.id) && !isSimulatedAssessment(assessment.id)">
                        <div class="input-group input-group-sm disabled">
                          <input 
                            type="number" 
                            class="form-control bg-light"
                            :value="getMarkForAssessment(assessment.id).mark"
                            disabled
                          >
                          <span class="input-group-text">/ {{ assessment.max_mark }}</span>
                        </div>
                        <small class="text-muted">Actual mark ({{ calculatePercentage(getMarkForAssessment(assessment.id).mark, assessment.max_mark) }}%)</small>
                      </div>
                      <div v-else>
                        <div class="input-group input-group-sm">
                          <input 
                            type="number" 
                            class="form-control"
                            v-model.number="simulatedMarks[assessment.id]"
                            :min="0" 
                            :max="assessment.max_mark"
                            @input="updateSimulation"
                          >
                          <span class="input-group-text">/ {{ assessment.max_mark }}</span>
                        </div>
                        <small class="text-muted" v-if="simulatedMarks[assessment.id] !== undefined">
                          Simulated ({{ calculatePercentage(simulatedMarks[assessment.id], assessment.max_mark) }}%)
                        </small>
                        <small class="text-muted" v-else>
                          Enter expected mark
                        </small>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
              <button class="btn btn-outline-secondary me-2" @click="resetSimulation">
                Reset Simulation
              </button>
              <div class="dropdown">
                <button 
                  class="btn btn-outline-primary dropdown-toggle" 
                  type="button" 
                  id="presetDropdown" 
                  data-bs-toggle="dropdown" 
                  aria-expanded="false"
                >
                  Apply Preset
                </button>
                <ul class="dropdown-menu" aria-labelledby="presetDropdown">
                  <li><a class="dropdown-item" href="#" @click.prevent="applyPreset('best')">Best Case (90%)</a></li>
                  <li><a class="dropdown-item" href="#" @click.prevent="applyPreset('good')">Good Case (75%)</a></li>
                  <li><a class="dropdown-item" href="#" @click.prevent="applyPreset('average')">Average Case (65%)</a></li>
                  <li><a class="dropdown-item" href="#" @click.prevent="applyPreset('minimal')">Minimal Pass (50%)</a></li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4 mb-4">
        <div class="card mb-4">
          <div class="card-header bg-light">
            <h5 class="mb-0">Simulation Results</h5>
          </div>
          <div class="card-body text-center">
            <div class="mb-4">
              <div class="display-4 fw-bold" :class="getSimulatedMarkClass">
                {{ simulatedOverallMark }}%
              </div>
              <div class="badge fs-6 my-2" :class="getSimulatedGradeBadgeClass">
                {{ simulatedGrade }}
              </div>
              <p class="text-muted">Projected Final Grade</p>
            </div>
            
            <hr class="my-4">
            
            <div class="comparison mb-3">
              <div class="row">
                <div class="col-6 text-start">
                  <p class="mb-1 small text-muted">Current Mark</p>
                  <h4 :class="getCurrentMarkClass">{{ currentOverallMark }}%</h4>
                </div>
                <div class="col-6 text-end">
                  <p class="mb-1 small text-muted">Change</p>
                  <h4 :class="getMarkChangeClass">
                    {{ markChange > 0 ? '+' : '' }}{{ markChange }}%
                  </h4>
                </div>
              </div>
            </div>
            
            <div class="progress mb-3" style="height: 10px;">
              <div 
                class="progress-bar bg-danger" 
                role="progressbar" 
                :style="`width: 50%`"
                aria-valuenow="50" 
                aria-valuemin="0" 
                aria-valuemax="100">
              </div>
            </div>
            <div class="d-flex justify-content-between">
              <small>Fail</small>
              <small>Pass</small>
              <small>Distinction</small>
            </div>
            
            <div 
              class="position-indicator" 
              :style="`left: ${Math.min(Math.max(simulatedOverallMark, 0), 100)}%`"
            >
              <i class="fas fa-caret-down"></i>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header bg-light">
            <h5 class="mb-0">Required Marks</h5>
          </div>
          <div class="card-body">
            <p class="text-muted small mb-3">
              Marks required on remaining assessments to achieve these grades:
            </p>
            
            <div class="required-mark-row">
              <div class="d-flex justify-content-between mb-2">
                <span class="badge bg-success">Distinction (70%+)</span>
                <span 
                  :class="{ 'text-success': requiredMarks.distinction <= 100, 'text-danger': requiredMarks.distinction > 100 }"
                >
                  {{ formatRequiredMark(requiredMarks.distinction) }}
                </span>
              </div>
              <div class="progress mb-3" style="height: 8px;">
                <div 
                  class="progress-bar bg-success" 
                  role="progressbar" 
                  :style="`width: ${Math.min(requiredMarks.distinction, 100)}%`"
                  :aria-valuenow="Math.min(requiredMarks.distinction, 100)"
                  aria-valuemin="0" 
                  aria-valuemax="100">
                </div>
              </div>
            </div>
            
            <div class="required-mark-row">
              <div class="d-flex justify-content-between mb-2">
                <span class="badge bg-warning">Pass (50%+)</span>
                <span
                  :class="{ 'text-success': requiredMarks.pass <= 100, 'text-danger': requiredMarks.pass > 100 }"
                >
                  {{ formatRequiredMark(requiredMarks.pass) }}
                </span>
              </div>
              <div class="progress mb-3" style="height: 8px;">
                <div 
                  class="progress-bar bg-warning" 
                  role="progressbar" 
                  :style="`width: ${Math.min(requiredMarks.pass, 100)}%`"
                  :aria-valuenow="Math.min(requiredMarks.pass, 100)"
                  aria-valuemin="0" 
                  aria-valuemax="100">
                </div>
              </div>
            </div>
            
            <div class="alert alert-secondary mt-3 small">
              <i class="fas fa-info-circle me-2"></i>
              These calculations show the average mark needed on all remaining assessments to achieve the target grade.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapGetters, mapActions } from 'vuex'

export default {
  name: 'MarkSimulation',
  data() {
    return {
      courseId: null,
      simulatedMarks: {}, // assessment_id -> simulated_mark
      requiredMarks: {
        pass: 0,
        distinction: 0
      }
    }
  },
  computed: {
    ...mapState({
      isLoading: state => state.loading,
      course: state => state.courses.course,
      marks: state => state.marks.marks,
      userId: state => state.auth.userId
    }),
    ...mapGetters({
      getCourseAssessments: 'assessments/getCourseAssessments'
    }),
    courseAssessments() {
      return this.getCourseAssessments(this.courseId) || [];
    },
    sortedAssessments() {
      return [...this.courseAssessments].sort((a, b) => {
        // Sort by due date
        if (!a.due_date) return 1;
        if (!b.due_date) return -1;
        return new Date(a.due_date) - new Date(b.due_date);
      });
    },
    studentMarks() {
      return this.marks.filter(mark => 
        mark.student_id === this.userId && 
        mark.course_id === parseInt(this.courseId)
      );
    },
    unassessedItems() {
      return this.courseAssessments.filter(assessment => 
        !this.hasMarkForAssessment(assessment.id)
      );
    },
    totalRemainingWeight() {
      return this.unassessedItems.reduce((sum, assessment) => sum + assessment.weightage, 0);
    },
    currentOverallMark() {
      if (!this.studentMarks.length) return 0;
      
      let weightedSum = 0;
      let totalWeight = 0;
      
      this.studentMarks.forEach(mark => {
        const assessment = this.courseAssessments.find(a => a.id === mark.assessment_id);
        if (!assessment) return;
        
        const percentage = this.calculatePercentage(mark.mark, assessment.max_mark);
        weightedSum += percentage * assessment.weightage;
        totalWeight += parseInt(assessment.weightage);
      });
      
      if (totalWeight === 0) return 0;
      return Math.round(weightedSum / totalWeight);
    },
    simulatedOverallMark() {
      let weightedSum = 0;
      let totalWeight = 0;
      
      // Include actual marks
      this.studentMarks.forEach(mark => {
        // Skip if this assessment is being simulated
        if (this.isSimulatedAssessment(mark.assessment_id)) return;
        
        const assessment = this.courseAssessments.find(a => a.id === mark.assessment_id);
        if (!assessment) return;
        
        const percentage = this.calculatePercentage(mark.mark, assessment.max_mark);
        weightedSum += percentage * assessment.weightage;
        totalWeight += parseInt(assessment.weightage);
      });
      
      // Include simulated marks
      for (const assessmentId in this.simulatedMarks) {
        const mark = this.simulatedMarks[assessmentId];
        const assessment = this.courseAssessments.find(a => a.id === parseInt(assessmentId));
        
        if (!assessment || mark === undefined) continue;
        
        const percentage = this.calculatePercentage(mark, assessment.max_mark);
        weightedSum += percentage * assessment.weightage;
        totalWeight += parseInt(assessment.weightage);
      }
      
      if (totalWeight === 0) return 0;
      return Math.round(weightedSum / totalWeight);
    },
    simulatedGrade() {
      return this.calculateGrade(this.simulatedOverallMark);
    },
    markChange() {
      return this.simulatedOverallMark - this.currentOverallMark;
    },
    getSimulatedMarkClass() {
      if (this.simulatedOverallMark >= 70) return 'text-success';
      if (this.simulatedOverallMark >= 50) return 'text-warning';
      return 'text-danger';
    },
    getSimulatedGradeBadgeClass() {
      if (this.simulatedOverallMark >= 70) return 'bg-success';
      if (this.simulatedOverallMark >= 50) return 'bg-warning';
      return 'bg-danger';
    },
    getCurrentMarkClass() {
      if (this.currentOverallMark >= 70) return 'text-success';
      if (this.currentOverallMark >= 50) return 'text-warning';
      return 'text-danger';
    },
    getMarkChangeClass() {
      if (this.markChange > 0) return 'text-success';
      if (this.markChange < 0) return 'text-danger';
      return 'text-muted';
    }
  },
  async created() {
    this.courseId = parseInt(this.$route.params.id);
    
    try {
      // Fetch course details
      await this.fetchCourse(this.courseId);
      
      // Fetch assessments for this course
      await this.fetchAssessments({ courseId: this.courseId });
      
      // Fetch marks for this student in this course
      await this.fetchMarks({ 
        studentId: this.userId,
        courseId: this.courseId
      });
      
      // Initialize the simulation
      this.initializeSimulation();
      
      // Calculate required marks
      this.calculateRequiredMarks();
    } catch (error) {
      console.error('Error loading simulation data:', error);
    }
  },
  methods: {
    ...mapActions({
      fetchCourse: 'courses/fetchCourse',
      fetchAssessments: 'assessments/fetchAssessments',
      fetchMarks: 'marks/fetchMarks'
    }),
    formatDate(dateString) {
      if (!dateString) return 'Not set';
      return new Date(dateString).toLocaleDateString();
    },
    hasMarkForAssessment(assessmentId) {
      return this.studentMarks.some(mark => mark.assessment_id === parseInt(assessmentId));
    },
    getMarkForAssessment(assessmentId) {
      return this.studentMarks.find(mark => mark.assessment_id === parseInt(assessmentId));
    },
    isSimulatedAssessment(assessmentId) {
      return assessmentId in this.simulatedMarks;
    },
    calculatePercentage(mark, maxMark) {
      if (!maxMark) return 0;
      return Math.round((mark / maxMark) * 100);
    },
    getAssessmentTypeBadgeClass(type) {
      const types = {
        'exam': 'bg-danger',
        'midterm': 'bg-warning',
        'quiz': 'bg-info',
        'assignment': 'bg-success',
        'project': 'bg-primary',
        'lab': 'bg-secondary'
      };
      return types[type.toLowerCase()] || 'bg-secondary';
    },
    getAssessmentStatus(assessment) {
      // Check if it has a real mark
      if (this.hasMarkForAssessment(assessment.id) && !this.isSimulatedAssessment(assessment.id)) {
        return 'Completed';
      }
      
      // Check if it has a simulated mark
      if (this.isSimulatedAssessment(assessment.id)) {
        return 'Simulated';
      }
      
      const today = new Date();
      const dueDate = assessment.due_date ? new Date(assessment.due_date) : null;
      
      if (!dueDate) return 'Upcoming';
      
      if (dueDate < today) {
        return 'Overdue';
      }
      
      if (dueDate.getTime() - today.getTime() < 7 * 24 * 60 * 60 * 1000) {
        return 'Upcoming';
      }
      
      return 'Scheduled';
    },
    getStatusBadge(assessment) {
      const status = this.getAssessmentStatus(assessment);
      
      const badges = {
        'Completed': 'bg-success',
        'Simulated': 'bg-info',
        'Overdue': 'bg-danger',
        'Upcoming': 'bg-warning',
        'Scheduled': 'bg-secondary'
      };
      
      return badges[status] || 'bg-secondary';
    },
    calculateGrade(percentage) {
      if (percentage >= 90) return 'A+';
      if (percentage >= 80) return 'A';
      if (percentage >= 75) return 'B+';
      if (percentage >= 70) return 'B';
      if (percentage >= 65) return 'C+';
      if (percentage >= 60) return 'C';
      if (percentage >= 55) return 'D+';
      if (percentage >= 50) return 'D';
      return 'F';
    },
    initializeSimulation() {
      this.simulatedMarks = {};
      
      // For assessments without marks, initialize with empty values
      this.courseAssessments.forEach(assessment => {
        if (!this.hasMarkForAssessment(assessment.id)) {
          this.simulatedMarks[assessment.id] = undefined;
        }
      });
    },
    resetSimulation() {
      this.initializeSimulation();
      this.calculateRequiredMarks();
    },
    updateSimulation() {
      this.calculateRequiredMarks();
    },
    calculateRequiredMarks() {
      // Calculate how much is needed on remaining assessments to achieve target grades
      
      // First, get the current contribution from real and simulated marks
      let currentWeightedSum = 0;
      let assessedWeight = 0;
      
      // Include actual marks (that aren't being simulated)
      this.studentMarks.forEach(mark => {
        if (this.isSimulatedAssessment(mark.assessment_id)) return;
        
        const assessment = this.courseAssessments.find(a => a.id === mark.assessment_id);
        if (!assessment) return;
        
        const percentage = this.calculatePercentage(mark.mark, assessment.max_mark);
        currentWeightedSum += percentage * assessment.weightage;
        assessedWeight += assessment.weightage;
      });
      
      // Include simulated marks that have values
      for (const assessmentId in this.simulatedMarks) {
        const mark = this.simulatedMarks[assessmentId];
        if (mark === undefined) continue;
        
        const assessment = this.courseAssessments.find(a => a.id === parseInt(assessmentId));
        if (!assessment) continue;
        
        const percentage = this.calculatePercentage(mark, assessment.max_mark);
        currentWeightedSum += percentage * assessment.weightage;
        assessedWeight += assessment.weightage;
      }
      
      // Calculate remaining unassessed weight
      let unassessedWeight = 0;
      this.courseAssessments.forEach(assessment => {
        const assessmentId = assessment.id;
        if (!this.hasMarkForAssessment(assessmentId) && 
            !(assessmentId in this.simulatedMarks && this.simulatedMarks[assessmentId] !== undefined)) {
          unassessedWeight += assessment.weightage;
        }
      });
      
      // Calculate required marks for different grade thresholds
      const totalWeight = 100; // Assuming total is 100%
      
      // For passing grade (50%)
      const requiredForPass = (50 * totalWeight - currentWeightedSum) / unassessedWeight;
      this.requiredMarks.pass = unassessedWeight > 0 ? Math.round(requiredForPass) : 0;
      
      // For distinction grade (70%)
      const requiredForDistinction = (70 * totalWeight - currentWeightedSum) / unassessedWeight;
      this.requiredMarks.distinction = unassessedWeight > 0 ? Math.round(requiredForDistinction) : 0;
    },
    formatRequiredMark(value) {
      if (value <= 0) return 'Already achieved';
      if (value > 100) return 'Not possible';
      return `${value}% needed`;
    },
    applyPreset(preset) {
      // Apply preset values to all simulated assessments
      let presetValue = 0;
      
      switch(preset) {
        case 'best':
          presetValue = 90;
          break;
        case 'good':
          presetValue = 75;
          break;
        case 'average':
          presetValue = 65;
          break;
        case 'minimal':
          presetValue = 50;
          break;
      }
      
      // Apply the preset percentage to all unassessed items
      this.courseAssessments.forEach(assessment => {
        if (!this.hasMarkForAssessment(assessment.id)) {
          // Calculate the actual mark value based on the percentage and max mark
          const markValue = Math.round((presetValue / 100) * assessment.max_mark);
          this.simulatedMarks[assessment.id] = markValue;
        }
      });
      
      this.calculateRequiredMarks();
    }
  }
}
</script>

<style scoped>
.mark-simulation h1 {
  font-size: 1.75rem;
  font-weight: 600;
}

.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  margin-bottom: 1.5rem;
}

.progress {
  border-radius: 0.25rem;
  overflow: hidden;
}

.display-4 {
  font-size: 3rem;
}

.position-indicator {
  position: relative;
  width: 0;
  height: 0;
  margin-top: -24px;
  z-index: 2;
  color: #343a40;
  font-size: 1.5rem;
  transform: translateX(-50%);
}

@media (max-width: 768px) {
  .display-4 {
    font-size: 2.5rem;
  }
}
</style>
