/**
 * Category Store — глобальное состояние категорий для публички.
 *
 * @description Список категорий (GET /api/categories → CategorySummary[]) для
 * AppHeader nav и HomePage. Idempotent load (один запрос на сессию).
 * SSR-safe: Pinia-гидратация в Фазе 10 (vite-ssg).
 *
 * @example
 * ```ts
 * const store = useCategoryStore()
 * await store.load()
 * store.categories      // CategorySummary[]
 * store.bySlug('vue')   // CategorySummary | undefined
 * ```
 */

import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { CategorySummary } from '@/types/api'
import { categoryService } from '@/services/categoryService'

export const useCategoryStore = defineStore('categories', () => {
  const categories = ref<CategorySummary[]>([])
  const loaded = ref(false)
  const loading = ref(false)
  const error = ref<string | null>(null)

  /** Найти категорию по slug. */
  const bySlug = computed(
    () => (slug: string) => categories.value.find((c) => c.slug === slug),
  )

  /** Idempotent load: пропускает запрос если уже загружено (если не force). */
  async function load(force = false): Promise<void> {
    if (loaded.value && !force) return
    loading.value = true
    error.value = null
    try {
      categories.value = await categoryService.list()
      loaded.value = true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load categories'
    } finally {
      loading.value = false
    }
  }

  return { categories, loaded, loading, error, bySlug, load }
})