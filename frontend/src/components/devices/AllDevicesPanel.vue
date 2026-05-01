<script setup>
import FullscreenTable from '../FullscreenTable.vue';

defineProps({
  loading: {
    type: Boolean,
    required: true,
  },
  devices: {
    type: Array,
    required: true,
  },
  error: {
    type: String,
    required: true,
  },
});

defineEmits(['refresh']);

function deviceStatus(device) {
  return device.metadata?.status || 'unknown';
}
</script>

<template>
  <section class="catalog-section">
    <div class="catalog-header">
      <div>
        <p class="eyebrow">Admin catalog</p>
        <h2>Все устройства</h2>
      </div>
      <button class="btn btn-outline-secondary btn-sm" type="button" :disabled="loading" @click="$emit('refresh')">
        Обновить
      </button>
    </div>

    <div v-if="error" class="alert alert-danger mb-3">{{ error }}</div>

    <div class="table-block">
      <div class="table-title">
        <h3>Devices</h3>
        <div class="table-actions">
          <span class="status-pill">{{ devices.length }}</span>
          <FullscreenTable title="All devices">
            <table class="table catalog-table catalog-table-fullscreen align-middle">
              <thead>
                <tr>
                  <th scope="col">ID</th>
                  <th scope="col">External ID</th>
                  <th scope="col">Название</th>
                  <th scope="col">Владелец</th>
                  <th scope="col">Статус</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in devices" :key="item.id">
                  <td>{{ item.id }}</td>
                  <td><code>{{ item.external_id }}</code></td>
                  <td>{{ item.name || '-' }}</td>
                  <td>{{ item.user?.email || '-' }}</td>
                  <td><span class="role-badge">{{ deviceStatus(item) }}</span></td>
                </tr>
                <tr v-if="!devices.length">
                  <td colspan="5" class="empty-cell">Нет устройств</td>
                </tr>
              </tbody>
            </table>
          </FullscreenTable>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table catalog-table align-middle">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">External ID</th>
              <th scope="col">Название</th>
              <th scope="col">Владелец</th>
              <th scope="col">Статус</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in devices" :key="item.id">
              <td>{{ item.id }}</td>
              <td><code>{{ item.external_id }}</code></td>
              <td>{{ item.name || '-' }}</td>
              <td>{{ item.user?.email || '-' }}</td>
              <td><span class="role-badge">{{ deviceStatus(item) }}</span></td>
            </tr>
            <tr v-if="!devices.length">
              <td colspan="5" class="empty-cell">Нет устройств</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>
