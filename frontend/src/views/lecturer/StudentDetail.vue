<template>
  <div class="student-detail">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item">
              <router-link to="/lecturer/dashboard">Dashboard</router-link>
            </li>
            <li class="breadcrumb-item" v-if="course">
              <router-link :to="`/lecturer/course/${course.id}`">
                {{ course.code }} - {{ course.name }}
              </router-link>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              {{ student ? student.name : 'Student Details' }}
            </li>
          </ol>
        </nav>
        <h1 class="mb-2">
          <span v-if="student">{{ student.name }}</span>
          <span v-else>Student Details</span>
        </h1>
        <p class="text-muted" v-if="student">
          {{ student.email }} | Student ID: {{ student.student_id || 'N/A' }}
        </p>
      </div>
      <div class="btn-group">
        <button class="btn btn-outline-primary" @click="exportStudentData">
          <i class="fas fa-file-export me-2"></i> Export Data
        </button>
      </div>
    </div>

    <div v-if="isLoading" class="text-center my-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else-if="!student" class="alert alert-danger">
      Student not found or you don't have access to this student's information.
    </div>

    <div v-else class="row">
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-header bg-light">
            <h5 class="mb-0">Student Information</h5>
          </div>
          <div class="card-body">
            <div class="text-center mb-4">
              <div class="avatar-placeholder">
                <i class="fas fa-user fa-3x text-muted"></i>
              </div>
              <h4 class="mt-3">{{ student.name }}</h4>
              <p class="text-muted mb-1">{{ student.email }}</p>
              <p class="badge bg-primary">{{ student.role }}</p>
            </div>

            <table class="table table-sm">
              <tbody>
                <tr>
                  <th>Student ID:</th>
                  <td>{{ student.student_id || 'Not specified' }}</td>
                </tr>
                <tr>
                  <th>Department:</th>
                  <td>{{ student.department || 'Not specified' }}</td>
                </tr>
                <tr>
                  <th>Year:</th>
                  <td>{{ student.year || 'Not specified' }}</td>
                </tr>
                <tr>
                  <th>Advisor:</th>
                  <td>{{ advisorName || 'Not assigned' }}</td>
                </tr>
                <tr>
                  <th>Joined:</th>
                  <td>{{ formatDate(student.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-md-8 mb-4">
        <div class="card mb-4">
          <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Course Progress</h5>
            <div v-if="course">
              <span class="text-muted">Course: </span>
              <strong>{{ course.code }} - {{ course.name }}</strong>
            </div>
          </div>
          <div class="card-body">
            <div v-if="!course" class="text-center py-3">
              <p>No specific course selected. Showing overall performance.</p>
            </div>
            
            <div v-if="studentMarks.length === 0" class="text-center py-3">
              <p>No assessment marks recorded for this student yet.</p>
            </div>
            <div v-else>
              <div class="mb-4">
                <h6>Overall Progress</h6>
                <div class="d-flex justify-content-between mb-1">
                  <span>Current Mark: <strong>{{ overallMark }}%</strong></span>
                  <span>{{ completedAssessments }} of {{ totalAssessments }} assessments completed</span>
                </div>
                <div class="progress" style="height: 20px;">
                  <div 
                    class="progress-bar" 
                    :class="getProgressBarClass(overallMark)"
                    role="progressbar" 
                    :style="`width: ${overallMark}%`"
                    :aria-valuenow="overallMark" 
                    aria-valuemin="0" 
                    aria-valuemax="100">
                    {{ overallMark }}%
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
                      <th>Mark</th>
                      <th>Grade</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="mark in studentMarks" :key="mark.id">
                      <td>{{ mark.assessment_name }}</td>
                      <td>
                        <span class="badge" :class="getAssessmentTypeBadgeClass(mark.assessment_type)">
                          {{ mark.assessment_type }}
                        </span>
                      </td>
                      <td>{{ mark.weightage }}%</td>
                      <td>
                        <strong>{{ mark.mark }}</strong> / {{ mark.max_mark }}
                        <div class="small text-muted">
                          ({{ calculatePercentage(mark.mark, mark.max_mark) }}%)
                        </div>
                      </td>
                      <td>
                        <span :class="getGradeClass(calculatePercentage(mark.mark, mark.max_mark))">
                          {{ calculateGrade(calculatePercentage(mark.mark, mark.max_mark)) }}
                        </span>
                      </td>
                      <td>
                        <button 
                          class="btn btn-sm btn-outline-primary" 
                          @click="editMark(mark)"
                        >
                          <i class="fas fa-edit"></i> Edit
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Feedback & Notes</h5>
            <button class="btn btn-sm btn-primary" @click="openAddNoteModal">
              <i class="fas fa-plus-circle me-1"></i> Add Note
            </button>
          </div>
          <div class="card-body">
            <div v-if="studentNotes.length === 0" class="text-center py-3">
              <p>No feedback or notes have been added for this student yet.</p>
            </div>
            <div v-else class="timeline">
              <div class="timeline-item" v-for="(note, index) in studentNotes" :key="index">
                <div class="timeline-item-icon">
                  <i :class="note.icon || 'fas fa-comment-alt'"></i>
                </div>
                <div class="timeline-item-content">
                  <div class="d-flex justify-content-between align-items-start">
                    <h6>{{ note.title || 'Feedback' }}</h6>
                    <div class="btn-group btn-group-sm">
                      <button class="btn btn-link text-secondary p-0" @click="editNote(note)">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-link text-danger p-0 ms-2" @click="deleteNote(note)">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>
                  <p>{{ note.content }}</p>
                  <small class="text-muted">
                    {{ note.author || 'You' }} - {{ formatDate(note.created_at) }}
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Mark Modal -->
    <div class="modal fade" id="editMarkModal" tabindex="-1" aria-labelledby="editMarkModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editMarkModalLabel">Edit Mark</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div v-if="selectedMark">
              <div class="mb-3">
                <label for="markInput" class="form-label">Mark for {{ selectedMark.assessment_name }}</label>
                <div class="input-group">
                  <input 
                    type="number" 
                    class="form-control" 
                    id="markInput" 
                    v-model.number="editMarkForm.mark" 
                    :min="0" 
                    :max="selectedMark.max_mark"
                  >
                  <span class="input-group-text">/ {{ selectedMark.max_mark }}</span>
                </div>
              </div>
              <div class="mb-3">
                <label for="remarkInput" class="form-label">Feedback (optional)</label>
                <textarea 
                  class="form-control" 
                  id="remarkInput" 
                  v-model="editMarkForm.remarks" 
                  rows="3"
                ></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="saveMarkChanges">Save Changes</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Note Modal -->
    <div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="noteModalLabel">{{ editingNote ? 'Edit Note' : 'Add Note' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="noteTitle" class="form-label">Title</label>
              <input 
                type="text" 
                class="form-control" 
                id="noteTitle" 
                v-model="noteForm.title" 
                placeholder="e.g., Attendance Issue, Good Progress"
              >
            </div>
            <div class="mb-3">
              <label for="noteContent" class="form-label">Content</label>
              <textarea 
                class="form-control" 
                id="noteContent" 
                v-model="noteForm.content" 
                rows="4"
                placeholder="Enter your note or feedback here..."
              ></textarea>
            </div>
            <div class="mb-3 form-check">
              <input type="checkbox" class="form-check-input" id="notifyStudent" v-model="noteForm.notify_student">
              <label class="form-check-label" for="notifyStudent">Notify student</label>
              <small class="text-muted d-block">If checked, the student will be notified about this note.</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="saveNoteChanges">Save</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapGetters, mapActions } from 'vuex'

export default {
  name: 'StudentDetail',
  data() {
    return {
      studentId: null,
      courseId: null,
      studentNotes: [],
      selectedMark: null,
      editMarkForm: {
        mark: 0,
        remarks: ''
      },
      noteForm: {
        title: '',
        content: '',
        notify_student: false
      },
      editingNote: null
    }
  },
  computed: {
    ...mapState({
      isLoading: state => state.loading,
      course: state => state.courses.course,
      student: state => state.users.user,
      assessments: state => state.assessments.assessments,
      marks: state => state.marks.marks
    }),
    ...mapGetters({
      getCourseAssessments: 'assessments/getCourseAssessments'
    }),
    courseAssessments() {
      if (!this.courseId) return [];
      return this.getCourseAssessments(this.courseId) || [];
    },
    studentMarks() {
      if (!this.student) return [];
      
      return this.marks.filter(mark => 
        mark.student_id === this.studentId && 
        (!this.courseId || mark.course_id === parseInt(this.courseId))
      );
    },
    totalAssessments() {
      return this.courseAssessments.length;
    },
    completedAssessments() {
      return this.studentMarks.length;
    },
    overallMark() {
      if (!this.studentMarks.length) return 0;
      
      let weightedSum = 0;
      let totalWeight = 0;
      
      this.studentMarks.forEach(mark => {
        const percentage = this.calculatePercentage(mark.mark, mark.max_mark);
        weightedSum += percentage * mark.weightage;
        totalWeight += parseInt(mark.weightage);
      });
      
      if (totalWeight === 0) return 0;
      return Math.round(weightedSum / totalWeight);
    },
    advisorName() {
      return this.student?.advisor_name || 'Not assigned';
    }
  },
  async created() {
    this.studentId = parseInt(this.$route.params.id);
    this.courseId = this.$route.query.courseId;
    
    try {
      // Fetch student details
      await this.fetchUser(this.studentId);
      
      if (this.courseId) {
        // Fetch course details if a specific course is selected
        await this.fetchCourse(this.courseId);
        
        // Fetch assessments for this course
        await this.fetchAssessments({ courseId: this.courseId });
      }
      
      // Fetch marks for this student
      await this.fetchMarks({ 
        studentId: this.studentId,
        courseId: this.courseId
      });
      
      // Fetch notes for this student
      await this.fetchStudentNotes();
    } catch (error) {
      console.error('Error loading student details:', error);
    }
  },
  methods: {
    ...mapActions({
      fetchUser: 'users/fetchUser',
      fetchCourse: 'courses/fetchCourse',
      fetchAssessments: 'assessments/fetchAssessments',
      fetchMarks: 'marks/fetchMarks',
      updateMark: 'marks/updateMark'
    }),
    formatDate(dateString) {
      if (!dateString) return 'Not specified';
      return new Date(dateString).toLocaleDateString();
    },
    calculatePercentage(mark, maxMark) {
      if (!maxMark) return 0;
      return Math.round((mark / maxMark) * 100);
    },
    getProgressBarClass(percentage) {
      if (percentage >= 70) return 'bg-success';
      if (percentage >= 50) return 'bg-warning';
      return 'bg-danger';
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
    editMark(mark) {
      this.selectedMark = mark;
      this.editMarkForm = {
        mark: mark.mark,
        remarks: mark.remarks || ''
      };
      
      // In a real implementation, we would use Bootstrap's modal methods
      // $('#editMarkModal').modal('show');
    },
    async saveMarkChanges() {
      if (!this.selectedMark) return;
      
      try {
        await this.updateMark({
          id: this.selectedMark.id,
          mark: this.editMarkForm.mark,
          remarks: this.editMarkForm.remarks
        });
        
        // Close modal
        // $('#editMarkModal').modal('hide');
        
        this.$store.dispatch('showToast', {
          message: 'Mark updated successfully',
          type: 'success'
        });
        
        this.selectedMark = null;
      } catch (error) {
        console.error('Error updating mark:', error);
      }
    },
    async fetchStudentNotes() {
      try {
        // This would be replaced with an actual API call in a complete implementation
        // For now, we'll use some sample data
        this.studentNotes = [
          {
            id: 1,
            title: 'Excellent Progress',
            content: 'Student has shown significant improvement in recent assessments.',
            author: 'Dr. Smith',
            created_at: '2023-07-15',
            icon: 'fas fa-thumbs-up'
          },
          {
            id: 2,
            title: 'Attendance Concern',
            content: 'Student has missed multiple lab sessions. Please follow up.',
            author: 'Prof. Johnson',
            created_at: '2023-07-10',
            icon: 'fas fa-exclamation-triangle'
          }
        ];
      } catch (error) {
        console.error('Error fetching student notes:', error);
      }
    },
    openAddNoteModal() {
      this.editingNote = null;
      this.noteForm = {
        title: '',
        content: '',
        notify_student: false
      };
      
      // In a real implementation, we would use Bootstrap's modal methods
      // $('#noteModal').modal('show');
    },
    editNote(note) {
      this.editingNote = note;
      this.noteForm = {
        title: note.title,
        content: note.content,
        notify_student: false
      };
      
      // In a real implementation, we would use Bootstrap's modal methods
      // $('#noteModal').modal('show');
    },
    async saveNoteChanges() {
      try {
        if (this.editingNote) {
          // Update existing note
          const index = this.studentNotes.findIndex(note => note.id === this.editingNote.id);
          if (index !== -1) {
            this.studentNotes[index] = {
              ...this.editingNote,
              title: this.noteForm.title,
              content: this.noteForm.content,
              updated_at: new Date().toISOString().split('T')[0]
            };
          }
          
          this.$store.dispatch('showToast', {
            message: 'Note updated successfully',
            type: 'success'
          });
        } else {
          // Add new note
          const newNote = {
            id: Date.now(), // Temporary ID for demo
            title: this.noteForm.title,
            content: this.noteForm.content,
            author: 'You',
            created_at: new Date().toISOString().split('T')[0],
            icon: 'fas fa-comment-alt'
          };
          
          this.studentNotes.unshift(newNote);
          
          this.$store.dispatch('showToast', {
            message: 'Note added successfully',
            type: 'success'
          });
        }
        
        // Close modal
        // $('#noteModal').modal('hide');
        
        this.editingNote = null;
      } catch (error) {
        console.error('Error saving note:', error);
      }
    },
    async deleteNote(note) {
      if (!confirm(`Are you sure you want to delete the note "${note.title}"?`)) return;
      
      try {
        this.studentNotes = this.studentNotes.filter(n => n.id !== note.id);
        
        this.$store.dispatch('showToast', {
          message: 'Note deleted successfully',
          type: 'success'
        });
      } catch (error) {
        console.error('Error deleting note:', error);
      }
    },
    exportStudentData() {
      // This would initiate a download of student data in CSV format
      // Implementation would need to call an API endpoint
      this.$store.dispatch('showToast', {
        message: 'Exporting student data...',
        type: 'info'
      });
    }
  }
}
</script>

<style scoped>
.student-detail h1 {
  font-size: 1.75rem;
  font-weight: 600;
}

.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.avatar-placeholder {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background-color: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  border: 1px solid #dee2e6;
}

/* Timeline styling for notes */
.timeline {
  position: relative;
  padding-left: 40px;
}

.timeline-item {
  position: relative;
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e9ecef;
}

.timeline-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.timeline-item:before {
  content: "";
  position: absolute;
  left: -30px;
  top: 0;
  height: 100%;
  width: 2px;
  background-color: #e9ecef;
}

.timeline-item:last-child:before {
  height: 50%;
}

.timeline-item-icon {
  position: absolute;
  left: -38px;
  top: 0;
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #fff;
  border: 2px solid #007bff;
  border-radius: 50%;
}

.timeline-item-icon i {
  font-size: 8px;
  color: #007bff;
}
</style>
