<template>
  <div class="dashboard">
    <!-- Dashboard Header -->
    <div
      class="dashboard-header d-flex justify-content-between align-items-center mb-4"
    >
      <h1 class="mb-0">Academic Advisor Dashboard</h1>
      <div class="d-flex align-items-center gap-3">
        <router-link
          to="/advisor/advisee-reports"
          class="btn btn-primary"
          title="Comprehensive Advisee Reports"
        >
          <i class="fas fa-file-alt me-2"></i>Advisee Reports
        </router-link>
        <router-link
          to="/advisor/feedback"
          class="btn btn-info"
          title="Advisee Feedback"
        >
          <i class="fas fa-comments me-2"></i>Advisee Feedback
        </router-link>
        <router-link
          to="/advisor/comparisons"
          class="btn btn-success"
          title="Advisee Performance Comparisons"
        >
          <i class="fas fa-chart-line me-2"></i>Performance Comparisons
        </router-link>
        <div class="dropdown">
          <button
            class="btn btn-outline-warning dropdown-toggle"
            type="button"
            @click="toggleCourseDropdown"
            :class="{ show: showCourseDropdown }"
          >
            <i class="fas fa-chart-bar me-2"></i>Course Analytics
          </button>
          <ul class="dropdown-menu" :class="{ show: showCourseDropdown }">
            <li>
              <span class="dropdown-header">Advisee Course Breakdowns</span>
            </li>
            <li v-for="course in adviseesCourses" :key="course.id">
              <router-link
                :to="`/advisor/breakdown/${course.id}`"
                class="dropdown-item"
                @click="showCourseDropdown = false"
              >
                <i class="fas fa-chart-pie me-2"></i>{{ course.code }} -
                {{ course.name }}
              </router-link>
            </li>
            <li v-if="adviseesCourses.length === 0">
              <span class="dropdown-item-text text-muted"
                >No course data available</span
              >
            </li>
          </ul>
        </div>
        <div class="user-info d-flex align-items-center">
          <span class="me-3">Welcome, {{ userInfo.name }}</span>
          <button class="btn btn-outline-danger btn-sm" @click="logout">
            <i class="fas fa-sign-out-alt me-1"></i>
            Logout
          </button>
        </div>
      </div>
    </div>

    <!-- Overview Statistics -->
    <div class="row mb-4">
      <div class="col-md-3 mb-3">
        <div class="card text-center bg-primary text-white">
          <div class="card-body">
            <i class="fas fa-user-graduate fa-2x mb-2"></i>
            <h4 class="card-title">{{ advisees.length }}</h4>
            <p class="card-text">Total Advisees</p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-center bg-success text-white">
          <div class="card-body">
            <i class="fas fa-chart-line fa-2x mb-2"></i>
            <h4 class="card-title">{{ lowRiskCount }}</h4>
            <p class="card-text">Low Risk</p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-center bg-warning text-white">
          <div class="card-body">
            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
            <h4 class="card-title">{{ mediumRiskCount }}</h4>
            <p class="card-text">Medium Risk</p>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card text-center bg-danger text-white">
          <div class="card-body">
            <i class="fas fa-skull-crossbones fa-2x mb-2"></i>
            <h4 class="card-title">{{ highRiskCount }}</h4>
            <p class="card-text">High Risk</p>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-8 mb-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">My Advisees</h5>
            <p class="card-text text-muted mb-4">
              Students under your supervision
            </p>

            <div v-if="loading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>

            <div v-else-if="advisees.length === 0" class="text-center py-4">
              <p>You have no advisees assigned yet.</p>
              <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
            </div>

            <div v-else class="table-responsive" style="max-height: 600px; overflow-y: auto;">
              <table class="table table-hover align-middle">
                <thead>
                  <tr>
                    <th>Student</th>
                    <th>Matric Number</th>
                    <th>Academic Status</th>
                    <th>GPA</th>
                    <th>Risk Level</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="student in advisees" :key="student.id">
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2">
                          {{ getInitials(student.name) }}
                        </div>
                        <div>{{ student.name }}</div>
                      </div>
                    </td>
                    <td>{{ student.matric_number }}</td>
                    <td>{{ student.status }}</td>
                    <td>{{ student.gpa }}</td>
                    <td>
                      <span
                        class="badge"
                        :class="getRiskBadgeClass(student.risk)"
                      >
                        {{ student.risk }}
                      </span>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm" role="group">
                        <router-link
                          :to="`/advisor/advisee/${student.id}`"
                          class="btn btn-outline-primary"
                        >
                          <i class="fas fa-eye me-1"></i> Details
                        </router-link>
                        <button
                          class="btn btn-outline-info"
                          @click="addNote(student)"
                        >
                          <i class="fas fa-sticky-note"></i>
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

      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Risk Summary</h5>
            <p class="card-text text-muted mb-4">
              Overview of students at academic risk
            </p>

            <div v-if="loading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>

            <div v-else style="max-height: 500px; overflow-y: auto;">
              <div class="chart-container" style="position: relative; height: 250px; width: 100%;">
                <canvas id="riskChart"></canvas>
              </div>

              <div class="risk-stats mt-4">
                <div class="risk-stat-item high">
                  <div class="risk-stat-value">{{ highRiskCount }}</div>
                  <div class="risk-stat-label">High Risk</div>
                </div>
                <div class="risk-stat-item medium">
                  <div class="risk-stat-value">{{ mediumRiskCount }}</div>
                  <div class="risk-stat-label">Medium Risk</div>
                </div>
                <div class="risk-stat-item low">
                  <div class="risk-stat-value">{{ lowRiskCount }}</div>
                  <div class="risk-stat-label">Low Risk</div>
                </div>
              </div>

              <div class="alert alert-warning mt-4" v-if="highRiskCount > 0">
                <i class="fas fa-exclamation-triangle me-2"></i>
                You have {{ highRiskCount }} student(s) at high risk who need
                immediate attention.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12 mb-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">My Advisees Rankings</h5>
            <p class="card-text text-muted mb-4">
              Performance rankings of your advisees
            </p>

            <div v-if="loadingRankings" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading rankings...</span>
              </div>
            </div>

            <div v-else-if="adviseeRankings.length === 0" class="text-center py-4">
              <p>No ranking data available for your advisees.</p>
              <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
            </div>

            <div v-else class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Among My Advisees</th>
                    <th>Student</th>
                    <th>Matric Number</th>
                    <th>GPA</th>
                    <th>Overall Rank</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="student in adviseeRankings" :key="student.id">
                    <td>
                      <span class="rank-badge" :class="getAdvisorRankBadgeClass(student.advisor_rank)">
                        #{{ student.advisor_rank }}
                      </span>
                    </td>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2">
                          {{ getInitials(student.name) }}
                        </div>
                        <div>{{ student.name }}</div>
                      </div>
                    </td>
                    <td>{{ student.matric_number }}</td>
                    <td>
                      <strong :class="getGpaClass(student.gpa)">{{ student.gpa }}%</strong>
                    </td>
                    <td>
                      <span class="badge bg-info">#{{ student.overall_rank }} / {{ student.total_students }}</span>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm">
                        <router-link
                          :to="`/advisor/advisee/${student.id}`"
                          class="btn btn-outline-primary"
                          title="View Details"
                        >
                          <i class="fas fa-eye"></i>
                        </router-link>
                        <button
                          class="btn btn-outline-info"
                          @click="showStudentRanking(student)"
                          title="View Full Ranking"
                        >
                          <i class="fas fa-trophy"></i>
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
    </div>

    <div class="row">
      <div class="col-md-12 mb-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Recent Notes</h5>
            <p class="card-text text-muted mb-4">
              Your latest meeting notes and observations
            </p>

            <div v-if="loading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>

            <div v-else-if="notes.length === 0" class="text-center py-4">
              <p>You haven't added any notes yet.</p>
              <i class="fas fa-clipboard fa-3x text-muted mb-3"></i>
            </div>

            <div v-else class="notes-grid">
              <div v-for="note in notes" :key="note.id" class="note-card">
                <div class="note-header">
                  <div class="note-student">{{ note.student_name }}</div>
                  <div class="note-date">{{ formatDate(note.created_at) }}</div>
                </div>
                <div class="note-content">{{ note.note }}</div>
                <div class="note-footer">
                  <button
                    class="btn btn-sm btn-outline-primary"
                    @click="editNote(note)"
                  >
                    <i class="fas fa-edit"></i>
                  </button>
                  <button
                    class="btn btn-sm btn-outline-danger"
                    @click="deleteNote(note)"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Note Modal -->
    <div
      class="modal fade"
      id="noteModal"
      tabindex="-1"
      aria-labelledby="noteModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="noteModalLabel">
              {{ currentNote.id ? "Edit Note" : "Add Note" }}
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveNote">
              <div class="mb-3" v-if="!currentNote.id">
                <label for="student" class="form-label">Student</label>
                <select
                  class="form-select"
                  id="student"
                  v-model="currentNote.student_id"
                  required
                >
                  <option
                    v-for="student in advisees"
                    :key="student.id"
                    :value="student.id"
                  >
                    {{ student.name }} ({{ student.matric_number }})
                  </option>
                </select>
              </div>
              <div class="mb-3">
                <label for="noteContent" class="form-label">Note</label>
                <textarea
                  class="form-control"
                  id="noteContent"
                  rows="5"
                  v-model="currentNote.note"
                  required
                ></textarea>
              </div>
              <button type="submit" class="btn btn-primary">Save Note</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from "vuex";
