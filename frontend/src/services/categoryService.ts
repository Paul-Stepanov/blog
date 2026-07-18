/**
 * Category Service — публичные операции с категориями.
 *
 * @description GET /api/categories (CategorySummary[]), GET /api/categories/{slug} (Category).
 *
 * @example
 * ```ts
 * const categories = await categoryService.list()
 * ```
 */

import { apiClient } from '@/services/apiClient'
import type { Category, CategorySummary } from '@/types/api'

interface CategoryListResponse {
  success: true
  data: CategorySummary[]
}

interface CategoryResponse {
  success: true
  data: Category
}

export const categoryService = {
  /** Категории с опубликованными статьями (CategorySummary[]). limit: 1–500, default 100. */
  async list(limit?: number): Promise<CategorySummary[]> {
    const { data } = await apiClient.get<CategoryListResponse>('/categories', {
      params: limit ? { limit } : undefined,
    })
    return data.data
  },

  /** Категория по slug (404 если нет). */
  async getBySlug(slug: string): Promise<Category> {
    const { data } = await apiClient.get<CategoryResponse>(
      `/categories/${slug}`,
    )
    return data.data
  },
}