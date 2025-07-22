<template>
  <div class="student-ranking-component">
    <!-- Individual Student Ranking Card -->
    <div v-if="showIndividualRanking" class="card mb-4">
      <div class="card-body">
        <h5 class="card-title">
          <i class="fas fa-trophy me-2"></i>
          {{ isOwnRanking ? 'My Academic Ranking' : `${studentName}'s Academic Ranking` }}
        </h5>
        
        <div v-if="loadingIndividual" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading ranking...</span>
          </div>
        </div>

        <div v-else-if="individualRanking" class="row">
          <div class="col-md-4">
            <div class="ranking-card overall-rank">
              <div class="ranking-number">{{ individualRanking.overall_rank }}</div>
              <div class="ranking-label">Overall Rank</div>
              <div class="ranking-detail">out of {{ individualRanking.total_students }} students</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="ranking-card gpa-card">
              <div class="ranking-number">{{ individualRanking.gpa }}%</div>
              <div class="ranking-label">Current GPA</div>
              <div class="ranking-detail">{{ individualRanking.courses_taken }} courses taken</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="ranking-card assessments-card">
              <div class="ranking-number">{{ individualRanking.assessments_completed }}</div>
              <div class="ranking-label">Assessments</div>
              <div class="ranking-detail">completed</div>
            </div>
          </div>
        </div>

        <!-- Course-specific rankings -->
        <div v-if="individualRanking && individualRanking.course_rankings && individualRanking.course_rankings.length > 0" class="mt-4">
          <h6>Course Rankings</h6>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Course</th>
                  <th>Average</th>
                  <th>Rank</th>
                  <th>Students</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="course in individualRanking.course_rankings" :key="course.course_id">
                  <td>
                    <strong>{{ course.code }}</strong><br>
                    <small class="text-muted">{{ course.course_name }}</small>
                  </td>
                  <td>{{ course.course_average }}%</td>
                  <td>
                    <span class="badge" :class="getCourseRankBadgeClass(course.course_rank, course.students_in_course)">
                      #{{ course.course_rank }}
                    </span>
                  </td>
                  <td>{{ course.students_in_course }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Class Rankings Table -->
    <div v-if="showClassRankings" class="card">
      <div class="card-body">
        <h5 class="card-title">
          <i class="fas fa-list-ol me-2"></i>
          Class Rankings
        </h5>
        
        <div v-if="loadingClass" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading rankings...</span>
          </div>
        </div>

        <div v-else-if="classRankings.length > 0" class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Rank</th>
                <th>Student</th>
                <th>Matric Number</th>
                <th>GPA</th>
                <th>Courses</th>
                <th>Assessments</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="student in classRankings" :key="student.id" 
                  :class="{ 'table-warning': isCurrentUser(student.id) }">
                <td>
                  <span class="rank-badge" :class="getRankBadgeClass(student.overall_rank)">
                    #{{ student.overall_rank }}
                  </span>
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm me-2">
                      {{ getInitials(student.name) }}
                    </div>
                    <div>
                      {{ student.name }}
                      <span v-if="isCurrentUser(student.id)" class="badge bg-primary ms-2">You</span>
                    </div>
                  </div>
                </td>
                <td>{{ student.matric_number }}</td>
                <td>
                  <strong :class="getGpaClass(student.gpa)">{{ student.gpa }}%</strong>
                </td>
                <td>{{ student.courses_taken }}</td>
                <td>{{ student.assessments_completed }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="!loadingClass && classRankings.length > 0" class="d-flex justify-content-between align-items-center mt-3">
          <button class="btn btn-outline-secondary" @click="loadPreviousPage" :disabled="currentPage === 1">
            <i class="fas fa-chevron-left me-1"></i> Previous
          </button>
          <span class="text-muted">Page {{ currentPage }}</span>
          <button class="btn btn-outline-secondary" @click="loadNextPage" :disabled="classRankings.length < pageSize">
            Next <i class="fas fa-chevron-right ms-1"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
  name: 'StudentRanking',
  props: {
    studentId: {
      type: [Number, String],
      default: null
    },
    showIndividualRanking: {
      type: Boolean,
      default: true
    },
    showClassRankings: {
      type: Boolean,
      default: true
    },
    isOwnRanking: {
      type: Boolean,
      default: true
    },
    studentName: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      individualRanking: null,
      classRankings: [],
      loadingIndividual: false,
      loadingClass: false,
      currentPage: 1,
      pageSize: 20,
      error: null
    }
  },
  computed: {
    ...mapGetters('auth', ['getUser']),
    
    currentUser() {
      return this.getUser || {}
    },
    
    targetStudentId() {
      return this.studentId || this.currentUser.id
    }
  },
  mounted() {
    this.loadRankingData()
  },
  watch: {
    studentId() {
      this.loadRankingData()
    }
  },
  methods: {
    async loadRankingData() {
      if (this.showIndividualRanking) {
        await this.loadIndividualRanking()
      }
      if (this.showClassRankings) {
        await this.loadClassRankings()
      }
    },

    async loadIndividualRanking() {
      if (!this.targetStudentId) return

      this.loadingIndividual = true
      try {
        const token = this.$store.state.auth.token
        if (!token) {
          throw new Error('No authentication token')
        }

        const response = await fetch(
          `http://localhost:8000/ranking-api.php?action=student_ranking&student_id=${this.targetStudentId}`,
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
          this.individualRanking = data.ranking
        } else {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to load individual ranking')
        }
      } catch (error) {
        console.error('Error loading individual ranking:', error)
        this.error = error.message
      } finally {
        this.loadingIndividual = false
      }
    },

    async loadClassRankings() {
      this.loadingClass = true
      try {
        const token = this.$store.state.auth.token
        if (!token) {
          throw new Error('No authentication token')
        }

        const offset = (this.currentPage - 1) * this.pageSize
        const response = await fetch(
          `http://localhost:8000/ranking-api.php?action=class_rankings&limit=${this.pageSize}&offset=${offset}`,
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
          this.classRankings = data.rankings
        } else {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to load class rankings')
        }
      } catch (error) {
        console.error('Error loading class rankings:', error)
        this.error = error.message
      } finally {
        this.loadingClass = false
      }
    },

    loadPreviousPage() {
      if (this.currentPage > 1) {
        this.currentPage--
        this.loadClassRankings()
      }
    },

    loadNextPage() {
      if (this.classRankings.length === this.pageSize) {
        this.currentPage++
        this.loadClassRankings()
      }
    },

    isCurrentUser(studentId) {
      return studentId == this.currentUser.id
    },

    getInitials(name) {
      return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
    },

    getRankBadgeClass(rank) {
      if (rank <= 3) return 'rank-badge-gold'
      if (rank <= 10) return 'rank-badge-silver'
      if (rank <= 25) return 'rank-badge-bronze'
      return 'rank-badge-default'
    },

    getCourseRankBadgeClass(rank, total) {
      const percentile = (rank / total) * 100
      if (percentile <= 10) return 'bg-success'
      if (percentile <= 25) return 'bg-info'
      if (percentile <= 50) return 'bg-warning'
      return 'bg-secondary'
    },

    getGpaClass(gpa) {
      if (gpa >= 80) return 'text-success'
      if (gpa >= 70) return 'text-info'
      if (gpa >= 60) return 'text-warning'
      return 'text-danger'
    }
  }
}
</script>

<style scoped>
.ranking-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 20px;
  border-radius: 15px;
  text-align: center;
  margin-bottom: 15px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.ranking-card.overall-rank {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.ranking-card.gpa-card {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.ranking-card.assessments-card {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.ranking-number {
  font-size: 2.5rem;
  font-weight: bold;
  margin-bottom: 5px;
}

.ranking-label {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 5px;
}

.ranking-detail {
  font-size: 0.9rem;
  opacity: 0.9;
}

.rank-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-weight: bold;
  font-size: 0.9rem;
}

.rank-badge-gold {
  background: linear-gradient(135deg, #ffd700, #ffed4e);
  color: #333;
}

.rank-badge-silver {
  background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
  color: #333;
}

.rank-badge-bronze {
  background: linear-gradient(135deg, #cd7f32, #daa520);
  color: white;
}

.rank-badge-default {
  background: #6c757d;
  color: white;
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background-color: #3498db;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: bold;
}

.table-warning {
  --bs-table-bg: #fff3cd;
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
