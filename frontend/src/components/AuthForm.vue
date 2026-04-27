<script setup>
defineProps({
  apiBaseUrl: {
    type: String,
    required: true,
  },
  loading: {
    type: Boolean,
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
  mode: {
    type: String,
    required: true,
  },
});

defineEmits(['update:mode', 'login', 'register']);
</script>

<template>
  <div class="panel-header">
    <div>
      <p class="eyebrow">Авторизация</p>
      <h1>{{ mode === 'login' ? 'Вход в систему' : 'Новый пользователь' }}</h1>
    </div>
    <span class="api-chip">{{ apiBaseUrl }}</span>
  </div>

  <div class="mode-switch" role="tablist" aria-label="Auth mode">
    <button type="button" :class="{ active: mode === 'login' }" @click="$emit('update:mode', 'login')">Вход</button>
    <button type="button" :class="{ active: mode === 'register' }" @click="$emit('update:mode', 'register')">
      Регистрация
    </button>
  </div>

  <form v-if="mode === 'login'" class="auth-form" @submit.prevent="$emit('login')">
    <label>
      <span>Email</span>
      <input v-model.trim="loginForm.email" class="form-control" type="email" autocomplete="email" required />
    </label>
    <label>
      <span>Пароль</span>
      <input v-model="loginForm.password" class="form-control" type="password" autocomplete="current-password" required />
    </label>
    <button class="btn btn-primary w-100" type="submit" :disabled="loading">
      {{ loading ? 'Вход...' : 'Войти' }}
    </button>
  </form>

  <form v-else class="auth-form" @submit.prevent="$emit('register')">
    <label>
      <span>Имя</span>
      <input v-model.trim="registerForm.name" class="form-control" type="text" autocomplete="name" required />
    </label>
    <label>
      <span>Email</span>
      <input v-model.trim="registerForm.email" class="form-control" type="email" autocomplete="email" required />
    </label>
    <label>
      <span>Пароль</span>
      <input
        v-model="registerForm.password"
        class="form-control"
        type="password"
        autocomplete="new-password"
        minlength="8"
        required
      />
    </label>
    <button class="btn btn-primary w-100" type="submit" :disabled="loading">
      {{ loading ? 'Регистрация...' : 'Зарегистрироваться' }}
    </button>
  </form>
</template>
