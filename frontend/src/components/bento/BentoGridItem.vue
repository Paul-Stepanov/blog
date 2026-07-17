<script setup lang="ts">
/**
 * BentoGridItem — span-обёртка (только grid-placement, без визуала карточки).
 *
 * @description Низкоуровневый: задаёт col-span/row-span через CSS vars.
 * Используется, когда контент — не карточка (embed, raw-блок).
 *
 * @example
 * ```vue
 * <BentoGridItem :col-span="8"><iframe src="..." /></BentoGridItem>
 * ```
 */

import { computed } from 'vue'
import { useBentoSpan, type BentoSpanProps } from '@/composables/useBentoSpan'

interface Props extends BentoSpanProps {
  as?: 'div' | 'article' | 'section'
}

const props = withDefaults(defineProps<Props>(), {
  as: 'div',
})

const tag = computed(() => props.as)
const spanStyle = useBentoSpan(props)
</script>

<template>
  <component :is="tag" class="bento-item" :style="spanStyle()">
    <slot />
  </component>
</template>