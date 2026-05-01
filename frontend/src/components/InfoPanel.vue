<script setup>
import { RouterLink } from 'vue-router';

defineProps({
  isAdmin: {
    type: Boolean,
    required: true,
  },
});

const navItems = [
  { to: '/all-devices', label: 'All devices', adminOnly: true },
  { to: '/all-users', label: 'All users', adminOnly: true },
  { to: '/my-devices', label: 'My devices' },
  { to: '/live-packets', label: 'Live packets' },
  { to: '/my-profile', label: 'My profile' },
];
</script>

<template>
  <aside class="info-panel">
    <h2>Navigation</h2>
    <nav class="side-nav" aria-label="Admin navigation">
      <RouterLink
        v-for="item in navItems.filter((navItem) => !navItem.adminOnly || isAdmin)"
        :key="item.to"
        :to="item.to"
        class="side-nav-link"
      >
        {{ item.label }}
      </RouterLink>
    </nav>
  </aside>
</template>
