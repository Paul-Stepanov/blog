<script setup lang="ts">
/**
 * EmptyState — пустое состояние (нет данных).
 *
 * @description Иконка + заголовок + сообщение + опциональный slot action.
 *
 * @example
 * ```vue
 * <EmptyState message="Статей не найдено" />
 * ```
 */
import type { Component } from 'vue'
import { Inbox } from 'lucide-vue-next'

interface Props {
  title?: string
  message: string
  icon?: Component
}

withDefaults(defineProps<Props>(), {
  title: 'Ничего не найдено',
  icon: Inbox,
})
</script>

<template>
  <div class="empty-state">
    <component :is="icon" class="empty-state__icon" aria-hidden="true" />
    <h2 v-if="title" class="empty-state__title">{{ title }}</h2>
    <p class="empty-state__message">{{ message }}</p>
    <slot name="action" />
  </div>
</template>

<style scoped>
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-9) var(--space-5);
  text-align: center;
}

.empty-state__icon {
  width: var(--space-9);
  height: var(--space-9);
  color: var(--color-text-secondary);
  opacity: 0.5;
}

.empty-state__title {
  font-family: var(--font-display), serif;
  font-size: var(--text-lg);
  color: var(--color-text-primary);
  margin: 0;
}

.empty-state__message {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-base);
  color: var(--color-text-secondary);
  max-width: 40ch;
  margin: 0;
}
</style>