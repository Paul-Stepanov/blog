/**
 * API Contract — типы 1:1 с реальными Resources/DTO бэкенда.
 *
 * Источник правды: laravel/app/Infrastructure/Http/Resources/*,
 * Application/'*'/DTOs/'*', bootstrap/app.php.
 */

export type ArticleStatus = 'draft' | 'published' | 'archived'

/** Элемент category/tags в составе статьи */
export interface ArticleRef {
  name: string
  slug: string
}

/** Автор статьи (неполный — только имя) */
export interface ArticleAuthor {
  name: string
}

/** Полное представление статьи (GET /api/articles/{slug}) */
export interface Article {
  id: string
  title: string
  slug: string
  content: string // HTML
  excerpt: string
  status: ArticleStatus
  category_id: string | null
  category: ArticleRef | null
  author_id: string | null
  author: ArticleAuthor | null
  cover_image_id: string | null
  cover_image_url: string | null
  tags: ArticleRef[]
  published_at: string | null
  created_at: string
  updated_at: string
  word_count: number
  reading_time: number // минуты
  reading_time_text: string // напр. "5 min read"
}

/**
 * Лёгкое представление для списков (GET /api/articles).
 * ВНИМАНИЕ: ArticleListResource формирует массив вручную и НЕ отдаёт
 * reading_time_text (хотя DTO его имеет).
 */
export interface ArticleListItem {
  id: string
  title: string
  slug: string
  excerpt: string
  status: ArticleStatus
  category_id: string | null
  category: ArticleRef | null
  tags: ArticleRef[]
  cover_image_url: string | null
  published_at: string | null
  reading_time: number
  created_at: string
  updated_at: string
}

/** Параметры запроса списка статей */
export interface ArticleListParams {
  page?: number
  per_page?: number
  category?: string // slug
  search?: string
}

export interface Category {
  id: string
  name: string
  slug: string
  description: string | null
  created_at: string
  updated_at: string
}

export interface CategoryListItem {
  id: string
  name: string
  slug: string
  description: string | null
  article_count: number
}

export interface Tag {
  id: string
  name: string
  slug: string
  created_at: string
  updated_at: string
}

export interface TagListItem {
  id: string
  name: string
  slug: string
  article_count: number
}

export interface SiteSetting {
  id: string
  key: string
  group: string
  value: string
  type: string
  created_at: string
  updated_at: string
}

export interface MediaFile {
  id: string
  file_name: string
  file_path: string
  public_url: string
  mime_type: string
  file_size: number
  size_human: string
  width: number | null
  height: number | null
  alt_text: string | null
  is_image: boolean
  created_at: string
  updated_at: string
}

export interface User {
  id: string
  name: string
  email: string
  role: string
  created_at: string
  updated_at: string
}

/**
 * Формат ошибок бэкенда (bootstrap/app.php).
 * 404 EntityNotFoundException, 400 DomainException, 422 ValidationException,
 * 401 AuthenticationException.
 */
export interface ApiError {
  success: false
  error: string // 'entity_not_found' | <domain errorType> | 'validation_error' | 'unauthenticated'
  message: string
  context?: Record<string, unknown> // для 404/400
  errors?: Record<string, string[]> // для 422: { field: ['msg', ...] }
}

/** Универсальная обёртка пагинированного ответа (PaginatedResource). */
export interface PaginatedResponse<T> {
  success: true
  data: T[]
  meta: {
    pagination: {
      total: number
      count: number // число элементов в текущей странице
      per_page: number
      current_page: number
      total_pages: number
      has_more: boolean
    }
  }
}

/**
 * Ошибка запроса — оборачивает ApiError для try/catch в services/composables.
 * noUncheckedIndexedAccess: обращение к data[i] даёт T | undefined.
 */
export class ApiRequestError extends Error {
  readonly apiError: ApiError
  readonly status: number

  constructor(apiError: ApiError, status: number) {
    super(apiError.message)
    this.name = 'ApiRequestError'
    this.apiError = apiError
    this.status = status
  }

  /** Ошибка валидации поля (422) */
  get isValidation(): boolean {
    return this.status === 422
  }

  /** Карта ошибок полей (для 422) */
  get fieldErrors(): Record<string, string[]> {
    return this.apiError.errors ?? {}
  }
}
