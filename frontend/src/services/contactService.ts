/**
 * Contact Service — отправка контактной формы.
 *
 * @description POST /api/contact ({name,email,subject,message}) → {id}.
 * Требует CSRF-cookie Sanctum перед POST (ensureCsrfCookie).
 * Throttle: 3/hour → 429 при превышении (ловится в useContact).
 *
 * @example
 * ```ts
 * const { id } = await contactService.send({ name, email, subject, message })
 * ```
 */

import { apiClient, ensureCsrfCookie } from '@/services/apiClient'
import type { ContactPayload } from '@/types/models'

interface ContactResponse {
  success: true
  message: string
  data: { id: string }
}

export const contactService = {
  /** Отправить контактное сообщение. Возвращает id созданного сообщения. */
  async send(payload: ContactPayload): Promise<{ id: string }> {
    await ensureCsrfCookie()
    const { data } = await apiClient.post<ContactResponse>('/contact', payload)
    return data.data
  },
}