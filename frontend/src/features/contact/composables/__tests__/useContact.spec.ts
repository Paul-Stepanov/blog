import { describe, it, expect, beforeEach, vi } from 'vitest'
import { defineComponent } from 'vue'
import { mount } from '@vue/test-utils'
import { ApiRequestError } from '@/types/api'
import type { ApiError } from '@/types/api'
import type { ContactPayload } from '@/types/models'

vi.mock('@/services/contactService')
import { contactService } from '@/services/contactService'
import { useContact } from '../useContact'

const sendMock = vi.mocked(contactService.send)

/** Вызов composable внутри setup-контекста через mount. */
function withComposable<T>(fn: () => T): { result: T; unmount: () => void } {
  let result!: T
  const Comp = defineComponent({
    setup() {
      result = fn()
      return () => null
    },
  })
  const wrapper = mount(Comp)
  return { result, unmount: () => wrapper.unmount() }
}

const validPayload: ContactPayload = {
  name: 'John',
  email: 'john@example.com',
  subject: 'Hello',
  message: 'This is a valid message',
}

function apiError(status: number, errors?: Record<string, string[]>): ApiRequestError {
  const apiError: ApiError = {
    success: false,
    error: status === 422 ? 'validation_error' : 'too_many_attempts',
    message: 'error',
    ...(errors ? { errors } : {}),
  }
  return new ApiRequestError(apiError, status)
}

describe('useContact', () => {
  beforeEach(() => {
    sendMock.mockReset()
  })

  it('sets success=true on successful submit', async () => {
    sendMock.mockResolvedValueOnce({ id: 'msg-1' })
    const { result, unmount } = withComposable(() => useContact())

    const ok = await result.submit(validPayload)

    expect(ok).toBe(true)
    expect(result.success.value).toBe(true)
    expect(result.submitting.value).toBe(false)
    unmount()
  })

  it('maps 422 response to fieldErrors', async () => {
    sendMock.mockRejectedValueOnce(apiError(422, { email: ['Invalid email'] }))
    const { result, unmount } = withComposable(() => useContact())

    await result.submit(validPayload)

    expect(result.fieldErrors.value.email?.[0]).toBe('Invalid email')
    expect(result.success.value).toBe(false)
    unmount()
  })

  it('sets rateLimited=true on 429 (throttle)', async () => {
    sendMock.mockRejectedValueOnce(apiError(429))
    const { result, unmount } = withComposable(() => useContact())

    await result.submit(validPayload)

    expect(result.rateLimited.value).toBe(true)
    expect(result.success.value).toBe(false)
    unmount()
  })

  it('reset() clears success and fieldErrors', async () => {
    sendMock.mockResolvedValueOnce({ id: 'msg-1' })
    const { result, unmount } = withComposable(() => useContact())
    await result.submit(validPayload)

    result.reset()

    expect(result.success.value).toBe(false)
    expect(result.fieldErrors.value).toEqual({})
    unmount()
  })
})