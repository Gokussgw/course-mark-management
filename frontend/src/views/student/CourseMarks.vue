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

    <div v-if="isLoading || !getUser" class="text-center my-5">
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
              <div class="d-flex justify-content-center gap-2 mb-2">
                <div class="badge" :class="getOverallGradeBadgeClass">
                  {{ performance?.letter_grade || overallGrade }}
                </div>
                <div v-if="performance?.gpa" class="badge bg-info">
                  GPA: {{ performance.gpa }}
                </div>
              </div>
              <div v-if="ranking?.position && ranking?.total_students" class="small text-muted">
                Rank: {{ ranking.position }} of {{ ranking.total_students }} students
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
                  <div v-for="assessment in sortedAssessments" 
                       :key="assessment.assessment_id"
                       class="progress-bar" 
                       :class="getAssessmentTypeClass(assessment.assessment_type)"
                       :style="`width: ${assessment.weightage}%`"
                       :title="`${assessment.assessment_name} (${assessment.weightage}%)`">
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
                    <template v-for="assessment in sortedAssessments" :key="assessment.assessment_id">
                      <tr>
                        <td>
                          <strong>{{ assessment.assessment_name }}</strong>
                          <div v-if="assessment.description" class="small text-muted">
                            {{ assessment.description }}
                          </div>
                        </td>
                        <td>
                          <span class="badge" :class="getAssessmentTypeBadgeClass(assessment.assessment_type)">
                            {{ assessment.assessment_type }}
                          </span>
                        </td>
                        <td>{{ assessment.weightage }}%</td>
                        <td>{{ formatDate(assessment.date) }}</td>
                        <td>
                          <template v-if="hasMarkForAssessment(assessment.assessment_id)">
                            <strong>{{ getMarkForAssessment(assessment.assessment_id).mark }}</strong> / {{ assessment.max_mark }}
                            <div class="small text-muted">
                              ({{ calculatePercentage(getMarkForAssessment(assessment.assessment_id).mark, assessment.max_mark) }}%)
                            </div>
                          </template>
                          <template v-else>
                            <span class="text-muted">Pending</span>
                          </template>
                        </td>
                        <td>
                          <template v-if="hasMarkForAssessment(assessment.assessment_id)">
                            <span :class="getGradeClass(calculatePercentage(getMarkForAssessment(assessment.assessment_id).mark, assessment.max_mark))">
                              {{ calculateGrade(calculatePercentage(getMarkForAssessment(assessment.assessment_id).mark, assessment.max_mark)) }}
                            </span>
                          </template>
                          <template v-else>
                            <span class="text-muted">-</span>
                          </template>
                        </td>
                      </tr>
                      <tr v-if="hasMarkForAssessment(assessment.assessment_id) && getMarkForAssessment(assessment.assessment_id).remarks" class="remarks-row">
                        <td colspan="6" class="table-light">
                          <i class="fas fa-comment-alt me-2 text-muted"></i>
                          <span class="text-muted">Feedback: </span>
                          {{ getMarkForAssessment(assessment.assessment_id).remarks }}
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
            <div v-if="!performance || assessments.length === 0" class="text-center py-4">
              <p>Performance data will be available once assessments are completed.</p>
              <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Detailed analytics will show your strengths and areas for improvement.
              </div>
            </div>
            <div v-else>
              <!-- Performance Metrics Grid -->
              <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-3">
                  <div class="text-center">
                    <div class="h4 mb-1" :class="getPerformanceColor(getAssignmentPercentage())">
                      {{ getAssignmentPercentage() }}%
                    </div>
                    <div class="text-muted small">Assignment</div>
                    <div class="progress mt-2" style="height: 4px;">
                      <div class="progress-bar bg-success" :style="`width: ${getAssignmentPercentage()}%`"></div>
                    </div>
                  </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                  <div class="text-center">
                    <div class="h4 mb-1" :class="getPerformanceColor(getQuizPercentage())">
                      {{ getQuizPercentage() }}%
                    </div>
                    <div class="text-muted small">Quiz</div>
                    <div class="progress mt-2" style="height: 4px;">
                      <div class="progress-bar bg-info" :style="`width: ${getQuizPercentage()}%`"></div>
                    </div>
                  </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                  <div class="text-center">
                    <div class="h4 mb-1" :class="getPerformanceColor(getTestPercentage())">
                      {{ getTestPercentage() }}%
                    </div>
                    <div class="text-muted small">Midterm Test</div>
                    <div class="progress mt-2" style="height: 4px;">
                      <div class="progress-bar bg-warning" :style="`width: ${getTestPercentage()}%`"></div>
                    </div>
                  </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                  <div class="text-center">
                    <div class="h4 mb-1" :class="getPerformanceColor(getFinalExamPercentage())">
                      {{ getFinalExamPercentage() }}%
                    </div>
                    <div class="text-muted small">Final Exam</div>
                    <div class="progress mt-2" style="height: 4px;">
                      <div class="progress-bar bg-danger" :style="`width: ${getFinalExamPercentage()}%`"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Performance Chart -->
              <div class="chart-container mb-4" style="height: 300px;">
                <canvas id="performanceChart"></canvas>
              </div>

              <!-- Performance Insights -->
              <div class="row">
                <div class="col-md-6">
                  <div class="card bg-light">
                    <div class="card-body">
                      <h6 class="card-title text-success">
                        <i class="fas fa-trophy me-2"></i>Strengths
                      </h6>
                      <ul class="list-unstyled mb-0">
                        <li v-for="strength in getStrengths()" :key="strength" class="mb-1">
                          <i class="fas fa-check-circle text-success me-2"></i>{{ strength }}
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="card bg-light">
                    <div class="card-body">
                      <h6 class="card-title text-warning">
                        <i class="fas fa-lightbulb me-2"></i>Areas for Improvement
                      </h6>
                      <ul class="list-unstyled mb-0">
                        <li v-for="improvement in getAreasForImprovement()" :key="improvement" class="mb-1">
                          <i class="fas fa-arrow-up text-warning me-2"></i>{{ improvement }}
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Class Ranking and Statistics -->
              <div class="row mt-4" v-if="ranking">
                <div class="col-md-12">
                  <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                      <h6 class="card-title">Class Performance</h6>
                      <div class="row">
                        <div class="col-4">
                          <div class="h4 mb-1">{{ ranking.position }}</div>
                          <div class="small">Your Rank</div>
                        </div>
                        <div class="col-4">
                          <div class="h4 mb-1">{{ ranking.total_students }}</div>
                          <div class="small">Total Students</div>
                        </div>
                        <div class="col-4">
                          <div class="h4 mb-1">{{ getPercentile() }}%</div>
                          <div class="small">Percentile</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
