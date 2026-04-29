<script setup>
import AdminCatalogTables from './AdminCatalogTables.vue';
import UserDevicesPanel from './devices/UserDevicesPanel.vue';

defineProps({
  apiBaseUrl: {
    type: String,
    required: true,
  },
  loading: {
    type: Boolean,
    required: true,
  },
  user: {
    type: Object,
    required: true,
  },
  token: {
    type: Object,
    required: true,
  },
  adminUsers: {
    type: Array,
    required: true,
  },
  adminDevices: {
    type: Array,
    required: true,
  },
  catalogLoading: {
    type: Boolean,
    required: true,
  },
  catalogError: {
    type: String,
    required: true,
  },
  userDevices: {
    type: Array,
    required: true,
  },
  deviceLoading: {
    type: Boolean,
    required: true,
  },
  deviceError: {
    type: String,
    required: true,
  },
  authHeader: {
    type: String,
    required: true,
  },
});

defineEmits([
  'refresh-profile',
  'refresh-token',
  'refresh-catalog',
  'refresh-devices',
  'create-device',
  'update-device',
  'delete-device',
]);
</script>

<template>
  <div class="panel-header">
    <div>
      <p class="eyebrow">Авторизация</p>
      <h1>Сессия активна</h1>
    </div>
    <span class="status-pill">authenticated</span>
  </div>

  <dl class="profile-grid">
    <div>
      <dt>Пользователь</dt>
      <dd>{{ user.name }}</dd>
    </div>
    <div>
      <dt>Email</dt>
      <dd>{{ user.email }}</dd>
    </div>
    <div>
      <dt>Роль</dt>
      <dd>{{ user.role }}</dd>
    </div>
    <div>
      <dt>API</dt>
      <dd>{{ apiBaseUrl }}</dd>
    </div>
  </dl>

  <div class="token-box">
    <span>Access token</span>
    <code>{{ token.access_token }}</code>
  </div>

  <div class="actions-row">
    <button class="btn btn-primary" type="button" :disabled="loading" @click="$emit('refresh-profile')">
      Обновить профиль
    </button>
    <button class="btn btn-outline-secondary" type="button" :disabled="loading" @click="$emit('refresh-token')">
      Обновить токен
    </button>
  </div>

  <UserDevicesPanel
    :api-base-url="apiBaseUrl"
    :auth-header="authHeader"
    :devices="userDevices"
    :loading="deviceLoading"
    :error="deviceError"
    @refresh="$emit('refresh-devices')"
    @create="$emit('create-device', $event)"
    @update="$emit('update-device', $event)"
    @delete="$emit('delete-device', $event)"
  />

  <AdminCatalogTables
    v-if="user.role === 'admin'"
    :loading="catalogLoading"
    :users="adminUsers"
    :devices="adminDevices"
    :error="catalogError"
    @refresh="$emit('refresh-catalog')"
  />
</template>
