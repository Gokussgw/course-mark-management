<template>
  <div class="advisee-detail-report">
    <!-- Navigation Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div class="d-flex align-items-center">
        <button 
          class="btn btn-outline-secondary me-3" 
          @click="$router.back()"
        >
          <i class="fas fa-arrow-left me-2"></i>
          Back to Reports
        </button>
        <div v-if="studentData && studentData.student">
          <h1 class="mb-1">{{ studentData.student.name }}</h1>
          <p class="text-muted mb-0">
            {{ studentData.student.matric_number }} • {{ studentData.student.email }}
          </p>
        </div>
        <div v-else-if="!isLoading">
          <h1 class="mb-1">Student Report</h1>
          <p class="text-muted mb-0">Loading student information...</p>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button 
          class="btn btn-outline-success" 
          @click="exportStudentReport"
          :disabled="isLoading"
        >
          <i class="fas fa-download me-2"></i>
          Export Report
        </button>
        <button 
          class="btn btn-primary" 
          @click="loadStudentReport"
          :disabled="isLoading"
        >
          <i class="fas fa-sync-alt me-2" :class="{ 'fa-spin': isLoading }"></i>
          Refresh
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center my-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-3 text-muted">Loading detailed report...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger">
      <i class="fas fa-exclamation-triangle me-2"></i>
      {{ error }}
    </div>

    <!-- Main Content -->
    <div v-else-if="studentData">
      <!-- Academic Overview Cards -->
      <div class="row mb-4">
        <div class="col-md-3">
          <div class="card text-center">
            <div class="card-body">
              <h5 class="card-title text-muted">Overall GPA</h5>
              <h2 
                class="mb-0" 
                :class="getGPAColorClass(studentData.student.overall_gpa)"
              >
                {{ (parseFloat(studentData.student.overall_gpa) || 0).toFixed(2) }}
              </h2>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center">
            <div class="card-body">
              <h5 class="card-title text-muted">Courses Completed</h5>
              <h2 class="mb-0 text-primary">
                {{ studentData.student.total_courses }}
              </h2>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center">
            <div class="card-body">
              <h5 class="card-title text-muted">Credit Hours</h5>
              <h2 class="mb-0 text-info">
                {{ studentData.student.completed_courses || 0 }}
              </h2>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center">
            <div class="card-body">
              <h5 class="card-title text-muted">Performance Trend</h5>
              <h5 class="mb-0">
                <span 
                  class="badge" 
                  :class="getTrendBadgeClass(getOverallTrend())"
                >
                  <i :class="getTrendIcon(getOverallTrend())" class="me-1"></i>
                  {{ getTrendText(getOverallTrend()) }}
                </span>
              </h5>
            </div>
          </div>
        </div>
      </div>

      <!-- Risk Assessment & Recommendations -->
      <div class="row mb-4">
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-header">
              <h5 class="mb-0">
                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                Risk Assessment
              </h5>
            </div>
            <div class="card-body">
              <div v-if="studentData.risk_indicators && studentData.risk_indicators.length > 0">
                <div class="alert alert-warning">
                  <strong>Risk Level: {{ getRiskLevel(studentData.risk_indicators) }}</strong>
                </div>
                <ul class="list-unstyled">
                  <li 
                    v-for="indicator in studentData.risk_indicators" 
                    :key="indicator"
                    class="mb-2"
                  >
                    <i class="fas fa-arrow-right text-danger me-2"></i>
                    {{ formatRiskIndicator(indicator) }}
                  </li>
                </ul>
              </div>
              <div v-else class="text-center text-success">
                <i class="fas fa-check-circle fa-3x mb-3"></i>
                <h6>No Risk Indicators Identified</h6>
                <p class="text-muted">Student is performing well academically</p>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-header">
              <h5 class="mb-0">
                <i class="fas fa-lightbulb text-primary me-2"></i>
                Recommendations
              </h5>
            </div>
            <div class="card-body">
              <div v-if="studentData.analytics && studentData.analytics.recommendations && studentData.analytics.recommendations.length > 0">
                <ul class="list-unstyled">
                  <li 
                    v-for="recommendation in studentData.analytics.recommendations" 
                    :key="recommendation.text"
                    class="mb-3"
                  >
                    <div class="d-flex">
                      <i class="fas fa-arrow-right text-primary me-2 mt-1"></i>
                      <div>
                        <strong>{{ recommendation.category }}:</strong>
                        <span class="ms-1">{{ recommendation.text }}</span>
                        <span 
                          class="badge ms-2" 
                          :class="getPriorityBadgeClass(recommendation.priority)"
                        >
                          {{ recommendation.priority }}
                        </span>
                      </div>
                    </div>
                  </li>
                </ul>
              </div>
              <div v-else class="text-center text-muted">
                <i class="fas fa-smile fa-3x mb-3"></i>
                <h6>Great Performance!</h6>
                <p>Continue with current study patterns</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Grade Distribution Chart -->
      <div class="row mb-4">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">Grade Distribution</h5>
            </div>
            <div class="card-body">
              <div class="grade-chart">
                <div 
                  v-for="(count, grade) in studentData.analytics.grade_distribution" 
                  :key="grade"
                  class="grade-bar mb-3"
                >
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold">Grade {{ grade.toUpperCase() }}</span>
                    <span class="badge" :class="getGradeBadgeClass(grade)">{{ count }}</span>
                  </div>
                  <div class="progress">
                    <div 
                      class="progress-bar" 
                      :class="getGradeProgressClass(grade)"
                      :style="{ width: getGradePercentage(count) + '%' }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">Component Strengths Analysis</h5>
            </div>
            <div class="card-body">
              <div v-if="studentData.analytics && studentData.analytics.component_strengths">
                <div 
                  v-for="(average, component) in studentData.analytics.component_strengths" 
                  :key="component"
                  class="mb-3"
                >
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold">{{ formatComponentName(component) }}</span>
                    <span 
                      class="badge" 
                      :class="getComponentBadgeClass(average)"
                    >
                      {{ (parseFloat(average) || 0).toFixed(1) }}%
                    </span>
                  </div>
                  <div class="progress">
                    <div 
                      class="progress-bar" 
                      :class="getComponentProgressClass(average)"
                      :style="{ width: (average || 0) + '%' }"
                    ></div>
                  </div>
                  <small class="text-muted">{{ getPerformanceText(average) }}</small>
                </div>
              </div>
              <div v-else class="text-center text-muted">
                <p>No component analysis available</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Course Details Table -->
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Course Performance Details</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Course</th>
                  <th>Course Code</th>
                  <th>Credit Hours</th>
                  <th>Final Grade</th>
                  <th>Grade Point</th>
                  <th>Assignment %</th>
                  <th>Quiz %</th>
                  <th>Test %</th>
                  <th>Exam %</th>
                  <th>Performance</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="course in studentData.courses" :key="course.id">
                  <td>
                    <div class="fw-bold">{{ course.name }}</div>
                    <small class="text-muted">{{ course.semester || 'Current' }}</small>
                  </td>
                  <td>{{ course.code }}</td>
                  <td>{{ course.credit_hours || 3 }}</td>
                  <td>
                    <span 
                      class="badge fs-6" 
                      :class="getGradeBadgeClass(course.letter_grade)"
                    >
                      {{ course.letter_grade || 'N/A' }}
                    </span>
                  </td>
                  <td>{{ (parseFloat(course.gpa) || 0).toFixed(2) }}</td>
                  <td>
                    <span 
                      class="badge" 
                      :class="getPerformanceBadgeClass(course.assignment_mark)"
                    >
                      {{ (parseFloat(course.assignment_mark) || 0) }}%
                    </span>
                  </td>
                  <td>
                    <span 
                      class="badge" 
                      :class="getPerformanceBadgeClass(course.quiz_mark)"
                    >
                      {{ (parseFloat(course.quiz_mark) || 0) }}%
                    </span>
                  </td>
                  <td>
                    <span 
                      class="badge" 
                      :class="getPerformanceBadgeClass(course.test_mark)"
                    >
                      {{ (parseFloat(course.test_mark) || 0) }}%
                    </span>
                  </td>
                  <td>
                    <span 
                      class="badge" 
                      :class="getPerformanceBadgeClass(course.final_exam_mark)"
                    >
                      {{ (parseFloat(course.final_exam_mark) || 0) }}%
                    </span>
                  </td>
                  <td>
                    <span 
                      class="badge" 
                      :class="getOverallPerformanceBadgeClass(getPerformanceLevel(course.overall_percentage))"
                    >
                      {{ (parseFloat(course.overall_percentage) || 0).toFixed(1) }}%
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdviseeDetailReport',
  data() {
    return {
      isLoading: true,
      error: null,
      studentData: null,
      studentId: null
    }
  },
  async created() {
    this.studentId = this.$route.params.studentId
    await this.loadStudentReport()
  },
  methods: {
    async loadStudentReport() {
      this.isLoading = true
      this.error = null
      
      try {
        const token = localStorage.getItem('token')
        const response = await fetch(`http://localhost:8000/api/advisee-reports/individual/${this.studentId}`, {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()
        
        if (data.success) {
          this.studentData = data.data
        } else {
          this.error = data.error || 'Failed to load student report'
        }
      } catch (error) {
        console.error('Error loading student report:', error)
        this.error = 'Failed to load student report. Please try again later.'
      } finally {
        this.isLoading = false
      }
    },
    
    async exportStudentReport() {
      try {
        const token = localStorage.getItem('token')
        const response = await fetch(`http://localhost:8000/api/advisee-reports/export/csv?student_id=${this.studentId}`, {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${token}`
          }
        })

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }

        const blob = await response.blob()
        const url = window.URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.style.display = 'none'
        a.href = url
        a.download = `${this.studentData.student.name}_report_${new Date().toISOString().split('T')[0]}.csv`
        document.body.appendChild(a)
        a.click()
        window.URL.revokeObjectURL(url)
        document.body.removeChild(a)
      } catch (error) {
        console.error('Error exporting report:', error)
        alert('Failed to export report. Please try again later.')
      }
    },
    
    getGPAColorClass(gpa) {
      const numericGpa = parseFloat(gpa) || 0
      if (numericGpa >= 3.5) return 'text-success'
      if (numericGpa >= 3.0) return 'text-primary'
      if (numericGpa >= 2.5) return 'text-warning'
      if (numericGpa >= 2.0) return 'text-orange'
      return 'text-danger'
    },
    
    getTrendBadgeClass(trend) {
      switch (trend) {
        case 'improving': return 'bg-success'
        case 'declining': return 'bg-danger'
        case 'stable': return 'bg-primary'
        default: return 'bg-secondary'
      }
    },
    
    getTrendIcon(trend) {
      switch (trend) {
        case 'improving': return 'fas fa-arrow-up'
        case 'declining': return 'fas fa-arrow-down'
        case 'stable': return 'fas fa-minus'
        default: return 'fas fa-question'
      }
    },
    
    getTrendText(trend) {
      switch (trend) {
        case 'improving': return 'Improving'
        case 'declining': return 'Declining'
        case 'stable': return 'Stable'
        default: return 'Insufficient Data'
      }
    },
    
    getRiskLevel(riskIndicators) {
      const riskScore = riskIndicators ? riskIndicators.length : 0
      if (riskScore >= 3) return 'High Risk'
      if (riskScore >= 2) return 'Medium Risk'
      if (riskScore >= 1) return 'Low Risk'
      return 'No Risk'
    },
    
    formatRiskIndicator(indicator) {
      const indicators = {
        'low_gpa': 'GPA below 2.0',
        'failing_grades': 'Has failing grades',
        'poor_assignment_performance': 'Poor assignment performance',
        'poor_quiz_performance': 'Poor quiz performance',
        'low_completion_rate': 'Low course completion rate'
      }
      return indicators[indicator] || indicator.replace(/_/g, ' ')
    },
    
    getGradeBadgeClass(grade) {
      switch (grade?.toLowerCase()) {
        case 'a': return 'bg-success'
        case 'b': return 'bg-primary'
        case 'c': return 'bg-warning'
        case 'd': return 'bg-orange'
        case 'f': return 'bg-danger'
        default: return 'bg-secondary'
      }
    },
    
    getGradeProgressClass(grade) {
      switch (grade?.toLowerCase()) {
        case 'a': return 'bg-success'
        case 'b': return 'bg-primary'
        case 'c': return 'bg-warning'
        case 'd': return 'bg-orange'
        case 'f': return 'bg-danger'
        default: return 'bg-secondary'
      }
    },
    
    getGradePercentage(count) {
      if (!this.studentData || !this.studentData.analytics || !this.studentData.analytics.grade_distribution) return 0
      const total = Object.values(this.studentData.analytics.grade_distribution).reduce((sum, val) => sum + val, 0)
      return total > 0 ? (count / total) * 100 : 0
    },
    
    getComponentBadgeClass(average) {
      const score = parseFloat(average) || 0
      if (score >= 85) return 'bg-success'
      if (score >= 75) return 'bg-primary' 
      if (score >= 65) return 'bg-warning'
      if (score >= 50) return 'bg-orange'
      return 'bg-danger'
    },
    
    getComponentProgressClass(average) {
      const score = parseFloat(average) || 0
      if (score >= 85) return 'bg-success'
      if (score >= 75) return 'bg-primary'
      if (score >= 65) return 'bg-warning'
      if (score >= 50) return 'bg-orange'
      return 'bg-danger'
    },
    
    getPerformanceText(average) {
      const score = parseFloat(average) || 0
      if (score >= 85) return 'Excellent'
      if (score >= 75) return 'Good'
      if (score >= 65) return 'Average'
      if (score >= 50) return 'Needs Improvement'
      return 'Poor'
    },
    
    getPerformanceLevel(percentage) {
      const score = parseFloat(percentage) || 0
      if (score >= 85) return 'excellent'
      if (score >= 75) return 'good'
      if (score >= 65) return 'satisfactory'
      if (score >= 50) return 'needs_improvement'
      return 'poor'
    },
    
    formatComponentName(component) {
      return component.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
    },
    
    getStrengthBadgeClass(performance) {
      switch (performance) {
        case 'excellent': return 'bg-success'
        case 'good': return 'bg-primary'
        case 'average': return 'bg-warning'
        case 'poor': return 'bg-danger'
        default: return 'bg-secondary'
      }
    },
    
    getStrengthProgressClass(performance) {
      switch (performance) {
        case 'excellent': return 'bg-success'
        case 'good': return 'bg-primary'
        case 'average': return 'bg-warning'
        case 'poor': return 'bg-danger'
        default: return 'bg-secondary'
      }
    },
    
    getPerformanceBadgeClass(score) {
      if (!score) return 'bg-secondary'
      if (score >= 80) return 'bg-success'
      if (score >= 70) return 'bg-primary'
      if (score >= 60) return 'bg-warning'
      if (score >= 50) return 'bg-orange'
      return 'bg-danger'
    },
    
    getOverallPerformanceBadgeClass(performance) {
      switch (performance) {
        case 'excellent': return 'bg-success'
        case 'good': return 'bg-primary'
        case 'satisfactory': return 'bg-warning'
        case 'needs_improvement': return 'bg-orange'
        case 'poor': return 'bg-danger'
        default: return 'bg-secondary'
      }
    },
    getPriorityBadgeClass(priority) {
      switch (priority?.toLowerCase()) {
        case 'urgent': return 'bg-danger'
        case 'high': return 'bg-warning'
        case 'medium': return 'bg-primary'
        case 'low': return 'bg-success'
        default: return 'bg-secondary'
      }
    },
    
    getOverallTrend() {
      if (!this.studentData || !this.studentData.analytics || !this.studentData.analytics.performance_trend) {
        return 'insufficient_data'
      }
      
      const trendData = this.studentData.analytics.performance_trend
      if (Array.isArray(trendData) && trendData.length >= 2) {
        const firstScore = parseFloat(trendData[0].percentage) || 0
        const lastScore = parseFloat(trendData[trendData.length - 1].percentage) || 0
        const diff = lastScore - firstScore
        
        if (diff > 5) return 'improving'
        if (diff < -5) return 'declining'
        return 'stable'
      }
      
      return 'insufficient_data'
    }
  }
}
</script>

<style scoped>
.advisee-detail-report {
  padding: 20px;
}

.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border: 1px solid rgba(0, 0, 0, 0.125);
}

.grade-chart .progress {
  height: 20px;
}

.table th {
  font-weight: 600;
  border-bottom: 2px solid #dee2e6;
}

.text-orange {
  color: #fd7e14 !important;
}

.bg-orange {
  background-color: #fd7e14 !important;
}

@media (max-width: 768px) {
  .d-flex.gap-2 {
    flex-direction: column;
    gap: 0.5rem !important;
  }
  
  .table-responsive {
    font-size: 0.875rem;
  }
}
</style>
