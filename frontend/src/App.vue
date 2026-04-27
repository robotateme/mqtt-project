<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AppTopbar from './components/AppTopbar.vue';
import AuthPanel from './components/AuthPanel.vue';
import InfoPanel from './components/InfoPanel.vue';

const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL || 'http://api.mqtt.local').replace(/\/$/, '');
const storageKey = 'mqtt-project.auth';

const mode = ref('login');
const loading = ref(false);
const checkingSession = ref(true);
const error = ref('');
const notice = ref('');
const user = ref(null);
const token = ref(null);

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
const authHeader = computed(() => `${token.value?.token_type || 'Bearer'} ${token.value?.access_token || ''}`);

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
  restoreSession();
  loadProfile();
});
</script>

<template>
  <main class="app-shell">
    <AppTopbar :authenticated="isAuthenticated" :loading="loading" @logout="logout" />

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
        :error="error"
        :notice="notice"
        @login="submitLogin"
        @register="submitRegister"
        @refresh-profile="loadProfile"
        @refresh-token="refreshToken"
      />
      <InfoPanel :api-base-url="apiBaseUrl" />
    </section>
  </main>
</template>
