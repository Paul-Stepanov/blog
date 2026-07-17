<script setup lang="ts">
/**
 * BaseLoader — индикатор загрузки.
 *
 * @description Три варианта: spinner (inline/кнопки), dots (загрузка блоков),
 * skeleton (placeholder списков). spinner/dots — role=status с aria-label.
 *
 * @example
 * ```vue
 * <BaseLoader variant="spinner" size="sm" />
 * <BaseLoader variant="skeleton" />
 * ```
 */

interface Props {
  size?: 'sm' | 'md' | 'lg'
  variant?: 'spinner' | 'dots' | 'skeleton'
  label?: string
}

withDefaults(defineProps<Props>(), {
  size: 'md',
  variant: 'spinner',
  label: 'Loading',
})
</script>

<template>
  <span
    v-if="variant === 'skeleton'"
    class="loader loader--skeleton"
    :class="[`loader--skeleton-${size}`]"
    aria-hidden="true"
  />
  <span
    v-else
    class="loader"
    :class="[`loader--${variant}`, `loader--${size}`]"
    role="status"
    :aria-label="label"
  >
    <span v-if="variant === 'spinner'" class="loader__spinner" />
    <template v-else>
      <span class="loader__dot" />
      <span class="loader__dot" />
      <span class="loader__dot" />
    </template>
  </span>
</template>

<style scoped>
.loader {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-1);
}

/* === spinner === */
.loader__spinner {
  width: 1em;
  height: 1em;
  border: 2px solid var(--color-accent-soft);
  border-top-color: var(--color-accent);
  border-radius: 50%;
  animation: loader-spin 0.7s linear infinite;
}

@keyframes loader-spin {
  to {
    transform: rotate(360deg);
  }
}

/* === dots === */
.loader__dot {
  width: 0.4em;
  height: 0.4em;
  border-radius: 50%;
  background: var(--color-accent);
  animation: loader-pulse 1.2s ease-in-out infinite;
}

.loader__dot:nth-child(2) {
  animation-delay: 0.2s;
}

.loader__dot:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes loader-pulse {
  0%,
  80%,
  100% {
    opacity: 0.3;
    transform: scale(0.8);
  }
  40% {
    opacity: 1;
    transform: scale(1);
  }
}

/* === sizes === */
.loader--sm {
  font-size: var(--text-sm);
}

.loader--md {
  font-size: var(--text-base);
}

.loader--lg {
  font-size: var(--text-xl);
}

/* === skeleton === */
.loader--skeleton {
  display: block;
  width: 100%;
  background: linear-gradient(
    90deg,
    var(--color-bg-inset) 25%,
    var(--color-bg-card) 50%,
    var(--color-bg-inset) 75%
  );
  background-size: 200% 100%;
  animation: loader-shimmer 1.5s ease-in-out infinite;
  border-radius: var(--radius-sm);
}

.loader--skeleton-sm {
  height: var(--space-5);
}

.loader--skeleton-md {
  height: var(--space-7);
}

.loader--skeleton-lg {
  height: var(--space-9);
}

@keyframes loader-shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .loader__spinner,
  .loader__dot,
  .loader--skeleton {
    animation: none;
  }
}
</style>