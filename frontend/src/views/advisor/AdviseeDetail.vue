<template>
  <div class="advisee-detail-container">
    <!-- Loading state -->
    <div v-if="loading" class="text-center my-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p>Loading advisee details...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="alert alert-danger">
      <i class="fas fa-exclamation-triangle me-2"></i>
      {{ error }}
      <div class="mt-2">
        <router-link to="/advisor/advisees" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left"></i> Back to Advisees
        </router-link>
      </div>
    </div>

    <!-- Content -->
    <div v-else-if="student">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <router-link to="/advisor/advisees" class="btn btn-outline-secondary mb-3">
            <i class="fas fa-arrow-left"></i> Back to Advisees
          </router-link>
          <h1>{{ student.fullName || 'Student Name Not Available' }}</h1>
          <p class="text-muted">{{ student.studentId || 'ID Not Available' }} | {{ student.program || 'Program Not Specified' }}</p>
        </div>
        <div class="student-contact">
          <div class="mb-2">
            <i class="fas fa-envelope"></i> {{ student.email || 'Email Not Available' }}
          </div>
          <div v-if="student.phone && student.phone !== 'Not provided'">
            <i class="fas fa-phone"></i> {{ student.phone }}
          </div>
        </div>
      </div>

      <!-- Student Overview -->
      <div class="row mb-5">
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0">Academic Overview</h5>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between mb-3">
                <strong>Current GPA:</strong>
                <span :class="{'text-success': student.gpa >= 3.0, 'text-warning': student.gpa < 3.0 && student.gpa >= 2.0, 'text-danger': student.gpa < 2.0}">
                  {{ student.gpa?.toFixed(2) || '0.00' }}
                </span>
              </div>
              <div class="d-flex justify-content-between mb-3">
                <strong>Completed Credits:</strong>
                <span>{{ student.completedCredits || 0 }}</span>
              </div>
              <div class="d-flex justify-content-between mb-3">
                <strong>Current Status:</strong>
                <span :class="{'text-success': student.status === 'Good Standing', 'text-warning': student.status === 'Warning', 'text-danger': student.status === 'Probation'}">
                  {{ student.status || 'Unknown' }}
                </span>
              </div>
              <div class="d-flex justify-content-between">
                <strong>Program Progress:</strong>
                <span>{{ student.completedCredits && student.totalCreditsRequired ? ((student.completedCredits / student.totalCreditsRequired) * 100).toFixed(1) : '0.0' }}%</span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0">Current Semester Summary</h5>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between mb-3">
                <strong>Enrolled Courses:</strong>
                <span>{{ student.enrolled_courses || 0 }}</span>
              </div>
              <div class="d-flex justify-content-between mb-3">
                <strong>Total Credits:</strong>
                <span>{{ totalCurrentCredits || 0 }}</span>
              </div>
              <div class="d-flex justify-content-between mb-3">
                <strong>At-Risk Courses:</strong>
                <span class="text-danger">{{ atRiskCourses.length }}</span>
              </div>
              <div class="d-flex justify-content-between">
                <strong>Projected GPA:</strong>
                <span :class="{'text-success': projectedGpa >= 3.0, 'text-warning': projectedGpa < 3.0 && projectedGpa >= 2.0, 'text-danger': projectedGpa < 2.0}">
                  {{ projectedGpa?.toFixed(2) || '0.00' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Current Courses -->
      <h2 class="mb-4">Current Courses</h2>
      <div class="mb-5">
        <div v-if="student.currentCourses && student.currentCourses.length > 0" class="table-responsive">
          <table class="table table-hover">
            <thead class="table-light">
              <tr>
                <th>Course</th>
                <th>Code</th>
                <th>Credits</th>
                <th>Lecturer</th>
                <th>Current Grade</th>
                <th>Progress</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="course in student.currentCourses" :key="course.id">
                <td>
                  <strong>{{ course.name }}</strong>
                  <br>
                  <small class="text-muted">{{ course.semester }}</small>
                </td>
                <td>{{ course.code }}</td>
                <td>{{ course.credits }}</td>
                <td>{{ course.lecturer }}</td>
                <td>
                  <span :class="{'text-success': course.currentMark >= 70, 'text-warning': course.currentMark < 70 && course.currentMark >= 50, 'text-danger': course.currentMark < 50}">
                    {{ course.currentMark }}%
                  </span>
                </td>
                <td>
                  <div class="progress" style="height: 20px;">
                    <div 
                      class="progress-bar" 
                      :class="{'bg-success': course.progress >= 80, 'bg-warning': course.progress < 80 && course.progress >= 50, 'bg-danger': course.progress < 50}"
                      :style="`width: ${course.progress}%`"
                    >
                      {{ course.progress }}%
                    </div>
                  </div>
                  <small class="text-muted">{{ course.completedAssessments }}/{{ course.totalAssessments }} assessments</small>
                </td>
                <td>
                  <span class="badge" :class="{'bg-success': course.currentMark >= 70, 'bg-warning': course.currentMark < 70 && course.currentMark >= 50, 'bg-danger': course.currentMark < 50}">
                    {{ course.status }}
                  </span>
                </td>
                <td>
                  <button class="btn btn-sm btn-primary" @click="viewCourseDetails(course)">
                    View Details
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          <strong>Course enrollment information loading...</strong><br>
          This student is enrolled in {{ student.enrolled_courses || 0 }} course(s). 
          <span v-if="student.enrolled_courses > 0">Detailed course information is being loaded.</span>
          <span v-else>No current enrollments found.</span>
        </div>
      </div>

      <!-- Previous Courses -->
      <h2 class="mb-4">Academic History</h2>
      <div class="card">
        <div class="card-header bg-light">
          <ul class="nav nav-pills" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="courses-tab" data-bs-toggle="tab" data-bs-target="#courses" type="button" role="tab" aria-controls="courses" aria-selected="true">
                Previous Courses
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="gpa-tab" data-bs-toggle="tab" data-bs-target="#gpa" type="button" role="tab" aria-controls="gpa" aria-selected="false">
                GPA Trend
              </button>
            </li>
          </ul>
        </div>
        <div class="card-body">
          <div class="tab-content">
            <!-- Previous Courses Tab -->
            <div class="tab-pane fade show active" id="courses" role="tabpanel" aria-labelledby="courses-tab">
              <div v-if="student.previousCourses.length === 0" class="alert alert-info">
                No previous course records found.
              </div>
              <div v-else class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Course</th>
                      <th>Code</th>
                      <th>Semester</th>
                      <th>Credits</th>
                      <th>Final Mark</th>
                      <th>Grade</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="course in student.previousCourses" :key="course.id">
                      <td>{{ course.name }}</td>
                      <td>{{ course.code }}</td>
                      <td>{{ course.semester }}</td>
                      <td>{{ course.credits }}</td>
                      <td>{{ course.finalMark }}%</td>
                      <td>{{ getGradeLetter(course.finalMark) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            
            <!-- GPA Trend Tab -->
            <div class="tab-pane fade" id="gpa" role="tabpanel" aria-labelledby="gpa-tab">
              <div class="chart-container">
                <canvas id="gpaChart" height="300"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Notes & Comments -->
      <div class="card mt-5">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">Advisor Notes</h5>
        </div>
        <div class="card-body">
          <div v-if="student.advisorNotes.length === 0" class="text-muted mb-4">
            No notes added yet.
          </div>
          <div v-else class="notes-list mb-4">
            <div v-for="(note, index) in student.advisorNotes" :key="index" class="note-item mb-3">
              <div class="note-header d-flex justify-content-between">
                <strong>{{ note.date }}</strong>
                <div>
                  <button class="btn btn-sm btn-link text-danger" @click="deleteNote(index)">Delete</button>
                </div>
              </div>
              <p class="note-content mb-0">{{ note.content }}</p>
            </div>
          </div>
          
          <form @submit.prevent="addNote">
            <div class="form-group">
              <label for="newNote" class="form-label">Add New Note</label>
              <textarea 
                id="newNote" 
                v-model="newNote" 
                class="form-control" 
                rows="3"
                placeholder="Add a note about this student..."
              ></textarea>
            </div>
            <div class="mt-3">
              <button type="submit" class="btn btn-primary" :disabled="!newNote.trim()">
                Add Note
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions } from 'vuex'
import Chart from 'chart.js/auto'
import axios from 'axios'

export default {
  name: 'AdviseeDetail',
  data() {
    return {
      student: null,
      loading: true,
      error: null,
      newNote: '',
      gpaChart: null
    }
  },
  computed: {
    studentId() {
      return this.$route.params.id;
    },
    totalCurrentCredits() {
      return this.student?.currentCourses.reduce((total, course) => total + course.credits, 0) || 0;
    },
    atRiskCourses() {
      return this.student?.currentCourses.filter(course => course.currentMark < 50) || [];
    },
    projectedGpa() {
      if (!this.student || !this.student.currentCourses.length) return 0;
      
      const totalCredits = this.totalCurrentCredits;
      const weightedSum = this.student.currentCourses.reduce((sum, course) => {
        return sum + (this.markToGpaPoints(course.currentMark) * course.credits);
      }, 0);
      
      return totalCredits > 0 ? weightedSum / totalCredits : 0;
    }
  },
  methods: {
    ...mapActions('users', ['fetchAdviseeById', 'updateAdviseeNotes']),
    
    async loadStudentData() {
      try {
        this.loading = true;
        console.log('Loading advisee data for ID:', this.studentId);
        
        const adviseeData = await this.fetchAdviseeById(this.studentId);
        console.log('Received advisee data:', adviseeData);
        
        // Transform the API response to match the component's expected structure
        this.student = {
          id: adviseeData.id,
          fullName: adviseeData.name,
          studentId: adviseeData.matric_number,
          email: adviseeData.email,
          phone: adviseeData.phone || 'Not provided',
          program: adviseeData.program || 'Not specified',
          gpa: parseFloat(adviseeData.gpa) || 0,
          completedCredits: adviseeData.completed_credits || 0,
          totalCreditsRequired: adviseeData.total_credits_required || 120,
          status: adviseeData.status || 'Unknown',
          currentCourses: [],
          previousCourses: adviseeData.previousCourses || [],
          advisorNotes: adviseeData.advisorNotes || [],
          enrolled_courses: adviseeData.enrolled_courses || 0
        };
        
        console.log('Transformed student data:', this.student);
        
        // Load detailed course information
        await this.loadStudentCourses();
        
        this.$nextTick(() => {
          this.initGpaChart();
        });
      } catch (err) {
        console.error('Error in loadStudentData:', err);
        this.error = 'Error loading student data: ' + (err.message || err);
      } finally {
        this.loading = false;
      }
    },

    async loadStudentCourses() {
      try {
        console.log('Loading course data for student ID:', this.studentId);
        
        const token = localStorage.getItem('token');
        const response = await axios.get(`http://localhost:3000/advisor-dashboard-api.php?action=advisee_courses&student_id=${this.studentId}`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        console.log('Course data response:', response.data);
        
        const courses = response.data.courses || [];
        
        // Transform courses data to match component expectations
        this.student.currentCourses = courses.map(course => ({
          id: course.id,
          name: course.name,
          code: course.code,
          credits: course.credits || 3,
          currentMark: course.current_grade || 0,
          lecturer: course.lecturer_name || 'TBA',
          semester: course.semester || 'Current',
          status: course.status || 'In Progress',
          progress: course.progress || 0,
          totalAssessments: course.total_assessments || 0,
          completedAssessments: course.completed_assessments || 0
        }));
        
        console.log('Transformed course data:', this.student.currentCourses);
      } catch (err) {
        console.error('Error loading student courses:', err);
        // Don't throw error here, just log it - the main student data is more important
        console.warn('Course data not available, showing basic enrollment count only');
      }
    },

    getCourseStatus(course) {
      if (course.currentMark >= 70) return 'Good';
      if (course.currentMark >= 50) return 'Concern';
      return 'At Risk';
    },
    
    getGradeLetter(mark) {
      if (mark >= 90) return 'A+';
      if (mark >= 85) return 'A';
      if (mark >= 80) return 'A-';
      if (mark >= 75) return 'B+';
      if (mark >= 70) return 'B';
      if (mark >= 65) return 'C+';
      if (mark >= 60) return 'C';
      if (mark >= 50) return 'D';
      return 'F';
    },
    
    markToGpaPoints(mark) {
      if (mark >= 90) return 4.0;
      if (mark >= 85) return 4.0;
      if (mark >= 80) return 3.7;
      if (mark >= 75) return 3.3;
      if (mark >= 70) return 3.0;
      if (mark >= 65) return 2.3;
      if (mark >= 60) return 2.0;
      if (mark >= 50) return 1.0;
      return 0.0;
    },
    
    viewCourseDetails(course) {
      // Implement course details view
      alert(`Course details for ${course.name} will be shown here`);
    },
    
    async addNote() {
      if (!this.newNote.trim()) return;
      
      const today = new Date();
      const formattedDate = today.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
      
      const newNoteObj = {
        content: this.newNote,
        date: formattedDate
      };
      
      // Add to local state
      this.student.advisorNotes.unshift(newNoteObj);
      
      // Clear the input
      this.newNote = '';
      
      try {
        // Update in the store/backend
        await this.updateAdviseeNotes({
          studentId: this.studentId,
          notes: this.student.advisorNotes
        });
      } catch (err) {
        // Revert if there's an error
        this.student.advisorNotes.shift();
        this.$toast.error('Failed to save note. Please try again.');
        console.error('Error saving note:', err);
      }
    },
    
    async deleteNote(index) {
      if (!confirm('Are you sure you want to delete this note?')) return;
      
      const deletedNote = this.student.advisorNotes.splice(index, 1)[0];
      
      try {
        // Update in the store/backend
        await this.updateAdviseeNotes({
          studentId: this.studentId,
          notes: this.student.advisorNotes
        });
      } catch (err) {
        // Revert if there's an error
        this.student.advisorNotes.splice(index, 0, deletedNote);
        this.$toast.error('Failed to delete note. Please try again.');
        console.error('Error deleting note:', err);
      }
    },
    
    initGpaChart() {
      // Sample GPA data by semester - replace with actual data when available
      const labels = ['Fall 2021', 'Spring 2022', 'Fall 2022', 'Spring 2023'];
      const data = [3.1, 3.3, 3.0, this.student.gpa];
      
      const ctx = document.getElementById('gpaChart');
      if (!ctx) return;
      
      // Check if Chart.js is loaded
      if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded');
        return;
      }
      
      this.gpaChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'GPA by Semester',
            data: data,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            tension: 0.2,
            fill: true
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: false,
              min: Math.max(0, Math.min(...data) - 0.5),
              max: Math.min(4.0, Math.max(...data) + 0.5),
              ticks: {
                stepSize: 0.5
              }
            }
          },
          plugins: {
            tooltip: {
              callbacks: {
                label: function(context) {
                  return `GPA: ${context.parsed.y.toFixed(2)}`;
                }
              }
            }
          }
        }
      });
    }
  },
  created() {
    this.loadStudentData();
  },
  beforeUnmount() {
    if (this.gpaChart) {
      this.gpaChart.destroy();
    }
  }
}
</script>

<style scoped>
.advisee-detail-container {
  padding: 2rem 0;
}

.student-contact {
  text-align: right;
}

.notes-list {
  max-height: 300px;
  overflow-y: auto;
}

.note-item {
  padding: 10px;
  background-color: #f8f9fa;
  border-radius: 5px;
}

.note-header {
  margin-bottom: 5px;
  font-size: 0.9rem;
}

.note-content {
  white-space: pre-line;
}

.chart-container {
  height: 300px;
}

@media (max-width: 768px) {
  .student-contact {
    text-align: left;
    margin-top: 1rem;
  }
}
</style>
