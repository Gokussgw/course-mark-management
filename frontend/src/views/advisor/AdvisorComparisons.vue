<template>
  <div class="advisor-comparisons">
    <div class="container mt-4">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header bg-success text-white">
              <h4 class="mb-0">
                <i class="fas fa-user-graduate me-2"></i>
                Advisee Performance Monitoring
              </h4>
              <small v-if="advisorData">
                Managing {{ advisorData.total_advisees }} student{{
                  advisorData.total_advisees !== 1 ? "s" : ""
                }}
              </small>
            </div>
            <div class="card-body">
              <!-- Loading State -->
              <div v-if="loading" class="text-center py-4">
                <div class="spinner-border text-success" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading advisee data...</p>
              </div>

              <!-- Error State -->
              <div v-if="error" class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ error }}
              </div>

              <!-- Advisees List -->
              <div v-if="advisees && !loading" class="advisees-section">
                <!-- Summary Cards -->
                <div class="row mb-4">
                  <div class="col-md-4">
                    <div class="card bg-primary text-white">
                      <div class="card-body text-center">
                        <h3>{{ advisees.length }}</h3>
                        <p class="mb-0">Total Advisees</p>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="card bg-success text-white">
                      <div class="card-body text-center">
                        <h3>{{ performingSatisfactorily }}</h3>
                        <p class="mb-0">Performing Well</p>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="card bg-warning text-dark">
                      <div class="card-body text-center">
                        <h3>{{ needingAttention }}</h3>
                        <p class="mb-0">Need Attention</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Advisees Table -->
                <div class="card">
                  <div class="card-header">
                    <h5 class="mb-0">
                      <i class="fas fa-table me-2"></i>
                      Advisee Performance Overview
                    </h5>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-striped">
                        <thead class="table-dark">
                          <tr>
                            <th>Student</th>
                            <th>Matric Number</th>
                            <th>Courses</th>
                            <th>Overall Performance</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="advisee in advisees" :key="advisee.id">
                            <td>
                              <strong>{{ advisee.name }}</strong>
                            </td>
                            <td>{{ advisee.matric_number }}</td>
                            <td>
                              <span class="badge bg-info">
                                {{ advisee.total_courses }} course{{
                                  advisee.total_courses !== 1 ? "s" : ""
                                }}
                              </span>
                            </td>
                            <td>
                              <div v-if="advisee.courses.length > 0">
                                <div class="row">
                                  <div class="col-12">
                                    <small class="text-muted"
                                      >Course Performance:</small
                                    >
                                  </div>
                                </div>
                                <div
                                  v-for="course in advisee.courses"
                                  :key="course.course_id"
                                  class="mb-1"
                                >
                                  <div
                                    class="d-flex justify-content-between align-items-center"
                                  >
                                    <small
                                      ><strong
                                        >{{ course.code }}:</strong
                                      ></small
                                    >
                                    <span
                                      class="badge ms-1"
                                      :class="
                                        getPerformanceBadgeClass(
                                          course.average_percentage
                                        )
                                      "
                                    >
                                      {{ course.average_percentage || "N/A" }}%
                                    </span>
                                  </div>
                                  <div
                                    class="progress mb-1"
                                    style="height: 5px"
                                  >
                                    <div
                                      class="progress-bar"
                                      :class="
                                        getPerformanceProgressClass(
                                          course.average_percentage
                                        )
                                      "
                                      :style="{
                                        width:
                                          (course.average_percentage || 0) +
                                          '%',
                                      }"
                                    ></div>
                                  </div>
                                </div>
                              </div>
                              <div v-else class="text-muted">
                                No assessment data available
                              </div>
                            </td>
                            <td>
                              <div class="btn-group" role="group">
                                <button
                                  class="btn btn-sm btn-outline-primary"
                                  @click="viewDetailedComparison(advisee)"
                                  :disabled="advisee.courses.length === 0"
                                >
                                  <i class="fas fa-chart-line me-1"></i>
                                  View Details
                                </button>
                                <button
                                  class="btn btn-sm btn-outline-success"
                                  @click="scheduleOneOnOne(advisee)"
                                >
                                  <i class="fas fa-calendar me-1"></i>
                                  Schedule
                                </button>
                              </div>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Detailed Comparison Modal -->
              <div
                class="modal fade"
                id="comparisonModal"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-xl">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">
                        <i class="fas fa-chart-bar me-2"></i>
                        Detailed Performance Comparison -
                        {{ selectedAdvisee?.name }}
                      </h5>
                      <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                      ></button>
                    </div>
                    <div class="modal-body">
                      <!-- Course Selection for Detailed View -->
                      <div class="row mb-3">
                        <div class="col-md-6">
                          <label for="modalCourseSelect" class="form-label"
                            >Select Course:</label
                          >
                          <select
                            id="modalCourseSelect"
                            v-model="selectedCourseForComparison"
                            @change="loadDetailedComparison"
                            class="form-select"
                          >
                            <option value="">Choose a course...</option>
                            <option
                              v-for="course in selectedAdvisee?.courses"
                              :key="course.course_id"
                              :value="course.course_id"
                            >
                              {{ course.code }} - {{ course.course_name }}
                            </option>
                          </select>
                        </div>
                      </div>

                      <!-- Detailed Comparison Loading -->
                      <div v-if="detailedLoading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                          <span class="visually-hidden"
                            >Loading detailed comparison...</span
                          >
                        </div>
                      </div>

                      <!-- Detailed Comparison Results -->
                      <div v-if="detailedComparison && !detailedLoading">
                        <!-- Student vs Class Performance -->
                        <div class="row mb-4">
                          <div class="col-md-6">
                            <div class="card border-primary">
                              <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">
                                  {{ selectedAdvisee.name }}'s Performance
                                </h6>
                              </div>
                              <div class="card-body">
                                <h4 class="text-primary">
                                  {{
                                    detailedComparison.student_performance
                                      .total_score
                                  }}%
                                </h4>
                                <p class="mb-1">
                                  <strong>Rank:</strong>
                                  {{
                                    detailedComparison.student_performance.rank
                                  }}
                                  /
                                  {{
                                    detailedComparison.student_performance
                                      .total_students
                                  }}
                                </p>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="card border-info">
                              <div class="card-header bg-info text-white">
                                <h6 class="mb-0">Class Average</h6>
                              </div>
                              <div class="card-body">
                                <h4 class="text-info">
                                  {{
                                    detailedComparison.class_performance
                                      .average_score
                                  }}%
                                </h4>
                                <p class="mb-1">
                                  <strong>Total Students:</strong>
                                  {{
                                    detailedComparison.class_performance
                                      .total_enrolled
                                  }}
                                </p>
                                <span
                                  class="badge"
                                  :class="
                                    getComparisonBadgeClass(
                                      detailedComparison.student_performance
                                        .total_score,
                                      detailedComparison.class_performance
                                        .average_score
                                    )
                                  "
                                >
                                  {{
                                    getComparisonText(
                                      detailedComparison.student_performance
                                        .total_score,
                                      detailedComparison.class_performance
                                        .average_score
                                    )
                                  }}
                                </span>
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Assessment Details Table -->
                        <div class="table-responsive">
                          <table class="table table-sm">
                            <thead class="table-light">
                              <tr>
                                <th>Assessment</th>
                                <th>Student Score</th>
                                <th>Class Average</th>
                                <th>Comparison</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr
                                v-for="assessment in detailedComparison.assessments"
                                :key="assessment.assessment.name"
                              >
                                <td>
                                  <strong>{{
                                    assessment.assessment.name
                                  }}</strong
                                  ><br />
                                  <small class="text-muted">{{
                                    assessment.assessment.type
                                  }}</small>
                                </td>
                                <td>
                                  {{ assessment.student_performance.mark }} /
                                  {{ assessment.assessment.max_mark }}
                                  <br />
                                  <span
                                    class="badge"
                                    :class="
                                      getPerformanceBadgeClass(
                                        assessment.student_performance
                                          .percentage
                                      )
                                    "
                                  >
                                    {{
                                      assessment.student_performance.percentage
                                    }}%
                                  </span>
                                </td>
                                <td>
                                  {{ assessment.class_statistics.average }}
                                  <br />
                                  <small class="text-muted"
                                    >{{
                                      assessment.class_statistics
                                        .average_percentage
                                    }}%</small
                                  >
                                </td>
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
                                        ? "Above"
                                        : "Below"
                                    }}
                                    Average
                                  </span>
                                  <br />
                                  <small class="text-muted">
                                    {{
                                      assessment.comparison
                                        .difference_from_average > 0
                                        ? "+"
                                        : ""
                                    }}{{
                                      assessment.comparison
                                        .difference_from_average
                                    }}
                                  </small>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                      >
                        Close
                      </button>
                      <button
                        type="button"
                        class="btn btn-primary"
                        @click="exportComparison"
                      >
                        <i class="fas fa-download me-1"></i>Export Report
                      </button>
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
import { Modal } from "bootstrap";

