<script setup lang="ts">
/**
 * CategoryPage — статьи одной категории.
 *
 * @description categoryService.getBySlug(slug) → header (name/description).
 * Затем articleService.list({category_id, page}) через useAsyncData с локальной
 * пагинацией (?page не обязателен — slug уже определяет категорию, URL чистый).
 * Breadcrumbs: Главная / <категория>. 404 → ErrorState + CTA.
 */
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { categoryService } from '@/services/categoryService'
import { articleService } from '@/services/articleService'
import { useAsyncData } from '@/composables/useAsyncData'
import { useSeo } from '@/composables/useSeo'
import Breadcrumbs from '@/components/common/Breadcrumbs.vue'
import Pagination from '@/components/common/Pagination.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import ErrorState from '@/components/common/ErrorState.vue'
import ArticleList from '@/features/articles/components/ArticleList.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import type {
  ArticleListItem,
  Category,
  PaginatedResponse,
} from '@/types/api'
import type { BreadcrumbItem } from '@/types/models'

const route = useRoute()
const slug = computed(() => route.params.slug as string)

const {
  data: category,
  error: categoryError,
  refresh: refreshCategory,
} = useAsyncData<Category, string>(
  () => categoryService.getBySlug(slug.value),
  { immediate: true },
)
watch(slug, () => {
  page.value = 1
  void refreshCategory()
})

const page = ref(1)
const {
  data: articlesData,
  loading: articlesLoading,
  error: articlesError,
  execute: executeArticles,
  refresh: refreshArticles,
} = useAsyncData<PaginatedResponse<ArticleListItem>, void>(
  () => articleService.list({
    category_id: category.value?.id ?? '',
    page: page.value,
    per_page: 9,
  }),
  { immediate: false },
)

// Статьи грузятся после резолва категории (category_id нужен).
watch(
  () => category.value?.id,
  (id) => {
    if (id) void executeArticles()
  },
  { immediate: true },
)
watch(page, () => {
  if (category.value) {
    void executeArticles()
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
})

const articles = computed<ArticleListItem[]>(
  () => articlesData.value?.data ?? [],
)
const pagination = computed(() => articlesData.value?.meta.pagination ?? null)
const listLoading = computed(() => articlesLoading.value || !articlesData.value)

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
  { title: 'Главная', to: '/' },
  ...(category.value
    ? [{ title: category.value.name }]
    : [{ title: 'Категория' }]),
])

function onPage(n: number): void {
  page.value = n
}

useSeo({
  title: () => category.value?.name,
  description: () => category.value?.description ?? undefined,
})
</script>

<template>
  <section class="category-page container">
    <Breadcrumbs :items="breadcrumbs" />

    <div v-if="categoryError && !category" class="category-page__not-found">
      <ErrorState
        message="Категория не найдена."
        @retry="refreshCategory"
      />
      <BaseButton as="router-link" to="/articles" variant="ghost">
        Все статьи
      </BaseButton>
    </div>

    <template v-else-if="category">
      <header class="category-page__header">
        <p class="category-page__eyebrow text-caps">Категория</p>
        <h1 class="category-page__title">{{ category.name }}</h1>
        <p v-if="category.description" class="category-page__desc">
          {{ category.description }}
        </p>
      </header>

      <ErrorState
        v-if="articlesError"
        :message="articlesError.message"
        @retry="refreshArticles"
      />
      <ArticleList
        v-else
        :articles="articles"
        :loading="listLoading"
        :columns="3"
      >
        <template #empty>
          <EmptyState message="В этой категории пока нет статей." />
        </template>
      </ArticleList>

      <Pagination
        v-if="pagination"
        :pagination="pagination"
        @change="onPage"
      />
    </template>

    <div
      v-else
      class="category-page__loading"
      role="status"
      aria-live="polite"
      aria-busy="true"
    >
      <div class="category-page__skeleton category-page__skeleton--title" />
    </div>
  </section>
</template>

<style scoped>
.category-page {
  padding-block: var(--space-6) var(--space-9);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.category-page__not-found {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-4);
}

.category-page__header {
  max-width: 65ch;
}

.category-page__eyebrow {
  color: var(--color-accent-strong);
  margin-bottom: var(--space-2);
}

.category-page__title {
  font-family: var(--font-display), serif;
  font-size: var(--text-3xl);
}

.category-page__desc {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-lg);
  line-height: var(--leading-normal);
  color: var(--color-text-secondary);
  margin-top: var(--space-3);
}

.category-page__loading {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.category-page__skeleton {
  background: linear-gradient(
    90deg,
    var(--color-bg-inset) 25%,
    var(--color-bg-card) 50%,
    var(--color-bg-inset) 75%
  );
  background-size: 200% 100%;
  animation: category-page-shimmer 1.5s ease-in-out infinite;
  border-radius: var(--radius-sm);
}

.category-page__skeleton--title {
  height: var(--space-9);
  width: 50%;
}

@keyframes category-page-shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .category-page__skeleton {
    animation: none;
  }
}
</style>