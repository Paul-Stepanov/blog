/**
 * useArticle — загрузка одной статьи по slug.
 *
 * @description Поверх useAsyncData. Реактивен к смене slug (router param):
 * при смене slug автоматически ре-фетчит. 404 (архив/нет) → error, не крашит.
 *
 * @example
 * ```ts
 * const route = useRoute()
 * const { article, loading, error } = useArticle(() => route.params.slug as string)
 * ```
 */

import { toRef, watch } from 'vue'
import { articleService } from '@/services/articleService'
import { useAsyncData } from '@/composables/useAsyncData'
import type { Article } from '@/types/api'

export function useArticle(slug: Parameters<typeof toRef<string>>[0]) {
  const slugRef = toRef(slug)

  const { data: article, loading, error, execute, refresh } = useAsyncData<Article, string>(
    (s) => articleService.getBySlug(s ?? slugRef.value),
    { immediate: true },
  )

  watch(slugRef, () => {
    void execute(slugRef.value)
  })

  return { article, loading, error, refresh }
}