import Chart from "chart.js/auto";
import * as bootstrap from "bootstrap";

export default {
  name: "AdvisorDashboard",
  data() {
    return {
      loading: false,
      advisees: [],
      adviseesCourses: [],
      adviseeRankings: [],
      showCourseDropdown: false,
      notes: [],
      riskChart: null,
      loadingRankings: false,
      currentNote: {
        id: null,
        student_id: null,
        note: "",
      },
      // Sample data for demonstration - in a real app, this would come from the API
      adviseesData: [
        {
          id: 1,
          name: "John Smith",
          matric_number: "A12345",
          status: "Good Standing",
          gpa: 3.5,
          risk: "Low",
        },
        {
          id: 2,
          name: "Emily Johnson",
          matric_number: "A23456",
          status: "Warning",
          gpa: 1.8,
          risk: "High",
        },
        {
          id: 3,
          name: "Michael Brown",
          matric_number: "A34567",
          status: "Probation",
          gpa: 2.1,
          risk: "Medium",
        },
        {
          id: 4,
          name: "Sarah Davis",
          matric_number: "A45678",
          status: "Good Standing",
          gpa: 3.2,
          risk: "Low",
        },
        {
          id: 5,
          name: "David Wilson",
          matric_number: "A56789",
          status: "Warning",
          gpa: 1.9,
          risk: "High",
        },
      ],
      notesData: [
        {
          id: 1,
          student_id: 2,
          student_name: "Emily Johnson",
          note: "Discussed academic improvement plan. Student committed to attending all classes and seeking tutoring for difficult subjects.",
          created_at: "2023-09-15T10:30:00",
        },
        {
          id: 2,
          student_id: 3,
          student_name: "Michael Brown",
          note: "Reviewed midterm results and identified areas for improvement. Recommended study group and additional practice problems.",
          created_at: "2023-09-10T14:15:00",
        },
        {
          id: 3,
          student_id: 5,
          student_name: "David Wilson",
          note: "Student expressed concerns about workload. Discussed time management strategies and prioritization techniques.",
          created_at: "2023-09-05T09:45:00",
        },
      ],
    };
  },
  computed: {
    ...mapGetters("auth", ["getUser"]),

    userInfo() {
      return this.getUser || { name: "Advisor" };
    },

    highRiskCount() {
      return this.advisees.filter((s) => s.risk === "High").length;
    },
    mediumRiskCount() {
      return this.advisees.filter((s) => s.risk === "Medium").length;
    },
    lowRiskCount() {
      return this.advisees.filter((s) => s.risk === "Low").length;
    },
  },
  mounted() {
    this.loadData();

    // Close dropdown when clicking outside
    document.addEventListener("click", (e) => {
      if (!e.target.closest(".dropdown")) {
        this.showCourseDropdown = false;
      }
    });
  },
  methods: {
    toggleCourseDropdown() {
      this.showCourseDropdown = !this.showCourseDropdown;
    },

    async loadData() {
      this.loading = true;
      try {
        // Load real data from the API
        await Promise.all([
          this.loadAdvisees(),
          this.loadNotes(),
          this.loadAdviseesCourses(),
          this.loadAdviseeRankings()
        ]);

        this.$nextTick(() => {
          // Add a small delay to ensure DOM is fully rendered
          setTimeout(() => {
            this.initRiskChart();
          }, 100);
        });
      } catch (error) {
        console.error("Error loading dashboard data:", error);
        // Fallback to sample data if API fails
        this.advisees = this.adviseesData;
        this.notes = this.notesData;
        
        this.$nextTick(() => {
          // Add a small delay to ensure DOM is fully rendered
          setTimeout(() => {
            this.initRiskChart();
          }, 100);
        });
      } finally {
        this.loading = false;
      }
    },

    async loadAdvisees() {
      try {
        const token = this.$store.state.auth.token;
        if (!token) {
          throw new Error('No authentication token');
        }

        const response = await fetch(
          `http://localhost:8080/advisor-dashboard-api.php?action=advisees`,
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "Authorization": `Bearer ${token}`
            },
          }
        );

        if (response.ok) {
          const data = await response.json();
          this.advisees = data.advisees || [];
        } else {
          console.error("Failed to load advisees");
          // Use fallback data
          this.advisees = this.adviseesData;
        }
      } catch (error) {
        console.error("Error loading advisees:", error);
        // Use fallback data
        this.advisees = this.adviseesData;
      }
    },

    async loadNotes() {
      try {
        const token = this.$store.state.auth.token;
        if (!token) {
          throw new Error('No authentication token');
        }

        const response = await fetch(
          `http://localhost:8080/advisor-dashboard-api.php?action=notes`,
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "Authorization": `Bearer ${token}`
            },
          }
        );

        if (response.ok) {
          const data = await response.json();
          this.notes = data.notes || [];
        } else {
          console.error("Failed to load notes");
          // Use fallback data
          this.notes = this.notesData;
        }
      } catch (error) {
        console.error("Error loading notes:", error);
        // Use fallback data
        this.notes = this.notesData;
      }
    },

    async loadAdviseesCourses() {
      try {
        const userId = this.userInfo?.id;
        if (!userId) return;

        const response = await fetch(
          `http://localhost:8080/breakdown-api.php?action=advisor_courses&advisor_id=${userId}`,
          {
            method: "GET",
            credentials: "include",
            headers: {
              "Content-Type": "application/json",
            },
          }
        );

        if (response.ok) {
          const data = await response.json();
          this.adviseesCourses = data.courses || [];

          // If no courses from API, add sample data for testing
          if (this.adviseesCourses.length === 0) {
            this.adviseesCourses = [
              {
                id: 1,
                code: "CS101",
                name: "Introduction to Computer Science",
              },
              { id: 2, code: "MATH201", name: "Calculus II" },
              { id: 3, code: "PHYS101", name: "Physics I" },
            ];
          }
        } else {
          console.error("Failed to load advisor courses");
          // Fallback sample data
          this.adviseesCourses = [
            { id: 1, code: "CS101", name: "Introduction to Computer Science" },
            { id: 2, code: "MATH201", name: "Calculus II" },
            { id: 3, code: "PHYS101", name: "Physics I" },
          ];
        }
      } catch (error) {
        console.error("Error loading advisor courses:", error);
        // Fallback sample data
        this.adviseesCourses = [
          { id: 1, code: "CS101", name: "Introduction to Computer Science" },
          { id: 2, code: "MATH201", name: "Calculus II" },
          { id: 3, code: "PHYS101", name: "Physics I" },
        ];
      }
    },

    async loadAdviseeRankings() {
      try {
        this.loadingRankings = true;
        const token = this.$store.state.auth.token;
        if (!token) {
          throw new Error('No authentication token');
        }

        const response = await fetch(
          `http://localhost:8080/ranking-api.php?action=advisor_students_rankings`,
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "Authorization": `Bearer ${token}`
            },
          }
        );

        if (response.ok) {
          const data = await response.json();
          this.adviseeRankings = data.rankings || [];
        } else {
          console.error("Failed to load advisee rankings");
          this.adviseeRankings = [];
        }
      } catch (error) {
        console.error("Error loading advisee rankings:", error);
        this.adviseeRankings = [];
      } finally {
        this.loadingRankings = false;
      }
    },

    getAdvisorRankBadgeClass(rank) {
      if (rank <= 3) return 'rank-badge-gold';
      if (rank <= 5) return 'rank-badge-silver';
      if (rank <= 10) return 'rank-badge-bronze';
      return 'rank-badge-default';
    },

    getGpaClass(gpa) {
      if (gpa >= 80) return 'text-success';
      if (gpa >= 70) return 'text-info';
      if (gpa >= 60) return 'text-warning';
      return 'text-danger';
    },

    showStudentRanking(student) {
      // Navigate to student ranking page or show modal
      this.$router.push(`/advisor/advisee/${student.id}/ranking`);
    },

    getInitials(name) {
      return name
        .split(" ")
        .map((n) => n[0])
        .join("")
        .toUpperCase();
    },

    getRiskBadgeClass(risk) {
      switch (risk) {
        case "High":
          return "bg-danger";
        case "Medium":
          return "bg-warning";
        case "Low":
          return "bg-success";
        default:
          return "bg-secondary";
      }
    },

    formatDate(dateString) {
      if (!dateString) return "";

      const date = new Date(dateString);
      return date.toLocaleDateString("en-GB", {
        day: "numeric",
        month: "short",
        year: "numeric",
      });
    },

    initRiskChart() {
      const ctx = document.getElementById("riskChart");

      if (!ctx) {
        console.warn('Risk chart canvas element not found');
        return;
      }

      if (this.riskChart) {
        this.riskChart.destroy();
      }

      this.riskChart = new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: ["High Risk", "Medium Risk", "Low Risk"],
          datasets: [
            {
              data: [
                this.highRiskCount,
                this.mediumRiskCount,
                this.lowRiskCount,
              ],
              backgroundColor: ["#e74c3c", "#f39c12", "#2ecc71"],
              borderWidth: 0,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: {
            legend: {
              position: "bottom",
            },
          },
          cutout: "65%",
        },
      });
    },

    addNote(student = null) {
      this.currentNote = {
        id: null,
        student_id: student ? student.id : null,
        note: "",
      };

      const modal = new bootstrap.Modal(document.getElementById("noteModal"));
      modal.show();
    },

    editNote(note) {
      this.currentNote = { ...note };

      const modal = new bootstrap.Modal(document.getElementById("noteModal"));
      modal.show();
    },

    async saveNote() {
      try {
        const token = this.$store.state.auth.token;
        if (!token) {
          throw new Error('No authentication token');
        }

        const response = await fetch(
          `http://localhost:8080/advisor-dashboard-api.php?action=notes`,
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "Authorization": `Bearer ${token}`
            },
            body: JSON.stringify(this.currentNote)
          }
        );

        if (response.ok) {
          const data = await response.json();
          
          if (this.currentNote.id) {
            // Update existing note
            const index = this.notes.findIndex(n => n.id === this.currentNote.id);
            if (index !== -1) {
              this.notes[index] = data.note;
            }
          } else {
            // Add new note
            this.notes.unshift(data.note);
          }

          // Close the modal
          const modalElement = document.getElementById("noteModal");
          const modal = bootstrap.Modal.getInstance(modalElement);
          modal.hide();

          // Show success message
          this.$store.dispatch("showToast", {
            message: `Note ${this.currentNote.id ? "updated" : "added"} successfully`,
            type: "success",
          });
        } else {
          const errorData = await response.json();
          throw new Error(errorData.error || 'Failed to save note');
        }
      } catch (error) {
        console.error("Error saving note:", error);
        this.$store.dispatch("showToast", {
          message: "Failed to save note: " + error.message,
          type: "error",
        });
      }
    },

    async deleteNote(note) {
      if (confirm("Are you sure you want to delete this note?")) {
        try {
          const token = this.$store.state.auth.token;
          if (!token) {
            throw new Error('No authentication token');
          }

          const response = await fetch(
            `http://localhost:8080/advisor-dashboard-api.php?action=notes&note_id=${note.id}`,
            {
              method: "DELETE",
              headers: {
                "Content-Type": "application/json",
                "Authorization": `Bearer ${token}`
              }
            }
          );

          if (response.ok) {
            this.notes = this.notes.filter((n) => n.id !== note.id);

            // Show success message
            this.$store.dispatch("showToast", {
              message: "Note deleted successfully",
              type: "success",
            });
          } else {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Failed to delete note');
          }
        } catch (error) {
          console.error("Error deleting note:", error);
          this.$store.dispatch("showToast", {
            message: "Failed to delete note: " + error.message,
            type: "error",
          });
        }
      }
    },

    async logout() {
      if (confirm("Are you sure you want to logout?")) {
        await this.$store.dispatch("auth/logout");
        this.$router.push("/login");
      }
    },
  },
};
</script>

