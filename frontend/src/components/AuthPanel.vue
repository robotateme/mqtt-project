<script setup>
import { computed } from 'vue';
import { RouterView, useRoute } from 'vue-router';
import AuthForm from './AuthForm.vue';
import LoadingState from './LoadingState.vue';

const props = defineProps({
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

const emit = defineEmits([
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

const route = useRoute();

const routedPanelProps = computed(() => {
  if (route.name === 'all-devices') {
    return {
      loading: props.catalogLoading,
      devices: props.adminDevices,
      error: props.catalogError,
    };
  }

  if (route.name === 'all-users') {
    return {
      loading: props.catalogLoading,
      users: props.adminUsers,
      error: props.catalogError,
    };
  }

  if (route.name === 'my-profile') {
    return {
      apiBaseUrl: props.apiBaseUrl,
      loading: props.loading,
      user: props.user,
      token: props.token,
    };
  }

  if (route.name === 'live-packets') {
    return {
      apiBaseUrl: props.apiBaseUrl,
      authHeader: props.authHeader,
      devices: props.user?.role === 'admin' ? props.adminDevices : props.userDevices,
      loading: props.user?.role === 'admin' ? props.catalogLoading : props.deviceLoading,
      error: props.user?.role === 'admin' ? props.catalogError : props.deviceError,
      view: 'packets',
    };
  }

  return {
    apiBaseUrl: props.apiBaseUrl,
    authHeader: props.authHeader,
    devices: props.userDevices,
    loading: props.deviceLoading,
    error: props.deviceError,
    view: route.meta.view || 'devices',
  };
});

const routedPanelListeners = computed(() => {
  if (route.name === 'all-devices') {
    return {
      refresh: () => emit('refresh-catalog'),
    };
  }

  if (route.name === 'all-users') {
    return {
      refresh: () => emit('refresh-catalog'),
    };
  }

  if (route.name === 'my-profile') {
    return {
      'refresh-profile': () => emit('refresh-profile'),
      'refresh-token': () => emit('refresh-token'),
    };
  }

  return {
    refresh: () => emit('refresh-devices'),
    create: (event) => emit('create-device', event),
    update: (event) => emit('update-device', event),
    delete: (event) => emit('delete-device', event),
  };
});
</script>

<template>
  <div class="auth-panel">
    <LoadingState v-if="checkingSession" />

    <RouterView v-else-if="authenticated" v-slot="{ Component }">
      <component :is="Component" v-bind="routedPanelProps" v-on="routedPanelListeners" />
    </RouterView>

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
