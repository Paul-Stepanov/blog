<script setup lang="ts">
/**
 * Pagination — постраничная навигация.
 *
 * @description Windowed-навигация по meta.pagination: prev/next + номера
 * (первая, последняя, текущая ±1, ellipsis). aria-current на текущей.
 *
 * @example
 * ```vue
 * <Pagination :pagination="meta.pagination" @change="onPage" />
 * ```
 */
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import BaseButton from '@/components/base/BaseButton.vue'

interface PaginationMeta {
  current_page: number
  total_pages: number
  has_more: boolean
}

interface Props {
  pagination: PaginationMeta
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'change', page: number): void
}>()

const current = computed(() => props.pagination.current_page)
const total = computed(() => props.pagination.total_pages)
const isFirst = computed(() => current.value <= 1)
const isLast = computed(() => current.value >= total.value || total.value <= 1)

/** Окно номеров: первая, последняя, текущая ±1; ellipsis для разрывов. */
const pages = computed<(number | 'ellipsis')[]>(() => {
  const t = total.value
  const c = current.value
  if (t <= 7) return Array.from({ length: t }, (_, i) => i + 1)

  const set = new Set<number>([1, t, c, c - 1, c + 1])
  const sorted = [...set].filter((n) => n >= 1 && n <= t).sort((a, b) => a - b)

  const result: (number | 'ellipsis')[] = []
  let prev = 0
  for (const n of sorted) {
    if (n - prev > 1) result.push('ellipsis')
    result.push(n)
    prev = n
  }
  return result
})

function go(page: number | 'ellipsis'): void {
  if (page === 'ellipsis') return
  if (page < 1 || page > total.value || page === current.value) return
  emit('change', page)
}
</script>

<template>
  <nav v-if="total > 1" class="pagination" aria-label="Пагинация">
    <BaseButton
      variant="ghost"
      size="sm"
      :disabled="isFirst"
      aria-label="Предыдущая страница"
      @click="go(current - 1)"
    >
      <ChevronLeft aria-hidden="true" />
    </BaseButton>

    <BaseButton
      v-for="(p, i) in pages"
      :key="i"
      variant="ghost"
      size="sm"
      :disabled="p === 'ellipsis' || p === current"
      :aria-current="p === current ? 'page' : undefined"
      :aria-label="p === 'ellipsis' ? undefined : `Страница ${p}`"
      @click="go(p)"
    >
      {{ p === 'ellipsis' ? '…' : p }}
    </BaseButton>

    <BaseButton
      variant="ghost"
      size="sm"
      :disabled="isLast"
      aria-label="Следующая страница"
      @click="go(current + 1)"
    >
      <ChevronRight aria-hidden="true" />
    </BaseButton>
  </nav>
</template>

<style scoped>
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: var(--space-1);
  margin-top: var(--space-8);
}
</style>