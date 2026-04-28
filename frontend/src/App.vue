<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AppTopbar from './components/AppTopbar.vue';
import AuthPanel from './components/AuthPanel.vue';
import InfoPanel from './components/InfoPanel.vue';

const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL || 'http://api.mqtt.local').replace(/\/$/, '');
const storageKey = 'mqtt-project.auth';
const themeStorageKey = 'mqtt-project.theme';
const themes = [
  { id: 'default', label: 'Default' },
  { id: 'empire-night', label: 'StarWars (empire-night)' },
  { id: 'republic-day', label: 'StarWars (republic-day)' },
  { id: 'tron-neon-night', label: 'Tron (tron-neon-night)' },
];

const mode = ref('login');
const theme = ref('default');
const loading = ref(false);
const checkingSession = ref(true);
const error = ref('');
const notice = ref('');
const user = ref(null);
const token = ref(null);
const adminUsers = ref([]);
const adminDevices = ref([]);
const catalogLoading = ref(false);
const catalogError = ref('');

const loginForm = reactive({
  email: '',
  password: '',
});

const registerForm = reactive({
  name: '',
  email: '',
  password: '',
});

const isAuthenticated = computed(() => Boolean(user.value && token.value?.access_token));
const isAdmin = computed(() => user.value?.role === 'admin');
const authHeader = computed(() => `${token.value?.token_type || 'Bearer'} ${token.value?.access_token || ''}`);

function applyTheme(nextTheme) {
  const normalizedTheme = themes.some((item) => item.id === nextTheme) ? nextTheme : 'default';
  theme.value = normalizedTheme;
  document.documentElement.dataset.theme = normalizedTheme;
  document.documentElement.dataset.bsTheme = normalizedTheme.includes('night') ? 'dark' : 'light';
  localStorage.setItem(themeStorageKey, normalizedTheme);
}

function restoreTheme() {
  applyTheme(localStorage.getItem(themeStorageKey) || 'default');
}

function apiUrl(path) {
  return `${apiBaseUrl}/api/v1${path}`;
}

function saveSession(nextUser, nextToken) {
  user.value = nextUser;
  token.value = nextToken;
  localStorage.setItem(storageKey, JSON.stringify({ user: nextUser, token: nextToken }));
}

function clearSession() {
  user.value = null;
  token.value = null;
  adminUsers.value = [];
  adminDevices.value = [];
  catalogLoading.value = false;
  catalogError.value = '';
  localStorage.removeItem(storageKey);
}

function restoreSession() {
  const payload = localStorage.getItem(storageKey);

  if (!payload) {
    return;
  }

  try {
    const session = JSON.parse(payload);
    user.value = session.user || null;
    token.value = session.token || null;
  } catch {
    clearSession();
  }
}

async function request(path, options = {}) {
  const response = await fetch(apiUrl(path), {
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(options.headers || {}),
    },
  });

  if (response.status === 204) {
    return null;
  }

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    const message = data.message || data.error || 'API request failed.';
    throw new Error(message);
  }

  return data;
}

async function loadAdminCatalog() {
  if (!isAdmin.value || !token.value?.access_token) {
    adminUsers.value = [];
    adminDevices.value = [];
    catalogError.value = '';
    return;
  }

  catalogLoading.value = true;
  catalogError.value = '';

  try {
    const [users, devices] = await Promise.all([
      request('/admin/users', {
        headers: {
          Authorization: authHeader.value,
        },
      }),
      request('/admin/devices', {
        headers: {
          Authorization: authHeader.value,
        },
      }),
    ]);

    adminUsers.value = users.data || [];
    adminDevices.value = devices.data || [];
  } catch (exception) {
    catalogError.value = exception.message;
  } finally {
    catalogLoading.value = false;
  }
}

async function submitLogin() {
  loading.value = true;
  error.value = '';
  notice.value = '';

  try {
    const data = await request('/auth/login', {
      method: 'POST',
      body: JSON.stringify(loginForm),
    });

    saveSession(data.user, data.token);
    await loadAdminCatalog();
    notice.value = 'Сессия открыта.';
  } catch (exception) {
    error.value = exception.message;
  } finally {
    loading.value = false;
  }
}

async function submitRegister() {
  loading.value = true;
  error.value = '';
  notice.value = '';

  try {
    const data = await request('/auth/register', {
      method: 'POST',
      body: JSON.stringify(registerForm),
    });

    saveSession(data.user, data.token);
    notice.value = 'Пользователь зарегистрирован.';
  } catch (exception) {
    error.value = exception.message;
  } finally {
    loading.value = false;
  }
}

async function refreshToken() {
  if (!token.value?.refresh_token) {
    return;
  }

  loading.value = true;
  error.value = '';
  notice.value = '';

  try {
    const data = await request('/auth/refresh', {
      method: 'POST',
      body: JSON.stringify({ refresh_token: token.value.refresh_token }),
    });

    saveSession(user.value, data.token);
    notice.value = 'Токен обновлен.';
  } catch (exception) {
    error.value = exception.message;
    clearSession();
  } finally {
    loading.value = false;
  }
}

async function loadProfile() {
  if (!token.value?.access_token) {
    checkingSession.value = false;
    return;
  }

  try {
    const data = await request('/auth/me', {
      headers: {
        Authorization: authHeader.value,
      },
    });

    user.value = data.user;
    saveSession(data.user, token.value);
    await loadAdminCatalog();
  } catch {
    clearSession();
  } finally {
    checkingSession.value = false;
  }
}

async function logout() {
  loading.value = true;
  error.value = '';
  notice.value = '';

  try {
    if (token.value?.access_token) {
      await request('/auth/logout', {
        method: 'POST',
        headers: {
          Authorization: authHeader.value,
        },
      });
    }
  } catch (exception) {
    error.value = exception.message;
  } finally {
    clearSession();
    loading.value = false;
  }
}

onMounted(() => {
  restoreTheme();
  restoreSession();
  loadProfile();
});
</script>

<template>
  <main class="app-shell">
    <AppTopbar
      :authenticated="isAuthenticated"
      :loading="loading"
      :themes="themes"
      :theme="theme"
      @update:theme="applyTheme"
      @logout="logout"
    />

    <section class="workspace">
      <AuthPanel
        v-model:mode="mode"
        :api-base-url="apiBaseUrl"
        :checking-session="checkingSession"
        :authenticated="isAuthenticated"
        :loading="loading"
        :login-form="loginForm"
        :register-form="registerForm"
        :user="user"
        :token="token"
        :admin-users="adminUsers"
        :admin-devices="adminDevices"
        :catalog-loading="catalogLoading"
        :catalog-error="catalogError"
        :error="error"
        :notice="notice"
        @login="submitLogin"
        @register="submitRegister"
        @refresh-profile="loadProfile"
        @refresh-token="refreshToken"
        @refresh-catalog="loadAdminCatalog"
      />
      <InfoPanel :api-base-url="apiBaseUrl" />
    </section>
  </main>
</template>
