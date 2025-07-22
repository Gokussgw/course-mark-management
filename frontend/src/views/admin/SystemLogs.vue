<template>
  <div class="system-logs">
    <h1>System Logs</h1>
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Activity Logs</h5>
        <div>
          <button class="btn btn-outline-secondary me-2" @click="refreshLogs">
            <i class="bi bi-arrow-clockwise"></i> Refresh
          </button>
          <button class="btn btn-success" @click="exportLogsCSV">
            <i class="bi bi-download"></i> Export CSV
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="mb-3 d-flex flex-wrap justify-content-between align-items-center">
          <!-- Search and filter controls -->
          <div class="d-flex mb-2 mb-md-0">
            <div class="input-group me-2" style="max-width: 300px;">
              <span class="input-group-text">
                <i class="bi bi-search"></i>
              </span>
              <input 
                type="text" 
                class="form-control" 
                v-model="searchQuery" 
                placeholder="Search logs..." 
              />
            </div>
            <select class="form-select me-2" v-model="actionFilter" style="max-width: 200px;">
              <option value="">All Actions</option>
              <option value="login">Login</option>
              <option value="logout">Logout</option>
              <option value="create">Create</option>
              <option value="update">Update</option>
              <option value="delete">Delete</option>
              <option value="import">Import</option>
              <option value="export">Export</option>
            </select>
          </div>
          
          <div class="d-flex">
            <div class="input-group me-2" style="max-width: 200px;">
              <span class="input-group-text">From</span>
              <input type="date" class="form-control" v-model="dateFrom">
            </div>
            <div class="input-group" style="max-width: 200px;">
              <span class="input-group-text">To</span>
              <input type="date" class="form-control" v-model="dateTo">
            </div>
          </div>
        </div>

        <div v-if="loading" class="d-flex justify-content-center my-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div v-else-if="logs.length === 0" class="alert alert-info">
          No logs found matching your criteria.
        </div>

        <div v-else class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Description</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in filteredLogs" :key="log.id">
                <td>{{ formatDate(log.created_at) }}</td>
                <td>
                  <span v-if="log.user_name">
                    {{ log.user_name }}<br>
                    <small class="text-muted">{{ log.user_email }}</small>
                  </span>
                  <span v-else class="text-muted">System</span>
                </td>
                <td>
                  <span class="badge" :class="getActionBadgeClass(log.action)">
                    {{ log.action }}
                  </span>
                </td>
                <td>{{ log.description }}</td>
                <td>
                  <button 
                    class="btn btn-sm btn-outline-info"
                    @click="showLogDetail(log)"
                  >
                    <i class="bi bi-info-circle"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3">
          <div>
            Showing {{ logs.length ? (currentPage - 1) * pageSize + 1 : 0 }} to 
            {{ Math.min(currentPage * pageSize, totalLogs) }} of {{ totalLogs }} logs
          </div>
          <div>
            <button 
              class="btn btn-sm btn-outline-primary me-2" 
              @click="prevPage" 
              :disabled="currentPage <= 1"
            >
              <i class="bi bi-chevron-left"></i> Previous
            </button>
            <span class="mx-2">
              Page {{ currentPage }} of {{ totalPages }}
            </span>
            <button 
              class="btn btn-sm btn-outline-primary" 
              @click="nextPage" 
              :disabled="currentPage >= totalPages"
            >
              Next <i class="bi bi-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Log Detail Modal -->
    <div class="modal fade" id="logDetailModal" tabindex="-1" ref="logDetailModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Log Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div v-if="selectedLog">
              <p><strong>ID:</strong> {{ selectedLog.id }}</p>
              <p><strong>Time:</strong> {{ formatDate(selectedLog.created_at, true) }}</p>
              <p><strong>User:</strong> {{ selectedLog.user_name || 'System' }}</p>
              <p><strong>Action:</strong> {{ selectedLog.action }}</p>
              <p><strong>Description:</strong> {{ selectedLog.description }}</p>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from 'bootstrap';
import axios from 'axios';

