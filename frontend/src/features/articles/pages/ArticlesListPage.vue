<script setup lang="ts">
/**
 * ArticlesListPage — каталог статей с поиском, фильтром по категории и пагинацией.
 *
 * @description URL — единый источник правды (?page, ?search, ?category_id) через
 * useArticles. SearchInput (debounce) → setFilter({search}). Облако категорий
 * (categoryStore) → toggle setFilter({category_id}). Pagination → setPage.
 * Empty/Loading/Error делегированы ArticleList/ErrorState.
 */
import { computed, onMounted, ref, watch } from 'vue'
import { useArticles } from '@/features/articles/composables/useArticles'
import { useCategoryStore } from '@/stores/categoryStore'
import { useSeo } from '@/composables/useSeo'
import ArticleList from '@/features/articles/components/ArticleList.vue'
import SearchInput from '@/components/common/SearchInput.vue'
import Pagination from '@/components/common/Pagination.vue'
import EmptyState from '@/components/common/EmptyState.vue'
import ErrorState from '@/components/common/ErrorState.vue'
import BaseTag from '@/components/base/BaseTag.vue'
import BaseButton from '@/components/base/BaseButton.vue'

const categoryStore = useCategoryStore()
const {
  articles,
  pagination,
  loading,
  error,
  params,
  setFilter,
  setPage,
  refresh,
} = useArticles()

const searchQuery = ref(params.value.search ?? '')
watch(
  () => params.value.search,
  (value) => {
    searchQuery.value = value ?? ''
  },
)

const hasFilters = computed(
  () => Boolean(params.value.search) || Boolean(params.value.category_id),
)
const emptyMessage = computed(() =>
  hasFilters.value
    ? 'По вашим фильтрам ничего не найдено.'
    : 'Статей пока нет.',
)

function onSearch(value: string): void {
  setFilter({ search: value || undefined })
}

function toggleCategory(id: string): void {
  setFilter({
    category_id: params.value.category_id === id ? undefined : id,
  })
}

function clearFilters(): void {
  setFilter({ search: undefined, category_id: undefined })
}

function onPage(page: number): void {
  setPage(page)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

useSeo({
  title: 'Статьи',
  description: 'Каталог опубликованных статей — поиск, фильтры по категориям.',
})

onMounted(() => {
  void categoryStore.load()
})
</script>

<template>
  <section class="articles-page container">
    <header class="articles-page__head">
      <h1 class="articles-page__title">Статьи</h1>
      <SearchInput
        v-model="searchQuery"
        placeholder="Поиск по статьям…"
        @search="onSearch"
      />
    </header>

    <div
      v-if="categoryStore.categories.length"
      class="articles-page__filters"
    >
      <BaseTag
        v-for="category in categoryStore.categories"
        :key="category.id"
        as="button"
        variant="soft"
        :selected="params.category_id === category.id"
        @click="toggleCategory(category.id)"
      >
        {{ category.name }}
      </BaseTag>
    </div>

    <ErrorState v-if="error" :message="error.message" @retry="refresh" />

    <ArticleList
      v-else
      :articles="articles"
      :loading="loading"
      :columns="3"
    >
      <template #empty>
        <EmptyState :message="emptyMessage">
          <template v-if="hasFilters" #action>
            <BaseButton variant="ghost" size="sm" @click="clearFilters">
              Сбросить фильтры
            </BaseButton>
          </template>
        </EmptyState>
      </template>
    </ArticleList>

    <Pagination
      v-if="pagination"
      :pagination="pagination"
      @change="onPage"
    />
  </section>
</template>

<style scoped>
.articles-page {
  padding-block: var(--space-8);
}

.articles-page__head {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
  margin-bottom: var(--space-6);
}

.articles-page__title {
  font-family: var(--font-display), serif;
  font-size: var(--text-3xl);
}

.articles-page__filters {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
  margin-bottom: var(--space-6);
}
</style>