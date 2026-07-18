/**
 * Tag Service — публичные операции с тегами.
 *
 * @description GET /api/tags (TagSummary[]), GET /api/tags/popular (PopularTag[] —
 * ВНИМАНИЕ: поле articles_count с 's'), GET /api/tags/{slug} (Tag).
 *
 * @example
 * ```ts
 * const popular = await tagService.popular(10)
 * ```
 */

import { apiClient } from '@/services/apiClient'
import type { PopularTag, Tag, TagSummary } from '@/types/api'

interface TagListResponse {
  success: true
  data: TagSummary[]
}

interface PopularTagListResponse {
  success: true
  data: PopularTag[]
}

interface TagResponse {
  success: true
  data: Tag
}

export const tagService = {
  /** Все теги по имени (TagSummary[]). limit: 1–500, default 100. */
  async list(limit?: number): Promise<TagSummary[]> {
    const { data } = await apiClient.get<TagListResponse>('/tags', {
      params: limit ? { limit } : undefined,
    })
    return data.data
  },

  /** Популярные теги (PopularTag[]; articles_count с 's'). limit: 1–50, default 10. */
  async popular(limit = 10): Promise<PopularTag[]> {
    const { data } = await apiClient.get<PopularTagListResponse>(
      '/tags/popular',
      { params: { limit } },
    )
    return data.data
  },

  /** Тег по slug (404 если нет). */
  async getBySlug(slug: string): Promise<Tag> {
    const { data } = await apiClient.get<TagResponse>(`/tags/${slug}`)
    return data.data
  },
}