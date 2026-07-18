<script setup lang="ts">
/**
 * SearchInput — поле поиска с debounce.
 *
 * @description form[role=search] + BaseInput type=search. v-model (мгновенно,
 * для отзывчивости ввода) + emit 'search' (debounced, для URL-sync/фетча).
 *
 * @example
 * ```vue
 * <SearchInput v-model="query" @search="onSearch" />
 * ```
 */
import { computed, watch } from 'vue'
import { refDebounced } from '@vueuse/core'
import { Search } from 'lucide-vue-next'
import BaseInput from '@/components/base/BaseInput.vue'

interface Props {
  modelValue: string
  placeholder?: string
  /** Задержка debounce в мс. */
  delay?: number
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Поиск…',
  delay: 350,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'search', value: string): void
}>()

const local = computed<string>({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const debounced = refDebounced(local, props.delay)
watch(debounced, (value) => emit('search', value))
</script>

<template>
  <form class="search" role="search" @submit.prevent>
    <BaseInput
      v-model="local"
      type="search"
      :placeholder="placeholder"
      :icon-left="Search"
      aria-label="Поиск"
    />
  </form>
</template>

<style scoped>
.search {
  max-width: 420px;
}
</style>