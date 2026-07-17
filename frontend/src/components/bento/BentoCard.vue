<script setup lang="ts">
/**
 * BentoCard — ячейка Bento-сетки + полноценная карточка.
 *
 * @description = BentoGridItem (grid-span) + BaseCard (визуал).
 * props span управляют размещением; padding/interactive — внешним видом.
 *
 * @example
 * ```vue
 * <BentoCard :col-span="8" padding="lg" interactive>
 *   <h3>Latest articles</h3>
 * </BentoCard>
 * ```
 */

import { computed } from 'vue'
import BaseCard from '@/components/base/BaseCard.vue'
import { useBentoSpan, type BentoSpanProps } from '@/composables/useBentoSpan'

type Padding = 'none' | 'sm' | 'md' | 'lg'

interface Props extends BentoSpanProps {
  padding?: Padding
  interactive?: boolean
  as?: 'div' | 'article' | 'section'
}

const props = withDefaults(defineProps<Props>(), {
  padding: 'md',
  interactive: false,
  as: 'div',
})

const spanStyle = computed(() => useBentoSpan(props)())
</script>

<template>
  <!-- class/style падают на корень BaseCard через fallthrough attrs -->
  <BaseCard
    class="bento-item"
    :style="spanStyle"
    :padding="padding"
    :interactive="interactive"
    :as="as"
  >
    <slot />
  </BaseCard>
</template>