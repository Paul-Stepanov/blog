/**
 * useAsyncData — каноничный composable для асинхронных данных.
 *
 * @description { data, loading, error, execute, refresh }. Параметризуемый
 * (execute принимает params). Ошибки нормализуются к ApiRequestError.
 * SSR-safe (нет top-level window).
 *
 * @example
 * ```ts
 * const { data, loading, error, execute } = useAsyncData(
 *   (slug: string) => articleService.getBySlug(slug),
 *   { immediate: false },
 * )
 * await execute('my-slug')
 * ```
 */

import { ref, type Ref } from 'vue'
import { ApiRequestError, type ApiError } from '@/types/api'

interface UseAsyncDataOptions<T> {
  /** Выполнить fetchFn на создании */
  immediate?: boolean
  /** Начальное значение data */
  initial?: T | null
}

export interface UseAsyncDataReturn<T, P> {
  data: Ref<T | null>
  loading: Ref<boolean>
  error: Ref<ApiRequestError | null>
  execute: (params?: P) => Promise<T | null>
  refresh: () => Promise<T | null>
}

export function useAsyncData<T, P = unknown>(
  fetchFn: (params?: P) => Promise<T>,
  options: UseAsyncDataOptions<T> = {},
): UseAsyncDataReturn<T, P> {
  const data = ref<T | null>(options.initial ?? null) as Ref<T | null>
  const loading = ref(false)
  const error = ref<ApiRequestError | null>(null)

  async function run(params?: P): Promise<T | null> {
    loading.value = true
    error.value = null
    try {
      const result = await fetchFn(params)
      data.value = result
      return result
    } catch (e) {
      error.value = toApiRequestError(e)
      return null
    } finally {
      loading.value = false
    }
  }

  if (options.immediate) {
    void run()
  }

  return {
    data,
    loading,
    error,
    execute: run,
    refresh: () => run(),
  }
}

function toApiRequestError(e: unknown): ApiRequestError {
  if (e instanceof ApiRequestError) return e
  const apiError: ApiError = {
    success: false,
    error: 'unknown_error',
    message: e instanceof Error ? e.message : 'Unknown error',
  }
  return new ApiRequestError(apiError, 0)
}