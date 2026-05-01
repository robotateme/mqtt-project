<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

defineProps({
  title: {
    type: String,
    required: true,
  },
});

const opened = ref(false);
let previousBodyOverflow = '';

function open() {
  opened.value = true;
}

function close() {
  opened.value = false;
}

function closeOnEscape(event) {
  if (event.key === 'Escape') {
    close();
  }
}

onMounted(() => {
  window.addEventListener('keydown', closeOnEscape);
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', closeOnEscape);
  document.body.style.overflow = previousBodyOverflow;
});

watch(opened, (isOpened) => {
  if (isOpened) {
    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return;
  }

  document.body.style.overflow = previousBodyOverflow;
});
</script>

<template>
  <button class="btn btn-outline-secondary btn-sm" type="button" @click="open">
    Fullscreen
  </button>

  <Teleport to="body">
    <div v-if="opened" class="fullscreen-table-backdrop" role="presentation" @click.self="close">
      <section class="fullscreen-table-dialog" role="dialog" aria-modal="true" :aria-label="title">
        <header class="fullscreen-table-header">
          <h2>{{ title }}</h2>
          <button class="btn btn-outline-secondary btn-sm" type="button" @click="close">
            Close
          </button>
        </header>
        <div class="fullscreen-table-content">
          <slot />
        </div>
      </section>
    </div>
  </Teleport>
</template>
