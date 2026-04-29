<script setup>
import AuthForm from './AuthForm.vue';
import LoadingState from './LoadingState.vue';
import ProfilePanel from './ProfilePanel.vue';

defineProps({
  apiBaseUrl: {
    type: String,
    required: true,
  },
  checkingSession: {
    type: Boolean,
    required: true,
  },
  authenticated: {
    type: Boolean,
    required: true,
  },
  loading: {
    type: Boolean,
    required: true,
  },
  mode: {
    type: String,
    required: true,
  },
  loginForm: {
    type: Object,
    required: true,
  },
  registerForm: {
    type: Object,
    required: true,
  },
  user: {
    type: Object,
    default: null,
  },
  token: {
    type: Object,
    default: null,
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
  error: {
    type: String,
    required: true,
  },
  notice: {
    type: String,
    required: true,
  },
});

defineEmits([
  'update:mode',
  'login',
  'register',
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
  <div class="auth-panel">
    <LoadingState v-if="checkingSession" />

    <ProfilePanel
      v-else-if="authenticated"
      :api-base-url="apiBaseUrl"
      :loading="loading"
      :user="user"
      :token="token"
      :admin-users="adminUsers"
      :admin-devices="adminDevices"
      :catalog-loading="catalogLoading"
      :catalog-error="catalogError"
      :user-devices="userDevices"
      :device-loading="deviceLoading"
      :device-error="deviceError"
      :auth-header="authHeader"
      @refresh-profile="$emit('refresh-profile')"
      @refresh-token="$emit('refresh-token')"
      @refresh-catalog="$emit('refresh-catalog')"
      @refresh-devices="$emit('refresh-devices')"
      @create-device="$emit('create-device', $event)"
      @update-device="$emit('update-device', $event)"
      @delete-device="$emit('delete-device', $event)"
    />

    <AuthForm
      v-else
      :api-base-url="apiBaseUrl"
      :loading="loading"
      :mode="mode"
      :login-form="loginForm"
      :register-form="registerForm"
      @update:mode="$emit('update:mode', $event)"
      @login="$emit('login')"
      @register="$emit('register')"
    />

    <div v-if="error" class="alert alert-danger mt-3 mb-0">{{ error }}</div>
    <div v-if="notice" class="alert alert-success mt-3 mb-0">{{ notice }}</div>
  </div>
</template>
