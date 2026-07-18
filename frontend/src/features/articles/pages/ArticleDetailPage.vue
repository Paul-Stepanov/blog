<script setup lang="ts">
/**
 * ArticleDetailPage — страница одной статьи.
 *
 * @description useArticle(slug) реактивен к route.params.slug (навигация между
 * статьями). Breadcrumbs → hero (cover, category, title, мета, excerpt) →
 * ArticleContent (DOMPurify sanitize) → теги. 404 → ErrorState + CTA на главную
 * (мягче жёсткого redirect). SEO reactive (title/description/OG image).
 */
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { Calendar, Clock, User } from 'lucide-vue-next'
import { useArticle } from '@/features/articles/composables/useArticle'
import { useSeo } from '@/composables/useSeo'
import { formatDate } from '@/utils/format'
import Breadcrumbs from '@/components/common/Breadcrumbs.vue'
import ErrorState from '@/components/common/ErrorState.vue'
import ArticleContent from '@/features/articles/components/ArticleContent.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseTag from '@/components/base/BaseTag.vue'
import type { BreadcrumbItem } from '@/types/models'

const route = useRoute()
const slug = computed(() => route.params.slug as string)
const { article, error, refresh } = useArticle(slug)

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { title: 'Главная', to: '/' },
  { title: 'Статьи', to: '/articles' },
  ...(article.value ? [{ title: article.value.title }] : []),
])

const published = computed(() =>
  formatDate(article.value?.published_at ?? null),
)

useSeo({
  title: () => article.value?.title,
  description: () => article.value?.excerpt || undefined,
  image: () => article.value?.cover_image_url ?? undefined,
})
</script>

<template>
  <article class="article-page container">
    <Breadcrumbs :items="breadcrumbs" />

    <div v-if="error && !article" class="article-page__not-found">
      <ErrorState
        message="Статья не найдена или была удалена."
        @retry="refresh"
      />
      <BaseButton as="router-link" to="/" variant="ghost">
        На главную
      </BaseButton>
    </div>

    <template v-else-if="article">
      <header class="article-page__header">
        <div
          v-if="article.category"
          class="article-page__category"
        >
          <BaseTag
            as="router-link"
            :to="`/categories/${article.category.slug}`"
            variant="soft"
          >
            {{ article.category.name }}
          </BaseTag>
        </div>

        <h1 class="article-page__title">{{ article.title }}</h1>
        <p v-if="article.excerpt" class="article-page__excerpt">
          {{ article.excerpt }}
        </p>

        <div class="article-page__meta">
          <span v-if="article.author" class="article-page__meta-item">
            <User aria-hidden="true" />
            {{ article.author.name }}
          </span>
          <span v-if="published" class="article-page__meta-item">
            <Calendar aria-hidden="true" />
            {{ published }}
          </span>
          <span
            v-if="article.reading_time_text"
            class="article-page__meta-item"
          >
            <Clock aria-hidden="true" />
            {{ article.reading_time_text }}
          </span>
        </div>
      </header>

      <img
        v-if="article.cover_image_url"
        :src="article.cover_image_url"
        :alt="article.title"
        class="article-page__cover"
      />

      <ArticleContent :html="article.content" />

      <footer v-if="article.tags.length" class="article-page__tags">
        <h2 class="article-page__tags-title">Теги</h2>
        <div class="article-page__tags-list">
          <BaseTag
            v-for="tag in article.tags"
            :key="tag.slug"
            as="router-link"
            :to="`/tags/${tag.slug}`"
            variant="outline"
          >
            {{ tag.name }}
          </BaseTag>
        </div>
      </footer>
    </template>

    <div
      v-else
      class="article-page__loading"
      role="status"
      aria-live="polite"
      aria-busy="true"
    >
      <div class="article-page__skeleton article-page__skeleton--title" />
      <div class="article-page__skeleton article-page__skeleton--line" />
      <div class="article-page__skeleton article-page__skeleton--line" />
      <div class="article-page__skeleton article-page__skeleton--line" />
    </div>
  </article>
</template>

<style scoped>
.article-page {
  padding-block: var(--space-6) var(--space-9);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.article-page__not-found {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-4);
}

.article-page__header {
  max-width: 65ch;
}

.article-page__category {
  margin-bottom: var(--space-4);
}

.article-page__title {
  font-family: var(--font-display), serif;
  font-size: var(--text-3xl);
  line-height: var(--leading-tight);
}

.article-page__excerpt {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-lg);
  line-height: var(--leading-normal);
  color: var(--color-text-secondary);
  margin-top: var(--space-4);
}

.article-page__meta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-5);
  margin-top: var(--space-5);
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

.article-page__meta-item {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
}

.article-page__meta-item :deep(svg) {
  width: 1em;
  height: 1em;
}

.article-page__cover {
  width: 100%;
  max-height: 480px;
  object-fit: cover;
  border-radius: var(--radius-lg);
}

.article-page__tags {
  max-width: 65ch;
  padding-top: var(--space-6);
  border-top: 1px solid var(--color-divider);
}

.article-page__tags-title {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-sm);
  letter-spacing: var(--tracking-caps);
  text-transform: uppercase;
  color: var(--color-text-secondary);
  margin-bottom: var(--space-3);
}

.article-page__tags-list {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.article-page__loading {
  max-width: 65ch;
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.article-page__skeleton {
  background: linear-gradient(
    90deg,
    var(--color-bg-inset) 25%,
    var(--color-bg-card) 50%,
    var(--color-bg-inset) 75%
  );
  background-size: 200% 100%;
  animation: article-page-shimmer 1.5s ease-in-out infinite;
  border-radius: var(--radius-sm);
}

.article-page__skeleton--title {
  height: var(--space-9);
  width: 60%;
}

.article-page__skeleton--line {
  height: var(--space-4);
}

@keyframes article-page-shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .article-page__skeleton {
    animation: none;
  }
}
</style>