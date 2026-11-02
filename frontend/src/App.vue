<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
</script>

<template>
  <header class="app-header">
    <div class="app-header__inner">
      <RouterLink :to="{ name: 'queue' }" class="brand">Review Queue</RouterLink>
    </div>
  </header>
  <main class="app-main">
    <RouterView />
  </main>
</template>

<style scoped>
.app-header {
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
}

.app-header__inner,
.app-main {
  max-width: 72rem;
  margin: 0 auto;
  padding: 0 1rem;
}

.app-header__inner {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  min-height: 3.5rem;
}

.brand {
  font-weight: 700;
  color: var(--color-text);
  text-decoration: none;
}

.app-main {
  padding-top: 1.5rem;
  padding-bottom: 3rem;
}
</style>
