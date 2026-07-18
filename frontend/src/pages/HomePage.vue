<script setup lang="ts">
/**
 * HomePage — placeholder (фаза 8 наполнит Bento-раскладкой).
 *
 * @description Демонстрирует работающий фундамент: layout, router, токены.
 */
import { useSeo } from '@/composables/useSeo'

useSeo({ title: 'Blog — Home' })
</script>

<template>
  <section class="home container">
    <h1 class="home__title text-hero">Blog</h1>
    <p class="home__lead">
      Frontend foundation is ready — design tokens, base components, Bento Grid
      and API client are in place. Phase 8 will fill this page.
    </p>
  </section>
</template>

<style scoped>
.home {
  padding-block: var(--space-9);
}

.home__title {
  margin-bottom: var(--space-4);
}

.home__lead {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-base);
  color: var(--color-text-secondary);
  max-width: 60ch;
}
</style>