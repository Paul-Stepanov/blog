/**
 * useContact — состояние формы обратной связи.
 *
 * @description Submit + обработка ошибок: 422 → fieldErrors (для полей формы),
 * 429 (throttle 3/hour) → rateLimited (блокировка), success → флаг. reset() очищает.
 *
 * @example
 * ```ts
 * const { submitting, fieldErrors, rateLimited, submit } = useContact()
 * const ok = await submit({ name, email, subject, message })
 * ```
 */

import { ref } from 'vue'
import { contactService } from '@/services/contactService'
import { ApiRequestError } from '@/types/api'
import type { ContactPayload } from '@/types/models'

export function useContact() {
  const submitting = ref(false)
  const fieldErrors = ref<Record<string, string[]>>({})
  const rateLimited = ref(false)
  const success = ref(false)

  /** Отправить форму. true — успех, false — ошибка (смотрите fieldErrors/rateLimited). */
  async function submit(payload: ContactPayload): Promise<boolean> {
    submitting.value = true
    fieldErrors.value = {}
    rateLimited.value = false
    try {
      await contactService.send(payload)
      success.value = true
      return true
    } catch (e) {
      if (e instanceof ApiRequestError) {
        if (e.isValidation) fieldErrors.value = e.fieldErrors
        if (e.status === 429) rateLimited.value = true
      }
      return false
    } finally {
      submitting.value = false
    }
  }

  /** Очистить состояние (после успеха/закрытия). */
  function reset(): void {
    success.value = false
    fieldErrors.value = {}
    rateLimited.value = false
  }

  return { submitting, fieldErrors, rateLimited, success, submit, reset }
}