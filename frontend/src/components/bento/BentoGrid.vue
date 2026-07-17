<script setup lang="ts">
/**
 * BentoGrid — контейнер CSS Grid с container queries.
 *
 * @description 12 колонок (desktop) → 6 (tablet) → 1 (mobile) через
 * @container. Дочерние .bento-item используют span из useBentoSpan.
 *
 * @example
 * ```vue
 * <BentoGrid>
 *   <BentoCard :col-span="12"><Hero /></BentoCard>
 *   <BentoCard :col-span="4" :col-span-md="6"><About /></BentoCard>
 * </BentoGrid>
 * ```
 */

import { computed } from 'vue'

interface Props {
  columns?: number
  gutter?: string
  margin?: string
  as?: 'div' | 'section' | 'main'
}

const props = withDefaults(defineProps<Props>(), {
  columns: 12,
  as: 'div',
})

const tag = computed(() => props.as)

const styleVars = computed(() => ({
  '--cols': String(props.columns),
  '--gutter': props.gutter ?? 'var(--bento-gutter)',
  '--margin': props.margin ?? 'var(--bento-margin)',
}))
</script>

<template>
  <component :is="tag" class="bento-grid" :style="styleVars">
    <slot />
  </component>
</template>

<style scoped>
.bento-grid {
  display: grid;
  grid-template-columns: repeat(var(--cols), minmax(0, 1fr));
  gap: var(--gutter);
  padding: var(--margin);
  container-type: inline-size;
}
</style>