<template>
  <div class="dashboard">
    <!-- Dashboard Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="mb-1">Student Dashboard</h1>
        <p class="text-muted mb-0">Welcome back, {{ getUser.name }}</p>
      </div>
      <div class="d-flex align-it      // When loading changes from true to false, initialize chart
      if (oldVal && !newVal) {
        this.$nextTick(() => {
          setTimeout(() => {
            this.initChart();
          }, 100); // Small delay to ensure DOM is fully rendered
        });nter gap-3">
        <router-link
          to="/student/feedback"
          class="btn btn-info"
          title="My Feedback"
        >
          <i class="fas fa-comments me-2"></i>My Feedback
        </router-link>
        <div class="user-info text-end">
          <small class="text-muted d-block">{{ getUser.email }}</small>
          <small class="badge bg-success">{{ getUser.role }}</small>
        </div>
        <button class="btn btn-outline-danger" @click="logout" title="Logout">
          <i class="fas fa-sign-out-alt me-2"></i>Logout
        </button>
      </div>
    </div>

    <div class="row">
      <!-- My Academic Ranking -->
      <div class="col-md-12 mb-4">
        <student-ranking 
          :show-individual-ranking="true"
          :show-class-rankings="true"
          :is-own-ranking="true"
        />
      </div>
    </div>

    <!-- Notifications Row -->
    <div class="row">
      <div class="col-md-12 mb-4">
        <div class="card">
          <div class="card-body">
            <NotificationPanel 
              :user-id="getUser.id"
              @notification-clicked="handleNotificationClick"
            />
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-7 mb-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">My Courses</h5>
            <p class="card-text text-muted mb-4">
              Your current courses and progress
            </p>

            <div v-if="isLoading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>

            <div v-else-if="courses.length === 0" class="text-center py-4">
              <p>You are not enrolled in any courses yet.</p>
              <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
            </div>

            <div v-else>
              <div
                v-for="course in courses"
                :key="course.id"
                class="course-card mb-3"
              >
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <h6>
                      {{ course.code }} - {{ course.name }}
                      <span class="badge bg-primary ms-2">{{
                        course.semester
                      }}</span>
                    </h6>
                    <p class="text-muted">
                      Lecturer: {{ course.lecturer_name || "Not assigned" }}
                    </p>
                  </div>

                  <div class="text-end">
                    <router-link
                      :to="`/student/course/${course.id}`"
                      class="btn btn-sm btn-outline-primary me-1"
                    >
                      <i class="fas fa-chart-bar me-1"></i> View Marks
                    </router-link>
                    <router-link
                      :to="`/student/breakdown/${course.id}`"
                      class="btn btn-sm btn-outline-warning me-1"
                    >
                      <i class="fas fa-chart-pie me-1"></i> Breakdown
                    </router-link>
                    <router-link
                      :to="`/student/simulation/${course.id}`"
                      class="btn btn-sm btn-outline-info me-1"
                    >
                      <i class="fas fa-calculator me-1"></i> Simulate
                    </router-link>
                    <router-link
                      to="/student/comparison"
                      class="btn btn-sm btn-outline-success"
                    >
                      <i class="fas fa-users me-1"></i> Compare
                    </router-link>
                  </div>
                </div>

                <div class="progress mt-2" style="height: 10px">
                  <div
                    class="progress-bar"
                    :class="getCourseProgressBarClass(course.progress)"
                    role="progressbar"
                    :style="`width: ${course.progress}%`"
                    :aria-valuenow="course.progress"
                    aria-valuemin="0"
                    aria-valuemax="100"
                  >
                    {{ course.progress }}%
                  </div>
                </div>

                <div class="d-flex justify-content-between mt-1">
                  <small class="text-muted"
                    >Current Average: {{ course.average }}%</small
                  >
                  <small class="text-muted"
                    >Rank: {{ course.rank || "N/A" }}</small
                  >
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-5 mb-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Upcoming Assessments</h5>
            <p class="card-text text-muted mb-4">
              Your next scheduled assessments
            </p>

            <div v-if="isLoading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>

            <div
              v-else-if="upcomingAssessments.length === 0"
              class="text-center py-4"
            >
              <p>No upcoming assessments.</p>
              <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
            </div>

            <ul v-else class="list-group list-group-flush">
              <li
                v-for="assessment in upcomingAssessments"
                :key="assessment.id"
                class="list-group-item"
              >
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1">{{ assessment.name }}</h6>
                  <span
                    class="badge"
                    :class="getAssessmentTypeBadgeClass(assessment.type)"
                  >
                    {{ assessment.type }}
                  </span>
                </div>
                <p class="mb-1">
                  {{ getCourseNameById(assessment.course_id) }}
                </p>
                <div class="d-flex w-100 justify-content-between">
                  <small class="text-muted">
                    <i class="fas fa-weight me-1"></i>
                    {{ assessment.weightage }}% of total
                  </small>
                  <small class="text-muted">
                    <i class="far fa-calendar-alt me-1"></i>
                    {{ formatDate(assessment.date) }}
                  </small>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12 mb-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Performance Overview</h5>
            <p class="card-text text-muted mb-4">
              Your academic performance across all courses
            </p>

            <div v-if="isLoading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>

            <div v-else class="chart-container">
              <!-- Placeholder for Chart.js integration -->
              <canvas id="performanceChart" width="400" height="200"></canvas>
              <p class="text-center text-muted mt-3">
                <i class="fas fa-info-circle me-1"></i>
                Chart shows your performance trend over time across all courses
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from "vuex";
import Chart from "chart.js/auto";
import StudentRanking from '@/components/rankings/StudentRanking.vue';
import NotificationPanel from '@/components/notifications/NotificationPanel.vue';

export default {
  name: "StudentDashboard",
  components: {
    StudentRanking,
    NotificationPanel
  },
  data() {
    return {
      courses: [],
      assessments: [],
      performanceChart: null,
      // Sample data for demonstration - in a real app, this would come from the API
      coursesData: [
        {
          id: 1,
          code: "CS101",
          name: "Introduction to Programming",
          lecturer_name: "Dr. Smith",
          semester: "Fall 2023",
          progress: 75,
          average: 82,
          rank: "3/30",
        },
        {
          id: 2,
          code: "CS202",
          name: "Database Systems",
          lecturer_name: "Prof. Johnson",
          semester: "Fall 2023",
          progress: 60,
          average: 78,
          rank: "5/25",
        },
        {
          id: 3,
          code: "MATH201",
          name: "Discrete Mathematics",
          lecturer_name: "Dr. Wilson",
          semester: "Fall 2023",
          progress: 90,
          average: 85,
          rank: "2/28",
        },
      ],
      assessmentsData: [
        {
          id: 1,
          name: "Midterm Exam",
          course_id: 1,
          type: "midterm",
          date: "2023-10-15",
          weightage: 30,
        },
        {
          id: 2,
          name: "Assignment 2",
          course_id: 2,
          type: "assignment",
          date: "2023-10-10",
          weightage: 15,
        },
        {
          id: 3,
          name: "Quiz 3",
          course_id: 3,
          type: "quiz",
          date: "2023-10-05",
          weightage: 10,
        },
      ],
    };
  },
  computed: {
    ...mapGetters(["isLoading"]),
    ...mapGetters("auth", ["getUser"]),

    upcomingAssessments() {
      const today = new Date();

      return this.assessmentsData
        .filter((a) => new Date(a.date) >= today)
        .sort((a, b) => new Date(a.date) - new Date(b.date))
        .slice(0, 5);
    },
  },
  watch: {
    isLoading(newVal, oldVal) {
      // When loading changes from true to false, initialize chart
      if (oldVal && !newVal) {
        this.$nextTick(() => {
          setTimeout(() => {
            this.initChart();
          }, 300);
        });
      }
    }
  },
  mounted() {
    this.loadData();
  },
  beforeUnmount() {
    // Clean up chart to prevent memory leaks
    if (this.performanceChart) {
      this.performanceChart.destroy();
      this.performanceChart = null;
    }
  },
  methods: {
    async loadData() {
      try {
        // In a real app, we would fetch the student's courses and assessments from the API
        // For now, using sample data
        this.courses = this.coursesData;
        this.assessments = this.assessmentsData;

        // Set loading to false - this will trigger the watcher to init the chart
        this.$store.dispatch('setLoading', false);
      } catch (error) {
        console.error("Error loading dashboard data:", error);
      }
    },

    getCourseNameById(courseId) {
      const course = this.courses.find((c) => c.id === courseId);
      return course ? `${course.code} - ${course.name}` : "Unknown Course";
    },

    getCourseProgressBarClass(progress) {
      if (progress >= 80) return "bg-success";
      if (progress >= 60) return "bg-info";
      if (progress >= 40) return "bg-warning";
      return "bg-danger";
    },

    getAssessmentTypeBadgeClass(type) {
      switch (type) {
        case "quiz":
          return "bg-info";
        case "assignment":
          return "bg-primary";
        case "midterm":
          return "bg-warning";
        case "final_exam":
          return "bg-danger";
        default:
          return "bg-secondary";
      }
    },

    formatDate(dateString) {
      if (!dateString) return "Not scheduled";

      const date = new Date(dateString);
      return date.toLocaleDateString("en-GB", {
        day: "numeric",
        month: "short",
        year: "numeric",
      });
    },

    initChart() {
      try {
        const ctx = document.getElementById("performanceChart");
        
        // Check if canvas element exists
        if (!ctx) {
          console.warn("Performance chart canvas not found, skipping chart initialization");
          return;
        }

        // Destroy existing chart to prevent conflicts
        if (this.performanceChart) {
          this.performanceChart.destroy();
          this.performanceChart = null;
        }

        // Sample data for the chart
        this.performanceChart = new Chart(ctx, {
        type: "line",
        data: {
          labels: [
            "Quiz 1",
            "Assignment 1",
            "Quiz 2",
            "Midterm",
            "Assignment 2",
            "Quiz 3",
          ],
          datasets: [
            {
              label: "CS101",
              data: [75, 82, 80, 85, 90, 88],
              borderColor: "rgba(52, 152, 219, 1)",
              backgroundColor: "rgba(52, 152, 219, 0.1)",
              tension: 0.4,
            },
            {
              label: "CS202",
              data: [65, 70, 75, 78, 80, 82],
              borderColor: "rgba(46, 204, 113, 1)",
              backgroundColor: "rgba(46, 204, 113, 0.1)",
              tension: 0.4,
            },
            {
              label: "MATH201",
              data: [80, 85, 82, 90, 88, 92],
              borderColor: "rgba(231, 76, 60, 1)",
              backgroundColor: "rgba(231, 76, 60, 0.1)",
              tension: 0.4,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: "top",
            },
            tooltip: {
              mode: "index",
              intersect: false,
            },
          },
          scales: {
            y: {
              min: 0,
              max: 100,
              ticks: {
                callback: function (value) {
                  return value + "%";
                },
              },
            },
          },
        },
      });
      } catch (error) {
        console.error("Error initializing performance chart:", error);
      }
    },

    logout() {
      if (confirm("Are you sure you want to logout?")) {
        this.$store.dispatch("auth/logout");
        this.$router.push("/login");
      }
    },

    handleNotificationClick(notification) {
      // Handle different notification types
      switch (notification.type) {
        case 'mark':
          // Navigate to course marks if related_id is a course_id
          if (notification.related_id) {
            this.$router.push(`/student/course/${notification.related_id}`);
          }
          break;
        case 'course':
          // Navigate to course details
          if (notification.related_id) {
            this.$router.push(`/student/course/${notification.related_id}`);
          }
          break;
        default:
          // Show notification content as a toast for other types
          this.$store.dispatch('showToast', {
            message: notification.content,
            type: 'info',
            timeout: 8000
          });
      }
    },
  },
};
</script>

<style scoped>
.dashboard h1 {
  color: #2c3e50;
  font-weight: 700;
}

.card {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border: none;
  border-radius: 10px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
}

.card-title {
  color: #2c3e50;
  font-weight: 600;
}

.course-card {
  padding: 15px;
  border-radius: 8px;
  background-color: #f8f9fa;
  transition: background-color 0.3s ease;
}

.course-card:hover {
  background-color: #e9ecef;
}

.progress {
  border-radius: 5px;
  background-color: #e9ecef;
}

.progress-bar {
  border-radius: 5px;
  font-size: 0.6rem;
  line-height: 10px;
  font-weight: bold;
}

.list-group-item {
  padding: 15px;
  border-left: 0;
  border-right: 0;
  transition: background-color 0.2s ease;
}

.list-group-item:first-child {
  border-top: 0;
}

.list-group-item:last-child {
  border-bottom: 0;
}

.badge {
  padding: 6px 10px;
  font-weight: 500;
  text-transform: capitalize;
}

.chart-container {
  position: relative;
  height: 300px;
  width: 100%;
}
</style>
