/**
 * API Client — инстанс axios с нормализацией ошибок.
 *
 * @description Конфигурация для Sanctum SPA-auth (cookie-based):
 * - baseURL ОТНОСИТЕЛЬНЫЙ ('/api') — same-origin через nginx (:80),
 *   cookies и CSRF работают автоматически.
 * - withCredentials: true — передача session-cookie.
 * - response-error interceptor нормализует любую ошибку к ApiRequestError.
 *
 * @example
 * ```ts
 * import { apiClient } from '@/services/apiClient'
 * const { data } = await apiClient.get<Article>('/articles/my-slug')
 * ```
 */

import axios from 'axios'
import { ApiRequestError, type ApiError } from '@/types/api'

export const apiClient = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 15_000,
})

/**
 * Нормализует Axios-ошибку к ApiRequestError.
 * Lazy-import authStore — чтобы избежать cycle (фаза 9: authStore будет
 * импортировать services).
 */
function toApiRequestError(error: unknown): ApiRequestError {
  // axios ошибка с ответом сервера
  if (axios.isAxiosError(error) && error.response) {
    const status = error.response.status
    const payload = error.response.data as Partial<ApiError> | undefined

    const apiError: ApiError = {
      success: false,
      error: payload?.error ?? 'http_error',
      message: payload?.message ?? error.message,
      ...(payload?.context ? { context: payload.context } : {}),
      ...(payload?.errors ? { errors: payload.errors } : {}),
    }

    // 401 → сброс аутентификации (lazy import)
    if (status === 401) {
      void triggerUnauthorized()
    }

    return new ApiRequestError(apiError, status)
  }

  // network / timeout / отменённый запрос
  if (axios.isAxiosError(error)) {
    const apiError: ApiError = {
      success: false,
      error: 'network_error',
      message: error.code === 'ECONNABORTED' ? 'Request timeout' : 'Network error',
    }
    return new ApiRequestError(apiError, 0)
  }

  // неизвестная ошибка
  const apiError: ApiError = {
    success: false,
    error: 'unknown_error',
    message: error instanceof Error ? error.message : 'Unknown error',
  }
  return new ApiRequestError(apiError, 0)
}

async function triggerUnauthorized(): Promise<void> {
  try {
    const { useAuthStore } = await import('@/stores/authStore')
    useAuthStore().handleUnauthorized()
  } catch {
    // store недоступен (нет активного pinia) — игнорируем на уровне интерсептора
  }
}

apiClient.interceptors.response.use(
  (response) => response,
  (error) => Promise.reject(toApiRequestError(error)),
)

/**
 * Получает CSRF-cookie Sanctum перед небезопасными методами (POST/PUT/DELETE).
 * Вызывать точечно в конкретном service (не глобально — чтобы не плодить лишние запросы).
 */
export async function ensureCsrfCookie(): Promise<void> {
  await apiClient.get('/sanctum/csrf-cookie')
}