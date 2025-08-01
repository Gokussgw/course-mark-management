<template>
  <div class="coursemate-comparison">
    <div class="container mt-4">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header bg-primary text-white">
              <h4 class="mb-0">
                <i class="fas fa-chart-line me-2"></i>
                Performance Comparison with Coursemates
              </h4>
            </div>
            <div class="card-body">
              <!-- Course Selection -->
              <div class="row mb-4">
                <div class="col-md-6">
                  <label for="courseSelect" class="form-label"
                    >Select Course:</label
                  >
                  <select
                    id="courseSelect"
                    v-model="selectedCourseId"
                    @change="loadComparison"
                    class="form-select"
                  >
                    <option value="">Choose a course...</option>
                    <option
                      v-for="course in courses"
                      :key="course.id"
                      :value="course.id"
                    >
                      {{ course.code }} - {{ course.name }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Loading State -->
              <div v-if="loading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading comparison data...</p>
              </div>

              <!-- Error State -->
              <div v-if="error" class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ error }}
              </div>

              <!-- Comparison Results -->
              <div v-if="comparisonData && !loading" class="comparison-results">
                <!-- Overall Performance Summary -->
                <div class="row mb-4">
                  <div class="col-md-6">
                    <div class="card border-success">
                      <div class="card-header bg-light">
                        <h5 class="mb-0">
                          <i class="fas fa-user me-2"></i>
                          Your Performance
                        </h5>
                      </div>
                      <div class="card-body">
                        <div class="row">
                          <div class="col-6">
                            <h3 class="text-success">
                              {{
                                comparisonData?.student?.marks?.overall?.final_grade || 0
                              }}%
                            </h3>
                            <small class="text-muted">Overall Score</small>
                          </div>
                          <div class="col-6">
                            <h4 class="text-primary">
                              {{ comparisonData?.student?.position || 'N/A' }}
                              <small class="text-muted"
                                >/
                                {{
                                  comparisonData?.student?.total_students || 0
                                }}</small
                              >
                            </h4>
                            <small class="text-muted">Class Ranking</small>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="card border-info">
                      <div class="card-header bg-light">
                        <h5 class="mb-0">
                          <i class="fas fa-users me-2"></i>
                          Class Performance
                        </h5>
                      </div>
                      <div class="card-body">
                        <div class="row">
                          <div class="col-6">
                            <h3 class="text-info">
                              {{
                                comparisonData?.student?.marks?.overall?.class_stats?.class_average || 0
                              }}%
                            </h3>
                            <small class="text-muted">Class Average</small>
                          </div>
                          <div class="col-6">
                            <h4 class="text-secondary">
                              {{
                                comparisonData?.student?.total_students || 0
                              }}
                            </h4>
                            <small class="text-muted">Total Students</small>
                          </div>
                        </div>
                        <div class="mt-2">
                          <span
                            class="badge"
                            :class="performanceComparisonClass"
                          >
                            {{ performanceComparisonText }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Assessment-wise Comparison -->
                <div class="card">
                  <div class="card-header">
                    <h5 class="mb-0">
                      <i class="fas fa-clipboard-list me-2"></i>
                      Assessment-wise Comparison
                    </h5>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-striped">
                        <thead class="table-dark">
                          <tr>
                            <th>Assessment</th>
                            <th>Type</th>
                            <th>Your Score</th>
                            <th>Your %</th>
                            <th>Class Avg</th>
                            <th>Class Avg %</th>
                            <th>Highest</th>
                            <th>Performance</th>
                            <th>Percentile Range</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr
                            v-for="(assessment, key) in assessmentData"
                            :key="key"
                          >
                            <td>
                              <strong>{{ formatAssessmentName(key) }}</strong>
                              <br />
                              <small class="text-muted">
                                Component: {{ key }}
                              </small>
                            </td>
                            <td>
                              <span class="badge bg-secondary">
                                {{ key.replace('_', ' ') }}
                              </span>
                            </td>
                            <td>
                              {{ assessment.mark || 0 }} /
                              100
                            </td>
                            <td>
                              <span
                                class="badge"
                                :class="
                                  getPerformanceBadgeClass(
                                    assessment.percentage
                                  )
                                "
                              >
                                {{ assessment.percentage || 0 }}%
                              </span>
                            </td>
                            <td>{{ assessment.class_stats?.class_average || 0 }}</td>
                            <td>
                              {{
                                assessment.class_stats?.class_average || 0
                              }}%
                            </td>
                            <td>{{ assessment.class_stats?.highest_mark || 0 }}</td>
                            <td>
                              <span
                                class="badge"
                                :class="
                                  assessment.comparison?.above_average
                                    ? 'bg-success'
                                    : 'bg-warning'
                                "
                              >
                                {{
                                  assessment.comparison?.above_average
                                    ? "Above Average"
                                    : "Below Average"
                                }}
                              </span>
                              <br />
                              <small class="text-muted">
                                {{
                                  (assessment.comparison?.difference_from_average || 0) > 0
                                    ? "+"
                                    : ""
                                }}{{
                                  assessment.comparison?.difference_from_average || 0
                                }}
                                pts
                              </small>
                            </td>
                            <td>
                              <small class="text-muted">
                                25th:
                                {{
                                  assessment.class_stats?.percentiles?.[
                                    "25th"
                                  ] || 0
                                }}<br />
                                50th:
                                {{
                                  assessment.class_stats?.percentiles?.[
                                    "50th"
                                  ] || 0
                                }}<br />
                                75th:
                                {{
                                  assessment.class_stats?.percentiles?.[
                                    "75th"
                                  ] || 0
                                }}
                              </small>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- Performance Insights -->
                <div class="row mt-4">
                  <div class="col-12">
                    <div class="card border-warning">
                      <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                          <i class="fas fa-lightbulb me-2"></i>
                          Performance Insights
                        </h5>
                      </div>
                      <div class="card-body">
                        <div class="row">
                          <div class="col-md-6">
                            <h6>Strengths:</h6>
                            <ul class="list-unstyled">
                              <li
                                v-for="strength in performanceInsights.strengths"
                                :key="strength"
                                class="text-success"
                              >
                                <i class="fas fa-check-circle me-2"></i
                                >{{ strength }}
                              </li>
                            </ul>
                          </div>
                          <div class="col-md-6">
                            <h6>Areas for Improvement:</h6>
                            <ul class="list-unstyled">
                              <li
                                v-for="improvement in performanceInsights.improvements"
                                :key="improvement"
                                class="text-warning"
                              >
                                <i class="fas fa-exclamation-circle me-2"></i
                                >{{ improvement }}
                              </li>
                            </ul>
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
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "CourseComparison",
  data() {
    return {
      courses: [],
      selectedCourseId: "",
      comparisonData: null,
      loading: false,
      error: null,
    };
  },
  computed: {
    assessmentData() {
      if (!this.comparisonData?.student?.marks) return {};
      const marks = this.comparisonData.student.marks;
      const assessments = {};
      Object.keys(marks).forEach(key => {
        if (key !== 'overall') {
          const assessment = marks[key];
          const studentPercentage = assessment.percentage || 0;
          const classAverage = assessment.class_stats?.class_average || 0;
          const difference = studentPercentage - classAverage;
          
          assessments[key] = {
            ...assessment,
            comparison: {
              above_average: difference > 0,
              difference_from_average: difference.toFixed(1)
            }
          };
        }
      });
      return assessments;
    },
    performanceComparisonClass() {
      if (!this.comparisonData) return "bg-secondary";
      const studentScore = this.comparisonData.student?.marks?.overall?.final_grade || 0;
      const classAverage = this.comparisonData.student?.marks?.overall?.class_stats?.class_average || 0;

      if (studentScore > classAverage + 10) return "bg-success";
      if (studentScore > classAverage) return "bg-primary";
      if (studentScore > classAverage - 10) return "bg-warning";
      return "bg-danger";
    },
    performanceComparisonText() {
      if (!this.comparisonData) return "No data";
      const studentScore = this.comparisonData.student?.marks?.overall?.final_grade || 0;
      const classAverage = this.comparisonData.student?.marks?.overall?.class_stats?.class_average || 0;
      const difference = studentScore - classAverage;

      if (difference > 10)
        return `Excellent (+${difference.toFixed(1)} above average)`;
      if (difference > 0) return `Above Average (+${difference.toFixed(1)})`;
      if (difference > -10) return `Below Average (${difference.toFixed(1)})`;
      return `Needs Improvement (${difference.toFixed(1)})`;
    },
    performanceInsights() {
      if (!this.comparisonData) return { strengths: [], improvements: [] };

      const strengths = [];
      const improvements = [];

      Object.keys(this.assessmentData).forEach((key) => {
        const assessment = this.assessmentData[key];
        const studentPercentage = parseFloat(assessment.percentage) || 0;
        const classAverage = parseFloat(assessment.class_stats?.class_average) || 0;
        const difference = studentPercentage - classAverage;
        
        if (difference > 5) {
          strengths.push(
            `Strong performance in ${this.formatAssessmentName(key)} (+${difference.toFixed(1)}% above average)`
          );
        } else if (difference < -5) {
          improvements.push(
            `Focus needed on ${this.formatAssessmentName(key)} (${difference.toFixed(1)}% below average)`
          );
        }
      });

      // Overall performance insights based on rank
      const studentRank = this.comparisonData?.student?.position;
      const totalStudents = this.comparisonData?.student?.total_students;
      if (studentRank && totalStudents) {
        if (studentRank <= Math.ceil(totalStudents * 0.25)) {
          strengths.push("Consistently high performer in class");
        } else if (studentRank > Math.ceil(totalStudents * 0.75)) {
          improvements.push("Overall performance below class median");
        }
      }

      return { strengths, improvements };
    },
  },
  async created() {
    await this.loadCourses();
  },
  methods: {
    async loadCourses() {
      try {
        console.log("=== loadCourses called ===");
        
        // Check if user is authenticated first
        const isAuthenticated = this.$store.getters["auth/isAuthenticated"];
        console.log("Is authenticated:", isAuthenticated);
        
        if (!isAuthenticated) {
          this.error = "Please log in to view your courses";
          this.$router.push('/login');
          return;
        }

        let currentUser = this.$store.getters["auth/user"];
        console.log("Current user from store:", currentUser);
        console.log("LocalStorage token:", localStorage.getItem('token'));
        console.log("LocalStorage user:", localStorage.getItem('user'));
        
        // If store user is undefined but we have token, try to re-init from localStorage
        if (!currentUser && localStorage.getItem('token') && localStorage.getItem('user')) {
          console.log("Re-initializing auth from localStorage...");
          await this.$store.dispatch('auth/checkAuth');
          currentUser = this.$store.getters["auth/user"];
          console.log("Refreshed user:", currentUser);
        }
        
        // Final fallback: parse user directly from localStorage if store is still empty
        if (!currentUser && localStorage.getItem('user')) {
          try {
            console.log("Emergency fallback: parsing user from localStorage");
            currentUser = JSON.parse(localStorage.getItem('user'));
            console.log("Emergency fallback user:", currentUser);
          } catch (e) {
            console.error("Failed to parse user from localStorage:", e);
          }
        }

        if (currentUser && currentUser.role === "student") {
          console.log("Making API call to get courses..."); // Debug log
          // Get courses the student is enrolled in
          const response = await axios.get("/api/enrollments/student/courses");
          console.log("API Response:", response.data); // Debug log
          this.courses = response.data;
          
          if (this.courses.length === 0) {
            this.error = "You are not enrolled in any courses yet";
          }
        } else {
          this.error = `Access denied - Student role required. Current user role: ${currentUser?.role || 'undefined'}`;
        }
      } catch (error) {
        console.error("Error loading courses:", error);
        if (error.response?.status === 401) {
          this.error = "Authentication failed - please log in again";
          this.$router.push('/login');
        } else {
          this.error = error.response?.data?.error || "Failed to load courses";
        }
      }
    },
    async loadComparison() {
      if (!this.selectedCourseId) {
        this.comparisonData = null;
        return;
      }

      this.loading = true;
      this.error = null;

      try {
        const response = await axios.get(
          `/api/comparisons/coursemates/${this.selectedCourseId}`
        );
        this.comparisonData = response.data;
      } catch (error) {
        console.error("Error loading comparison:", error);
        this.error =
          error.response?.data?.error || "Failed to load comparison data";
      } finally {
        this.loading = false;
      }
    },
    getPerformanceBadgeClass(percentage) {
      if (percentage >= 90) return "bg-success";
      if (percentage >= 80) return "bg-primary";
      if (percentage >= 70) return "bg-info";
      if (percentage >= 60) return "bg-warning";
      return "bg-danger";
    },
    formatAssessmentName(key) {
      const names = {
        assignment: 'Assignment',
        quiz: 'Quiz',
        test: 'Test',
        final_exam: 'Final Exam'
      };
      return names[key] || key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    },
  },
};
</script>

<style scoped>
.coursemate-comparison {
  min-height: 100vh;
  background-color: #f8f9fa;
}

.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border: 1px solid rgba(0, 0, 0, 0.125);
}

.table th {
  font-weight: 600;
  border-top: none;
}

.badge {
  font-size: 0.8em;
}

.performance-metric {
  font-size: 1.2em;
  font-weight: 600;
}
</style>
