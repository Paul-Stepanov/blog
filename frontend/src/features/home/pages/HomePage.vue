<script setup lang="ts">
/**
 * HomePage — главная страница (Bento).
 *
 * @description Hero из settingsStore (site.title/description), свежие 3 статьи
 * напрямую через useAsyncData (без useArticles — там per_page=9 + URL-sync),
 * облако категорий (categoryStore) и популярных тегов (tagService.popular).
 * stores грузятся в AppLayout.onMounted (H4); здесь — idempotent load на случай
 * прямой навигации.
 */
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAsyncData } from '@/composables/useAsyncData'
import { articleService } from '@/services/articleService'
import { tagService } from '@/services/tagService'
import { useCategoryStore } from '@/stores/categoryStore'
import { useSettingsStore } from '@/stores/settingsStore'
import { useSeo } from '@/composables/useSeo'
import BentoGrid from '@/components/bento/BentoGrid.vue'
import BentoCard from '@/components/bento/BentoCard.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseTag from '@/components/base/BaseTag.vue'
import ArticleCard from '@/features/articles/components/ArticleCard.vue'
import ArticleSkeleton from '@/features/articles/components/ArticleSkeleton.vue'
import type { ArticleListItem } from '@/types/api'

const categoryStore = useCategoryStore()
const settingsStore = useSettingsStore()

const siteTitle = computed(() => settingsStore.get('site.title') ?? 'Blog')
const siteDescription = computed(() => settingsStore.get('site.description') ?? '')

const { data: latestData, loading: latestLoading } = useAsyncData(
  () => articleService.list({ page: 1, per_page: 3 }),
  { immediate: true },
)
const latestArticles = computed<ArticleListItem[]>(
  () => latestData.value?.data ?? [],
)

const { data: popularTags } = useAsyncData(() => tagService.popular(8), {
  immediate: true,
})

useSeo({
  title: () => siteTitle.value,
  description: () => siteDescription.value || undefined,
})

onMounted(() => {
  void categoryStore.load()
  void settingsStore.load()
})
</script>

<template>
  <BentoGrid as="main" class="home">
    <BentoCard :col-span="12" padding="lg" class="home__hero">
      <p class="home__eyebrow text-caps">Публикации</p>
      <h1 class="home__title text-hero">{{ siteTitle }}</h1>
      <p v-if="siteDescription" class="home__lead">{{ siteDescription }}</p>
      <div class="home__hero-actions">
        <BaseButton as="router-link" to="/articles" variant="primary">
          Читать статьи
        </BaseButton>
        <BaseButton as="router-link" to="/contact" variant="ghost">
          Связаться
        </BaseButton>
      </div>
    </BentoCard>

    <BentoCard :col-span="8" :col-span-md="12" padding="lg" class="home__latest">
      <header class="home__section-head">
        <h2 class="home__section-title">Свежие публикации</h2>
        <RouterLink to="/articles" class="home__section-link">
          Все статьи →
        </RouterLink>
      </header>

      <div class="home__latest-grid">
        <ArticleSkeleton v-if="latestLoading" :count="3" />
        <template v-else-if="latestArticles.length">
          <ArticleCard
            v-for="article in latestArticles"
            :key="article.id"
            :article="article"
          />
        </template>
        <p v-else class="home__empty">Пока нет опубликованных статей.</p>
      </div>
    </BentoCard>

    <BentoCard :col-span="4" :col-span-md="12" padding="lg" class="home__sidebar">
      <section class="home__panel">
        <h2 class="home__panel-title">Категории</h2>
        <div
          v-if="categoryStore.categories.length"
          class="home__tags"
        >
          <BaseTag
            v-for="category in categoryStore.categories"
            :key="category.id"
            as="router-link"
            :to="`/categories/${category.slug}`"
            variant="soft"
          >
            {{ category.name }}
          </BaseTag>
        </div>
        <p v-else-if="!categoryStore.loading" class="home__empty">
          Категорий пока нет.
        </p>
      </section>

      <section v-if="popularTags?.length" class="home__panel">
        <h2 class="home__panel-title">Популярные теги</h2>
        <div class="home__tags">
          <BaseTag
            v-for="tag in popularTags"
            :key="tag.id"
            as="router-link"
            :to="`/tags/${tag.slug}`"
            variant="outline"
          >
            {{ tag.name }}
          </BaseTag>
        </div>
      </section>
    </BentoCard>
  </BentoGrid>
</template>

<style scoped>
.home__hero {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
  align-items: flex-start;
}

.home__eyebrow {
  color: var(--color-accent-strong);
}

.home__lead {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-lg);
  line-height: var(--leading-normal);
  color: var(--color-text-secondary);
  max-width: 60ch;
}

.home__hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
  margin-top: var(--space-2);
}

.home__section-head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: var(--space-3);
  margin-bottom: var(--space-5);
}

.home__section-title {
  font-family: var(--font-display), serif;
  font-size: var(--text-xl);
}

.home__section-link {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-sm);
  color: var(--color-accent-strong);
}

.home__latest-grid {
  display: grid;
  gap: var(--space-5);
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

@media (max-width: 1023px) {
  .home__latest-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 767px) {
  .home__latest-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}

.home__sidebar {
  display: flex;
  flex-direction: column;
  gap: var(--space-7);
}

.home__panel {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.home__panel-title {
  font-family: var(--font-display), serif;
  font-size: var(--text-base);
  letter-spacing: var(--tracking-caps);
  text-transform: uppercase;
  color: var(--color-text-secondary);
}

.home__tags {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.home__empty {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}
</style>