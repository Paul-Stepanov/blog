<script setup lang="ts">
/**
 * NotFoundPage — 404.
 */
import { useSeo } from '@/composables/useSeo'
import BaseButton from '@/components/base/BaseButton.vue'

useSeo({ title: '404 — Not Found' })
</script>

<template>
  <section class="not-found container">
    <p class="not-found__code">404</p>
    <h1 class="not-found__title">Page not found</h1>
    <BaseButton as="router-link" to="/">Back to home</BaseButton>
  </section>
</template>

<style scoped>
.not-found {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-4);
  padding-block: var(--space-9);
  text-align: center;
}

.not-found__code {
  font-family: var(--font-display);
  font-size: var(--text-3xl);
  font-weight: var(--weight-bold);
  color: var(--color-accent);
}

.not-found__title {
  font-family: var(--font-display);
  font-size: var(--text-xl);
}
</style>