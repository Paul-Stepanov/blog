<script setup lang="ts">
/**
 * BaseTag — тег / категория.
 *
 * @description Span | button | router-link. uppercase Inter Medium (канон тегов).
 * variant: solid / soft / outline. selected — для фильтров.
 *
 * @example
 * ```vue
 * <BaseTag as="router-link" to="/tags/vue" variant="soft">Vue</BaseTag>
 * ```
 */

import { computed } from 'vue'

type Variant = 'solid' | 'soft' | 'outline'

interface Props {
  as?: 'span' | 'button' | 'router-link'
  to?: string
  size?: 'sm' | 'md'
  variant?: Variant
  selected?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  as: 'span',
  size: 'sm',
  variant: 'soft',
  selected: false,
})

const emit = defineEmits<{
  (e: 'click', event: MouseEvent): void
}>()

const tag = computed(() => {
  if (props.as === 'router-link') return 'router-link'
  return props.as
})

const isInteractive = computed(() => props.as !== 'span')

function onClick(event: MouseEvent): void {
  if (!isInteractive.value) return
  emit('click', event)
}
</script>

<template>
  <component
    :is="tag"
    class="tag"
    :class="[`tag--${variant}`, `tag--${size}`, { 'tag--selected': selected }]"
    :to="as === 'router-link' ? to : undefined"
    :type="as === 'button' ? 'button' : undefined"
    :aria-pressed="as === 'button' ? selected : undefined"
    @click="onClick"
  >
    <slot />
  </component>
</template>

<style scoped>
.tag {
  display: inline-flex;
  align-items: center;
  font-family: var(--font-body), sans-serif;
  font-weight: var(--weight-medium);
  letter-spacing: var(--tracking-caps);
  text-transform: uppercase;
  border-radius: var(--radius-pill);
  text-decoration: none;
  white-space: nowrap;
  transition:
    background-color var(--dur-fast) var(--ease),
    color var(--dur-fast) var(--ease);
}

.tag--sm {
  font-size: var(--text-xs);
  padding: var(--space-1) var(--space-3);
}

.tag--md {
  font-size: var(--text-sm);
  padding: var(--space-2) var(--space-4);
}

/* === variants === */
.tag--solid {
  background: var(--color-accent);
  color: var(--color-text-on-accent);
}

.tag--soft {
  background: var(--color-accent-soft);
  color: var(--color-accent-strong);
}

.tag--outline {
  background: transparent;
  color: var(--color-text-secondary);
  border: 1px solid var(--color-border-strong);
}

/* === interactive hover === */
a.tag:hover,
button.tag:hover {
  filter: brightness(0.96);
}

.tag--selected {
  outline: 2px solid var(--color-accent);
  outline-offset: 1px;
}
</style>