<template>
  <div class="student-ranking-page">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1>Student Academic Ranking</h1>
        <p class="text-muted" v-if="student">
          Detailed ranking information for {{ student.name }} ({{ student.matric_number }})
        </p>
      </div>
      <div>
        <router-link 
          to="/advisor/dashboard" 
          class="btn btn-outline-secondary me-2"
        >
          <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </router-link>
        <router-link 
          :to="`/advisor/advisee/${studentId}`" 
          class="btn btn-primary"
        >
          <i class="fas fa-user me-1"></i> View Profile
        </router-link>
      </div>
    </div>

    <!-- Student Basic Info -->
    <div class="row mb-4" v-if="student">
      <div class="col-md-12">
        <div class="card">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-2">
                <div class="student-avatar">
                  {{ getInitials(student.name) }}
                </div>
              </div>
              <div class="col-md-6">
                <h4>{{ student.name }}</h4>
                <p class="text-muted mb-1">Matric Number: {{ student.matric_number }}</p>
                <p class="text-muted mb-0">Academic Status: {{ student.status }}</p>
              </div>
              <div class="col-md-4 text-end">
                <div class="quick-stats">
                  <div class="stat-item">
                    <span class="stat-value">{{ student.gpa }}</span>
                    <span class="stat-label">Current GPA</span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-value">{{ student.enrolled_courses }}</span>
                    <span class="stat-label">Courses</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Ranking Component -->
    <student-ranking 
      :student-id="studentId"
      :show-individual-ranking="true"
      :show-class-rankings="true"
      :is-own-ranking="false"
      :student-name="student ? student.name : ''"
    />

    <!-- Course Performance Comparison -->
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">
              <i class="fas fa-chart-bar me-2"></i>
              Course-wise Performance Comparison
            </h5>
            
            <div v-if="loadingCourseComparison" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading comparison...</span>
              </div>
            </div>
            
            <div v-else class="row">
              <div class="col-md-6">
                <canvas id="courseComparisonChart" width="400" height="200"></canvas>
              </div>
              <div class="col-md-6">
                <h6>Performance Summary</h6>
                <div class="performance-summary">
                  <div class="summary-item" v-for="course in coursePerformance" :key="course.course_id">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <strong>{{ course.code }}</strong>
                        <small class="text-muted d-block">{{ course.course_name }}</small>
                      </div>
                      <div class="text-end">
                        <span class="badge" :class="getPerformanceBadgeClass(course.average)">
                          {{ course.average }}%
                        </span>
                        <small class="text-muted d-block">Rank: #{{ course.rank }}/{{ course.total_students }}</small>
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

    <!-- Advisor Actions -->
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">
              <i class="fas fa-clipboard-list me-2"></i>
              Advisor Actions
            </h5>
            <div class="btn-group" role="group">
              <button class="btn btn-outline-primary" @click="addNote">
                <i class="fas fa-sticky-note me-1"></i> Add Note
              </button>
              <button class="btn btn-outline-info" @click="scheduleMeeting">
                <i class="fas fa-calendar me-1"></i> Schedule Meeting
              </button>
              <button class="btn btn-outline-warning" @click="sendAlert">
                <i class="fas fa-exclamation-triangle me-1"></i> Send Alert
              </button>
              <router-link 
                :to="`/advisor/advisee/${studentId}/comparison`" 
                class="btn btn-outline-success"
              >
                <i class="fas fa-users me-1"></i> Compare with Peers
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import StudentRanking from '@/components/rankings/StudentRanking.vue'
import Chart from 'chart.js/auto'

