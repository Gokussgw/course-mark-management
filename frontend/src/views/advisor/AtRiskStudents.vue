<template>
  <div class="at-risk">
    <h1 class="mb-4">At-Risk Students</h1>
    
    <div class="row mb-4">
      <div class="col-md-9">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h5 class="card-title mb-0">Students Requiring Attention</h5>
              
              <div class="d-flex">
                <div class="input-group me-2" style="width: 250px;">
                  <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search text-muted"></i>
                  </span>
                  <input 
                    type="text" 
                    class="form-control border-start-0"
                    placeholder="Search students..."
                    v-model="searchQuery"
                  >
                </div>
                
                <button class="btn btn-outline-success" @click="exportToCSV">
                  <i class="fas fa-file-export me-2"></i>
                  Export Report
                </button>
              </div>
            </div>
            
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="table-light">
                  <tr>
                    <th>
                      <div class="d-flex align-items-center">
                        <span>Student</span>
                        <button class="btn btn-sm text-muted ms-1 p-0" @click="sortBy('name')">
                          <i class="fas" :class="getSortIconClass('name')"></i>
                        </button>
                      </div>
                    </th>
                    <th>
                      <div class="d-flex align-items-center">
                        <span>Matric No.</span>
                      </div>
                    </th>
                    <th>
                      <div class="d-flex align-items-center">
                        <span>Average</span>
                        <button class="btn btn-sm text-muted ms-1 p-0" @click="sortBy('average')">
                          <i class="fas" :class="getSortIconClass('average')"></i>
                        </button>
                      </div>
                    </th>
                    <th>
                      <div class="d-flex align-items-center">
                        <span>Risk Level</span>
                        <button class="btn btn-sm text-muted ms-1 p-0" @click="sortBy('riskLevel')">
                          <i class="fas" :class="getSortIconClass('riskLevel')"></i>
                        </button>
                      </div>
                    </th>
                    <th>Risk Factors</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="student in sortedStudents" :key="student.id">
                    <td>{{ student.name }}</td>
                    <td>{{ student.matricNumber }}</td>
                    <td>
                      <span 
                        :class="{
                          'text-danger': student.average < 45,
                          'text-warning': student.average >= 45 && student.average < 60,
                          'text-success': student.average >= 60
                        }"
                      >
                        {{ student.average.toFixed(1) }}%
                      </span>
                    </td>
                    <td>
                      <span class="badge" :class="getRiskBadgeClass(student.riskLevel)">
                        {{ student.riskLevel.toUpperCase() }}
                      </span>
                    </td>
                    <td>
                      <div v-for="(factor, index) in student.riskFactors" :key="index" class="risk-factor-item">
                        <i class="fas fa-exclamation-triangle me-1" :class="`text-${factor.severity}`"></i>
                        <span>{{ factor.description }}</span>
                      </div>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm">
                        <router-link 
                          :to="`/advisor/advisee/${student.id}`"
                          class="btn btn-outline-primary" 
                          title="View Student Profile"
                        >
                          <i class="fas fa-user"></i>
                        </router-link>
                        <button 
                          class="btn btn-outline-info" 
                          title="Add Note"
                          @click="openAddNoteModal(student)"
                        >
                          <i class="fas fa-sticky-note"></i>
                        </button>
                        <button 
                          class="btn btn-outline-success"
                          title="Contact Student"
                          @click="contactStudent(student)"
                        >
                          <i class="fas fa-envelope"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <div v-if="filteredStudents.length === 0" class="text-center my-5">
              <p class="text-muted">No students found matching your criteria.</p>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title">Risk Filters</h5>
            <div class="mb-3">
              <label class="form-label">Risk Level</label>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="highRisk" v-model="filters.highRisk">
                <label class="form-check-label" for="highRisk">
                  High Risk
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="mediumRisk" v-model="filters.mediumRisk">
                <label class="form-check-label" for="mediumRisk">
                  Medium Risk
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="lowRisk" v-model="filters.lowRisk">
                <label class="form-check-label" for="lowRisk">
                  Low Risk
                </label>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Risk Factors</label>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="lowGrades" v-model="filters.lowGrades">
                <label class="form-check-label" for="lowGrades">
                  Low Grades
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="missedAssessments" v-model="filters.missedAssessments">
                <label class="form-check-label" for="missedAssessments">
                  Missed Assessments
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="attendance" v-model="filters.attendance">
                <label class="form-check-label" for="attendance">
                  Poor Attendance
                </label>
              </div>
            </div>
            
            <div class="d-grid">
              <button class="btn btn-primary" @click="resetFilters">Reset Filters</button>
            </div>
          </div>
        </div>
        
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Summary</h5>
            <div class="mb-3">
              <div class="d-flex justify-content-between mb-2">
                <span>High Risk:</span>
                <span class="badge bg-danger">{{ riskCounts.high }}</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>Medium Risk:</span>
                <span class="badge bg-warning">{{ riskCounts.medium }}</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>Low Risk:</span>
                <span class="badge bg-success">{{ riskCounts.low }}</span>
              </div>
            </div>
            
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              You have {{ newNotesCount }} students with notes added in the last 7 days.
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Add Note Modal -->
    <div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addNoteModalLabel">
              Add Note for {{ selectedStudent ? selectedStudent.name : '' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveNote">
              <div class="mb-3">
                <label for="noteContent" class="form-label">Note</label>
                <textarea 
                  class="form-control" 
                  id="noteContent" 
                  rows="5" 
                  v-model="newNote" 
                  placeholder="Enter your observation or action plan for this student..."
                  required
                ></textarea>
              </div>
              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Note</button>
              </div>
            </form>
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
  name: 'AtRiskStudents',
  data() {
    return {
      students: [
        {
          id: 1,
          name: 'John Smith',
          matricNumber: 'A12345',
          average: 38.5,
          riskLevel: 'high',
          riskFactors: [
            { description: 'Failed 2 assessments', severity: 'danger' },
            { description: 'Missed 3 classes', severity: 'warning' },
            { description: 'Average below 40%', severity: 'danger' }
          ]
        },
        {
          id: 2,
          name: 'Mary Johnson',
          matricNumber: 'A12346',
          average: 52.7,
          riskLevel: 'medium',
          riskFactors: [
            { description: 'Failed 1 assessment', severity: 'warning' },
            { description: 'Average below 60%', severity: 'warning' }
          ]
        },
        {
          id: 3,
          name: 'Robert Williams',
          matricNumber: 'A12347',
          average: 45.2,
          riskLevel: 'medium',
          riskFactors: [
            { description: 'Missed deadline for 2 assignments', severity: 'warning' },
            { description: 'Average below 50%', severity: 'warning' }
          ]
        },
        {
          id: 4,
          name: 'Sarah Brown',
          matricNumber: 'A12348',
          average: 65.8,
          riskLevel: 'low',
          riskFactors: [
            { description: 'Declining performance trend', severity: 'info' }
          ]
        },
        {
          id: 5,
          name: 'Michael Davis',
          matricNumber: 'A12349',
          average: 31.2,
          riskLevel: 'high',
          riskFactors: [
            { description: 'Failed 3 assessments', severity: 'danger' },
            { description: 'No submissions for 2 assignments', severity: 'danger' },
            { description: 'Poor attendance (30%)', severity: 'danger' }
          ]
        }
      ],
      filters: {
        highRisk: true,
        mediumRisk: true,
        lowRisk: true,
        lowGrades: false,
        missedAssessments: false,
        attendance: false
      },
      searchQuery: '',
      sortField: 'riskLevel',
      sortDirection: 'asc',
      selectedStudent: null,
      newNote: '',
      newNotesCount: 2
    };
  },
  computed: {
    ...mapGetters(['isLoading']),
    ...mapGetters('auth', ['getUser']),
    
    filteredStudents() {
      return this.students.filter(student => {
        // Risk level filter
        if (!this.filters.highRisk && student.riskLevel === 'high') return false;
        if (!this.filters.mediumRisk && student.riskLevel === 'medium') return false;
        if (!this.filters.lowRisk && student.riskLevel === 'low') return false;
        
        // Risk factors filter
        if (this.filters.lowGrades && !student.riskFactors.some(f => f.description.includes('below') || f.description.includes('Failed'))) {
          return false;
        }
        if (this.filters.missedAssessments && !student.riskFactors.some(f => f.description.includes('missed') || f.description.includes('submission'))) {
          return false;
        }
        if (this.filters.attendance && !student.riskFactors.some(f => f.description.includes('attendance') || f.description.includes('classes'))) {
          return false;
        }
        
        // Search query
        if (this.searchQuery && !student.name.toLowerCase().includes(this.searchQuery.toLowerCase()) && 
            !student.matricNumber.toLowerCase().includes(this.searchQuery.toLowerCase())) {
          return false;
        }
        
        return true;
      });
    },
    
    sortedStudents() {
      return [...this.filteredStudents].sort((a, b) => {
        let comparison = 0;
        if (this.sortField === 'name') {
          comparison = a.name.localeCompare(b.name);
        } else if (this.sortField === 'average') {
          comparison = a.average - b.average;
        } else if (this.sortField === 'riskLevel') {
          const riskValue = { 'high': 3, 'medium': 2, 'low': 1 };
          comparison = riskValue[a.riskLevel] - riskValue[b.riskLevel];
        }
        
        return this.sortDirection === 'asc' ? comparison : -comparison;
      });
    },
    
    riskCounts() {
      const counts = { high: 0, medium: 0, low: 0 };
      this.students.forEach(student => {
        counts[student.riskLevel]++;
      });
      return counts;
    }
  },
  methods: {
    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'asc';
      }
    },
    
    getSortIconClass(field) {
      if (this.sortField !== field) return 'fa-sort';
      return this.sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
    },
    
    getRiskBadgeClass(level) {
      if (level === 'high') return 'bg-danger';
      if (level === 'medium') return 'bg-warning';
      return 'bg-success';
    },
    
    resetFilters() {
      this.filters = {
        highRisk: true,
        mediumRisk: true,
        lowRisk: true,
        lowGrades: false,
        missedAssessments: false,
        attendance: false
      };
      this.searchQuery = '';
    },
    
    openAddNoteModal(student) {
      this.selectedStudent = student;
      this.newNote = '';
      
      // Open modal
      const modal = new bootstrap.Modal(document.getElementById('addNoteModal'));
      modal.show();
    },
    
    async saveNote() {
      if (!this.newNote.trim() || !this.selectedStudent) return;
      
      try {
        // In a real app, this would be an API call
        console.log(`Saving note for student ${this.selectedStudent.id}: ${this.newNote}`);
        
        // Close modal
        const modalElement = document.getElementById('addNoteModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        modal.hide();
        
        // Show success message
        alert('Note saved successfully');
        
        // Update notes count (in a real app this would come from the API)
        this.newNotesCount++;
      } catch (error) {
        console.error('Error saving note:', error);
        alert('Failed to save note. Please try again.');
      }
    },
    
    contactStudent(student) {
      // In a real app, this might open an email client or messaging system
      alert(`Contact feature for ${student.name} would open here`);
    },
    
    exportToCSV() {
      // Convert students data to CSV format
      const headers = ['Name', 'Matric Number', 'Average', 'Risk Level', 'Risk Factors'];
      let csvContent = headers.join(',') + '\n';
      
      this.sortedStudents.forEach(student => {
        const riskFactors = student.riskFactors.map(f => f.description).join('; ');
        const row = [
          `"${student.name}"`,
          student.matricNumber,
          student.average.toFixed(1),
          student.riskLevel.toUpperCase(),
          `"${riskFactors}"`
        ];
        csvContent += row.join(',') + '\n';
      });
      
      // Create and trigger download
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'at-risk-students.csv');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  }
};
</script>

<style scoped>
.at-risk h1 {
  color: #2c3e50;
  font-weight: 700;
}

.card {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border: none;
  border-radius: 10px;
}

.table th {
  font-weight: 600;
}

.risk-factor-item {
  margin-bottom: 0.3rem;
  font-size: 0.85rem;
}

.risk-factor-item:last-child {
  margin-bottom: 0;
}

.badge {
  padding: 0.5rem 0.75rem;
  font-weight: 500;
}

.form-check-input:checked {
  background-color: #2c3e50;
  border-color: #2c3e50;
}

.form-check-input:focus {
  border-color: #2c3e50;
  box-shadow: 0 0 0 0.25rem rgba(44, 62, 80, 0.25);
}
</style>
