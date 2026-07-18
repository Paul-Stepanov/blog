<script setup lang="ts">
/**
 * ArticleContent — безопасный рендер HTML-контента статьи.
 *
 * @description DOMPurify санитизирует HTML (удаляет <script>, event-handlers,
 * javascript: URLs) перед v-html. Long-form типографика: max-width 65ch,
 * Fraunces для заголовков, Geist Sans body, Geist Mono для code.
 *
 * @example
 * ```vue
 * <ArticleContent :html="article.content" />
 * ```
 */
import { computed } from 'vue'
import DOMPurify from 'dompurify'

interface Props {
  html: string
}

const props = defineProps<Props>()

const sanitized = computed(() =>
  DOMPurify.sanitize(props.html, { USE_PROFILES: { html: true } }),
)
</script>

<template>
  <div class="article-content" v-html="sanitized" />
</template>

<style scoped>
.article-content {
  max-width: 65ch;
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-base);
  line-height: var(--leading-normal);
  color: var(--color-text-primary);
}

.article-content :deep(h2) {
  font-family: var(--font-display), serif;
  font-size: var(--text-xl);
  line-height: var(--leading-tight);
  margin: var(--space-7) 0 var(--space-3);
}

.article-content :deep(h3) {
  font-family: var(--font-display), serif;
  font-size: var(--text-lg);
  line-height: var(--leading-tight);
  margin: var(--space-6) 0 var(--space-2);
}

.article-content :deep(p) {
  margin: 0 0 var(--space-4);
}

.article-content :deep(a) {
  color: var(--color-accent-strong);
  text-decoration: underline;
  text-underline-offset: 2px;
}

.article-content :deep(ul),
.article-content :deep(ol) {
  margin: 0 0 var(--space-4);
  padding-left: var(--space-6);
}

.article-content :deep(li) {
  margin-bottom: var(--space-2);
}

.article-content :deep(blockquote) {
  margin: var(--space-5) 0;
  padding: var(--space-3) var(--space-5);
  border-left: 3px solid var(--color-accent);
  background: var(--color-bg-card);
  border-radius: var(--radius-sm);
  font-style: italic;
  color: var(--color-text-secondary);
}

.article-content :deep(img) {
  max-width: 100%;
  height: auto;
  border-radius: var(--radius-md);
  margin: var(--space-5) 0;
}

.article-content :deep(pre) {
  padding: var(--space-4);
  background: var(--color-bg-inset);
  border-radius: var(--radius-md);
  overflow-x: auto;
  font-family: var(--font-mono), monospace;
  font-size: var(--text-sm);
}

.article-content :deep(code) {
  font-family: var(--font-mono), monospace;
  font-size: 0.9em;
}

.article-content :deep(pre code) {
  background: none;
  padding: 0;
}

.article-content :deep(:not(pre) > code) {
  padding: 0.1em 0.3em;
  background: var(--color-bg-inset);
  border-radius: var(--radius-sm);
}
</style>