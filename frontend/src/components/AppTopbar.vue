<script setup>
defineProps({
  authenticated: {
    type: Boolean,
    required: true,
  },
  loading: {
    type: Boolean,
    required: true,
  },
  themes: {
    type: Array,
    required: true,
  },
  theme: {
    type: String,
    required: true,
  },
});

defineEmits(['logout', 'update:theme']);
</script>

<template>
  <nav class="topbar">
    <div>
      <span class="brand">MQTT Project</span>
      <span class="service-chip">Vue frontend</span>
    </div>
    <div class="topbar-actions">
      <label class="theme-select">
        <span>Тема</span>
        <select class="form-select form-select-sm" :value="theme" @change="$emit('update:theme', $event.target.value)">
          <option v-for="item in themes" :key="item.id" :value="item.id">
            {{ item.label }}
          </option>
        </select>
      </label>
      <button
        v-if="authenticated"
        class="btn btn-outline-light btn-sm"
        type="button"
        :disabled="loading"
        @click="$emit('logout')"
      >
        Выйти
      </button>
    </div>
  </nav>
</template>
