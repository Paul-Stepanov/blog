<script setup lang="ts">
/**
 * TagPage — упрощённая страница тега.
 *
 * @description GET /api/articles НЕ фильтрует по тегу → полноценная страница
 * невозможна. Показываем: тег по slug (header), облако популярных тегов, CTA на
 * /articles и заметку об ограничении. articles_count — из /tags/popular (если
 * тег там есть), т.к. /tags/{slug} не отдаёт count. 404 → ErrorState + CTA.
 */
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { tagService } from '@/services/tagService'
import { useAsyncData } from '@/composables/useAsyncData'
import { useSeo } from '@/composables/useSeo'
import Breadcrumbs from '@/components/common/Breadcrumbs.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import ErrorState from '@/components/common/ErrorState.vue'
import BaseTag from '@/components/base/BaseTag.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import type { PopularTag, Tag } from '@/types/api'
import type { BreadcrumbItem } from '@/types/models'

const route = useRoute()
const slug = computed(() => route.params.slug as string)

const {
  data: tag,
  error: tagError,
  refresh: refreshTag,
} = useAsyncData<Tag, string>(() => tagService.getBySlug(slug.value), {
  immediate: true,
})
watch(slug, () => void refreshTag())

const { data: popularTags } = useAsyncData<PopularTag[], void>(
  () => tagService.popular(10),
  { immediate: true },
)

// /tags/{slug} не отдаёт count — берём из popular, если тег там есть.
const articlesCount = computed(
  () => popularTags.value?.find((t) => t.id === tag.value?.id)?.articles_count ?? null,
)

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { title: 'Главная', to: '/' },
  ...(tag.value
    ? [{ title: `#${tag.value.name}` }]
    : [{ title: 'Тег' }]),
])

useSeo({
  title: () => (tag.value ? `#${tag.value.name}` : undefined),
  description: () =>
    tag.value ? `Публикации по тегу ${tag.value.name}.` : undefined,
})
</script>

<template>
  <section class="tag-page container">
    <Breadcrumbs :items="breadcrumbs" />

    <div v-if="tagError && !tag" class="tag-page__not-found">
      <ErrorState message="Тег не найден." @retry="refreshTag" />
      <BaseButton as="router-link" to="/articles" variant="ghost">
        Все статьи
      </BaseButton>
    </div>

    <template v-else-if="tag">
      <header class="tag-page__header">
        <p class="tag-page__eyebrow text-caps">Тег</p>
        <h1 class="tag-page__title">#{{ tag.name }}</h1>
        <p v-if="articlesCount !== null" class="tag-page__count">
          {{ articlesCount }}
          {{ articlesCount === 1 ? 'статья' : 'статей' }}
        </p>
      </header>

      <div class="tag-page__notice" role="note">
        Список статей с этим тегом скоро появится — сейчас backend не отдаёт
        статьи по тегу. А пока — посмотрите все публикации или другие теги.
      </div>

      <section v-if="popularTags?.length" class="tag-page__panel">
        <h2 class="tag-page__panel-title">Популярные теги</h2>
        <div class="tag-page__tags">
          <BaseTag
            v-for="t in popularTags"
            :key="t.id"
            as="router-link"
            :to="`/tags/${t.slug}`"
            :variant="t.id === tag.id ? 'soft' : 'outline'"
          >
            {{ t.name }}
          </BaseTag>
        </div>
      </section>

      <div v-else class="tag-page__panel">
        <EmptyState message="Популярных тегов пока нет." />
      </div>

      <div class="tag-page__cta">
        <BaseButton as="router-link" to="/articles" variant="primary">
          Смотреть все статьи
        </BaseButton>
      </div>
    </template>

    <div
      v-else
      class="tag-page__loading"
      role="status"
      aria-live="polite"
      aria-busy="true"
    >
      <div class="tag-page__skeleton tag-page__skeleton--title" />
    </div>
  </section>
</template>

<style scoped>
.tag-page {
  padding-block: var(--space-6) var(--space-9);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
  max-width: 65ch;
}

.tag-page__not-found {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-4);
}

.tag-page__header {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.tag-page__eyebrow {
  color: var(--color-accent-strong);
}

.tag-page__title {
  font-family: var(--font-display), serif;
  font-size: var(--text-3xl);
}

.tag-page__count {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

.tag-page__notice {
  padding: var(--space-4) var(--space-5);
  background: var(--color-bg-card);
  border-left: 3px solid var(--color-accent);
  border-radius: var(--radius-sm);
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-base);
  line-height: var(--leading-normal);
  color: var(--color-text-secondary);
}

.tag-page__panel {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.tag-page__panel-title {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-sm);
  letter-spacing: var(--tracking-caps);
  text-transform: uppercase;
  color: var(--color-text-secondary);
}

.tag-page__tags {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.tag-page__cta {
  margin-top: var(--space-2);
}

.tag-page__loading {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.tag-page__skeleton {
  background: linear-gradient(
    90deg,
    var(--color-bg-inset) 25%,
    var(--color-bg-card) 50%,
    var(--color-bg-inset) 75%
  );
  background-size: 200% 100%;
  animation: tag-page-shimmer 1.5s ease-in-out infinite;
  border-radius: var(--radius-sm);
}

.tag-page__skeleton--title {
  height: var(--space-9);
  width: 40%;
}

@keyframes tag-page-shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .tag-page__skeleton {
    animation: none;
  }
}
</style>