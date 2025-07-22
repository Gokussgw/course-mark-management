<template>
  <div class="assessment-form">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item">
              <router-link to="/lecturer/dashboard">Dashboard</router-link>
            </li>
            <li class="breadcrumb-item" v-if="selectedCourse">
              <router-link :to="`/lecturer/course/${selectedCourse.id}`">
                {{ selectedCourse.code }} - {{ selectedCourse.name }}
              </router-link>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              {{ isEditing ? 'Edit Assessment' : 'Create Assessment' }}
            </li>
          </ol>
        </nav>
        <h1 class="mb-2">
          {{ isEditing ? 'Edit Assessment' : 'Create New Assessment' }}
        </h1>
        <p class="text-muted" v-if="selectedCourse">
          For course: {{ selectedCourse.code }} - {{ selectedCourse.name }}
        </p>
      </div>
    </div>

    <div v-if="isLoading" class="text-center my-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else class="card">
      <div class="card-body">
        <form @submit.prevent="saveAssessment">
          <div class="row mb-3" v-if="!selectedCourse && !isEditing">
            <div class="col-md-6">
              <label for="course" class="form-label">Course <span class="text-danger">*</span></label>
              <select 
                id="course" 
                class="form-select" 
                v-model="formData.course_id" 
                required
                :class="{ 'is-invalid': errors.course_id }"
              >
                <option value="">Select a course</option>
                <option 
                  v-for="course in lecturerCourses" 
                  :key="course.id" 
                  :value="course.id"
                >
                  {{ course.code }} - {{ course.name }}
                </option>
              </select>
              <div class="invalid-feedback" v-if="errors.course_id">
                {{ errors.course_id }}
              </div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="name" class="form-label">Assessment Name <span class="text-danger">*</span></label>
              <input 
                type="text" 
                id="name" 
                class="form-control" 
                v-model="formData.name" 
                required
                :class="{ 'is-invalid': errors.name }"
                placeholder="e.g., Midterm Exam, Assignment 1"
              >
              <div class="invalid-feedback" v-if="errors.name">
                {{ errors.name }}
              </div>
            </div>

            <div class="col-md-6">
              <label for="type" class="form-label">Assessment Type <span class="text-danger">*</span></label>
              <select 
                id="type" 
                class="form-select" 
                v-model="formData.type" 
                required
                :class="{ 'is-invalid': errors.type }"
              >
                <option value="">Select type</option>
                <option value="Assignment">Assignment</option>
                <option value="Quiz">Quiz</option>
                <option value="Midterm">Midterm Exam</option>
                <option value="Project">Project</option>
                <option value="Exam">Final Exam</option>
                <option value="Lab">Lab Work</option>
                <option value="Participation">Participation</option>
                <option value="Other">Other</option>
              </select>
              <div class="invalid-feedback" v-if="errors.type">
                {{ errors.type }}
              </div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <label for="weightage" class="form-label">Weightage (%) <span class="text-danger">*</span></label>
              <input 
                type="number" 
                id="weightage" 
                class="form-control" 
                v-model.number="formData.weightage" 
                min="0" 
                max="100" 
                required
                :class="{ 'is-invalid': errors.weightage }"
              >
              <div class="invalid-feedback" v-if="errors.weightage">
                {{ errors.weightage }}
              </div>
              <small class="form-text text-muted">
                Total weightage for all assessments should equal 100%.
                Current total: {{ currentTotalWeightage }}%
                <span 
                  v-if="currentTotalWeightage > 100" 
                  class="text-danger"
                >
                  (exceeds 100%)
                </span>
              </small>
            </div>

            <div class="col-md-4">
              <label for="max_mark" class="form-label">Maximum Mark <span class="text-danger">*</span></label>
              <input 
                type="number" 
                id="max_mark" 
                class="form-control" 
                v-model.number="formData.max_mark" 
                min="1" 
                required
                :class="{ 'is-invalid': errors.max_mark }"
              >
              <div class="invalid-feedback" v-if="errors.max_mark">
                {{ errors.max_mark }}
              </div>
            </div>

            <div class="col-md-4">
              <label for="due_date" class="form-label">Due Date</label>
              <input 
                type="date" 
                id="due_date" 
                class="form-control" 
                v-model="formData.due_date"
                :class="{ 'is-invalid': errors.due_date }"
              >
              <div class="invalid-feedback" v-if="errors.due_date">
                {{ errors.due_date }}
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea 
              id="description" 
              class="form-control" 
              v-model="formData.description"
              rows="4"
              :class="{ 'is-invalid': errors.description }"
              placeholder="Provide details about the assessment..."
            ></textarea>
            <div class="invalid-feedback" v-if="errors.description">
              {{ errors.description }}
            </div>
          </div>

          <div class="mb-3 form-check">
            <input 
              type="checkbox" 
              class="form-check-input" 
              id="is_final_exam" 
              v-model="formData.is_final_exam"
            >
            <label class="form-check-label" for="is_final_exam">
              This is a final exam
            </label>
            <small class="form-text text-muted d-block">
              Mark this if this assessment is the final exam for the course.
            </small>
          </div>

          <div class="mb-3 form-check">
            <input 
              type="checkbox" 
              class="form-check-input" 
              id="is_published" 
              v-model="formData.is_published"
            >
            <label class="form-check-label" for="is_published">
              Publish to students
            </label>
            <small class="form-text text-muted d-block">
              If checked, students will be able to see this assessment in their course view.
            </small>
          </div>

          <div class="d-flex justify-content-between mt-4">
            <button 
              type="button" 
              class="btn btn-outline-secondary" 
              @click="cancelForm"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              class="btn btn-primary"
              :disabled="!isFormValid || isSaving"
            >
              <span v-if="isSaving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
              {{ isEditing ? 'Update Assessment' : 'Create Assessment' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapGetters, mapActions } from 'vuex'

export default {
  name: 'AssessmentForm',
  data() {
    return {
      formData: {
        course_id: '',
        name: '',
        type: '',
        description: '',
        due_date: '',
        weightage: 10,
        max_mark: 100,
        is_final_exam: false,
        is_published: true
      },
      errors: {},
      isSaving: false,
      assessmentId: null,
      courseIdFromQuery: null
    }
  },
  computed: {
    ...mapState({
      isLoading: state => state.loading,
      courses: state => state.courses.courses,
      assessment: state => state.assessments.assessment
    }),
    ...mapGetters({
      getCourseAssessments: 'assessments/getCourseAssessments'
    }),
    isEditing() {
      return !!this.assessmentId;
    },
    lecturerCourses() {
      return this.courses || [];
    },
    selectedCourse() {
      if (!this.formData.course_id) return null;
      return this.courses.find(course => course.id === parseInt(this.formData.course_id));
    },
    courseAssessments() {
      if (!this.formData.course_id) return [];
      return this.getCourseAssessments(this.formData.course_id) || [];
    },
    currentTotalWeightage() {
      if (!this.formData.course_id) return this.formData.weightage || 0;
      
      // Calculate total weightage of all assessments in this course, including the current one if editing
      let totalWeight = 0;
      
      this.courseAssessments.forEach(assessment => {
        // Skip the current assessment when editing
        if (this.isEditing && assessment.id === parseInt(this.assessmentId)) return;
        totalWeight += assessment.weightage;
      });
      
      // Add the current assessment weightage
      totalWeight += this.formData.weightage || 0;
      
      return totalWeight;
    },
    isFormValid() {
      return this.formData.course_id && 
             this.formData.name && 
             this.formData.type && 
             this.formData.weightage > 0 && 
             this.formData.max_mark > 0;
    }
  },
  async created() {
    // Check if we're editing an existing assessment
    this.assessmentId = this.$route.params.id;
    this.courseIdFromQuery = this.$route.query.courseId;
    
    try {
      // Fetch lecturer's courses
      await this.fetchCourses({ lecturerId: this.$store.getters['auth/userId'] });
      
      if (this.assessmentId) {
        // Editing mode: fetch the assessment details
        await this.fetchAssessment(this.assessmentId);
        this.populateFormFromAssessment();
      } else if (this.courseIdFromQuery) {
        // Coming from a course page with pre-selected course
        this.formData.course_id = parseInt(this.courseIdFromQuery);
        // Fetch existing assessments for this course to calculate total weightage
        await this.fetchAssessments({ courseId: this.formData.course_id });
      }
    } catch (error) {
      console.error('Error loading form data:', error);
      this.$store.dispatch('setError', 'Failed to load assessment data');
    }
  },
  methods: {
    ...mapActions({
      fetchCourses: 'courses/fetchCourses',
      fetchAssessment: 'assessments/fetchAssessment',
      fetchAssessments: 'assessments/fetchAssessments',
      createAssessment: 'assessments/createAssessment',
      updateAssessment: 'assessments/updateAssessment'
    }),
    populateFormFromAssessment() {
      if (!this.assessment) return;
      
      this.formData = {
        course_id: this.assessment.course_id,
        name: this.assessment.name || '',
        type: this.assessment.type || '',
        description: this.assessment.description || '',
        due_date: this.assessment.due_date ? this.formatDateForInput(this.assessment.due_date) : '',
        weightage: this.assessment.weightage || 0,
        max_mark: this.assessment.max_mark || 0,
        is_final_exam: this.assessment.is_final_exam || false,
        is_published: this.assessment.is_published || true
      };
      
      // Fetch existing assessments for this course to calculate total weightage
      this.fetchAssessments({ courseId: this.formData.course_id });
    },
    formatDateForInput(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toISOString().split('T')[0];
    },
    validateForm() {
      this.errors = {};
      
      if (!this.formData.course_id) {
        this.errors.course_id = 'Please select a course';
      }
      
      if (!this.formData.name || this.formData.name.trim() === '') {
        this.errors.name = 'Assessment name is required';
      }
      
      if (!this.formData.type) {
        this.errors.type = 'Please select an assessment type';
      }
      
      if (!this.formData.weightage || this.formData.weightage <= 0) {
        this.errors.weightage = 'Weightage must be greater than 0';
      } else if (this.formData.weightage > 100) {
        this.errors.weightage = 'Weightage cannot exceed 100%';
      } else if (this.currentTotalWeightage > 100) {
        this.errors.weightage = 'Total weightage exceeds 100%';
      }
      
      if (!this.formData.max_mark || this.formData.max_mark <= 0) {
        this.errors.max_mark = 'Maximum mark must be greater than 0';
      }
      
      return Object.keys(this.errors).length === 0;
    },
    async saveAssessment() {
      if (!this.validateForm()) return;
      
      this.isSaving = true;
      
      try {
        if (this.isEditing) {
          // Update existing assessment
          await this.updateAssessment({
            id: this.assessmentId,
            ...this.formData
          });
          this.$store.dispatch('showToast', {
            message: 'Assessment updated successfully',
            type: 'success'
          });
        } else {
          // Create new assessment
          await this.createAssessment(this.formData);
          this.$store.dispatch('showToast', {
            message: 'Assessment created successfully',
            type: 'success'
          });
        }
        
        // Navigate back to the course page
        this.navigateBack();
      } catch (error) {
        console.error('Error saving assessment:', error);
      } finally {
        this.isSaving = false;
      }
    },
    cancelForm() {
      this.navigateBack();
    },
    navigateBack() {
      if (this.formData.course_id) {
        this.$router.push(`/lecturer/course/${this.formData.course_id}`);
      } else {
        this.$router.push('/lecturer/dashboard');
      }
    }
  }
}
</script>

<style scoped>
.assessment-form h1 {
  font-size: 1.75rem;
  font-weight: 600;
}

.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
</style>