export default {
  name: 'SystemLogs',
  data() {
    return {
      logs: [],
      totalLogs: 0,
      currentPage: 1,
      pageSize: 15,
      loading: true,
      searchQuery: '',
      actionFilter: '',
      dateFrom: '',
      dateTo: '',
      selectedLog: null,
      logDetailModal: null
    };
  },
  computed: {
    filteredLogs() {
      let filtered = [...this.logs];
      
      // Apply search filter
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(log => 
          (log.description && log.description.toLowerCase().includes(query)) ||
          (log.user_name && log.user_name.toLowerCase().includes(query)) ||
          (log.user_email && log.user_email.toLowerCase().includes(query)) ||
          (log.action && log.action.toLowerCase().includes(query))
        );
      }
      
      // Apply action filter
      if (this.actionFilter) {
        filtered = filtered.filter(log => 
          log.action && log.action.toLowerCase() === this.actionFilter.toLowerCase()
        );
      }
      
      // Apply date filters
      if (this.dateFrom) {
        const fromDate = new Date(this.dateFrom);
        filtered = filtered.filter(log => {
          const logDate = new Date(log.created_at);
          return logDate >= fromDate;
        });
      }
      
      if (this.dateTo) {
        const toDate = new Date(this.dateTo);
        toDate.setHours(23, 59, 59, 999);  // End of the day
        filtered = filtered.filter(log => {
          const logDate = new Date(log.created_at);
          return logDate <= toDate;
        });
      }
      
      return filtered;
    },
    totalPages() {
      return Math.ceil(this.totalLogs / this.pageSize);
    }
  },
  mounted() {
    this.fetchLogs();
    this.$nextTick(() => {
      this.logDetailModal = new Modal(this.$refs.logDetailModal);
    });
  },
  methods: {
    async fetchLogs() {
      this.loading = true;
      try {
        const response = await axios.get(`${process.env.VUE_APP_API_URL}/api/admin/logs`, {
          params: {
            limit: this.pageSize,
            offset: (this.currentPage - 1) * this.pageSize
          },
          headers: {
            Authorization: `Bearer ${this.$store.getters['auth/token']}`
          }
        });
        
        this.logs = response.data.logs;
        this.totalLogs = response.data.total;
      } catch (error) {
        console.error('Failed to fetch logs:', error);
        this.$store.dispatch('notification/add', {
          type: 'danger',
          message: 'Failed to load system logs'
        });
      } finally {
        this.loading = false;
      }
    },
    refreshLogs() {
      this.fetchLogs();
    },
    getActionBadgeClass(action) {
      if (!action) return 'bg-secondary';
      
      switch(action.toLowerCase()) {
        case 'login':
          return 'bg-success';
        case 'logout':
          return 'bg-info';
        case 'create':
          return 'bg-primary';
        case 'update':
          return 'bg-warning';
        case 'delete':
          return 'bg-danger';
        case 'import':
          return 'bg-dark';
        case 'export':
          return 'bg-secondary';
        default:
          return 'bg-secondary';
      }
    },
    formatDate(dateString, includeTime = false) {
      if (!dateString) return '';
      
      const date = new Date(dateString);
      if (includeTime) {
        return new Intl.DateTimeFormat('en-GB', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit'
        }).format(date);
      }
      
      return new Intl.DateTimeFormat('en-GB', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }).format(date);
    },
    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
        this.fetchLogs();
      }
    },
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
        this.fetchLogs();
      }
    },
    showLogDetail(log) {
      this.selectedLog = log;
      this.logDetailModal.show();
    },
    async exportLogsCSV() {
      try {
        // Set up the CSV header
        const header = ['ID', 'Time', 'User', 'Email', 'Action', 'Description'];
        const csvRows = [header];
        
        // Get all logs (this is a simplified approach; for large datasets you might want to use the API)
        let allLogs = this.filteredLogs;
        
        // Add each log to the CSV data
        allLogs.forEach(log => {
          const row = [
            log.id,
            this.formatDate(log.created_at, true),
            log.user_name || 'System',
            log.user_email || 'N/A',
            log.action,
            log.description
          ];
          csvRows.push(row);
        });
        
        // Convert to CSV string
        const csvContent = csvRows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
        
        // Create and download the CSV file
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', `system_logs_${new Date().toISOString().slice(0,10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        this.$store.dispatch('notification/add', {
          type: 'success',
          message: 'Logs exported successfully'
        });
      } catch (error) {
        console.error('Failed to export logs:', error);
        this.$store.dispatch('notification/add', {
          type: 'danger',
          message: 'Failed to export logs'
        });
      }
    }
  }
};
</script>

<style scoped>
.system-logs {
  padding: 20px;
}

.card {
  border-radius: 0.5rem;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.badge {
  font-size: 0.8rem;
  padding: 0.35em 0.65em;
}

table {
  font-size: 0.9rem;
}

table th {
  font-weight: 600;
  background-color: #f8f9fa;
}

.table-responsive {
  max-height: 600px;
  overflow-y: auto;
}

.modal-body p {
  margin-bottom: 0.5rem;
}
</style>
