<template>
  <div class="advisee-reports">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="mb-2">Advisee Reports</h1>
        <p class="text-muted">Comprehensive academic performance reports for your advisees</p>
      </div>
      <div class="d-flex gap-2">
        <button 
          class="btn btn-outline-success" 
          @click="exportToCSV"
          :disabled="isLoading"
        >
          <i class="fas fa-download me-2"></i>
          Export CSV
        </button>
        <button 
          class="btn btn-primary" 
          @click="loadReports"
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
      <p class="mt-3 text-muted">Loading advisee reports...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger">
      <i class="fas fa-exclamation-triangle me-2"></i>
      {{ error }}
    </div>

    <!-- Main Content -->
    <div v-else-if="reportsData">
      <!-- Summary Cards -->
      <div class="row mb-4">
        <div class="col-md-3">
          <div class="card bg-primary text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <h6 class="card-title">Total Advisees</h6>
                  <h3 class="mb-0">{{ reportsData.summary.total_advisees }}</h3>
                </div>
                <div class="align-self-center">
                  <i class="fas fa-users fa-2x opacity-75"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card bg-success text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <h6 class="card-title">Average GPA</h6>
                  <h3 class="mb-0">{{ reportsData.summary.avg_gpa.toFixed(2) }}</h3>
                </div>
                <div class="align-self-center">
                  <i class="fas fa-chart-line fa-2x opacity-75"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card bg-warning text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <h6 class="card-title">At Risk Students</h6>
                  <h3 class="mb-0">{{ reportsData.summary.at_risk_count }}</h3>
                </div>
                <div class="align-self-center">
                  <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card bg-info text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <h6 class="card-title">Excellence</h6>
                  <h3 class="mb-0">{{ reportsData.summary.excellent_performers || 0 }}</h3>
                </div>
                <div class="align-self-center">
                  <i class="fas fa-star fa-2x opacity-75"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="card mb-4">
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <div class="input-group">
                <span class="input-group-text">
                  <i class="fas fa-search"></i>
                </span>
                <input 
                  type="text" 
                  class="form-control" 
                  placeholder="Search by name or matric number..."
                  v-model="searchQuery"
                >
              </div>
            </div>
            <div class="col-md-3">
              <select class="form-select" v-model="filterByRisk">
                <option value="">All Students</option>
                <option value="at_risk">At Risk Students</option>
                <option value="needs_attention">Needs Attention</option>
                <option value="good">Good Performance</option>
                <option value="excellent">Excellent</option>
              </select>
            </div>
            <div class="col-md-3">
              <select class="form-select" v-model="sortBy">
                <option value="name">Sort by Name</option>
                <option value="gpa_desc">Sort by GPA (High to Low)</option>
                <option value="gpa_asc">Sort by GPA (Low to High)</option>
                <option value="risk">Sort by Risk Level</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Advisee Reports Table -->
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Individual Advisee Performance</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Student</th>
                  <th>Matric Number</th>
                  <th>Courses</th>
                  <th>Overall GPA</th>
                  <th>Grade Distribution</th>
                  <th>Performance Trend</th>
                  <th>Risk Level</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="advisee in filteredAdvisees" :key="advisee.id">
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="avatar-circle me-3">
                        {{ getInitials(advisee.name) }}
                      </div>
                      <div>
                        <div class="fw-bold">{{ advisee.name }}</div>
                        <small class="text-muted">{{ advisee.email }}</small>
                      </div>
                    </div>
                  </td>
                  <td>{{ advisee.matric_number }}</td>
                  <td>
                    <span class="badge bg-light text-dark">
                      {{ advisee.completed_courses }}/{{ advisee.total_courses }}
                    </span>
                  </td>
                  <td>
                    <span 
                      class="badge fs-6" 
                      :class="getGPABadgeClass(advisee.overall_gpa)"
                    >
                      {{ (advisee.overall_gpa || 0).toFixed(2) }}
                    </span>
                  </td>
                  <td>
                    <div class="grade-distribution">
                      <span v-if="advisee.a_grades" class="badge bg-success me-1">A: {{ advisee.a_grades }}</span>
                      <span v-if="advisee.b_grades" class="badge bg-primary me-1">B: {{ advisee.b_grades }}</span>
                      <span v-if="advisee.c_grades" class="badge bg-warning me-1">C: {{ advisee.c_grades }}</span>
                      <span v-if="advisee.d_grades" class="badge bg-danger me-1">D: {{ advisee.d_grades }}</span>
                      <span v-if="advisee.f_grades" class="badge bg-dark me-1">F: {{ advisee.f_grades }}</span>
                    </div>
                  </td>
                  <td>
                    <span 
                      class="badge" 
                      :class="getTrendBadgeClass(advisee.performance_trend)"
                    >
                      <i :class="getTrendIcon(advisee.performance_trend)" class="me-1"></i>
                      {{ getTrendText(advisee.performance_trend) }}
                    </span>
                  </td>
                  <td>
                    <span 
                      class="badge" 
                      :class="getRiskBadgeClass(advisee.risk_indicators)"
                    >
                      {{ getRiskLevel(advisee.risk_indicators) }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group" role="group">
                      <button 
                        class="btn btn-sm btn-outline-primary"
                        @click="viewDetailedReport(advisee.id)"
                        title="View Detailed Report"
                      >
                        <i class="fas fa-chart-bar"></i>
                      </button>
                      <button 
                        class="btn btn-sm btn-outline-info"
                        @click="viewSuggestions(advisee)"
                        title="View Suggestions"
                      >
                        <i class="fas fa-lightbulb"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredAdvisees.length === 0" class="text-center py-5">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">No advisees found</h5>
        <p class="text-muted">{{ searchQuery ? 'Try adjusting your search criteria' : 'You have no advisees assigned yet' }}</p>
      </div>
    </div>

    <!-- Suggestions Modal -->
    <div class="modal fade" v-show="showSuggestionsModal" :class="{ show: showSuggestionsModal }" style="display: block;" tabindex="-1" v-if="showSuggestionsModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Academic Recommendations</h5>
            <button type="button" class="btn-close" @click="showSuggestionsModal = false"></button>
          </div>
          <div class="modal-body" v-if="selectedAdvisee">
            <h6>{{ selectedAdvisee.name }}</h6>
            <div class="alert alert-info">
              <h6>Risk Indicators:</h6>
              <ul class="mb-0">
                <li v-for="indicator in selectedAdvisee.risk_indicators" :key="indicator">
                  {{ formatRiskIndicator(indicator) }}
                </li>
                <li v-if="selectedAdvisee.risk_indicators.length === 0" class="text-success">
                  No significant risk indicators identified
                </li>
              </ul>
            </div>
            <h6>Recommendations:</h6>
            <ul class="list-group list-group-flush">
              <li v-for="suggestion in selectedAdvisee.suggestions" :key="suggestion" class="list-group-item">
                <i class="fas fa-arrow-right text-primary me-2"></i>
                {{ suggestion }}
              </li>
            </ul>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showSuggestionsModal = false">Close</button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal backdrop -->
    <div class="modal-backdrop fade" :class="{ show: showSuggestionsModal }" v-if="showSuggestionsModal" @click="showSuggestionsModal = false"></div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
  name: 'AdviseeReports',
  data() {
    return {
      isLoading: true,
      error: null,
      reportsData: null,
      searchQuery: '',
      filterByRisk: '',
      sortBy: 'name',
      selectedAdvisee: null,
      showSuggestionsModal: false
    }
  },
  computed: {
    ...mapGetters("auth", ["getUser"]),
    filteredAdvisees() {
      if (!this.reportsData || !this.reportsData.advisees) return []
      
      let filtered = [...this.reportsData.advisees]
      
      // Apply search filter
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(advisee => 
          advisee.name.toLowerCase().includes(query) ||
          advisee.matric_number.toLowerCase().includes(query) ||
          advisee.email.toLowerCase().includes(query)
        )
      }
      
      // Apply risk filter
      if (this.filterByRisk) {
        filtered = filtered.filter(advisee => {
          const gpa = advisee.overall_gpa || 0
          switch (this.filterByRisk) {
            case 'at_risk':
              return gpa < 2.0
            case 'needs_attention':
              return gpa >= 2.0 && gpa < 3.0
            case 'good':
              return gpa >= 3.0 && gpa < 3.5
            case 'excellent':
              return gpa >= 3.5
            default:
              return true
          }
        })
      }
      
      // Apply sorting
      filtered.sort((a, b) => {
        switch (this.sortBy) {
          case 'name':
            return a.name.localeCompare(b.name)
          case 'gpa_desc':
            return (b.overall_gpa || 0) - (a.overall_gpa || 0)
          case 'gpa_asc':
            return (a.overall_gpa || 0) - (b.overall_gpa || 0)
          case 'risk':
            return this.getRiskScore(b.risk_indicators) - this.getRiskScore(a.risk_indicators)
          default:
            return 0
        }
      })
      
      return filtered
    }
  },
  async created() {
    await this.loadReports()
  },
  methods: {
    async loadReports() {
      this.isLoading = true
      this.error = null
      
      try {
        const token = localStorage.getItem('token')
        const response = await fetch('http://localhost:8000/api/advisee-reports/comprehensive', {
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
          this.reportsData = data.data
        } else {
          this.error = data.error || 'Failed to load advisee reports'
        }
      } catch (error) {
        console.error('Error loading advisee reports:', error)
        this.error = 'Failed to load advisee reports. Please try again later.'
      } finally {
        this.isLoading = false
      }
    },
    
    async exportToCSV() {
      try {
        const token = localStorage.getItem('token')
        const response = await fetch('http://localhost:8000/api/advisee-reports/export/csv', {
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
        a.download = `advisee_reports_${new Date().toISOString().split('T')[0]}.csv`
        document.body.appendChild(a)
        a.click()
        window.URL.revokeObjectURL(url)
        document.body.removeChild(a)
      } catch (error) {
        console.error('Error exporting CSV:', error)
        alert('Failed to export CSV. Please try again later.')
      }
    },
    
    viewDetailedReport(studentId) {
      this.$router.push(`/advisor/advisee-report/${studentId}`)
    },
    
    viewSuggestions(advisee) {
      this.selectedAdvisee = advisee
      this.showSuggestionsModal = true
    },
    
    getInitials(name) {
      return name.split(' ').map(n => n[0]).join('').toUpperCase()
    },
    
    getGPABadgeClass(gpa) {
      if (!gpa) return 'bg-secondary'
      if (gpa >= 3.5) return 'bg-success'
      if (gpa >= 3.0) return 'bg-primary'
      if (gpa >= 2.5) return 'bg-warning'
      if (gpa >= 2.0) return 'bg-orange'
      return 'bg-danger'
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
    
    getRiskBadgeClass(riskIndicators) {
      const riskLevel = this.getRiskScore(riskIndicators)
      if (riskLevel >= 3) return 'bg-danger'
      if (riskLevel >= 2) return 'bg-warning'
      if (riskLevel >= 1) return 'bg-info'
      return 'bg-success'
    },
    
    getRiskLevel(riskIndicators) {
      const riskScore = this.getRiskScore(riskIndicators)
      if (riskScore >= 3) return 'High Risk'
      if (riskScore >= 2) return 'Medium Risk'
      if (riskScore >= 1) return 'Low Risk'
      return 'No Risk'
    },
    
    getRiskScore(riskIndicators) {
      return riskIndicators ? riskIndicators.length : 0
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
    }
  }
}
</script>

<style scoped>
.advisee-reports {
  padding: 20px;
}

.avatar-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 14px;
}

.grade-distribution .badge {
  font-size: 0.7rem;
}

.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border: 1px solid rgba(0, 0, 0, 0.125);
}

.table th {
  font-weight: 600;
  border-bottom: 2px solid #dee2e6;
}

.btn-group .btn {
  border-radius: 0.25rem;
  margin-right: 0.25rem;
}

.btn-group .btn:last-child {
  margin-right: 0;
}

@media (max-width: 768px) {
  .d-flex.gap-2 {
    flex-direction: column;
    gap: 0.5rem !important;
  }
}
</style>
