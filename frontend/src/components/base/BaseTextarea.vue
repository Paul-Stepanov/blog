<script setup lang="ts">
/**
 * BaseTextarea — многострочное поле, v-model, авто-resize.
 *
 * @description Паттерн BaseInput + авто-подстройка высоты под контент
 * (@vueuse useTextareaAutosize). Ручной resize (vertical) также доступен.
 *
 * @example
 * ```vue
 * <BaseTextarea v-model="message" label="Message" :rows="5" />
 * ```
 */

import { computed, ref, useId } from 'vue'
import { useTextareaAutosize } from '@vueuse/core'

interface Props {
  label?: string
  error?: string
  hint?: string
  placeholder?: string
  id?: string
  rows?: number
  maxlength?: number
  required?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  rows: 4,
  required: false,
  disabled: false,
})

const model = defineModel<string>({ required: true })

const autoId = useId()
const textareaId = computed(() => props.id ?? `textarea-${autoId}`)
const hintId = computed(() => `${textareaId.value}-hint`)
const errorId = computed(() => `${textareaId.value}-error`)

const describedBy = computed(() => {
  const ids: string[] = []
  if (props.hint) ids.push(hintId.value)
  if (props.error) ids.push(errorId.value)
  return ids.length ? ids.join(' ') : undefined
})

// SSR-safe: useTextareaAutosize guard'ит window внутри @vueuse.
const textareaEl = ref<HTMLTextAreaElement | null>(null)
useTextareaAutosize({
  element: textareaEl,
  input: model,
})
</script>

<template>
  <div class="field" :class="{ 'field--error': !!error, 'field--disabled': disabled }">
    <label v-if="label" :for="textareaId" class="field__label">
      {{ label }}
      <span v-if="required" class="field__required" aria-hidden="true">*</span>
    </label>

    <textarea
      :id="textareaId"
      ref="textareaEl"
      v-model="model"
      :rows="rows"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :maxlength="maxlength"
      :aria-invalid="error ? 'true' : undefined"
      :aria-describedby="describedBy"
      class="field__textarea"
    />

    <div v-if="hint || maxlength" class="field__meta">
      <p v-if="hint && !error" :id="hintId" class="field__hint">{{ hint }}</p>
      <span v-if="maxlength" class="field__count" :aria-hidden="true">
        {{ model?.length ?? 0 }}/{{ maxlength }}
      </span>
    </div>
    <p v-if="error" :id="errorId" class="field__error" role="alert">{{ error }}</p>
  </div>
</template>

<style scoped>
.field {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.field__label {
  font-family: var(--font-body);
  font-size: var(--text-sm);
  font-weight: var(--weight-medium);
  color: var(--color-text-primary);
}

.field__required {
  color: var(--color-error);
}

.field__textarea {
  width: 100%;
  padding: var(--space-3) var(--space-4);
  font-family: var(--font-body);
  font-size: var(--text-base);
  line-height: var(--leading-normal);
  color: var(--color-text-primary);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border-strong);
  border-radius: var(--radius-md);
  resize: vertical;
  transition:
    border-color var(--dur-fast) var(--ease),
    box-shadow var(--dur-fast) var(--ease);
}

.field__textarea::placeholder {
  color: var(--color-text-secondary);
}

.field__textarea:focus {
  outline: none;
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}

.field__textarea:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.field--error .field__textarea {
  border-color: var(--color-error);
}

.field__meta {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: var(--space-3);
}

.field__hint {
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

.field__count {
  font-size: var(--text-xs);
  color: var(--color-text-secondary);
  margin-left: auto;
}

.field__error {
  font-size: var(--text-sm);
  color: var(--color-error);
}
</style>