export default {
  name: 'StudentRankingPage',
  components: {
    StudentRanking
  },
  data() {
    return {
      student: null,
      coursePerformance: [],
      loadingStudent: false,
      loadingCourseComparison: false,
      comparisonChart: null
    }
  },
  computed: {
    ...mapGetters('auth', ['getUser']),
    
    studentId() {
      return this.$route.params.id
    }
  },
  mounted() {
    this.loadStudentData()
    this.loadCourseComparison()
  },
  beforeUnmount() {
    if (this.comparisonChart) {
      this.comparisonChart.destroy()
    }
  },
  methods: {
    async loadStudentData() {
      this.loadingStudent = true
      try {
        const token = this.$store.state.auth.token
        if (!token) {
          throw new Error('No authentication token')
        }

        const response = await fetch(
          `http://localhost:8080/advisor-dashboard-api.php?action=advisees`,
          {
            method: 'GET',
            headers: {
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${token}`
            }
          }
        )

        if (response.ok) {
          const data = await response.json()
          this.student = data.advisees.find(s => s.id == this.studentId)
        } else {
          throw new Error('Failed to load student data')
        }
      } catch (error) {
        console.error('Error loading student data:', error)
        this.$store.dispatch('showToast', {
          message: 'Failed to load student data',
          type: 'error'
        })
      } finally {
        this.loadingStudent = false
      }
    },

    async loadCourseComparison() {
      this.loadingCourseComparison = true
      try {
        const token = this.$store.state.auth.token
        if (!token) {
          throw new Error('No authentication token')
        }

        // Get courses for the student and their performance
        const response = await fetch(
          `http://localhost:8080/ranking-api.php?action=student_ranking&student_id=${this.studentId}`,
          {
            method: 'GET',
            headers: {
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${token}`
            }
          }
        )

        if (response.ok) {
          const data = await response.json()
          this.coursePerformance = data.ranking.course_rankings || []
          this.$nextTick(() => {
            this.initCourseComparisonChart()
          })
        }
      } catch (error) {
        console.error('Error loading course comparison:', error)
      } finally {
        this.loadingCourseComparison = false
      }
    },

    initCourseComparisonChart() {
      const ctx = document.getElementById('courseComparisonChart')
      if (!ctx || this.coursePerformance.length === 0) return

      if (this.comparisonChart) {
        this.comparisonChart.destroy()
      }

      const labels = this.coursePerformance.map(course => course.code)
      const averages = this.coursePerformance.map(course => course.course_average)
      const classAverages = this.coursePerformance.map(() => 70) // Mock class average

      this.comparisonChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Student Performance',
              data: averages,
              backgroundColor: 'rgba(52, 152, 219, 0.8)',
              borderColor: 'rgba(52, 152, 219, 1)',
              borderWidth: 1
            },
            {
              label: 'Class Average',
              data: classAverages,
              backgroundColor: 'rgba(149, 165, 166, 0.8)',
              borderColor: 'rgba(149, 165, 166, 1)',
              borderWidth: 1
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
              ticks: {
                callback: function(value) {
                  return value + '%'
                }
              }
            }
          },
          plugins: {
            legend: {
              position: 'top'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return context.dataset.label + ': ' + context.raw + '%'
                }
              }
            }
          }
        }
      })
    },

    getInitials(name) {
      return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
    },

    getPerformanceBadgeClass(average) {
      if (average >= 80) return 'bg-success'
      if (average >= 70) return 'bg-info'
      if (average >= 60) return 'bg-warning'
      return 'bg-danger'
    },

    addNote() {
      // Navigate to add note or show modal
      this.$router.push(`/advisor/advisee/${this.studentId}`)
    },

    scheduleTeeting() {
      this.$store.dispatch('showToast', {
        message: 'Meeting scheduling feature coming soon!',
        type: 'info'
      })
    },

    sendAlert() {
      this.$store.dispatch('showToast', {
        message: 'Alert notification feature coming soon!',
        type: 'info'
      })
    }
  }
}
</script>

<style scoped>
.student-avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: bold;
  margin: 0 auto;
}

.quick-stats {
  display: flex;
  gap: 20px;
}

.stat-item {
  text-align: center;
}

.stat-value {
  display: block;
  font-size: 1.5rem;
  font-weight: bold;
  color: #2c3e50;
}

.stat-label {
  display: block;
  font-size: 0.8rem;
  color: #7f8c8d;
  text-transform: uppercase;
}

.performance-summary {
  max-height: 300px;
  overflow-y: auto;
}

.summary-item {
  padding: 10px;
  border-bottom: 1px solid #eee;
}

.summary-item:last-child {
  border-bottom: none;
}

.card {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border: none;
  border-radius: 10px;
}

.card-title {
  color: #2c3e50;
  font-weight: 600;
}
</style>
