<script setup>
defineProps({
  apiBaseUrl: {
    type: String,
    required: true,
  },
});
</script>

<template>
  <aside class="info-panel">
    <h2>API endpoints</h2>
    <ul>
      <li><code>POST /api/v1/auth/login</code></li>
      <li><code>POST /api/v1/auth/register</code></li>
      <li><code>GET /api/v1/auth/me</code></li>
      <li><code>POST /api/v1/auth/refresh</code></li>
      <li><code>GET /api/v1/devices</code></li>
      <li><code>POST /api/v1/devices</code></li>
      <li><code>GET /api/v1/devices/{id}/stream</code></li>
      <li><code>GET /api/v1/admin/users</code></li>
      <li><code>GET /api/v1/admin/devices</code></li>
    </ul>
    <a class="btn btn-outline-dark w-100" :href="`${apiBaseUrl}/health`">API health</a>
  </aside>
</template>
