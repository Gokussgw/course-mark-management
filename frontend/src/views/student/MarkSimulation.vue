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

    <div v-else-if="error" class="alert alert-danger">
      {{ error }}
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
                  <tr v-for="assessment in sortedAssessments" :key="assessment.assessment_type">
                    <td>
                      <strong>{{ assessment.assessment_type }}</strong>
                    </td>
                    <td>
                      <span class="badge" :class="getAssessmentTypeBadgeClass(assessment.assessment_type)">
                        {{ assessment.assessment_type }}
                      </span>
                    </td>
                    <td>{{ assessment.weightage }}%</td>
                    <td>
                      <span 
                        class="badge" 
                        :class="assessment.mark_id ? 'bg-success' : 'bg-secondary'"
                      >
                        {{ assessment.mark_id ? 'Completed' : 'Pending' }}
                      </span>
                    </td>
                    <td style="width: 180px;">
                      <div class="input-group input-group-sm">
                        <input 
                          type="number" 
                          class="form-control"
                          v-model.number="simulatedMarks[assessment.assessment_type]"
                          :min="0" 
                          :max="assessment.max_mark"
                          :placeholder="assessment.mark || 'Enter mark'"
                          @input="updateSimulatedMark(assessment.assessment_type, $event.target.value)"
                        >
                        <span class="input-group-text">/ {{ assessment.max_mark }}</span>
                      </div>
                      <small class="text-muted" v-if="assessment.mark_id && simulatedMarks[assessment.assessment_type] === null">
                        Current: {{ assessment.mark }} ({{ calculatePercentage(parseFloat(assessment.mark), parseFloat(assessment.max_mark)) }}%)
                      </small>
                      <small class="text-muted" v-else-if="simulatedMarks[assessment.assessment_type] !== null && simulatedMarks[assessment.assessment_type] !== undefined">
                        Simulated ({{ calculatePercentage(simulatedMarks[assessment.assessment_type], parseFloat(assessment.max_mark)) }}%)
                      </small>
                      <small class="text-muted" v-else>
                        {{ assessment.mark_id ? 'Override current mark' : 'Enter expected mark' }}
                      </small>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
              <button class="btn btn-outline-secondary me-2" @click="clearSimulation">
                Clear Simulation
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
              <div 
                class="progress-bar bg-warning" 
                role="progressbar" 
                :style="`width: 20%`"
                aria-valuenow="20" 
                aria-valuemin="0" 
                aria-valuemax="100">
              </div>
              <div 
                class="progress-bar bg-success" 
                role="progressbar" 
                :style="`width: 30%`"
                aria-valuenow="30" 
                aria-valuemin="0" 
                aria-valuemax="100">
              </div>
            </div>
            <div class="d-flex justify-content-between">
              <small>0%</small>
              <small>50% (Pass)</small>
              <small>70% (Distinction)</small>
              <small>100%</small>
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
                <span class="badge bg-warning">A+ Grade (80%+)</span>
                <span 
                  :class="{ 'text-success': requiredMarks.a_grade <= 100, 'text-danger': requiredMarks.a_grade > 100 }"
                >
                  {{ formatRequiredMark(requiredMarks.a_grade) }}
                </span>
              </div>
              <div class="progress mb-3" style="height: 8px;">
                <div 
                  class="progress-bar bg-warning" 
                  role="progressbar" 
                  :style="`width: ${Math.min(requiredMarks.a_grade, 100)}%`"
                  :aria-valuenow="Math.min(requiredMarks.a_grade, 100)"
                  aria-valuemin="0" 
                  aria-valuemax="100">
                </div>
              </div>
            </div>
            
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
                <span class="badge bg-primary">Pass (50%+)</span>
                <span
                  :class="{ 'text-success': requiredMarks.pass <= 100, 'text-danger': requiredMarks.pass > 100 }"
                >
                  {{ formatRequiredMark(requiredMarks.pass) }}
                </span>
              </div>
              <div class="progress mb-3" style="height: 8px;">
                <div 
                  class="progress-bar bg-primary" 
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
import { mapGetters } from 'vuex'