// Uncomment in real implementation
// import Chart from 'chart.js/auto'

export default {
  name: 'CourseMarks',
  data() {
    return {
      courseId: null,
      sortOrder: 'date',
      performanceChart: null,
      isLoading: true,
      course: null,
      assessments: [],
      performance: null,
      ranking: null,
      error: null
    }
  },
  computed: {
    ...mapGetters("auth", ["getUser"]),
    userId() {
      return this.getUser ? this.getUser.id : null;
    },
    sortedAssessments() {
      const assessments = [...this.assessments];
      
      switch(this.sortOrder) {
        case 'name':
          return assessments.sort((a, b) => a.assessment_name.localeCompare(b.assessment_name));
        case 'weight':
          return assessments.sort((a, b) => b.weightage - a.weightage);
        case 'mark':
          return assessments.sort((a, b) => {
            const markA = this.calculatePercentage(a.mark, a.max_mark);
            const markB = this.calculatePercentage(b.mark, b.max_mark);
            return markB - markA;
          });
        case 'date':
        default:
          return assessments.sort((a, b) => {
            if (!a.date) return 1;
            if (!b.date) return -1;
            return new Date(a.date) - new Date(b.date);
          });
      }
    },
    marks() {
      // Convert assessments to the expected format for backward compatibility
      return this.assessments.map(assessment => ({
        id: assessment.mark_id,
        student_id: this.userId,
        assessment_id: assessment.assessment_id,
        mark: parseFloat(assessment.mark),
        course_id: this.courseId,
        remarks: null // Not available in current API
      }));
    },
    studentMarks() {
      return this.marks.filter(mark => mark.id !== null);
    },
    courseAssessments() {
      // Convert API assessments to expected format
      return this.assessments.map(assessment => ({
        id: assessment.assessment_id,
        name: assessment.assessment_name,
        type: assessment.assessment_type,
        weightage: parseFloat(assessment.weightage),
        max_mark: parseFloat(assessment.max_mark),
        due_date: assessment.date,
        is_final_exam: assessment.is_final_exam
      }));
    },
    totalAssessments() {
      return this.assessments.length;
    },
    completedAssessments() {
      return this.assessments.filter(a => a.mark_id !== null).length;
    },
    completionPercentage() {
      if (this.totalAssessments === 0) return 0;
      return Math.round((this.completedAssessments / this.totalAssessments) * 100);
    },
    overallMark() {
      return this.performance ? this.performance.overall_percentage : 0;
    },
    overallGrade() {
      // Use letter grade from API if available, otherwise calculate
      return this.performance?.letter_grade || this.calculateGrade(this.overallMark);
    },
    getOverallMarkClass() {
      if (this.overallMark >= 70) return 'text-success';
      if (this.overallMark >= 50) return 'text-warning';
      return 'text-danger';
    },
    getOverallGradeBadgeClass() {
      const grade = this.performance?.letter_grade || this.overallGrade;
      if (['A+', 'A', 'A-'].includes(grade)) return 'bg-success';
      if (['B+', 'B', 'B-'].includes(grade)) return 'bg-primary';
      if (['C+', 'C', 'C-'].includes(grade)) return 'bg-warning';
      if (['D+', 'D'].includes(grade)) return 'bg-orange';
      return 'bg-danger';
    }
  },
  async created() {
    this.courseId = parseInt(this.$route.params.id);
    
    console.log('CourseMarks created, getUser:', this.getUser);
    
    // Wait a bit for authentication to be ready, then try to load data
    setTimeout(() => {
      console.log('After timeout, getUser:', this.getUser);
      if (this.getUser) {
        this.loadCourseData();
      } else {
        this.error = 'Please log in to view course details';
        this.isLoading = false;
      }
    }, 100);
  },
  mounted() {
    // Initialize performance chart once data is loaded
    this.$nextTick(() => {
      if (this.performance && this.assessments.length > 0) {
        setTimeout(() => {
          this.initPerformanceChart();
        }, 500);
      }
    });
  },
  watch: {
    // Watch for changes in performance data and reinitialize chart
    performance: {
      handler() {
        if (this.performance && this.assessments.length > 0) {
          this.$nextTick(() => {
            setTimeout(() => {
              this.initPerformanceChart();
            }, 100);
          });
        }
      },
      deep: true
    }
  },
  methods: {
    async loadCourseData() {
      this.isLoading = true;
      this.error = null;
      
      // Debug logging
      console.log('Loading course data with:', {
        userId: this.userId,
        courseId: this.courseId
      });
      
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
        
        console.log('Request data:', requestData);
        
        const response = await fetch('http://localhost:8000/api/marks/student_course_detail', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(requestData)
        });

        console.log('Response status:', response.status);

        if (!response.ok) {
          const errorText = await response.text();
          console.log('Error response:', errorText);
          throw new Error(`HTTP error! status: ${response.status}, body: ${errorText}`);
        }

        const data = await response.json();
        
        if (data.success) {
          this.course = data.course;
          this.assessments = data.assessments;
          this.performance = data.performance;
          this.ranking = data.ranking;
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
    formatDate(dateString) {
      if (!dateString) return 'Not set';
      return new Date(dateString).toLocaleDateString();
    },
    hasMarkForAssessment(assessmentId) {
      if (!assessmentId || !this.assessments) return false;
      const assessment = this.assessments.find(a => a.assessment_id === assessmentId);
      // Check if mark_id exists (assessment has been graded), not just if mark > 0
      return assessment && assessment.mark_id !== null;
    },
    getMarkForAssessment(assessmentId) {
      if (!assessmentId || !this.assessments) return null;
      const assessment = this.assessments.find(a => a.assessment_id === assessmentId);
      if (!assessment) return null;
      return {
        mark: parseFloat(assessment.mark) || 0,
        remarks: null // Not available in current API
      };
    },
    calculatePercentage(mark, maxMark) {
      if (!maxMark) return 0;
      return Math.round((mark / maxMark) * 100);
    },
    getAssessmentTypeClass(type) {
      if (!type) return 'bg-secondary';
      const types = {
        'final_exam': 'bg-danger',
        'midterm': 'bg-warning',
        'quiz': 'bg-info',
        'assignment': 'bg-success',
        'project': 'bg-primary',
        'other': 'bg-secondary'
      };
      return types[type.toLowerCase()] || 'bg-secondary';
    },
    getAssessmentTypeBadgeClass(type) {
      if (!type) return 'bg-secondary';
      const types = {
        'final_exam': 'bg-danger',
        'midterm': 'bg-warning',
        'quiz': 'bg-info',
        'assignment': 'bg-success',
        'project': 'bg-primary',
        'other': 'bg-secondary'
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
    // Performance Analysis Methods
    getAssignmentPercentage() {
      const assignment = this.assessments.find(a => a.assessment_type === 'assignment');
      return assignment ? parseFloat(assignment.mark) || 0 : 0;
    },
    getQuizPercentage() {
      const quiz = this.assessments.find(a => a.assessment_type === 'quiz');
      return quiz ? parseFloat(quiz.mark) || 0 : 0;
    },
    getTestPercentage() {
      const test = this.assessments.find(a => a.assessment_type === 'midterm');
      return test ? parseFloat(test.mark) || 0 : 0;
    },
    getFinalExamPercentage() {
      const finalExam = this.assessments.find(a => a.assessment_type === 'final_exam');
      return finalExam ? parseFloat(finalExam.mark) || 0 : 0;
    },
    getPerformanceColor(percentage) {
      if (percentage >= 80) return 'text-success';
      if (percentage >= 70) return 'text-primary';
      if (percentage >= 60) return 'text-warning';
      return 'text-danger';
    },
    getStrengths() {
      const strengths = [];
      const scores = [
        { name: 'Assignment', score: this.getAssignmentPercentage() },
        { name: 'Quiz', score: this.getQuizPercentage() },
        { name: 'Midterm Test', score: this.getTestPercentage() },
        { name: 'Final Exam', score: this.getFinalExamPercentage() }
      ];
      
      scores.forEach(item => {
        if (item.score >= 85) {
          strengths.push(`Excellent ${item.name} performance (${item.score}%)`);
        } else if (item.score >= 75) {
          strengths.push(`Strong ${item.name} performance (${item.score}%)`);
        }
      });
      
      if (this.ranking && this.ranking.position <= 3) {
        strengths.push(`Top ${this.ranking.position} in class ranking`);
      }
      
      if (this.performance && this.performance.overall_percentage >= 80) {
        strengths.push(`Consistent high performance (${this.performance.overall_percentage}%)`);
      }
      
      return strengths.length > 0 ? strengths : ['Working towards building strengths'];
    },
    getAreasForImprovement() {
      const improvements = [];
      const scores = [
        { name: 'Assignment', score: this.getAssignmentPercentage() },
        { name: 'Quiz', score: this.getQuizPercentage() },
        { name: 'Midterm Test', score: this.getTestPercentage() },
        { name: 'Final Exam', score: this.getFinalExamPercentage() }
      ];
      
      scores.forEach(item => {
        if (item.score < 60 && item.score > 0) {
          improvements.push(`Focus on ${item.name} preparation (${item.score}%)`);
        } else if (item.score >= 60 && item.score < 75) {
          improvements.push(`Enhance ${item.name} technique (${item.score}%)`);
        }
      });
      
      if (this.ranking && this.ranking.position > this.ranking.total_students * 0.5) {
        improvements.push('Consider additional study time and practice');
      }
      
      return improvements.length > 0 ? improvements : ['Keep up the excellent work!'];
    },
    getPercentile() {
      if (!this.ranking) return 0;
      const percentile = Math.round(((this.ranking.total_students - this.ranking.position + 1) / this.ranking.total_students) * 100);
      return percentile;
    },
    initPerformanceChart() {
      // Enhanced Chart.js implementation
      const ctx = document.getElementById('performanceChart');
      if (!ctx) return;
      
      // Performance data by assessment type
      const labels = ['Assignment', 'Quiz', 'Midterm Test', 'Final Exam'];
      const yourScores = [
        this.getAssignmentPercentage(),
        this.getQuizPercentage(),
        this.getTestPercentage(),
        this.getFinalExamPercentage()
      ];
      
      // Sample class averages (in real app, this would come from API)
      const classAverages = [75, 72, 68, 78];
      
      // Create a simple bar chart using CSS if Chart.js is not available
      this.createSimpleChart(labels, yourScores, classAverages);
      
      /* Uncomment when Chart.js is available
      this.performanceChart = new Chart(ctx, {
        type: 'radar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Your Performance',
              data: yourScores,
              borderColor: '#0d6efd',
              backgroundColor: 'rgba(13, 110, 253, 0.2)',
              borderWidth: 2,
              pointBackgroundColor: '#0d6efd',
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              pointRadius: 5
            },
            {
              label: 'Class Average',
              data: classAverages,
              borderColor: '#6c757d',
              backgroundColor: 'rgba(108, 117, 125, 0.1)',
              borderWidth: 2,
              borderDash: [5, 5],
              pointBackgroundColor: '#6c757d',
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              pointRadius: 4
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            r: {
              beginAtZero: true,
              max: 100,
              ticks: {
                stepSize: 20
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
    },
    createSimpleChart(labels, yourScores, classAverages) {
      // Create a simple HTML chart if Chart.js is not available
      const ctx = document.getElementById('performanceChart');
      if (!ctx) return;
      
      ctx.style.display = 'none';
      const chartContainer = ctx.parentElement;
      
      const chartHTML = `
        <div class="simple-chart">
          <div class="chart-legend mb-3">
            <span class="badge bg-primary me-2">Your Performance</span>
            <span class="badge bg-secondary">Class Average</span>
          </div>
          ${labels.map((label, index) => `
            <div class="chart-item mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-medium">${label}</span>
                <span class="text-muted">${yourScores[index]}% vs ${classAverages[index]}%</span>
              </div>
              <div class="position-relative">
                <div class="progress" style="height: 20px;">
                  <div class="progress-bar bg-light" style="width: 100%"></div>
                  <div class="progress-bar bg-primary" style="width: ${yourScores[index]}%; position: absolute; left: 0;"></div>
                  <div class="progress-bar bg-secondary" style="width: 2px; position: absolute; left: ${classAverages[index]}%; height: 100%; opacity: 0.8;"></div>
                </div>
              </div>
            </div>
          `).join('')}
        </div>
      `;
      
      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = chartHTML;
      chartContainer.appendChild(tempDiv.firstElementChild);
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

.simple-chart {
  padding: 1rem;
}

.simple-chart .chart-item {
  position: relative;
}

.simple-chart .progress {
  border-radius: 10px;
  overflow: visible;
}

.simple-chart .progress-bar {
  border-radius: 10px;
}

.simple-chart .chart-legend .badge {
  font-size: 0.8rem;
}

.bg-orange {
  background-color: #fd7e14 !important;
}

@media (max-width: 768px) {
  .display-4 {
    font-size: 2.5rem;
  }
  
  .simple-chart {
    padding: 0.5rem;
  }
}
</style>
