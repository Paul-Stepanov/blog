<script setup lang="ts">
/**
 * ArticleCard — карточка статьи в списке/сетке.
 *
 * @description Обложка, категория (BaseTag), заголовок, excerpt, мета
 * (reading_time, дата). Кликабельна → /articles/:slug.
 *
 * @example
 * ```vue
 * <ArticleCard :article="item" />
 * ```
 */
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Clock } from 'lucide-vue-next'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseTag from '@/components/base/BaseTag.vue'
import { formatDate } from '@/utils/format'
import type { ArticleListItem } from '@/types/api'

interface Props {
  article: ArticleListItem
  /** Показывать excerpt (на HomePage — да, в compact — нет) */
  showExcerpt?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showExcerpt: true,
})

const articleUrl = computed(() => `/articles/${props.article.slug}`)
const categoryUrl = computed(() => {
  const slug = props.article.category?.slug
  return slug ? `/categories/${slug}` : null
})
const published = computed(() => formatDate(props.article.published_at))
</script>

<template>
  <BaseCard as="article" padding="none" interactive class="article-card">
    <RouterLink
      v-if="article.cover_image_url"
      :to="articleUrl"
      class="article-card__cover"
    >
      <img
        :src="article.cover_image_url"
        :alt="article.title"
        loading="lazy"
        decoding="async"
        class="article-card__img"
      />
    </RouterLink>

    <div class="article-card__body">
      <div v-if="article.category" class="article-card__tags">
        <BaseTag
          v-if="categoryUrl"
          as="router-link"
          :to="categoryUrl"
          variant="soft"
          size="sm"
        >
          {{ article.category.name }}
        </BaseTag>
      </div>

      <h3 class="article-card__title">
        <RouterLink :to="articleUrl">{{ article.title }}</RouterLink>
      </h3>

      <p v-if="showExcerpt" class="article-card__excerpt">{{ article.excerpt }}</p>

      <div class="article-card__meta">
        <span v-if="published">{{ published }}</span>
        <span v-if="article.reading_time" class="article-card__reading">
          <Clock aria-hidden="true" />
          {{ article.reading_time }} мин
        </span>
      </div>
    </div>
  </BaseCard>
</template>

<style scoped>
.article-card {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
}

.article-card__cover {
  display: block;
  aspect-ratio: 16 / 9;
  overflow: hidden;
  background: var(--color-bg-inset);
}

.article-card__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform var(--dur) var(--ease);
}

.article-card:hover .article-card__img {
  transform: scale(1.03);
}

.article-card__body {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  padding: var(--space-5);
  flex: 1;
}

.article-card__tags {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.article-card__title {
  font-family: var(--font-display), serif;
  font-size: var(--text-lg);
  line-height: var(--leading-tight);
  font-weight: var(--weight-bold);
  margin: 0;
}

.article-card__title a {
  color: var(--color-text-primary);
  text-decoration: none;
  transition: color var(--dur-fast) var(--ease);
}

.article-card__title a:hover {
  color: var(--color-accent-strong);
}

.article-card__excerpt {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-sm);
  line-height: var(--leading-normal);
  color: var(--color-text-secondary);
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.article-card__meta {
  display: flex;
  align-items: center;
  gap: var(--space-4);
  margin-top: auto;
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-xs);
  color: var(--color-text-secondary);
}

.article-card__reading {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
}

.article-card__reading :deep(svg) {
  width: 1em;
  height: 1em;
}
</style>