<script setup lang="ts">
/**
 * BaseButton — полиморфная кнопка.
 *
 * @description Рендерит <button> | <a> | <router-link> по prop `as`.
 * Варианты/размеры, loading (спиннер + aria-busy), disabled, block, иконки.
 *
 * @example
 * ```vue
 * <BaseButton variant="primary" @click="save">Save</BaseButton>
 * <BaseButton as="router-link" to="/articles" icon-left="ArrowRight">Articles</BaseButton>
 * ```
 */

import { computed, type Component } from 'vue'
import BaseLoader from './BaseLoader.vue'

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger'
type Size = 'sm' | 'md' | 'lg'

interface Props {
  variant?: Variant
  size?: Size
  type?: 'button' | 'submit' | 'reset'
  as?: 'button' | 'a' | 'router-link'
  to?: string | Record<string, unknown>
  href?: string
  loading?: boolean
  disabled?: boolean
  block?: boolean
  iconLeft?: Component
  iconRight?: Component
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  type: 'button',
  as: 'button',
  loading: false,
  disabled: false,
  block: false,
})

const emit = defineEmits<{
  (e: 'click', event: MouseEvent): void
}>()

const tag = computed(() => {
  if (props.as === 'router-link') return 'router-link'
  if (props.as === 'a') return 'a'
  return 'button'
})

const isDisabled = computed(() => props.disabled || props.loading)

function onClick(event: MouseEvent): void {
  if (isDisabled.value) {
    event.preventDefault()
    return
  }
  emit('click', event)
}
</script>

<template>
  <component
    :is="tag"
    class="btn"
    :class="[
      `btn--${variant}`,
      `btn--${size}`,
      { 'btn--block': block, 'btn--loading': loading, 'btn--disabled': isDisabled },
    ]"
    :type="as === 'button' ? type : undefined"
    :to="as === 'router-link' ? to : undefined"
    :href="as === 'a' ? href : undefined"
    :disabled="as === 'button' ? isDisabled : undefined"
    :aria-busy="loading || undefined"
    :aria-disabled="isDisabled || undefined"
    @click="onClick"
  >
    <BaseLoader v-if="loading" :size="size" class="btn__spinner" />
    <component
      :is="iconLeft"
      v-else-if="iconLeft"
      class="btn__icon btn__icon--left"
      aria-hidden="true"
    />
    <span class="btn__label"><slot /></span>
    <component
      :is="iconRight"
      v-if="iconRight && !loading"
      class="btn__icon btn__icon--right"
      aria-hidden="true"
    />
  </component>
</template>

<style scoped>
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
  font-family: var(--font-body);
  font-weight: var(--weight-medium);
  line-height: 1;
  white-space: nowrap;
  border-radius: var(--radius-md);
  cursor: pointer;
  user-select: none;
  transition:
    background-color var(--dur-fast) var(--ease),
    color var(--dur-fast) var(--ease),
    transform var(--dur-fast) var(--ease),
    box-shadow var(--dur-fast) var(--ease);
}

.btn:active {
  transform: translateY(1px);
}

/* === sizes === */
.btn--sm {
  font-size: var(--text-sm);
  padding: var(--space-2) var(--space-3);
}

.btn--md {
  font-size: var(--text-base);
  padding: var(--space-3) var(--space-5);
}

.btn--lg {
  font-size: var(--text-lg);
  padding: var(--space-4) var(--space-6);
}

/* === variants === */
.btn--primary {
  background: var(--color-accent);
  color: var(--color-text-on-accent);
}

.btn--primary:hover {
  background: var(--color-accent-strong);
}

.btn--secondary {
  background: var(--color-bg-card);
  color: var(--color-text-primary);
  border: 1px solid var(--color-border-strong);
}

.btn--secondary:hover {
  background: var(--color-bg-elevated);
}

.btn--ghost {
  background: transparent;
  color: var(--color-accent-strong);
}

.btn--ghost:hover {
  background: var(--color-accent-soft);
}

.btn--danger {
  background: var(--color-error);
  color: var(--color-text-on-accent);
}

.btn--danger:hover {
  filter: brightness(0.92);
}

/* === states === */
.btn--block {
  display: flex;
  width: 100%;
}

.btn--disabled {
  opacity: 0.55;
  cursor: not-allowed;
  pointer-events: none;
}

.btn__icon {
  width: 1.1em;
  height: 1.1em;
}

.btn__spinner {
  color: currentColor;
}
</style>