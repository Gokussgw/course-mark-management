<template>
  <div class="csv-import">
    <h1 class="mb-4">Import Marks from CSV</h1>
    
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title">CSV Upload Instructions</h5>
        <p>Please follow these guidelines to ensure your CSV file is imported correctly:</p>
        <ol>
          <li>Your CSV file should have headers in the first row</li>
          <li>Required columns: <code>student_id</code>, <code>mark</code></li>
          <li>Optional columns: <code>comments</code></li>
          <li>Student IDs should match those in the system</li>
          <li>Marks should be numeric values between 0 and the assessment's maximum mark</li>
        </ol>
        
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          <strong>Tip:</strong> You can download a template by clicking the button below
        </div>
        
        <button class="btn btn-outline-primary mb-4" @click="downloadTemplate">
          <i class="fas fa-download me-2"></i>
          Download CSV Template
        </button>
        
        <h5>Select Course and Assessment</h5>
        <div class="row mb-4">
          <div class="col-md-6">
            <div class="mb-3">
              <label for="courseSelect" class="form-label">Course</label>
              <select class="form-select" id="courseSelect" v-model="selectedCourseId" @change="loadAssessments">
                <option value="">-- Select Course --</option>
                <option v-for="course in courses" :key="course.id" :value="course.id">
                  {{ course.code }} - {{ course.name }}
                </option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label for="assessmentSelect" class="form-label">Assessment</label>
              <select class="form-select" id="assessmentSelect" v-model="selectedAssessmentId" :disabled="!selectedCourseId">
                <option value="">-- Select Assessment --</option>
                <option v-for="assessment in assessments" :key="assessment.id" :value="assessment.id">
                  {{ assessment.name }} ({{ assessment.type }})
                </option>
              </select>
            </div>
          </div>
        </div>
        
        <h5>Upload CSV File</h5>
        <div class="mb-3">
          <input 
            type="file" 
            class="form-control" 
            id="csvFile" 
            accept=".csv" 
            @change="handleFileUpload"
            :disabled="!selectedAssessmentId"
          >
        </div>
        
        <div v-if="filePreview.length > 0" class="mt-4">
          <h5>Preview</h5>
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th v-for="(header, index) in filePreview[0]" :key="index">{{ header }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, rowIndex) in filePreview.slice(1, 6)" :key="rowIndex">
                  <td v-for="(cell, cellIndex) in row" :key="cellIndex">{{ cell }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="text-muted" v-if="filePreview.length > 6">
            <em>Showing first 5 rows of {{ filePreview.length - 1 }} total rows</em>
          </div>
        </div>
        
        <div class="mt-4 d-flex justify-content-between">
          <router-link to="/lecturer/dashboard" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Back to Dashboard
          </router-link>
          <button 
            class="btn btn-primary" 
            @click="importCSV" 
            :disabled="!csvFile || !selectedAssessmentId || isLoading"
          >
            <div v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <i v-else class="fas fa-upload me-2"></i>
            Import Marks
          </button>
        </div>
      </div>
    </div>
    
    <!-- Results section, shows after import -->
    <div v-if="importResults.success > 0 || importResults.errors.length > 0" class="card">
      <div class="card-body">
        <h5 class="card-title">Import Results</h5>
        
        <div v-if="importResults.success > 0" class="alert alert-success">
          <i class="fas fa-check-circle me-2"></i>
          Successfully imported {{ importResults.success }} student marks
        </div>
        
        <div v-if="importResults.errors.length > 0" class="alert alert-danger">
          <h6><i class="fas fa-exclamation-triangle me-2"></i> Errors occurred during import:</h6>
          <ul class="mb-0">
            <li v-for="(error, index) in importResults.errors" :key="index">{{ error }}</li>
          </ul>
        </div>
        
        <button class="btn btn-primary" @click="notifyStudents" v-if="importResults.success > 0">
          <i class="fas fa-bell me-2"></i>
          Notify Students
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import Papa from 'papaparse';

export default {
  name: 'CSVImport',
  data() {
    return {
      selectedCourseId: '',
      selectedAssessmentId: '',
      courses: [],
      assessments: [],
      csvFile: null,
      filePreview: [],
      isLoading: false,
      importResults: {
        success: 0,
        errors: []
      }
    };
  },
  computed: {
    ...mapGetters(['isLoading']),
    ...mapGetters('auth', ['getUser'])
  },
  created() {
    this.loadCourses();
  },
  methods: {
    async loadCourses() {
      try {
        this.courses = await this.$store.dispatch('courses/fetchCourses', {
          lecturerId: this.getUser.id
        });
      } catch (error) {
        console.error('Error loading courses:', error);
      }
    },
    
    async loadAssessments() {
      if (!this.selectedCourseId) {
        this.assessments = [];
        this.selectedAssessmentId = '';
        return;
      }
      
      try {
        this.assessments = await this.$store.dispatch('assessments/fetchAssessments', {
          courseId: this.selectedCourseId
        });
      } catch (error) {
        console.error('Error loading assessments:', error);
      }
    },
    
    handleFileUpload(event) {
      this.csvFile = event.target.files[0];
      if (!this.csvFile) return;
      
      // Parse CSV for preview
      Papa.parse(this.csvFile, {
        complete: (results) => {
          this.filePreview = results.data;
        },
        header: false
      });
    },
    
    downloadTemplate() {
      // Generate a CSV template based on the selected assessment
      const headers = ['student_id', 'mark', 'comments'];
      let csvContent = headers.join(',') + '\n';
      csvContent += '12345,85,"Good work"\n';
      csvContent += '12346,78,"Needs improvement on section 3"\n';
      
      // Create a Blob and download
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'marks_template.csv');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },
    
    async importCSV() {
      if (!this.csvFile || !this.selectedAssessmentId) return;
      
      this.isLoading = true;
      this.importResults = { success: 0, errors: [] };
      
      // Parse CSV data
      Papa.parse(this.csvFile, {
        header: true,
        skipEmptyLines: true,
        complete: async (results) => {
          // Check required headers
          const requiredHeaders = ['student_id', 'mark'];
          const headers = results.meta.fields;
          
          if (!requiredHeaders.every(header => headers.includes(header))) {
            this.importResults.errors.push('CSV is missing required headers: student_id, mark');
            this.isLoading = false;
            return;
          }
          
          // Process each row
          for (const row of results.data) {
            try {
              // In a real app, this would make an API call to save the mark
              // Simulating API call with a delay
              await new Promise(resolve => setTimeout(resolve, 100));
              
              // Validate mark
              const mark = parseFloat(row.mark);
              if (isNaN(mark) || mark < 0) {
                this.importResults.errors.push(`Invalid mark for student ID ${row.student_id}: ${row.mark}`);
                continue;
              }
              
              // Success for this row
              this.importResults.success++;
            } catch (error) {
              this.importResults.errors.push(`Error processing student ID ${row.student_id}: ${error.message}`);
            }
          }
          
          this.isLoading = false;
        },
        error: (error) => {
          this.importResults.errors.push(`Error parsing CSV: ${error}`);
          this.isLoading = false;
        }
      });
    },
    
    async notifyStudents() {
      // In a real app, this would send notifications to all students with new marks
      alert(`Notification sent to ${this.importResults.success} students about their new marks`);
    }
  }
};
</script>

<style scoped>
.csv-import h1 {
  color: #2c3e50;
  font-weight: 700;
}

.card {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border: none;
  border-radius: 10px;
}

.table {
  font-size: 0.9rem;
}

code {
  background-color: #f8f9fa;
  padding: 0.2rem 0.4rem;
  border-radius: 0.25rem;
  font-size: 0.875em;
}
</style>
