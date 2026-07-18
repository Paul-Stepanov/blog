<script setup lang="ts">
/**
 * BaseInput — текстовое поле с label/error/hint, v-model.
 *
 * @description label связан через id, aria-invalid при error, aria-describedby
 * связывает hint/error. iconLeft — lucide-иконка.
 *
 * @example
 * ```vue
 * <BaseInput v-model="email" label="Email" type="email" :error="errors.email" />
 * ```
 */

import { computed, useId, type Component } from 'vue'

type InputType = 'text' | 'email' | 'password' | 'search' | 'url' | 'number'

interface Props {
  label?: string
  error?: string
  hint?: string
  type?: InputType
  placeholder?: string
  id?: string
  required?: boolean
  disabled?: boolean
  autocomplete?: string
  iconLeft?: Component
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  required: false,
  disabled: false,
})

const model = defineModel<string>({ required: true })

const autoId = useId()
const inputId = computed(() => props.id ?? `input-${autoId}`)
const hintId = computed(() => `${inputId.value}-hint`)
const errorId = computed(() => `${inputId.value}-error`)

const describedBy = computed(() => {
  const ids: string[] = []
  if (props.hint) ids.push(hintId.value)
  if (props.error) ids.push(errorId.value)
  return ids.length ? ids.join(' ') : undefined
})
</script>

<template>
  <div class="field" :class="{ 'field--error': !!error, 'field--disabled': disabled }">
    <label v-if="label" :for="inputId" class="field__label">
      {{ label }}
      <span v-if="required" class="field__required" aria-hidden="true">*</span>
    </label>

    <div class="field__control">
      <component
        :is="iconLeft"
        v-if="iconLeft"
        class="field__icon"
        aria-hidden="true"
      />
      <input
        :id="inputId"
        v-model="model"
        :type="type"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :autocomplete="autocomplete"
        :aria-invalid="error ? 'true' : undefined"
        :aria-describedby="describedBy"
        class="field__input"
        :class="{ 'field__input--icon': iconLeft }"
      />
    </div>

    <p v-if="hint && !error" :id="hintId" class="field__hint">{{ hint }}</p>
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
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-sm);
  font-weight: var(--weight-medium);
  color: var(--color-text-primary);
}

.field__required {
  color: var(--color-error);
}

.field__control {
  position: relative;
  display: flex;
  align-items: center;
}

.field__icon {
  position: absolute;
  left: var(--space-3);
  width: 1.1em;
  height: 1.1em;
  color: var(--color-text-secondary);
  pointer-events: none;
}

.field__input {
  width: 100%;
  padding: var(--space-3) var(--space-4);
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-base);
  color: var(--color-text-primary);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border-strong);
  border-radius: var(--radius-md);
  transition:
    border-color var(--dur-fast) var(--ease),
    box-shadow var(--dur-fast) var(--ease);
}

.field__input--icon {
  padding-left: calc(var(--space-3) * 2 + 1.1em);
}

.field__input::placeholder {
  color: var(--color-text-secondary);
}

.field__input:focus {
  outline: none;
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}

.field__input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.field--error .field__input {
  border-color: var(--color-error);
}

.field--error .field__input:focus {
  box-shadow: 0 0 0 3px rgba(168, 68, 58, 0.15);
}

.field__hint {
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

.field__error {
  font-size: var(--text-sm);
  color: var(--color-error);
}
</style>