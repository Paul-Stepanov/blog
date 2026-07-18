<script setup lang="ts">
/**
 * ContactForm — форма обратной связи.
 *
 * @description name/email/subject/message через useContact. 422 → ошибки под
 * полями (fieldErrors). 429 (throttle) → баннер + disabled submit. Success →
 * уведомление (uiStore) + очистка + emit 'sent'.
 *
 * @example
 * ```vue
 * <ContactForm @sent="onSent" />
 * ```
 */
import { ref } from 'vue'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseTextarea from '@/components/base/BaseTextarea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import { useUiStore } from '@/stores/uiStore'
import { useContact } from '../composables/useContact'
import type { ContactPayload } from '@/types/models'

const emit = defineEmits<{
  (e: 'sent'): void
}>()

const ui = useUiStore()
const { submitting, fieldErrors, rateLimited, submit, reset } = useContact()

const name = ref('')
const email = ref('')
const subject = ref('')
const message = ref('')

function fieldError(field: keyof ContactPayload): string | undefined {
  return fieldErrors.value[field]?.[0]
}

async function onSubmit(): Promise<void> {
  const ok = await submit({
    name: name.value,
    email: email.value,
    subject: subject.value,
    message: message.value,
  })

  if (ok) {
    ui.notify('success', 'Сообщение отправлено. Спасибо!')
    name.value = ''
    email.value = ''
    subject.value = ''
    message.value = ''
    reset()
    emit('sent')
  }
}
</script>

<template>
  <form class="contact-form" @submit.prevent="onSubmit">
    <div v-if="rateLimited" class="contact-form__alert" role="alert">
      Слишком много сообщений отправлено. Попробуйте позже.
    </div>

    <BaseInput
      v-model="name"
      label="Имя"
      placeholder="Ваше имя"
      :error="fieldError('name')"
      required
    />

    <BaseInput
      v-model="email"
      label="Email"
      type="email"
      placeholder="you@example.com"
      :error="fieldError('email')"
      autocomplete="email"
      required
    />

    <BaseInput
      v-model="subject"
      label="Тема"
      placeholder="О чём сообщение"
      :error="fieldError('subject')"
      required
    />

    <BaseTextarea
      v-model="message"
      label="Сообщение"
      placeholder="Напишите ваше сообщение (минимум 10 символов)"
      :error="fieldError('message')"
      :maxlength="5000"
      :rows="5"
      required
    />

    <BaseButton
      type="submit"
      variant="primary"
      :loading="submitting"
      :disabled="rateLimited"
    >
      Отправить
    </BaseButton>
  </form>
</template>

<style scoped>
.contact-form {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
  max-width: 560px;
}

.contact-form__alert {
  padding: var(--space-3) var(--space-4);
  background: var(--color-accent-soft);
  border-left: 3px solid var(--color-warning);
  border-radius: var(--radius-sm);
  font-family: var(--font-body);
  font-size: var(--text-sm);
  color: var(--color-text-primary);
}
</style>