<style scoped>
.dashboard {
  max-height: 100vh;
  overflow-y: auto;
}

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

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background-color: #3498db;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: bold;
}

.badge {
  padding: 6px 10px;
  font-weight: 500;
}

.risk-stats {
  display: flex;
  justify-content: space-around;
  text-align: center;
  margin-top: 20px;
}

.risk-stat-item {
  padding: 10px;
  border-radius: 5px;
  min-width: 80px;
}

.risk-stat-value {
  font-size: 24px;
  font-weight: bold;
}

.risk-stat-label {
  font-size: 12px;
  opacity: 0.7;
}

.risk-stat-item.high {
  color: #e74c3c;
  background-color: rgba(231, 76, 60, 0.1);
}

.risk-stat-item.medium {
  color: #f39c12;
  background-color: rgba(243, 156, 18, 0.1);
}

.risk-stat-item.low {
  color: #2ecc71;
  background-color: rgba(46, 204, 113, 0.1);
}

.notes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  max-height: 600px;
  overflow-y: auto;
}

.note-card {
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  padding: 15px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.note-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.note-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.note-student {
  font-weight: 600;
  color: #2c3e50;
}

.note-date {
  font-size: 0.8rem;
  color: #7f8c8d;
}

.note-content {
  color: #34495e;
  margin-bottom: 15px;
  white-space: pre-line;
}

.note-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.chart-container {
  display: flex;
  justify-content: center;
  align-items: center;
}

.chart-container canvas {
  max-width: 100%;
  max-height: 100%;
}

.rank-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-weight: bold;
  font-size: 0.9rem;
  color: white;
}

.rank-badge-gold {
  background: linear-gradient(135deg, #ffd700, #ffed4e);
  color: #333;
}

.rank-badge-silver {
  background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
  color: #333;
}

.rank-badge-bronze {
  background: linear-gradient(135deg, #cd7f32, #daa520);
  color: white;
}

.rank-badge-default {
  background: #6c757d;
  color: white;
}
</style>
