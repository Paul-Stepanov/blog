import { describe, it, expect, vi } from 'vitest'
import { nextTick } from 'vue'
import { useAsyncData } from '../useAsyncData'
import { ApiRequestError } from '@/types/api'

describe('useAsyncData', () => {
  it('starts idle (not immediate) with null data', () => {
    const fetchFn = vi.fn()
    const { data, loading, error } = useAsyncData(fetchFn, { immediate: false })

    expect(data.value).toBeNull()
    expect(loading.value).toBe(false)
    expect(error.value).toBeNull()
    expect(fetchFn).not.toHaveBeenCalled()
  })

  it('runs immediately when immediate=true', async () => {
    const fetchFn = vi.fn().mockResolvedValue({ id: 1, title: 'Test' })
    const { data, loading } = useAsyncData(fetchFn, { immediate: true })

    // immediate запускает fetch синхронно (promise в полёте)
    expect(loading.value).toBe(true)

    await nextTick()
    await Promise.resolve()
    await nextTick()

    expect(fetchFn).toHaveBeenCalledTimes(1)
    expect(data.value).toEqual({ id: 1, title: 'Test' })
    expect(loading.value).toBe(false)
  })

  it('resolves data on execute and returns result', async () => {
    const fetchFn = vi.fn().mockResolvedValue('hello')
    const { execute, data } = useAsyncData<string>(fetchFn, { immediate: false })

    const result = await execute()

    expect(result).toBe('hello')
    expect(data.value).toBe('hello')
  })

  it('passes params to fetchFn', async () => {
    const fetchFn = vi.fn().mockResolvedValue('ok')
    const { execute } = useAsyncData<string, string>(fetchFn, { immediate: false })

    await execute('my-slug')

    expect(fetchFn).toHaveBeenCalledWith('my-slug')
  })

  it('normalizes ApiRequestError on failure without throwing', async () => {
    const apiError = new ApiRequestError(
      { success: false, error: 'entity_not_found', message: 'Not found' },
      404,
    )
    const fetchFn = vi.fn().mockRejectedValue(apiError)
    const { execute, data, error, loading } = useAsyncData(fetchFn, { immediate: false })

    const result = await execute()

    expect(result).toBeNull()
    expect(data.value).toBeNull()
    expect(error.value).toBeInstanceOf(ApiRequestError)
    expect(error.value?.status).toBe(404)
    expect(error.value?.apiError.error).toBe('entity_not_found')
    expect(loading.value).toBe(false)
  })

  it('wraps generic errors into ApiRequestError', async () => {
    const fetchFn = vi.fn().mockRejectedValue(new Error('boom'))
    const { execute, error } = useAsyncData(fetchFn, { immediate: false })

    await execute()

    expect(error.value).toBeInstanceOf(ApiRequestError)
    expect(error.value?.status).toBe(0)
    expect(error.value?.apiError.error).toBe('unknown_error')
  })

  it('refresh re-runs fetchFn', async () => {
    const fetchFn = vi.fn().mockResolvedValue('ok')
    const { execute, refresh } = useAsyncData(fetchFn, { immediate: false })

    await execute()
    await refresh()

    expect(fetchFn).toHaveBeenCalledTimes(2)
  })
})