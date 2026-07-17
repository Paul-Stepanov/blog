<script setup lang="ts">
/**
 * AppLayout — каркас публичной страницы.
 *
 * @description Header + main(router-view) + Footer. Admin-layout (фаза 9)
 * может заменить через route.meta.layout.
 */

import AppHeader from './AppHeader.vue'
import AppFooter from './AppFooter.vue'
</script>

<template>
  <div class="app">
    <AppHeader />
    <main class="app__main">
      <router-view />
    </main>
    <AppFooter />
  </div>
</template>

<style scoped>
.app {
  display: flex;
  flex-direction: column;
  min-height: 100dvh;
}

.app__main {
  flex: 1 0 auto;
}
</style>