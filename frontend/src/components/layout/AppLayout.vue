<script setup lang="ts">
/**
 * AppLayout — каркас публичной страницы.
 *
 * @description Header + main(router-view) + Footer. При mount один раз грузит
 * categoryStore + settingsStore (один запрос на сессию, реактивно расходится по
 * страницам/footer). Admin-layout (фаза 9) может заменить через route.meta.layout.
 */
import { onMounted } from 'vue'
import AppHeader from './AppHeader.vue'
import AppFooter from './AppFooter.vue'
import { useCategoryStore } from '@/stores/categoryStore'
import { useSettingsStore } from '@/stores/settingsStore'

const categoryStore = useCategoryStore()
const settingsStore = useSettingsStore()

onMounted(() => {
  // Idempotent: повторные вызовы (напр. из страниц) пропустят запрос.
  // Ошибки пишутся в store.error, layout не падает.
  void categoryStore.load()
  void settingsStore.load()
})
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