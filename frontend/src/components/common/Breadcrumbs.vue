<script setup lang="ts">
/**
 * Breadcrumbs — хлебные крошки.
 *
 * @description nav + ol; последний элемент — aria-current="page".
 * RouterLink для `to`, `<a>` для `href`.
 *
 * @example
 * ```vue
 * <Breadcrumbs :items="[{ title: 'Главная', to: '/' }, { title: 'Статьи' }]" />
 * ```
 */
import { RouterLink } from 'vue-router'
import { ChevronRight } from 'lucide-vue-next'
import type { BreadcrumbItem } from '@/types/models'

interface Props {
  items: BreadcrumbItem[]
}

defineProps<Props>()
</script>

<template>
  <nav v-if="items.length" class="breadcrumbs" aria-label="Хлебные крошки">
    <ol class="breadcrumbs__list">
      <li v-for="(item, i) in items" :key="i" class="breadcrumbs__item">
        <RouterLink
          v-if="item.to && i < items.length - 1"
          :to="item.to"
          class="breadcrumbs__link"
        >
          {{ item.title }}
        </RouterLink>
        <a
          v-else-if="item.href && i < items.length - 1"
          :href="item.href"
          class="breadcrumbs__link"
        >
          {{ item.title }}
        </a>
        <span
          v-else
          class="breadcrumbs__current"
          :aria-current="i === items.length - 1 ? 'page' : undefined"
        >
          {{ item.title }}
        </span>
        <ChevronRight
          v-if="i < items.length - 1"
          class="breadcrumbs__sep"
          aria-hidden="true"
        />
      </li>
    </ol>
  </nav>
</template>

<style scoped>
.breadcrumbs {
  margin-bottom: var(--space-5);
}

.breadcrumbs__list {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0;
  list-style: none;
  margin: 0;
  padding: 0;
  font-family: var(--font-body);
  font-size: var(--text-sm);
}

.breadcrumbs__item {
  display: inline-flex;
  align-items: center;
}

.breadcrumbs__link {
  color: var(--color-text-secondary);
  text-decoration: none;
  transition: color var(--dur-fast) var(--ease);
}

.breadcrumbs__link:hover {
  color: var(--color-accent-strong);
}

.breadcrumbs__current {
  color: var(--color-text-primary);
  font-weight: var(--weight-medium);
}

.breadcrumbs__sep {
  width: 1em;
  height: 1em;
  color: var(--color-text-secondary);
  opacity: 0.6;
  margin: 0 var(--space-1);
}
</style>