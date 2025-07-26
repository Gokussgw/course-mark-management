<template>
  <div class="mark-breakdown">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1><i class="fas fa-chart-line me-2"></i>Mark Breakdown Analysis</h1>
      <div class="d-flex gap-2">
        <button class="btn btn-success" @click="exportBreakdown">
          <i class="fas fa-file-export me-2"></i>Export Report
        </button>
        <button class="btn         ];
        
        // Auto-select course from route if available
        if (this.$route.params.courseId || this.courseId) {
          this.selectedCourseId = parseInt(this.$route.params.courseId || this.courseId);
          await this.loadCourseData();
        }
      } catch (error) {ine-secondary" @click="refreshData">
          <i class="fas fa-sync me-2"></i>Refresh
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-4">
            <label class="form-label fw-bold">Course:</label>
            <select v-model="selectedCourseId" @change="loadCourseData" class="form-select">
              <option value="">-- Select Course --</option>
              <option v-for="course in courses" :key="course.id" :value="course.id">
                {{ course.code }} - {{ course.name }}
              </option>
            </select>
          </div>
          <div class="col-md-4" v-if="currentUserRole === 'lecturer' || currentUserRole === 'advisor'">
            <label class="form-label fw-bold">Student:</label>
            <select v-model="selectedStudentId" @change="loadStudentBreakdown" class="form-select">
              <option value="">-- All Students --</option>
              <option v-for="student in students" :key="student.id" :value="student.id">
                {{ student.name }} ({{ student.matric_number }})
              </option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">Assessment Type:</label>
            <select v-model="selectedAssessmentType" @change="filterBreakdown" class="form-select">
              <option value="">-- All Types --</option>
              <option value="quiz">Quiz</option>
              <option value="assignment">Assignment</option>
              <option value="midterm">Midterm</option>
              <option value="final_exam">Final Exam</option>
              <option value="project">Project</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center p-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-3 text-muted">Loading mark breakdown...</p>
    </div>

    <!-- Course Overview Stats -->
    <div v-else-if="selectedCourseId && courseStats" class="row mb-4">
      <div class="col-md-3">
        <div class="card stat-card bg-primary text-white">
          <div class="card-body text-center">
            <i class="fas fa-users fa-2x mb-2"></i>
            <h4>{{ courseStats.totalStudents }}</h4>
            <p class="mb-0">Total Students</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card bg-info text-white">
          <div class="card-body text-center">
            <i class="fas fa-tasks fa-2x mb-2"></i>
            <h4>{{ courseStats.totalAssessments }}</h4>
            <p class="mb-0">Assessments</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card bg-success text-white">
          <div class="card-body text-center">
            <i class="fas fa-percentage fa-2x mb-2"></i>
            <h4>{{ courseStats.classAverage }}%</h4>
            <p class="mb-0">Class Average</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card bg-warning text-dark">
          <div class="card-body text-center">
            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
            <h4>{{ courseStats.atRiskStudents }}</h4>
            <p class="mb-0">At Risk</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Detailed Breakdown -->
    <div v-if="selectedCourseId">
      <!-- Assessment Breakdown Chart -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">
            <i class="fas fa-chart-bar me-2"></i>Assessment Performance Overview
          </h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-8">
              <canvas id="assessmentChart" height="300"></canvas>
            </div>
            <div class="col-md-4">
              <h6>Assessment Distribution</h6>
              <div v-for="assessment in assessmentBreakdown" :key="assessment.type" class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="badge" :class="getAssessmentBadgeClass(assessment.type)">
                    {{ formatAssessmentType(assessment.type) }}
                  </span>
                  <strong>{{ assessment.weightage }}%</strong>
                </div>
                <div class="progress" style="height: 8px;">
                  <div class="progress-bar" :class="getAssessmentProgressClass(assessment.type)" 
                       :style="{ width: assessment.weightage + '%' }"></div>
                </div>
                <small class="text-muted">
                  Average: {{ assessment.average }}% | Submitted: {{ assessment.submitted }}/{{ assessment.total }}
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Individual Student Breakdown (if selected) -->
      <div v-if="selectedStudentId && studentBreakdown" class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">
            <i class="fas fa-user-graduate me-2"></i>
            Individual Student Analysis: {{ studentBreakdown.name }}
          </h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <div class="student-summary">
                <div class="row text-center mb-4">
                  <div class="col-4">
                    <div class="metric-box">
                      <h3 class="text-primary">{{ studentBreakdown.finalMark }}%</h3>
                      <p class="text-muted mb-0">Final Mark</p>
                    </div>
                  </div>
                  <div class="col-4">
                    <div class="metric-box">
                      <h3 class="text-success">{{ studentBreakdown.grade }}</h3>
                      <p class="text-muted mb-0">Grade</p>
                    </div>
                  </div>
                  <div class="col-4">
                    <div class="metric-box">
                      <h3 class="text-info">{{ studentBreakdown.rank }}/{{ courseStats.totalStudents }}</h3>
                      <p class="text-muted mb-0">Class Rank</p>
                    </div>
                  </div>
                </div>

                <h6>Assessment Performance</h6>
                <div class="table-responsive">
                  <table class="table table-sm table-hover">
                    <thead class="table-light">
                      <tr>
                        <th>Assessment</th>
                        <th>Type</th>
                        <th>Score</th>
                        <th>Weighted</th>
                        <th>Class Avg</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="assessment in studentBreakdown.assessments" :key="assessment.id">
                        <td>{{ assessment.name }}</td>
                        <td>
                          <span class="badge badge-sm" :class="getAssessmentBadgeClass(assessment.type)">
                            {{ formatAssessmentType(assessment.type) }}
                          </span>
                        </td>
                        <td>
                          <span :class="getScoreClass(assessment.percentage)">
                            {{ assessment.obtained }}/{{ assessment.max_marks }}
                            ({{ assessment.percentage }}%)
                          </span>
                        </td>
                        <td>
                          <strong class="text-primary">{{ assessment.weighted }}%</strong>
                        </td>
                        <td>{{ assessment.classAverage }}%</td>
                        <td>
                          <span class="badge" :class="getPerformanceStatusClass(assessment.percentage, assessment.classAverage)">
                            {{ getPerformanceStatus(assessment.percentage, assessment.classAverage) }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <h6>Performance Trends</h6>
              <canvas id="studentTrendChart" height="200"></canvas>
              
              <div class="mt-4">
                <h6>Strengths & Areas for Improvement</h6>
                <div class="row">
                  <div class="col-6">
                    <h6 class="text-success">Strengths</h6>
                    <ul class="list-unstyled">
                      <li v-for="strength in studentBreakdown.strengths" :key="strength" class="mb-1">
                        <i class="fas fa-check-circle text-success me-2"></i>{{ strength }}
                      </li>
                    </ul>
                  </div>
                  <div class="col-6">
                    <h6 class="text-warning">Improvements</h6>
                    <ul class="list-unstyled">
                      <li v-for="improvement in studentBreakdown.improvements" :key="improvement" class="mb-1">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>{{ improvement }}
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Class Performance Table -->
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="fas fa-table me-2"></i>
            {{ selectedStudentId ? 'Student' : 'Class' }} Performance Breakdown
          </h5>
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-primary" @click="toggleView('summary')" 
                    :class="{ active: viewMode === 'summary' }">
              Summary
            </button>
            <button class="btn btn-outline-primary" @click="toggleView('detailed')" 
                    :class="{ active: viewMode === 'detailed' }">
              Detailed
            </button>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th v-if="!selectedStudentId">Student</th>
                  <th v-for="assessment in uniqueAssessments" :key="assessment.id" class="text-center">
                    {{ assessment.name }}
                    <br>
                    <small class="text-muted">({{ assessment.weightage }}%)</small>
                  </th>
                  <th class="text-center">Final Mark</th>
                  <th class="text-center">Grade</th>
                  <th v-if="!selectedStudentId" class="text-center">Rank</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(student, index) in filteredStudentMarks" :key="student.id">
                  <td v-if="!selectedStudentId">
                    <div class="d-flex align-items-center">
                      <div class="avatar me-3">
                        {{ getStudentInitials(student.name) }}
                      </div>
                      <div>
                        <strong>{{ student.name }}</strong>
                        <br>
                        <small class="text-muted">{{ student.matric_number }}</small>
                      </div>
                    </div>
                  </td>
                  <td v-for="assessment in uniqueAssessments" :key="assessment.id" class="text-center">
                    <div v-if="student.marks[assessment.type]">
                      <strong :class="getScoreClass(student.marks[assessment.type].percentage)">
                        {{ student.marks[assessment.type].percentage }}%
                      </strong>
                      <br>
                      <small class="text-muted">
                        {{ student.marks[assessment.type].obtained }}/{{ student.marks[assessment.type].max_mark }}
                      </small>
                    </div>
                    <div v-else class="text-muted">
                      <i class="fas fa-minus"></i>
                      <br>
                      <small>Not submitted</small>
                    </div>
                  </td>
                  <td class="text-center">
                    <strong :class="getScoreClass(student.finalMark)" class="fs-5">
                      {{ student.finalMark }}%
                    </strong>
                  </td>
                  <td class="text-center">
                    <span class="badge badge-lg" :class="getGradeBadgeClass(student.grade)">
                      {{ student.grade }}
                    </span>
                  </td>
                  <td v-if="!selectedStudentId" class="text-center">
                    <span class="badge bg-secondary">{{ index + 1 }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- No Course Selected -->
    <div v-else class="text-center p-5">
      <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
      <h3 class="text-muted">Select a Course</h3>
      <p class="text-muted">Choose a course from the dropdown above to view detailed mark breakdown analysis.</p>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { mapGetters } from 'vuex';
import Chart from 'chart.js/auto';

export default {
  name: 'MarkBreakdown',
  props: {
    courseId: {
      type: [String, Number],
      default: null
    },
    userRole: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      courses: [],
      students: [],
      selectedCourseId: '',
      selectedStudentId: '',
      selectedAssessmentType: '',
      isLoading: false,
      viewMode: 'summary',
      courseStats: null,
      assessmentBreakdown: [],
      studentBreakdown: null,
      uniqueAssessments: [],
      filteredStudentMarks: [],
      assessmentChart: null,
      studentTrendChart: null
    };
  },
  computed: {
    ...mapGetters('auth', ['getUser']),
    currentUserRole() {
      return this.userRole || this.getUser?.role || '';
    }
  },
  mounted() {
    this.loadCourses();
  },
  beforeUnmount() {
    this.destroyCharts();
  },
  methods: {
    async loadCourses() {
      try {
        const userId = this.getUser?.id || 4; // Use Student One (ID: 4) for testing
        console.log('Loading courses for user ID:', userId, 'Role:', this.currentUserRole);

        let endpoint = '';
        if (this.currentUserRole === 'lecturer') {
          endpoint = `/marks-api.php?action=lecturer_courses&lecturer_id=${userId}`;
        } else if (this.currentUserRole === 'student') {
          endpoint = `/breakdown-api.php?action=student_courses&student_id=${userId}`;
        } else if (this.currentUserRole === 'advisor') {
          endpoint = `/breakdown-api.php?action=advisor_courses&advisor_id=${userId}`;
        } else {
          // Default to student courses for testing
          endpoint = `/breakdown-api.php?action=student_courses&student_id=4`;
        }

        console.log('API endpoint:', endpoint);
        const response = await axios.get(endpoint);
        this.courses = response.data.courses || [];
        
        console.log(`Loaded ${this.courses.length} courses for ${this.currentUserRole}:`, this.courses);
        
        // Add sample data if no courses are returned
        if (this.courses.length === 0) {
          console.log('No real courses found, adding sample courses');
          this.courses = [
            { id: 1, code: 'CS101', name: 'Introduction to Computer Science', semester: 'Fall 2024', academic_year: '2024/2025' },
            { id: 2, code: 'MATH201', name: 'Calculus II', semester: 'Fall 2024', academic_year: '2024/2025' },
            { id: 3, code: 'PHYS101', name: 'Physics I', semester: 'Fall 2024', academic_year: '2024/2025' }
          ];
        }
        
        // Auto-select course from route if available
        if (this.$route.params.courseId) {
          this.selectedCourseId = parseInt(this.$route.params.courseId);
          await this.loadCourseData();
        }
      } catch (error) {
        console.error('Error loading courses:', error);
        // Fallback to sample data
        this.courses = [
          { id: 1, code: 'CS101', name: 'Introduction to Computer Science', semester: 'Fall 2024', academic_year: '2024/2025' },
          { id: 2, code: 'MATH201', name: 'Calculus II', semester: 'Fall 2024', academic_year: '2024/2025' },
          { id: 3, code: 'PHYS101', name: 'Physics I', semester: 'Fall 2024', academic_year: '2024/2025' }
        ];
        
        // Auto-select course from route if available
        if (this.$route.params.courseId || this.courseId) {
          this.selectedCourseId = parseInt(this.$route.params.courseId || this.courseId);
          await this.loadCourseData();
        }
      }
    },

    async loadCourseData() {
      if (!this.selectedCourseId) {
        this.clearData();
        return;
      }

      this.isLoading = true;
      try {
        // Load students and marks for the course
        const response = await axios.get(`/breakdown-api.php?action=course_breakdown&course_id=${this.selectedCourseId}`);
        
        if (response.data && response.data.students) {
          // Map real data to component format
          this.students = response.data.students || [];
          this.uniqueAssessments = (response.data.components || []).map(comp => ({
            ...comp,
            id: comp.type // Use type as id for template key
          }));
          
          // Use backend's component breakdown if available
          if (response.data.component_breakdown) {
            this.assessmentBreakdown = response.data.component_breakdown.map(item => ({
              type: item.type,
              weightage: item.weightage,
              total: item.total_students,
              submitted: item.submissions,
              average: item.average_percentage
            }));
            
            console.log('Component breakdown mapped:', this.assessmentBreakdown);
          }
          
          // Map statistics with correct property names
          if (response.data.statistics) {
            this.courseStats = {
              totalStudents: response.data.statistics.total_students,
              totalAssessments: response.data.statistics.total_components,
              classAverage: response.data.statistics.class_average,
              atRiskStudents: response.data.statistics.at_risk_students
            };
          }
          
          console.log('Real data loaded:', {
            students: this.students.length,
            assessments: this.uniqueAssessments.length,
            stats: this.courseStats,
            breakdown: this.assessmentBreakdown
          });
          
          // Only process if we don't have backend breakdown data
          if (!response.data.component_breakdown) {
            console.log('No backend breakdown data, processing marks...');
            await this.processMarkBreakdown();
          } else {
            console.log('Using backend breakdown data, skipping processMarkBreakdown');
          }
        } else {
          // No real data, create sample data for demonstration
          console.log('No real data found, using sample data');
          this.createSampleData();
        }
        
        this.filteredStudentMarks = this.students;
        
        // If student role, auto-select current student
        if (this.currentUserRole === 'student') {
          this.selectedStudentId = this.getUser.id;
          await this.loadStudentBreakdown();
        }
        
        this.$nextTick(() => {
          this.createAssessmentChart();
        });
        
      } catch (error) {
        console.error('Error loading course data:', error);
        // Create sample data on error
        console.log('Error occurred, using sample data fallback');
        this.createSampleData();
      } finally {
        this.isLoading = false;
      }
    },

    createSampleData() {
      // Create sample course statistics
      this.courseStats = {
        totalStudents: 25,
        totalAssessments: 6,
        classAverage: 78.5,
        atRiskStudents: 3
      };

      // Create sample assessments
      this.uniqueAssessments = [
        { id: 1, name: 'Quiz 1', type: 'quiz', max_marks: 20, weightage: 5, date: '2024-09-15' },
        { id: 2, name: 'Assignment 1', type: 'assignment', max_marks: 100, weightage: 15, date: '2024-10-01' },
        { id: 3, name: 'Midterm Exam', type: 'midterm', max_marks: 100, weightage: 30, date: '2024-10-15' },
        { id: 4, name: 'Quiz 2', type: 'quiz', max_marks: 20, weightage: 5, date: '2024-11-01' },
        { id: 5, name: 'Assignment 2', type: 'assignment', max_marks: 100, weightage: 15, date: '2024-11-15' },
        { id: 6, name: 'Final Exam', type: 'final_exam', max_marks: 100, weightage: 30, date: '2024-12-10' }
      ];

      // Create sample students with marks
      this.students = [
        {
          id: 1,
          name: 'Alice Johnson',
          matric_number: 'A123456',
          email: 'alice@university.edu',
          finalMark: 85.2,
          grade: 'A',
          marks: {
            quiz: { obtained: 18, max_marks: 20, percentage: 90, weighted: 4.5 },
            assignment: { obtained: 85, max_marks: 100, percentage: 85, weighted: 12.75 },
            midterm: { obtained: 82, max_marks: 100, percentage: 82, weighted: 24.6 },
            final_exam: { obtained: 88, max_marks: 100, percentage: 88, weighted: 26.4 }
          }
        },
        {
          id: 2,
          name: 'Bob Smith',
          matric_number: 'B234567',
          email: 'bob@university.edu',
          finalMark: 72.8,
          grade: 'B',
          marks: {
            quiz: { obtained: 15, max_marks: 20, percentage: 75, weighted: 3.75 },
            assignment: { obtained: 78, max_marks: 100, percentage: 78, weighted: 11.7 },
            midterm: { obtained: 70, max_marks: 100, percentage: 70, weighted: 21 },
            final_exam: { obtained: 75, max_marks: 100, percentage: 75, weighted: 22.5 }
          }
        },
        {
          id: 3,
          name: 'Carol Davis',
          matric_number: 'C345678',
          email: 'carol@university.edu',
          finalMark: 45.2,
          grade: 'F',
          marks: {
            quiz: { obtained: 10, max_marks: 20, percentage: 50, weighted: 2.5 },
            assignment: { obtained: 45, max_marks: 100, percentage: 45, weighted: 6.75 },
            midterm: { obtained: 40, max_marks: 100, percentage: 40, weighted: 12 },
            final_exam: { obtained: 48, max_marks: 100, percentage: 48, weighted: 14.4 }
          }
        }
      ];

      // Process the sample data
      this.processMarkBreakdown();
    },

    async processMarkBreakdown() {
      const breakdown = {};
      
      this.uniqueAssessments.forEach(assessment => {
        if (!breakdown[assessment.type]) {
          breakdown[assessment.type] = {
            type: assessment.type,
            weightage: 0,
            total: 0,
            submitted: 0,
            totalMarks: 0,
            obtainedMarks: 0
          };
        }
        
        breakdown[assessment.type].weightage += assessment.weightage || 0;
        breakdown[assessment.type].total += this.students.length;
        
        this.students.forEach(student => {
          if (student.marks && student.marks[assessment.type]) {
            breakdown[assessment.type].submitted++;
            breakdown[assessment.type].totalMarks += student.marks[assessment.type].max_mark || 0;
            breakdown[assessment.type].obtainedMarks += student.marks[assessment.type].obtained || 0;
          }
        });
      });
      
      this.assessmentBreakdown = Object.values(breakdown).map(item => ({
        ...item,
        average: item.totalMarks > 0 ? Math.round((item.obtainedMarks / item.totalMarks) * 100) : 0
      }));
    },

    async calculateCourseStats() {
      const totalStudents = this.students.length;
      const totalAssessments = this.uniqueAssessments.length;
      
      let totalMarks = 0;
      let atRiskCount = 0;
      
      this.students.forEach(student => {
        const finalMark = this.calculateStudentFinalMark(student);
        totalMarks += finalMark;
        if (finalMark < 50) atRiskCount++;
      });
      
      this.courseStats = {
        totalStudents,
        totalAssessments,
        classAverage: totalStudents > 0 ? Math.round(totalMarks / totalStudents) : 0,
        atRiskStudents: atRiskCount
      };
    },

    async loadStudentBreakdown() {
      if (!this.selectedStudentId) {
        this.studentBreakdown = null;
        return;
      }

      const student = this.students.find(s => s.id == this.selectedStudentId);
      if (!student) return;

      const assessments = this.uniqueAssessments.map(assessment => {
        const mark = student.marks?.[assessment.type] || null;
        const classTotal = this.students.reduce((sum, s) => 
          sum + (s.marks?.[assessment.type]?.obtained || 0), 0);
        const classAverage = this.students.length > 0 ? 
          Math.round(classTotal / this.students.length) : 0;

        return {
          id: assessment.id,
          name: assessment.name,
          type: assessment.type,
          obtained: mark?.obtained || 0,
          max_marks: mark?.max_mark || assessment.max_mark || 0,
          percentage: mark ? Math.round((mark.obtained / mark.max_mark) * 100) : 0,
          weighted: mark?.weighted || 0,
          classAverage
        };
      });

      const finalMark = this.calculateStudentFinalMark(student);
      const grade = this.calculateGrade(finalMark);
      const rank = this.calculateStudentRank(student);

      // Analyze strengths and improvements
      const strengths = this.analyzeStrengths(assessments);
      const improvements = this.analyzeImprovements(assessments);

      this.studentBreakdown = {
        name: student.name,
        finalMark,
        grade,
        rank,
        assessments,
        strengths,
        improvements
      };

      this.$nextTick(() => {
        // Add a small delay to ensure the DOM is fully updated
        setTimeout(() => {
          this.createStudentTrendChart();
        }, 100);
      });
    },

    calculateStudentFinalMark(student) {
      // Use the final mark from backend if available
      if (student.finalMark !== undefined && student.finalMark !== null) {
        return student.finalMark;
      }
      
      // Fallback to calculation
      let totalWeighted = 0;
      
      Object.values(student.marks || {}).forEach(mark => {
        totalWeighted += mark.weighted || 0;
      });
      
      return Math.round(totalWeighted);
    },

    calculateGrade(mark) {
      if (mark >= 80) return 'A';
      if (mark >= 70) return 'B';
      if (mark >= 60) return 'C';
      if (mark >= 50) return 'D';
      return 'F';
    },

    calculateStudentRank(targetStudent) {
      const sortedStudents = [...this.students].sort((a, b) => 
        this.calculateStudentFinalMark(b) - this.calculateStudentFinalMark(a));
      
      return sortedStudents.findIndex(s => s.id === targetStudent.id) + 1;
    },

    analyzeStrengths(assessments) {
      const strengths = [];
      
      assessments.forEach(assessment => {
        if (assessment.percentage >= assessment.classAverage + 10) {
          strengths.push(`${this.formatAssessmentType(assessment.type)} (${assessment.percentage}%)`);
        }
      });
      
      return strengths.length > 0 ? strengths : ['Consistent performance across assessments'];
    },

    analyzeImprovements(assessments) {
      const improvements = [];
      
      assessments.forEach(assessment => {
        if (assessment.percentage < assessment.classAverage - 10) {
          improvements.push(`${this.formatAssessmentType(assessment.type)} (${assessment.percentage}%)`);
        }
      });
      
      return improvements.length > 0 ? improvements : ['Continue current study approach'];
    },

    createAssessmentChart() {
      this.destroyCharts();
      
      const ctx = document.getElementById('assessmentChart');
      if (!ctx || !this.assessmentBreakdown || this.assessmentBreakdown.length === 0) {
        return;
      }

      try {
        this.assessmentChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: this.assessmentBreakdown.map(item => this.formatAssessmentType(item.type)),
          datasets: [{
            label: 'Average Score (%)',
            data: this.assessmentBreakdown.map(item => item.average),
            backgroundColor: 'rgba(54, 162, 235, 0.8)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
          }, {
            label: 'Weightage (%)',
            data: this.assessmentBreakdown.map(item => item.weightage),
            backgroundColor: 'rgba(255, 99, 132, 0.8)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              max: 100
            }
          }
        }
      });
      } catch (error) {
        console.error('Error creating assessment chart:', error);
      }
    },

    createStudentTrendChart() {
      // Destroy existing chart first
      if (this.studentTrendChart) {
        this.studentTrendChart.destroy();
        this.studentTrendChart = null;
      }
      
      // Wait for DOM to be ready with a timeout
      this.$nextTick(() => {
        const ctx = document.getElementById('studentTrendChart');
        if (!ctx || !this.studentBreakdown || !this.studentBreakdown.assessments) {
          console.log('Chart creation skipped: element or data not ready', {
            hasCtx: !!ctx,
            hasBreakdown: !!this.studentBreakdown,
            hasAssessments: !!(this.studentBreakdown?.assessments)
          });
          return;
        }

        try {
          this.studentTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
              labels: this.studentBreakdown.assessments.map(a => a.name),
              datasets: [{
                label: 'Student Score',
                data: this.studentBreakdown.assessments.map(a => a.percentage),
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
              }, {
                label: 'Class Average',
                data: this.studentBreakdown.assessments.map(a => a.classAverage),
                borderColor: 'rgba(255, 159, 64, 1)',
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                tension: 0.1
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                y: {
                  beginAtZero: true,
                  max: 100
                }
              }
            }
          });
        } catch (error) {
          console.error('Error creating student trend chart:', error);
        }
      });
    },

    destroyCharts() {
      if (this.assessmentChart) {
        this.assessmentChart.destroy();
        this.assessmentChart = null;
      }
      if (this.studentTrendChart) {
        this.studentTrendChart.destroy();
        this.studentTrendChart = null;
      }
    },

    filterBreakdown() {
      // Implementation for filtering by assessment type
      this.filteredStudentMarks = this.students;
    },

    toggleView(mode) {
      this.viewMode = mode;
    },

    async exportBreakdown() {
      // Implementation for exporting detailed breakdown
      console.log('Exporting breakdown...');
    },

    async refreshData() {
      await this.loadCourseData();
    },

    clearData() {
      this.students = [];
      this.courseStats = null;
      this.assessmentBreakdown = [];
      this.studentBreakdown = null;
      this.uniqueAssessments = [];
      this.filteredStudentMarks = [];
      this.destroyCharts();
    },

    // Utility methods
    getStudentInitials(name) {
      return name.split(' ').map(n => n.charAt(0)).join('').toUpperCase();
    },

    formatAssessmentType(type) {
      return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    },

    getAssessmentBadgeClass(type) {
      const classes = {
        'quiz': 'bg-info',
        'assignment': 'bg-primary',
        'midterm': 'bg-warning text-dark',
        'final_exam': 'bg-danger',
        'project': 'bg-success'
      };
      return classes[type] || 'bg-secondary';
    },

    getAssessmentProgressClass(type) {
      const classes = {
        'quiz': 'bg-info',
        'assignment': 'bg-primary',
        'midterm': 'bg-warning',
        'final_exam': 'bg-danger',
        'project': 'bg-success'
      };
      return classes[type] || 'bg-secondary';
    },

    getScoreClass(percentage) {
      if (percentage >= 80) return 'text-success fw-bold';
      if (percentage >= 70) return 'text-info fw-bold';
      if (percentage >= 60) return 'text-warning fw-bold';
      if (percentage >= 50) return 'text-primary';
      return 'text-danger fw-bold';
    },

    getGradeBadgeClass(grade) {
      const classes = {
        'A': 'bg-success',
        'B': 'bg-info',
        'C': 'bg-warning text-dark',
        'D': 'bg-primary',
        'F': 'bg-danger'
      };
      return classes[grade] || 'bg-secondary';
    },

    getPerformanceStatus(studentScore, classAverage) {
      if (studentScore >= classAverage + 10) return 'Excellent';
      if (studentScore >= classAverage) return 'Above Average';
      if (studentScore >= classAverage - 10) return 'Average';
      return 'Needs Improvement';
    },

    getPerformanceStatusClass(studentScore, classAverage) {
      if (studentScore >= classAverage + 10) return 'bg-success';
      if (studentScore >= classAverage) return 'bg-info';
      if (studentScore >= classAverage - 10) return 'bg-warning text-dark';
      return 'bg-danger';
    }
  }
};
</script>

<style scoped>
.mark-breakdown h1 {
  color: #2c3e50;
  font-weight: 700;
}

.stat-card {
  transition: transform 0.2s ease;
  border-radius: 10px;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.avatar {
  width: 40px;
  height: 40px;
  background: linear-gradient(45deg, #007bff, #0056b3);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 0.9rem;
}

.metric-box {
  padding: 1rem;
  border-radius: 8px;
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
}

.card {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border: none;
  border-radius: 10px;
}

.table th {
  font-weight: 600;
  border-top: none;
}

.table td {
  vertical-align: middle;
}

.badge-lg {
  font-size: 1rem;
  padding: 0.5rem 0.75rem;
}

.badge-sm {
  font-size: 0.7rem;
}

.btn-group .btn.active {
  background-color: #007bff;
  color: white;
}

canvas {
  max-height: 300px;
}
</style>
