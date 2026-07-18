import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { AxiosError, AxiosHeaders, type AxiosAdapter, type AxiosResponse, type InternalAxiosRequestConfig } from 'axios'
import { createPinia, setActivePinia } from 'pinia'

import { apiClient, ensureCsrfCookie } from '../apiClient'
import { ApiRequestError } from '@/types/api'
import { useAuthStore } from '@/stores/authStore'
import type { User } from '@/types/api'

const sampleUser: User = {
  id: '1',
  name: 'Admin',
  email: 'admin@example.com',
  role: 'admin',
  created_at: '2026-01-01T00:00:00Z',
  updated_at: '2026-01-01T00:00:00Z',
}

function emptyConfig(): InternalAxiosRequestConfig {
  return { headers: new AxiosHeaders() }
}

function makeResponse<T>(data: T, status = 200): AxiosResponse<T> {
  return {
    data,
    status,
    statusText: status === 200 ? 'OK' : 'ERR',
    headers: {},
    config: emptyConfig(),
  }
}

function makeHttpError(status: number, data: unknown): AxiosError {
  return new AxiosError('Request failed with status code ' + status, 'ERR_BAD_REQUEST', emptyConfig(), {}, {
    data,
    status,
    statusText: '',
    headers: {},
    config: emptyConfig(),
  })
}

function setAdapter(adapter: AxiosAdapter): void {
  apiClient.defaults.adapter = adapter
}

describe('apiClient response interceptor', () => {
  let originalAdapter: AxiosAdapter | undefined

  beforeEach(() => {
    originalAdapter = apiClient.defaults.adapter as AxiosAdapter | undefined
  })

  afterEach(() => {
    if (originalAdapter) {
      apiClient.defaults.adapter = originalAdapter
    }
  })

  it('passes a successful response through unchanged', async () => {
    const payload = { success: true, data: [{ id: 'a1', title: 'Hello' }] }
    setAdapter(async () => makeResponse(payload))

    const response = await apiClient.get('/articles')

    expect(response.data).toEqual(payload)
  })

  it('normalizes a 422 response into ApiRequestError with field errors', async () => {
    const body = {
      success: false,
      error: 'validation_error',
      message: 'The given data was invalid',
      errors: { email: ['The email is invalid'], password: ['Required'] },
    }
    setAdapter(async () => {
      throw makeHttpError(422, body)
    })

    await expect(apiClient.post('/contact', { email: 'bad' })).rejects.toSatisfy((error: unknown) => {
      const apiError = error as ApiRequestError
      return apiError.status === 422
        && apiError.isValidation
        && apiError.fieldErrors.email?.[0] === 'The email is invalid'
        && apiError.apiError.error === 'validation_error'
    })
  })

  it('triggers authStore.handleUnauthorized on 401 and keeps status 401', async () => {
    setActivePinia(createPinia())
    const auth = useAuthStore()
    auth.user = sampleUser

    setAdapter(async () => {
      throw makeHttpError(401, { success: false, error: 'unauthenticated', message: 'Unauthenticated.' })
    })

    await expect(apiClient.get('/admin/articles')).rejects.toMatchObject({ status: 401 })

    await vi.waitFor(() => {
      expect(auth.user).toBeNull()
    })
  })

  it('normalizes a network error (no response) into status 0 / network_error', async () => {
    setAdapter(async () => {
      throw new AxiosError('Network Error', 'ERR_NETWORK', emptyConfig())
    })

    await expect(apiClient.get('/x')).rejects.toSatisfy((error: unknown) => {
      const apiError = error as ApiRequestError
      return apiError.status === 0
        && apiError.apiError.error === 'network_error'
        && apiError.apiError.message === 'Network error'
    })
  })

  it('ensureCsrfCookie hits /sanctum/csrf-cookie', async () => {
    const capturedUrls: string[] = []
    setAdapter(async (config) => {
      capturedUrls.push(config.url ?? '')
      return makeResponse({ message: 'ok' })
    })

    await ensureCsrfCookie()

    expect(capturedUrls).toContain('/sanctum/csrf-cookie')
  })
})