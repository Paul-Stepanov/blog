<script setup lang="ts">
/**
 * BaseCard — визуальный контейнер-карточка.
 *
 * @description Без логики — фон/радиус/тень/padding. interactive — hover-lift.
 * Основа для BentoCard.
 *
 * @example
 * ```vue
 * <BaseCard padding="lg" interactive><h3>Title</h3><p>Body</p></BaseCard>
 * ```
 */

import { computed } from 'vue'

type Padding = 'none' | 'sm' | 'md' | 'lg'

interface Props {
  padding?: Padding
  interactive?: boolean
  as?: 'div' | 'article' | 'section'
}

const props = withDefaults(defineProps<Props>(), {
  padding: 'md',
  interactive: false,
  as: 'div',
})

const tag = computed(() => props.as)
</script>

<template>
  <component
    :is="tag"
    class="card"
    :class="[`card--p-${padding}`, { 'card--interactive': interactive }]"
  >
    <slot />
  </component>
</template>

<style scoped>
.card {
  background: var(--color-bg-card);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card);
  transition:
    transform var(--dur) var(--ease),
    box-shadow var(--dur) var(--ease);
}

.card--p-none {
  padding: 0;
}

.card--p-sm {
  padding: var(--space-4);
}

.card--p-md {
  padding: var(--space-7);
}

.card--p-lg {
  padding: var(--space-9);
}

.card--interactive {
  cursor: pointer;
}

.card--interactive:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-hover);
}
</style>