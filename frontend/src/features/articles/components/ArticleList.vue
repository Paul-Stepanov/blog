<script setup lang="ts">
/**
 * ArticleList — сетка карточек статей с loading/empty состояниями.
 *
 * @description CSS Grid (container queries: 3→2→1). loading → ArticleSkeleton.
 * Empty → slot empty (default EmptyState). Staggered reveal.
 *
 * @example
 * ```vue
 * <ArticleList :articles="items" :loading="loading" :columns="3" />
 * ```
 */
import ArticleCard from './ArticleCard.vue'
import ArticleSkeleton from './ArticleSkeleton.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import type { ArticleListItem } from '@/types/api'

interface Props {
  articles: ArticleListItem[]
  loading?: boolean
  /** Колонок на desktop */
  columns?: 2 | 3
}

withDefaults(defineProps<Props>(), {
  loading: false,
  columns: 3,
})
</script>

<template>
  <div v-if="loading" class="article-list" :class="[`article-list--${columns}`]">
    <ArticleSkeleton :count="columns * 2" />
  </div>

  <div v-else-if="articles.length === 0" class="article-list__empty">
    <slot name="empty">
      <EmptyState message="Статей пока нет." />
    </slot>
  </div>

  <div v-else class="article-list" :class="[`article-list--${columns}`]">
    <ArticleCard
      v-for="(article, i) in articles"
      :key="article.id"
      :article="article"
      class="article-list__item"
      :style="{ '--i': i }"
    />
  </div>
</template>

<style scoped>
.article-list {
  display: grid;
  gap: var(--space-6);
  container-type: inline-size;
}

.article-list--3 {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.article-list--2 {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

/* Tablet → 2 колонки */
@container (max-width: 1023px) {
  .article-list--3 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

/* Mobile → 1 колонка */
@container (max-width: 767px) {
  .article-list--3,
  .article-list--2 {
    grid-template-columns: minmax(0, 1fr);
  }
}

.article-list__item {
  animation: article-reveal var(--dur) var(--ease) both;
  animation-delay: calc(min(var(--i, 0), 8) * 0.06s);
}

@keyframes article-reveal {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .article-list__item {
    animation: none;
  }
}
</style>