/**
 * useArticles — список статей с пагинацией, фильтрами и URL-sync.
 *
 * @description Единый источник правды — route.query ({page, category_id, search}).
 * params — computed из query; watch(params) → re-fetch. setFilter/setPage → router.replace.
 * URL shareable/bookmarkable. Поверх useAsyncData.
 *
 * @example
 * ```ts
 * const { articles, pagination, loading, setFilter, setPage } = useArticles()
 * ```
 */

import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { articleService } from '@/services/articleService'
import { useAsyncData } from '@/composables/useAsyncData'
import type {
  ArticleListItem,
  ArticleListParams,
  PaginatedResponse,
} from '@/types/api'

const PER_PAGE = 9

export function useArticles() {
  const route = useRoute()
  const router = useRouter()

  /** Параметры запроса — из URL (единый источник правды). */
  const params = computed<ArticleListParams>(() => ({
    page: Number(route.query.page) || 1,
    per_page: PER_PAGE,
    category_id: (route.query.category_id as string | undefined) || undefined,
    search: (route.query.search as string | undefined) || undefined,
  }))

  const { data, loading, error, execute, refresh } = useAsyncData<
    PaginatedResponse<ArticleListItem>,
    ArticleListParams
  >(() => articleService.list(params.value), { immediate: true })

  // Ре-фетч при смене query (навигация/фильтр/search)
  watch(params, () => {
    void execute(params.value)
  })

  const articles = computed(() => data.value?.data ?? [])
  const pagination = computed(() => data.value?.meta.pagination ?? null)

  /** Применить фильтр: обновить URL (replace), сбросив страницу на 1. */
  function setFilter(patch: Partial<ArticleListParams>): void {
    void router.replace({ query: { ...route.query, ...patch, page: 1 } })
  }

  /** Перейти на страницу: обновить ?page. */
  function setPage(page: number): void {
    void router.replace({ query: { ...route.query, page } })
  }

  return {
    articles,
    pagination,
    loading,
    error,
    params,
    setFilter,
    setPage,
    refresh,
  }
}