<script setup lang="ts">
/**
 * BaseModal — модальный диалог.
 *
 * @description Teleport to body, focus-trap (@vueuse), Escape-закрытие,
 * scroll-lock, Transition fade+scale. v-model:open. a11y: role=dialog,
 * aria-modal, aria-labelledby.
 *
 * @example
 * ```vue
 * <BaseModal v-model:open="isOpen" title="Confirm">
 *   <p>Are you sure?</p>
 *   <template #footer><BaseButton @click="confirm">OK</BaseButton></template>
 * </BaseModal>
 * ```
 */

import { computed, ref, watch, onBeforeUnmount, useId } from 'vue'
import { useFocusTrap } from '@vueuse/integrations/useFocusTrap'
import { useScrollLock } from '@vueuse/core'

type Size = 'sm' | 'md' | 'lg' | 'fullscreen'

interface Props {
  open: boolean
  title?: string
  size?: Size
  closable?: boolean
  maskClosable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  closable: true,
  maskClosable: true,
})

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'close'): void
}>()

const panelRef = ref<HTMLElement | null>(null)
const titleId = `modal-title-${useId()}`

// Focus trap активируется только когда есть panel и open
const { activate, deactivate } = useFocusTrap(panelRef, {
  immediate: false,
})

// Scroll-lock на body (useScrollLock — SSR-safe)
const isLocked = useScrollLock(
  typeof document !== 'undefined' ? document.body : null,
)

function close(): void {
  if (!props.closable) return
  emit('update:open', false)
  emit('close')
}

function onMaskClick(): void {
  if (props.maskClosable) close()
}

function onKeydown(event: KeyboardEvent): void {
  if (props.open && event.key === 'Escape') {
    event.preventDefault()
    close()
  }
}

watch(
  () => props.open,
  (isOpen) => {
    if (typeof document === 'undefined') return
    if (isOpen) {
      isLocked.value = true
      document.addEventListener('keydown', onKeydown)
      // focus-trap после появления panel в DOM
      requestAnimationFrame(() => activate())
    } else {
      isLocked.value = false
      document.removeEventListener('keydown', onKeydown)
      deactivate()
    }
  },
)

onBeforeUnmount(() => {
  if (typeof document !== 'undefined') {
    document.removeEventListener('keydown', onKeydown)
  }
  isLocked.value = false
  deactivate()
})

const sizeClass = computed(() => `modal__panel--${props.size}`)
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="open"
        class="modal"
        :class="sizeClass"
        @click.self="onMaskClick"
      >
        <div
          ref="panelRef"
          class="modal__panel"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="title ? titleId : undefined"
          :aria-label="title"
        >
          <header v-if="title || closable" class="modal__header">
            <h2 v-if="title" :id="titleId" class="modal__title">{{ title }}</h2>
            <button
              v-if="closable"
              type="button"
              class="modal__close"
              aria-label="Close dialog"
              @click="close"
            >
              ×
            </button>
          </header>

          <div class="modal__body">
            <slot />
          </div>

          <footer v-if="$slots.footer" class="modal__footer">
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal {
  position: fixed;
  inset: 0;
  z-index: var(--z-modal);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-4);
  background: rgba(43, 40, 33, 0.5);
  backdrop-filter: blur(2px);
}

.modal__panel {
  width: 100%;
  max-height: calc(100dvh - 2 * var(--space-4));
  overflow: auto;
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-modal);
  display: flex;
  flex-direction: column;
}

.modal__panel--sm {
  max-width: 400px;
}

.modal__panel--md {
  max-width: 560px;
}

.modal__panel--lg {
  max-width: 800px;
}

.modal__panel--fullscreen {
  max-width: none;
  width: 100%;
  height: 100%;
  max-height: none;
  border-radius: 0;
}

.modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);
  padding: var(--space-5) var(--space-6);
  border-bottom: 1px solid var(--color-divider);
}

.modal__title {
  font-family: var(--font-display);
  font-size: var(--text-lg);
}

.modal__close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: var(--space-6);
  height: var(--space-6);
  font-size: var(--text-xl);
  line-height: 1;
  color: var(--color-text-secondary);
  border-radius: var(--radius-sm);
  transition: background-color var(--dur-fast) var(--ease);
}

.modal__close:hover {
  background: var(--color-bg-inset);
  color: var(--color-text-primary);
}

.modal__body {
  padding: var(--space-6);
}

.modal__footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--space-3);
  padding: var(--space-5) var(--space-6);
  border-top: 1px solid var(--color-divider);
}

/* === Transition === */
.modal-enter-active,
.modal-leave-active {
  transition: opacity var(--dur) var(--ease);
}

.modal-enter-active .modal__panel,
.modal-leave-active .modal__panel {
  transition:
    transform var(--dur) var(--ease),
    opacity var(--dur) var(--ease);
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal__panel,
.modal-leave-to .modal__panel {
  transform: scale(0.96) translateY(8px);
  opacity: 0;
}
</style>