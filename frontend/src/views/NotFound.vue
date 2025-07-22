<template>
  <div class="not-found-container text-center">
    <div class="error-code">404</div>
    <h1 class="mb-4">Page Not Found</h1>
    <p class="lead mb-5">The page you are looking for doesn't exist or has been moved.</p>
    <router-link to="/" class="btn btn-primary">Back to Home</router-link>
  </div>
</template>

<script>
export default {
  name: 'NotFound'
}
</script>

<style scoped>
.not-found-container {
  padding: 5rem 1rem;
}

.error-code {
  font-size: 8rem;
  font-weight: bold;
  color: #6c757d;
  margin-bottom: 2rem;
}

h1 {
  font-size: 2.5rem;
  color: #343a40;
}

.lead {
  color: #6c757d;
  max-width: 500px;
  margin: 0 auto;
}
</style>
