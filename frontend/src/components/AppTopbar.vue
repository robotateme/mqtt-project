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
});

defineEmits(['logout']);
</script>

<template>
  <nav class="topbar">
    <div>
      <span class="brand">MQTT Project</span>
      <span class="service-chip">Vue frontend</span>
    </div>
    <button
      v-if="authenticated"
      class="btn btn-outline-light btn-sm"
      type="button"
      :disabled="loading"
      @click="$emit('logout')"
    >
      Выйти
    </button>
  </nav>
</template>