export default {
  name: "AdvisorComparisons",
  data() {
    return {
      advisorData: null,
      advisees: [],
      loading: false,
      error: null,
      selectedAdvisee: null,
      selectedCourseForComparison: "",
      detailedComparison: null,
      detailedLoading: false,
      comparisonModal: null,
    };
  },
  computed: {
    performingSatisfactorily() {
      return this.advisees.filter((advisee) => {
        const avgPerformance = this.getOverallPerformance(advisee);
        return avgPerformance >= 70;
      }).length;
    },
    needingAttention() {
      return this.advisees.filter((advisee) => {
        const avgPerformance = this.getOverallPerformance(advisee);
        return avgPerformance < 70 && avgPerformance > 0;
      }).length;
    },
  },
  async created() {
    await this.loadAdvisees();
  },
  mounted() {
    this.comparisonModal = new Modal(
      document.getElementById("comparisonModal")
    );
  },
  methods: {
    async loadAdvisees() {
      this.loading = true;
      this.error = null;

      try {
        const response = await axios.get("/api/comparisons/advisor/students");
        this.advisorData = response.data.advisor;
        this.advisees = response.data.advisees;
      } catch (error) {
        console.error("Error loading advisees:", error);
        this.error =
          error.response?.data?.error || "Failed to load advisee data";
      } finally {
        this.loading = false;
      }
    },
    getOverallPerformance(advisee) {
      if (!advisee.courses || advisee.courses.length === 0) return 0;

      const validCourses = advisee.courses.filter(
        (course) => course.average_percentage !== null
      );
      if (validCourses.length === 0) return 0;

      const total = validCourses.reduce(
        (sum, course) => sum + course.average_percentage,
        0
      );
      return total / validCourses.length;
    },
    getPerformanceBadgeClass(percentage) {
      if (!percentage) return "bg-secondary";
      if (percentage >= 90) return "bg-success";
      if (percentage >= 80) return "bg-primary";
      if (percentage >= 70) return "bg-info";
      if (percentage >= 60) return "bg-warning";
      return "bg-danger";
    },
    getPerformanceProgressClass(percentage) {
      if (!percentage) return "bg-secondary";
      if (percentage >= 90) return "bg-success";
      if (percentage >= 80) return "bg-primary";
      if (percentage >= 70) return "bg-info";
      if (percentage >= 60) return "bg-warning";
      return "bg-danger";
    },
    getComparisonBadgeClass(studentScore, classAverage) {
      const difference = studentScore - classAverage;
      if (difference > 10) return "bg-success";
      if (difference > 0) return "bg-primary";
      if (difference > -10) return "bg-warning";
      return "bg-danger";
    },
    getComparisonText(studentScore, classAverage) {
      const difference = studentScore - classAverage;
      if (difference > 10) return `Excellent (+${difference.toFixed(1)})`;
      if (difference > 0) return `Above Average (+${difference.toFixed(1)})`;
      if (difference > -10) return `Below Average (${difference.toFixed(1)})`;
      return `Needs Support (${difference.toFixed(1)})`;
    },
    viewDetailedComparison(advisee) {
      this.selectedAdvisee = advisee;
      this.selectedCourseForComparison = "";
      this.detailedComparison = null;
      this.comparisonModal.show();
    },
    async loadDetailedComparison() {
      if (!this.selectedCourseForComparison || !this.selectedAdvisee) return;

      this.detailedLoading = true;

      try {
        const response = await axios.get(
          `/api/comparisons/coursemates/${this.selectedCourseForComparison}`,
          {
            params: { student_id: this.selectedAdvisee.id },
          }
        );
        this.detailedComparison = response.data;
      } catch (error) {
        console.error("Error loading detailed comparison:", error);
        this.error = "Failed to load detailed comparison";
      } finally {
        this.detailedLoading = false;
      }
    },
    scheduleOneOnOne(advisee) {
      // Placeholder for scheduling functionality
      alert(
        `Scheduling one-on-one meeting with ${advisee.name}. This feature would integrate with a calendar system.`
      );
    },
    exportComparison() {
      // Placeholder for export functionality
      alert(
        "Export functionality would generate a PDF or Excel report of the comparison data."
      );
    },
  },
};
</script>

<style scoped>
.advisor-comparisons {
  min-height: 100vh;
  background-color: #f8f9fa;
}

.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border: 1px solid rgba(0, 0, 0, 0.125);
}

.progress {
  height: 5px;
}

.btn-group .btn {
  font-size: 0.875rem;
}

.modal-xl {
  max-width: 1200px;
}

.table th {
  font-weight: 600;
  border-top: none;
}

.badge {
  font-size: 0.8em;
}
</style>
