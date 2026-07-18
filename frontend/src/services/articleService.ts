/**
 * Article Service — публичные операции со статьями.
 *
 * @description Тонкая типизированная обёртка над apiClient для публичных
 * article-эндпоинтов. Распаковывает конверт {success, data} → чистый тип.
 *
 * @example
 * ```ts
 * const result = await articleService.list({ page: 1, per_page: 9 })
 * result.data            // ArticleListItem[]
 * result.meta.pagination // { total, current_page, ... }
 * ```
 */

import { apiClient } from '@/services/apiClient'
import type {
  Article,
  ArticleListItem,
  ArticleListParams,
  PaginatedResponse,
} from '@/types/api'

interface ArticleResponse {
  success: true
  data: Article
}

export const articleService = {
  /** Список опубликованных статей с фильтрами (category_id, search) и пагинацией. */
  async list(
    params: ArticleListParams = {},
  ): Promise<PaginatedResponse<ArticleListItem>> {
    const { data } = await apiClient.get<PaginatedResponse<ArticleListItem>>(
      '/articles',
      { params },
    )
    return data
  },

  /** Статья по slug (404 → ApiRequestError, если нет/черновик). */
  async getBySlug(slug: string): Promise<Article> {
    const { data } = await apiClient.get<ArticleResponse>(`/articles/${slug}`)
    return data.data
  },
}