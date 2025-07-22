<template>
  <div class="course-marks">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item">
              <router-link to="/student/dashboard">Dashboard</router-link>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              {{ course?.code }} - {{ course?.name }}
            </li>
          </ol>
        </nav>
        <h1 class="mb-2">{{ course?.name }}</h1>
        <p class="text-muted">
          {{ course?.code }} | {{ course?.semester || 'No semester specified' }}
        </p>
      </div>
      <div class="btn-group">
        <button class="btn btn-outline-primary" @click="downloadMarks">
          <i class="fas fa-download me-2"></i> Download
        </button>
        <router-link :to="`/student/simulation/${courseId}`" class="btn btn-outline-primary">
          <i class="fas fa-calculator me-2"></i> Grade Simulator
        </router-link>
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
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-header bg-light">
            <h5 class="mb-0">Course Summary</h5>
          </div>
          <div class="card-body">
            <div class="mb-4 text-center">
              <div class="display-4 fw-bold" :class="getOverallMarkClass">
                {{ overallMark }}%
              </div>
              <p class="text-muted">Overall Mark</p>
              <div class="badge" :class="getOverallGradeBadgeClass">
                {{ overallGrade }}
              </div>
            </div>
            
            <div class="progress-info mb-3">
              <div class="d-flex justify-content-between align-items-center">
                <span>Course Completion</span>
                <span class="text-muted">{{ completionPercentage }}%</span>
              </div>
              <div class="progress" style="height: 8px;">
                <div 
                  class="progress-bar bg-info" 
                  role="progressbar" 
                  :style="`width: ${completionPercentage}%`"
                  :aria-valuenow="completionPercentage" 
                  aria-valuemin="0" 
                  aria-valuemax="100">
                </div>
              </div>
              <small class="text-muted">
                {{ completedAssessments }} of {{ totalAssessments }} assessments completed
              </small>
            </div>

            <table class="table table-sm">
              <tbody>
                <tr>
                  <th>Course:</th>
                  <td>{{ course?.name }}</td>
                </tr>
                <tr>
                  <th>Code:</th>
                  <td>{{ course?.code }}</td>
                </tr>
                <tr>
                  <th>Semester:</th>
                  <td>{{ course?.semester || 'Not specified' }}</td>
                </tr>
                <tr>
                  <th>Credits:</th>
                  <td>{{ course?.credits || 'Not specified' }}</td>
                </tr>
                <tr>
                  <th>Lecturer:</th>
                  <td>{{ course?.lecturer_name || 'Not specified' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-md-8 mb-4">
        <div class="card mb-4">
          <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Mark Breakdown</h5>
            <div class="dropdown">
              <button 
                class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                type="button" 
                id="sortDropdown" 
                data-bs-toggle="dropdown" 
                aria-expanded="false"
              >
                Sort by
              </button>
              <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                <li><a class="dropdown-item" href="#" @click.prevent="sortMarks('date')">Date</a></li>
                <li><a class="dropdown-item" href="#" @click.prevent="sortMarks('name')">Name</a></li>
                <li><a class="dropdown-item" href="#" @click.prevent="sortMarks('weight')">Weightage</a></li>
                <li><a class="dropdown-item" href="#" @click.prevent="sortMarks('mark')">Mark</a></li>
              </ul>
            </div>
          </div>
          <div class="card-body">
            <div v-if="marks.length === 0" class="text-center py-4">
              <p>No marks have been recorded for this course yet.</p>
              <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Marks will appear here once your assessments have been graded.
              </div>
            </div>
            <div v-else>
              <div class="mb-4">
                <h6>Assessment Distribution</h6>
                <div class="progress" style="height: 30px;">
                  <div v-for="(assessment, index) in sortedAssessments" 
                       :key="assessment.id"
                       class="progress-bar" 
                       :class="getAssessmentTypeClass(assessment.type)"
                       :style="`width: ${assessment.weightage}%`"
                       :title="`${assessment.name} (${assessment.weightage}%)`">
                    {{ assessment.weightage }}%
                  </div>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Assessment</th>
                      <th>Type</th>
                      <th>Weight</th>
                      <th>Due Date</th>
                      <th>Mark</th>
                      <th>Grade</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="assessment in sortedAssessments" :key="assessment.id">
                      <tr>
                        <td>
                          <strong>{{ assessment.name }}</strong>
                          <div v-if="assessment.description" class="small text-muted">
                            {{ assessment.description }}
                          </div>
                        </td>
                        <td>
                          <span class="badge" :class="getAssessmentTypeBadgeClass(assessment.type)">
                            {{ assessment.type }}
                          </span>
                        </td>
                        <td>{{ assessment.weightage }}%</td>
                        <td>{{ formatDate(assessment.due_date) }}</td>
                        <td>
                          <template v-if="hasMarkForAssessment(assessment.id)">
                            <strong>{{ getMarkForAssessment(assessment.id).mark }}</strong> / {{ assessment.max_mark }}
                            <div class="small text-muted">
                              ({{ calculatePercentage(getMarkForAssessment(assessment.id).mark, assessment.max_mark) }}%)
                            </div>
                          </template>
                          <template v-else>
                            <span class="text-muted">Pending</span>
                          </template>
                        </td>
                        <td>
                          <template v-if="hasMarkForAssessment(assessment.id)">
                            <span :class="getGradeClass(calculatePercentage(getMarkForAssessment(assessment.id).mark, assessment.max_mark))">
                              {{ calculateGrade(calculatePercentage(getMarkForAssessment(assessment.id).mark, assessment.max_mark)) }}
                            </span>
                          </template>
                          <template v-else>
                            <span class="text-muted">-</span>
                          </template>
                        </td>
                      </tr>
                      <tr v-if="hasMarkForAssessment(assessment.id) && getMarkForAssessment(assessment.id).remarks" class="remarks-row">
                        <td colspan="6" class="table-light">
                          <i class="fas fa-comment-alt me-2 text-muted"></i>
                          <span class="text-muted">Feedback: </span>
                          {{ getMarkForAssessment(assessment.id).remarks }}
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header bg-light">
            <h5 class="mb-0">Performance Analysis</h5>
          </div>
          <div class="card-body">
            <div v-if="marks.length < 2" class="text-center py-4">
              <p>Not enough data to show performance analysis.</p>
              <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Performance trends will be displayed after more assessments are completed.
              </div>
            </div>
            <div v-else>
              <!-- This is where we would integrate a chart.js component -->
              <div class="chart-container" style="height: 250px;">
                <canvas id="performanceChart"></canvas>
              </div>
              <div class="mt-3 text-center text-muted small">
                <i class="fas fa-info-circle me-1"></i>
                Chart shows your performance relative to class average (when available).
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapGetters, mapActions } from 'vuex'
// Uncomment in real implementation
// import Chart from 'chart.js/auto'

export default {
  name: 'CourseMarks',
  data() {
    return {
      courseId: null,
      sortOrder: 'date',
      performanceChart: null
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
      const assessments = [...this.courseAssessments];
      
      switch(this.sortOrder) {
        case 'name':
          return assessments.sort((a, b) => a.name.localeCompare(b.name));
        case 'weight':
          return assessments.sort((a, b) => b.weightage - a.weightage);
        case 'mark':
          return assessments.sort((a, b) => {
            const markA = this.hasMarkForAssessment(a.id) ? 
              this.calculatePercentage(this.getMarkForAssessment(a.id).mark, a.max_mark) : -1;
            const markB = this.hasMarkForAssessment(b.id) ? 
              this.calculatePercentage(this.getMarkForAssessment(b.id).mark, b.max_mark) : -1;
            return markB - markA;
          });
        case 'date':
        default:
          return assessments.sort((a, b) => {
            if (!a.due_date) return 1;
            if (!b.due_date) return -1;
            return new Date(a.due_date) - new Date(b.due_date);
          });
      }
    },
    studentMarks() {
      return this.marks.filter(mark => 
        mark.student_id === this.userId && 
        mark.course_id === parseInt(this.courseId)
      );
    },
    totalAssessments() {
      return this.courseAssessments.length;
    },
    completedAssessments() {
      return this.studentMarks.length;
    },
    completionPercentage() {
      if (this.totalAssessments === 0) return 0;
      return Math.round((this.completedAssessments / this.totalAssessments) * 100);
    },
    overallMark() {
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
    overallGrade() {
      return this.calculateGrade(this.overallMark);
    },
    getOverallMarkClass() {
      if (this.overallMark >= 70) return 'text-success';
      if (this.overallMark >= 50) return 'text-warning';
      return 'text-danger';
    },
    getOverallGradeBadgeClass() {
      if (this.overallMark >= 70) return 'bg-success';
      if (this.overallMark >= 50) return 'bg-warning';
      return 'bg-danger';
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
    } catch (error) {
      console.error('Error loading course marks:', error);
    }
  },
  mounted() {
    // Initialize performance chart once data is loaded
    this.$nextTick(() => {
      if (this.marks.length >= 2) {
        this.initPerformanceChart();
      }
    });
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
      return this.studentMarks.some(mark => mark.assessment_id === assessmentId);
    },
    getMarkForAssessment(assessmentId) {
      return this.studentMarks.find(mark => mark.assessment_id === assessmentId);
    },
    calculatePercentage(mark, maxMark) {
      if (!maxMark) return 0;
      return Math.round((mark / maxMark) * 100);
    },
    getAssessmentTypeClass(type) {
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
    getGradeClass(percentage) {
      if (percentage >= 70) return 'text-success fw-bold';
      if (percentage >= 50) return 'text-warning';
      return 'text-danger';
    },
    sortMarks(order) {
      this.sortOrder = order;
    },
    downloadMarks() {
      // This would initiate a download of marks in CSV format
      // Implementation would need to call an API endpoint
      this.$store.dispatch('showToast', {
        message: 'Downloading marks...',
        type: 'info'
      });
    },
    initPerformanceChart() {
      // This would be implemented with Chart.js in a real application
      /* Uncomment in real implementation
      const ctx = document.getElementById('performanceChart');
      
      // Sort marks by date
      const sortedMarks = [...this.studentMarks].sort((a, b) => {
        const assessmentA = this.courseAssessments.find(assessment => assessment.id === a.assessment_id);
        const assessmentB = this.courseAssessments.find(assessment => assessment.id === b.assessment_id);
        if (!assessmentA || !assessmentA.due_date) return -1;
        if (!assessmentB || !assessmentB.due_date) return 1;
        return new Date(assessmentA.due_date) - new Date(assessmentB.due_date);
      });
      
      const labels = sortedMarks.map(mark => {
        const assessment = this.courseAssessments.find(a => a.id === mark.assessment_id);
        return assessment ? assessment.name : 'Unknown';
      });
      
      const data = sortedMarks.map(mark => {
        const assessment = this.courseAssessments.find(a => a.id === mark.assessment_id);
        if (!assessment) return 0;
        return this.calculatePercentage(mark.mark, assessment.max_mark);
      });
      
      // Sample class averages (would come from API in real implementation)
      const classAverages = sortedMarks.map(() => Math.floor(Math.random() * 30) + 50);
      
      this.performanceChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Your Marks',
              data: data,
              borderColor: '#0d6efd',
              backgroundColor: 'rgba(13, 110, 253, 0.1)',
              borderWidth: 2,
              tension: 0.1,
              fill: true
            },
            {
              label: 'Class Average',
              data: classAverages,
              borderColor: '#6c757d',
              borderWidth: 2,
              borderDash: [5, 5],
              tension: 0.1,
              fill: false
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              max: 100,
              title: {
                display: true,
                text: 'Mark (%)'
              }
            }
          },
          plugins: {
            legend: {
              position: 'top',
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return `${context.dataset.label}: ${context.raw}%`;
                }
              }
            }
          }
        }
      });
      */
    }
  }
}
</script>

<style scoped>
.course-marks h1 {
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

.remarks-row {
  font-style: italic;
  font-size: 0.9em;
}

.chart-container {
  position: relative;
  width: 100%;
}

@media (max-width: 768px) {
  .display-4 {
    font-size: 2.5rem;
  }
}
</style>
