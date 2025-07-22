<template>
  <div class="app-container">
    <navbar v-if="isAuthenticated" />
    <div class="content-container">
      <router-view />
    </div>
    <notification-list />
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import Navbar from './components/layout/Navbar.vue';
import NotificationList from './components/notifications/NotificationList.vue';

export default {
  name: 'App',
  components: {
    Navbar,
    NotificationList
  },
  computed: {
    ...mapGetters('auth', ['isAuthenticated'])
  },
  created() {
    // Check if the user is already logged in
    this.$store.dispatch('auth/checkAuth');
  }
};
</script>

<style>
body {
  margin: 0;
  font-family: 'Roboto', sans-serif;
  background-color: #f5f5f5;
}

.app-container {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.content-container {
  flex: 1;
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
  width: 100%;
}

@media (max-width: 768px) {
  .content-container {
    padding: 10px;
  }
}

/* Global styles */
.card {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border: none;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.btn-primary {
  background-color: #3498db;
  border-color: #3498db;
}

.btn-primary:hover {
  background-color: #2980b9;
  border-color: #2980b9;
}

.btn-success {
  background-color: #2ecc71;
  border-color: #2ecc71;
}

.btn-success:hover {
  background-color: #27ae60;
  border-color: #27ae60;
}

.btn-danger {
  background-color: #e74c3c;
  border-color: #e74c3c;
}

.btn-danger:hover {
  background-color: #c0392b;
  border-color: #c0392b;
}

.form-control:focus {
  border-color: #3498db;
  box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
}
</style>
