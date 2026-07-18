<script setup lang="ts">
/**
 * ErrorState — состояние ошибки с кнопкой повтора.
 *
 * @description role=alert, иконка, сообщение, кнопка Retry (emit).
 *
 * @example
 * ```vue
 * <ErrorState :message="error.message" @retry="refresh" />
 * ```
 */
import { AlertCircle } from 'lucide-vue-next'
import BaseButton from '@/components/base/BaseButton.vue'

interface Props {
  message: string
}

defineProps<Props>()
defineEmits<{
  (e: 'retry'): void
}>()
</script>

<template>
  <div class="error-state" role="alert">
    <AlertCircle class="error-state__icon" aria-hidden="true" />
    <p class="error-state__message">{{ message }}</p>
    <BaseButton variant="secondary" size="sm" @click="$emit('retry')">
      Повторить
    </BaseButton>
  </div>
</template>

<style scoped>
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-9) var(--space-5);
  text-align: center;
}

.error-state__icon {
  width: var(--space-9);
  height: var(--space-9);
  color: var(--color-error);
}

.error-state__message {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-base);
  color: var(--color-text-secondary);
  max-width: 40ch;
  margin: 0;
}
</style>