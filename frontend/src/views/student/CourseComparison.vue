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
                                comparisonData.student_performance.total_score
                              }}%
                            </h3>
                            <small class="text-muted">Overall Score</small>
                          </div>
                          <div class="col-6">
                            <h4 class="text-primary">
                              {{ comparisonData.student_performance.rank }}
                              <small class="text-muted"
                                >/
                                {{
                                  comparisonData.student_performance
                                    .total_students
                                }}</small
                              >
                            </h4>
                            <small class="text-muted">Class Ranking</small>
                          </div>
                        </div>
                        <div class="mt-2">
                          <small class="text-muted">
                            You're in the
                            {{
                              comparisonData.student_performance.percentile
                            }}th percentile
                          </small>
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
                                comparisonData.class_performance.average_score
                              }}%
                            </h3>
                            <small class="text-muted">Class Average</small>
                          </div>
                          <div class="col-6">
                            <h4 class="text-secondary">
                              {{
                                comparisonData.class_performance.total_enrolled
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
                            v-for="assessment in comparisonData.assessments"
                            :key="assessment.assessment.name"
                          >
                            <td>
                              <strong>{{ assessment.assessment.name }}</strong>
                              <br />
                              <small class="text-muted">
                                Weight: {{ assessment.assessment.weightage }}%
                              </small>
                            </td>
                            <td>
                              <span class="badge bg-secondary">
                                {{ assessment.assessment.type }}
                              </span>
                            </td>
                            <td>
                              {{ assessment.student_performance.mark }} /
                              {{ assessment.assessment.max_mark }}
                            </td>
                            <td>
                              <span
                                class="badge"
                                :class="
                                  getPerformanceBadgeClass(
                                    assessment.student_performance.percentage
                                  )
                                "
                              >
                                {{ assessment.student_performance.percentage }}%
                              </span>
                            </td>
                            <td>{{ assessment.class_statistics.average }}</td>
                            <td>
                              {{
                                assessment.class_statistics.average_percentage
                              }}%
                            </td>
                            <td>{{ assessment.class_statistics.highest }}</td>
                            <td>
                              <span
                                class="badge"
                                :class="
                                  assessment.comparison.above_average
                                    ? 'bg-success'
                                    : 'bg-warning'
                                "
                              >
                                {{
                                  assessment.comparison.above_average
                                    ? "Above Average"
                                    : "Below Average"
                                }}
                              </span>
                              <br />
                              <small class="text-muted">
                                {{
                                  assessment.comparison
                                    .difference_from_average > 0
                                    ? "+"
                                    : ""
                                }}{{
                                  assessment.comparison.difference_from_average
                                }}
                                pts
                              </small>
                            </td>
                            <td>
                              <small class="text-muted">
                                25th:
                                {{
                                  assessment.class_statistics.percentiles[
                                    "25th"
                                  ]
                                }}<br />
                                50th:
                                {{
                                  assessment.class_statistics.percentiles[
                                    "50th"
                                  ]
                                }}<br />
                                75th:
                                {{
                                  assessment.class_statistics.percentiles[
                                    "75th"
                                  ]
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
    performanceComparisonClass() {
      if (!this.comparisonData) return "bg-secondary";
      const studentScore = this.comparisonData.student_performance.total_score;
      const classAverage = this.comparisonData.class_performance.average_score;

      if (studentScore > classAverage + 10) return "bg-success";
      if (studentScore > classAverage) return "bg-primary";
      if (studentScore > classAverage - 10) return "bg-warning";
      return "bg-danger";
    },
    performanceComparisonText() {
      if (!this.comparisonData) return "No data";
      const studentScore = this.comparisonData.student_performance.total_score;
      const classAverage = this.comparisonData.class_performance.average_score;
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

      this.comparisonData.assessments.forEach((assessment) => {
        if (assessment.comparison.above_average) {
          if (assessment.comparison.difference_from_average > 5) {
            strengths.push(
              `Strong performance in ${assessment.assessment.type} (${assessment.assessment.name})`
            );
          }
        } else {
          if (assessment.comparison.difference_from_average < -5) {
            improvements.push(
              `Focus needed on ${assessment.assessment.type} (${assessment.assessment.name})`
            );
          }
        }
      });

      // Overall performance insights
      const studentPercentile =
        this.comparisonData.student_performance.percentile;
      if (studentPercentile > 75) {
        strengths.push("Consistently high performer in class");
      } else if (studentPercentile < 25) {
        improvements.push("Overall performance below class median");
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
        const user = this.$store.getters["auth/user"];
        if (user && user.role === "student") {
          // Get courses the student is enrolled in
          const response = await axios.get("/api/enrollments/student/courses");
          this.courses = response.data;
        }
      } catch (error) {
        console.error("Error loading courses:", error);
        this.error = "Failed to load courses";
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
