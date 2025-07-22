<template>
  <div class="system-health">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">System Health</h5>
        <div>
          <button 
            class="btn btn-outline-primary btn-sm me-2" 
            @click="refreshHealth"
            :disabled="loading"
          >
            <i class="bi bi-arrow-clockwise" :class="{'spin': loading}"></i>
            Refresh
          </button>
          <button 
            class="btn btn-outline-success btn-sm" 
            @click="initiateBackup"
            :disabled="backupInProgress"
          >
            <i class="bi bi-cloud-arrow-up"></i>
            Backup Now
          </button>
        </div>
      </div>
      <div class="card-body">
        <div v-if="loading" class="text-center my-3">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
        
        <div v-else class="row">
          <!-- Overall Status -->
          <div class="col-md-6 mb-4">
            <div class="health-status-box" :class="getStatusClass(healthData.status)">
              <h5>Overall Status</h5>
              <div class="d-flex align-items-center">
                <div class="status-indicator me-3" :class="getStatusClass(healthData.status)">
                  <i :class="getStatusIcon(healthData.status)"></i>
                </div>
                <div>
                  <h3 class="mb-0 text-capitalize">{{ healthData.status || 'Unknown' }}</h3>
                  <p class="mb-0">Last checked: {{ formatDate(healthData.timestamp) }}</p>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Database Status -->
          <div class="col-md-6 mb-4">
            <div class="health-status-box" :class="getStatusClass(healthData.database?.status === 'online' ? 'ok' : 'critical')">
              <h5>Database Status</h5>
              <div class="d-flex align-items-center">
                <div class="status-indicator me-3" :class="getStatusClass(healthData.database?.status === 'online' ? 'ok' : 'critical')">
                  <i :class="getStatusIcon(healthData.database?.status === 'online' ? 'ok' : 'critical')"></i>
                </div>
                <div>
                  <h3 class="mb-0 text-capitalize">{{ healthData.database?.status || 'Unknown' }}</h3>
                  <p class="mb-0">Response time: {{ healthData.database?.responseTime || 0 }}ms</p>
                  <p class="mb-0">{{ healthData.database?.version }}</p>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Disk Usage -->
          <div class="col-md-6 mb-4">
            <div class="health-status-box">
              <h5>Disk Usage</h5>
              <div class="mb-2">
                <div class="progress" style="height: 10px;">
                  <div 
                    class="progress-bar" 
                    :class="getDiskUsageClass()"
                    role="progressbar" 
                    :style="{ width: `${healthData.system?.diskUsage?.usedPercent || 0}%` }"
                    :aria-valuenow="healthData.system?.diskUsage?.usedPercent || 0" 
                    aria-valuemin="0" 
                    aria-valuemax="100"
                  ></div>
                </div>
              </div>
              <div class="d-flex justify-content-between">
                <small>{{ formatBytes(healthData.system?.diskUsage?.free || 0) }} free</small>
                <small>{{ healthData.system?.diskUsage?.usedPercent || 0 }}% used</small>
                <small>{{ formatBytes(healthData.system?.diskUsage?.total || 0) }} total</small>
              </div>
            </div>
          </div>
          
          <!-- PHP Info -->
          <div class="col-md-6 mb-4">
            <div class="health-status-box">
              <h5>PHP Info</h5>
              <div>
                <p class="mb-1"><strong>Version:</strong> {{ healthData.system?.phpVersion || 'Unknown' }}</p>
                <p class="mb-1"><strong>Memory Limit:</strong> {{ healthData.system?.memoryLimit || 'Unknown' }}</p>
                <p class="mb-1"><strong>Max Execution Time:</strong> {{ healthData.system?.maxExecutionTime || 'Unknown' }}s</p>
                <p v-if="healthData.load && healthData.load.length" class="mb-0">
                  <strong>Load Average:</strong> 
                  {{ healthData.load[0].toFixed(2) }} (1m), 
                  {{ healthData.load[1].toFixed(2) }} (5m), 
                  {{ healthData.load[2].toFixed(2) }} (15m)
                </p>
              </div>
            </div>
          </div>
          
          <!-- Last Backup -->
          <div class="col-12">
            <div class="alert" :class="getBackupAlertClass()">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <strong>Last Database Backup:</strong> 
                  <span v-if="healthData.lastBackup">{{ formatDate(healthData.lastBackup) }}</span>
                  <span v-else>No recent backups found</span>
                </div>
                <button 
                  class="btn btn-sm" 
                  :class="getBackupButtonClass()" 
                  @click="initiateBackup"
                  :disabled="backupInProgress"
                >
                  <i class="bi bi-cloud-arrow-up me-1"></i>
                  {{ backupInProgress ? 'Backing up...' : 'Backup Now' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'SystemHealthMonitor',
  data() {
    return {
      healthData: {},
      loading: true,
      error: null,
      backupInProgress: false,
      refreshInterval: null
    };
  },
  mounted() {
    this.fetchHealthData();
    
    // Refresh every 5 minutes
    this.refreshInterval = setInterval(() => {
      this.fetchHealthData();
    }, 300000); // 5 minutes
  },
  beforeUnmount() {
    // Clear the interval when component is destroyed
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
    }
  },
  methods: {
    async fetchHealthData() {
      this.loading = true;
      try {
        const response = await axios.get(`${process.env.VUE_APP_API_URL}/api/admin/system/health`, {
          headers: {
            Authorization: `Bearer ${this.$store.getters['auth/token']}`
          }
        });
        
        this.healthData = response.data;
      } catch (error) {
        console.error('Failed to fetch system health data:', error);
        this.$store.dispatch('notification/add', {
          type: 'danger',
          message: 'Failed to load system health information'
        });
        this.error = error;
      } finally {
        this.loading = false;
      }
    },
    async initiateBackup() {
      this.backupInProgress = true;
      try {
        await axios.post(`${process.env.VUE_APP_API_URL}/api/admin/system/backup`, {}, {
          headers: {
            Authorization: `Bearer ${this.$store.getters['auth/token']}`
          }
        });
        
        this.$store.dispatch('notification/add', {
          type: 'success',
          message: 'Database backup initiated successfully'
        });
        
        // Update health data after backup
        this.fetchHealthData();
      } catch (error) {
        console.error('Failed to initiate backup:', error);
        this.$store.dispatch('notification/add', {
          type: 'danger',
          message: 'Failed to initiate database backup'
        });
      } finally {
        this.backupInProgress = false;
      }
    },
    refreshHealth() {
      this.fetchHealthData();
    },
    getStatusClass(status) {
      if (!status) return 'status-unknown';
      
      switch (status.toLowerCase()) {
        case 'ok':
          return 'status-ok';
        case 'warning':
          return 'status-warning';
        case 'critical':
          return 'status-critical';
        default:
          return 'status-unknown';
      }
    },
    getStatusIcon(status) {
      if (!status) return 'bi bi-question-circle-fill';
      
      switch (status.toLowerCase()) {
        case 'ok':
          return 'bi bi-check-circle-fill';
        case 'warning':
          return 'bi bi-exclamation-triangle-fill';
        case 'critical':
          return 'bi bi-exclamation-circle-fill';
        default:
          return 'bi bi-question-circle-fill';
      }
    },
    getDiskUsageClass() {
      const usedPercent = this.healthData.system?.diskUsage?.usedPercent || 0;
      
      if (usedPercent > 90) {
        return 'bg-danger';
      } else if (usedPercent > 80) {
        return 'bg-warning';
      } else if (usedPercent > 70) {
        return 'bg-info';
      } else {
        return 'bg-success';
      }
    },
    getBackupAlertClass() {
      if (!this.healthData.lastBackup) {
        return 'alert-danger';
      }
      
      // Check if last backup was more than 24 hours ago
      const lastBackup = new Date(this.healthData.lastBackup);
      const now = new Date();
      const diffHours = (now - lastBackup) / (1000 * 60 * 60);
      
      if (diffHours > 72) {
        return 'alert-danger';
      } else if (diffHours > 24) {
        return 'alert-warning';
      } else {
        return 'alert-success';
      }
    },
    getBackupButtonClass() {
      if (this.backupInProgress) {
        return 'btn-secondary';
      }
      
      if (!this.healthData.lastBackup) {
        return 'btn-danger';
      }
      
      // Check if last backup was more than 24 hours ago
      const lastBackup = new Date(this.healthData.lastBackup);
      const now = new Date();
      const diffHours = (now - lastBackup) / (1000 * 60 * 60);
      
      if (diffHours > 72) {
        return 'btn-danger';
      } else if (diffHours > 24) {
        return 'btn-warning';
      } else {
        return 'btn-success';
      }
    },
    formatBytes(bytes) {
      if (bytes === 0) return '0 Bytes';
      
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    formatDate(dateString, includeTime = false) {
      if (!dateString) return 'N/A';
      
      const date = new Date(dateString);
      
      if (includeTime) {
        return new Intl.DateTimeFormat('en-GB', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        }).format(date);
      }
      
      return new Intl.DateTimeFormat('en-GB', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      }).format(date);
    }
  }
};
</script>

<style scoped>
.health-status-box {
  border: 1px solid #dee2e6;
  border-radius: 0.5rem;
  padding: 1rem;
  background-color: #f8f9fa;
  height: 100%;
}

.status-indicator {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
}

.status-ok {
  background-color: #28a745;
  border-color: #28a745;
  color: white;
}

.status-warning {
  background-color: #ffc107;
  border-color: #ffc107;
  color: #212529;
}

.status-critical {
  background-color: #dc3545;
  border-color: #dc3545;
  color: white;
}

.status-unknown {
  background-color: #6c757d;
  border-color: #6c757d;
  color: white;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
