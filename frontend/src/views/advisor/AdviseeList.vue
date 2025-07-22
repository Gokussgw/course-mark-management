<template>
  <div class="advisee-list-container">
    <h1 class="mb-4">My Advisees</h1>

    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="search-wrapper">
            <input
              type="text"
              class="form-control"
              placeholder="Search advisees..."
              v-model="searchQuery"
            />
          </div>
          <div class="filter-wrapper">
            <select class="form-select" v-model="programFilter">
              <option value="">All Programs</option>
              <option v-for="program in programs" :key="program" :value="program">
                {{ program }}
              </option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center my-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else-if="filteredAdvisees.length === 0" class="alert alert-info">
      No advisees found.
    </div>

    <div v-else class="row">
      <div
        v-for="advisee in filteredAdvisees"
        :key="advisee.id"
        class="col-md-6 col-lg-4 mb-4"
      >
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">{{ advisee.fullName }}</h5>
            <p class="card-text text-muted">{{ advisee.studentId }}</p>
            <div class="mb-2">
              <span class="badge bg-secondary">{{ advisee.program }}</span>
            </div>
            <p class="card-text">
              <small class="text-muted">{{ advisee.email }}</small>
            </p>
          </div>
          <div class="card-footer bg-white border-0">
            <router-link
              :to="`/advisor/advisee/${advisee.id}`"
              class="btn btn-primary btn-sm w-100"
            >
              View Details
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapActions } from 'vuex'

export default {
  name: 'AdviseeList',
  data() {
    return {
      searchQuery: '',
      programFilter: '',
      loading: true
    }
  },
  computed: {
    ...mapState('users', ['advisees']),
    programs() {
      // Extract unique programs from advisees
      return [...new Set(this.advisees.map(advisee => advisee.program))];
    },
    filteredAdvisees() {
      return this.advisees.filter(advisee => {
        const matchesSearch = advisee.fullName.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
          advisee.studentId.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
          advisee.email.toLowerCase().includes(this.searchQuery.toLowerCase());
        
        const matchesProgram = this.programFilter === '' || advisee.program === this.programFilter;
        
        return matchesSearch && matchesProgram;
      });
    }
  },
  methods: {
    ...mapActions('users', ['fetchAdvisees'])
  },
  async created() {
    try {
      await this.fetchAdvisees();
    } catch (error) {
      this.$toast.error('Failed to load advisees.');
      console.error('Error fetching advisees:', error);
    } finally {
      this.loading = false;
    }
  }
}
</script>

<style scoped>
.advisee-list-container {
  padding: 2rem 0;
}

.card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border: none;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.search-wrapper {
  flex: 1;
  max-width: 300px;
  margin-right: 1rem;
}

@media (max-width: 768px) {
  .d-flex {
    flex-direction: column;
  }
  
  .search-wrapper,
  .filter-wrapper {
    width: 100%;
    max-width: none;
    margin-right: 0;
    margin-bottom: 1rem;
  }
}
</style>