export default {
  name: 'MarkSimulation',
  data() {
    return {
      courseId: null,
      isLoading: true,
      course: null,
      assessments: [],
      performance: null,
      simulatedMarks: {}, // assessment_type -> simulated_mark
      requiredMarks: {
        pass: 0,
        distinction: 0,
        a_grade: 0
      },
      error: null
    }
  },
  computed: {
    ...mapGetters("auth", ["getUser"]),
    userId() {
      return this.getUser ? this.getUser.id : null;
    },
    sortedAssessments() {
      return [...this.assessments].sort((a, b) => {
        // Sort by weightage (highest first)
        return b.weightage - a.weightage;
      });
    },
    currentOverallMark() {
      return this.performance ? this.performance.overall_percentage : 0;
    },
    simulatedOverallMark() {
      let weightedSum = 0;
      let totalWeight = 0;
      
      this.assessments.forEach(assessment => {
        const simulatedMark = this.simulatedMarks[assessment.assessment_type];
        const actualMark = parseFloat(assessment.mark) || 0;
        const weight = parseFloat(assessment.weightage);
        
        // Use simulated mark if it has a value, otherwise use actual mark
        const markToUse = (simulatedMark !== null && simulatedMark !== undefined) ? simulatedMark : actualMark;
        
        const percentage = this.calculatePercentage(markToUse, parseFloat(assessment.max_mark));
        weightedSum += percentage * weight;
        totalWeight += weight;
      });
      
      if (totalWeight === 0) return 0;
      return Math.round(weightedSum / totalWeight);
    },
    simulatedGrade() {
      return this.calculateGrade(this.simulatedOverallMark);
    },
    simulatedGPA() {
      return this.calculateGPA(this.simulatedOverallMark);
    },
    markChange() {
      return this.simulatedOverallMark - this.currentOverallMark;
    },
    getSimulatedMarkClass() {
      if (this.simulatedOverallMark >= 80) return 'text-success';
      if (this.simulatedOverallMark >= 70) return 'text-primary';
      if (this.simulatedOverallMark >= 60) return 'text-warning';
      return 'text-danger';
    },
    getSimulatedGradeBadgeClass() {
      const grade = this.simulatedGrade;
      if (['A+', 'A', 'A-'].includes(grade)) return 'bg-success';
      if (['B+', 'B', 'B-'].includes(grade)) return 'bg-primary';
      if (['C+', 'C', 'C-'].includes(grade)) return 'bg-warning';
      if (['D+', 'D'].includes(grade)) return 'bg-orange';
      return 'bg-danger';
    },
    getCurrentMarkClass() {
      if (this.currentOverallMark >= 80) return 'text-success';
      if (this.currentOverallMark >= 70) return 'text-primary';
      if (this.currentOverallMark >= 60) return 'text-warning';
      return 'text-danger';
    },
    getMarkChangeClass() {
      if (this.markChange > 0) return 'text-success';
      if (this.markChange < 0) return 'text-danger';
      return 'text-muted';
    },
    unassessedItems() {
      return this.assessments.filter(assessment => 
        assessment.mark_id === null
      );
    },
    totalRemainingWeight() {
      return this.unassessedItems.reduce((sum, assessment) => 
        sum + parseFloat(assessment.weightage), 0
      );
    }
  },
  async created() {
    this.courseId = parseInt(this.$route.params.id);
    
    console.log('MarkSimulation created, getUser:', this.getUser);
    
    // Wait a bit for authentication to be ready, then try to load data
    setTimeout(() => {
      console.log('After timeout, getUser:', this.getUser);
      if (this.getUser) {
        this.loadCourseData();
      } else {
        this.error = 'Please log in to access the grade simulator';
        this.isLoading = false;
      }
    }, 100);
  },
  methods: {
    async loadCourseData() {
      this.isLoading = true;
      this.error = null;
      
      if (!this.userId) {
        this.error = 'User not authenticated';
        this.isLoading = false;
        return;
      }
      
      try {
        const requestData = {
          student_id: this.userId,
          course_id: this.courseId
        };
        
        const response = await fetch('http://localhost:3000/api/marks/student_course_detail', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(requestData)
        });

        if (!response.ok) {
          const errorText = await response.text();
          throw new Error(`HTTP error! status: ${response.status}, body: ${errorText}`);
        }

        const data = await response.json();
        
        if (data.success) {
          this.course = data.course;
          this.assessments = data.assessments;
          this.performance = data.performance;
          
          // Initialize simulation
          this.initializeSimulation();
          this.calculateRequiredMarks();
        } else {
          this.error = data.error || 'Failed to load course data';
        }
      } catch (error) {
        console.error('Error loading course data:', error);
        this.error = 'Failed to load course data. Please try again later.';
      } finally {
        this.isLoading = false;
      }
    },
    initializeSimulation() {
      // Initialize simulated marks for assessments
      this.simulatedMarks = {};
      this.assessments.forEach(assessment => {
        // Allow simulation for all assessments to enable "what-if" scenarios
        this.simulatedMarks[assessment.assessment_type] = null;
      });
    },
    hasMarkForAssessment(assessmentType) {
      const assessment = this.assessments.find(a => a.assessment_type === assessmentType);
      return assessment && assessment.mark_id !== null;
    },
    isSimulatedAssessment(assessmentType) {
      return assessmentType in this.simulatedMarks;
    },
    calculatePercentage(mark, maxMark) {
      if (!mark || !maxMark) return 0;
      return Math.round((mark / maxMark) * 100);
    },
    calculateGrade(percentage) {
      if (percentage >= 90) return 'A+';
      if (percentage >= 85) return 'A';
      if (percentage >= 80) return 'A-';
      if (percentage >= 75) return 'B+';
      if (percentage >= 70) return 'B';
      if (percentage >= 65) return 'B-';
      if (percentage >= 60) return 'C+';
      if (percentage >= 55) return 'C';
      if (percentage >= 50) return 'C-';
      if (percentage >= 45) return 'D+';
      if (percentage >= 40) return 'D';
      return 'F';
    },
    calculateGPA(percentage) {
      if (percentage >= 90) return 4.3;
      if (percentage >= 85) return 4.0;
      if (percentage >= 80) return 3.7;
      if (percentage >= 75) return 3.3;
      if (percentage >= 70) return 3.0;
      if (percentage >= 65) return 2.7;
      if (percentage >= 60) return 2.3;
      if (percentage >= 55) return 2.0;
      if (percentage >= 50) return 1.7;
      if (percentage >= 45) return 1.3;
      if (percentage >= 40) return 1.0;
      return 0.0;
    },
    calculateRequiredMarks() {
      this.requiredMarks.pass = this.calculateRequiredMarkForGrade(50);
      this.requiredMarks.distinction = this.calculateRequiredMarkForGrade(70);
      this.requiredMarks.a_grade = this.calculateRequiredMarkForGrade(80);
    },
    calculateRequiredMarkForGrade(targetPercentage) {
      // Calculate current assessed weight and marks from actual marks only
      let assessedWeightedMarks = 0;
      
      this.assessments.forEach(assessment => {
        // Only include assessments that have actual marks (not simulated)
        if (assessment.mark_id !== null) {
          const percentage = this.calculatePercentage(parseFloat(assessment.mark), parseFloat(assessment.max_mark));
          assessedWeightedMarks += percentage * parseFloat(assessment.weightage);
        }
      });
      
      // If no remaining assessments, return 0
      if (this.totalRemainingWeight === 0) return 0;
      
      // Calculate required weighted marks from remaining assessments
      const totalRequiredWeightedMarks = targetPercentage * 100; // 100% total weight
      const requiredFromRemaining = totalRequiredWeightedMarks - assessedWeightedMarks;
      
      // Calculate required percentage on remaining assessments
      const requiredPercentage = requiredFromRemaining / this.totalRemainingWeight;
      
      return Math.max(0, Math.round(requiredPercentage));
    },
    updateSimulatedMark(assessmentType, value) {
      if (value === '' || value === null) {
        this.$delete(this.simulatedMarks, assessmentType);
      } else {
        this.$set(this.simulatedMarks, assessmentType, parseFloat(value));
      }
      this.calculateRequiredMarks();
    },
    clearSimulation() {
      this.simulatedMarks = {};
      this.assessments.forEach(assessment => {
        this.simulatedMarks[assessment.assessment_type] = null;
      });
      this.calculateRequiredMarks();
    },
    applyPreset(type) {
      this.assessments.forEach(assessment => {
        let targetMark;
        const maxMark = parseFloat(assessment.max_mark);
        
        switch(type) {
          case 'best':
            targetMark = maxMark;
            break;
          case 'good':
            targetMark = maxMark * 0.85;
            break;
          case 'average':
            targetMark = maxMark * 0.70;
            break;
          case 'minimal':
            targetMark = maxMark * 0.50;
            break;
          default:
            targetMark = 0;
        }
        
        this.simulatedMarks[assessment.assessment_type] = Math.round(targetMark);
      });
      
      this.calculateRequiredMarks();
    },
    getCurrentMarkForAssessment(assessmentType) {
      const assessment = this.assessments.find(a => a.assessment_type === assessmentType);
      return assessment && assessment.mark ? parseFloat(assessment.mark) : null;
    },
    getMaxMarkForAssessment(assessmentType) {
      const assessment = this.assessments.find(a => a.assessment_type === assessmentType);
      return assessment ? parseFloat(assessment.max_mark) : 0;
    },
    getAssessmentWeight(assessmentType) {
      const assessment = this.assessments.find(a => a.assessment_type === assessmentType);
      return assessment ? parseFloat(assessment.weightage) : 0;
    },
    getAssessmentTypeBadgeClass(type) {
      const types = {
        'Assignment': 'bg-success',
        'Quiz': 'bg-info',
        'Test': 'bg-warning',
        'Final Exam': 'bg-danger',
        'Project': 'bg-primary',
        'Lab': 'bg-secondary'
      };
      return types[type] || 'bg-secondary';
    },
    formatRequiredMark(value) {
      if (value <= 0) return 'Already achieved';
      if (value > 100) return 'Not possible';
      return `${value}% needed`;
    },
    formatDate(dateString) {
      if (!dateString) return 'Not set';
      return new Date(dateString).toLocaleDateString();
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
