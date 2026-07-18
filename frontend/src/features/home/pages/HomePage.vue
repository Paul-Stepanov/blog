<script setup lang="ts">
/**
 * HomePage — авторский лендинг (Bento, «Nature Distilled»).
 *
 * @description Соответствует первоначальной vision блога (blog-foundation/01-research:12-17):
 * Hero с информацией об авторе (аватар + имя + bio + соцсети + CTA), свежие статьи,
 * облако категорий/популярных тегов, превью контактов с CTA на /contact.
 * Данные — из settingsStore (site.author*, social.*), categoryStore, tagService.
 *
 * Иконки соцсетей обёрнуты в markRaw — defensively (см. vue-lucide-default-prop-trap):
 * lucide-vue-next 1.0 ломается на reactive-proxy иконки.
 */
import { computed, markRaw, onMounted, type Component } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowRight, Github, Linkedin, Twitter } from 'lucide-vue-next'
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

const author = computed(() => settingsStore.get('site.author') ?? 'Blog')
const authorBio = computed(() => settingsStore.get('site.author_bio') ?? '')
const authorPhoto = computed(() => settingsStore.get('site.author_photo_url') ?? '')
const siteTitle = computed(() => settingsStore.get('site.title') ?? author.value)
const siteDescription = computed(() => settingsStore.get('site.description') ?? '')

const SOCIAL_ICONS: Record<string, Component> = {
  github: markRaw(Github),
  twitter: markRaw(Twitter),
  linkedin: markRaw(Linkedin),
}
const socialLinks = computed(() =>
  Object.entries(SOCIAL_ICONS)
    .map(([key, icon]) => {
      const url = settingsStore.get(`social.${key}`)
      if (!url) return null
      return {
        key,
        url,
        icon,
        label: key.charAt(0).toUpperCase() + key.slice(1),
      }
    })
    .filter((s): s is { key: string; url: string; icon: Component; label: string } => s !== null),
)

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
  description: () => siteDescription.value || authorBio.value || undefined,
})

onMounted(() => {
  void categoryStore.load()
  void settingsStore.load()
})
</script>

<template>
  <BentoGrid as="main" class="home">
    <!-- Авторский Hero -->
    <BentoCard :col-span="12" padding="lg" class="home__hero">
      <div class="hero__inner">
        <img
          v-if="authorPhoto"
          :src="authorPhoto"
          :alt="author"
          class="hero__avatar"
          loading="lazy"
          decoding="async"
        />
        <div class="hero__body">
          <p class="hero__eyebrow text-caps">Блог</p>
          <h1 class="hero__name text-hero">{{ author }}</h1>
          <p v-if="authorBio" class="hero__bio">{{ authorBio }}</p>

          <div v-if="socialLinks.length" class="hero__socials">
            <a
              v-for="link in socialLinks"
              :key="link.key"
              :href="link.url"
              class="hero__social"
              target="_blank"
              rel="noopener noreferrer"
              :aria-label="link.label"
            >
              <component :is="link.icon" aria-hidden="true" />
            </a>
          </div>

          <div class="hero__actions">
            <BaseButton as="router-link" to="/articles" variant="primary">
              Читать статьи
            </BaseButton>
            <BaseButton as="router-link" to="/contact" variant="ghost">
              Связаться
            </BaseButton>
          </div>
        </div>
      </div>
    </BentoCard>

    <!-- Свежие публикации -->
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

    <!-- Облако категорий + популярные теги -->
    <BentoCard :col-span="4" :col-span-md="12" padding="lg" class="home__sidebar">
      <section class="home__panel">
        <h2 class="home__panel-title">Категории</h2>
        <div v-if="categoryStore.categories.length" class="home__tags">
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

    <!-- Превью контактов / CTA -->
    <BentoCard :col-span="12" padding="lg" class="home__contact">
      <div class="home__contact-inner">
        <div class="home__contact-text">
          <h2 class="home__contact-title">Есть идея, вопрос или предложение?</h2>
          <p class="home__contact-desc">
            Напишите — обсужу проект, сотрудничество или отвечу на вопрос.
          </p>
        </div>
        <BaseButton
          as="router-link"
          to="/contact"
          variant="primary"
          :icon-right="markRaw(ArrowRight)"
        >
          Связаться
        </BaseButton>
      </div>
    </BentoCard>
  </BentoGrid>
</template>

<style scoped>
.home__hero {
  display: block;
}

.hero__inner {
  display: flex;
  gap: var(--space-6);
  align-items: center;
}

.hero__avatar {
  flex-shrink: 0;
  width: 140px;
  height: 140px;
  border-radius: 50%;
  object-fit: cover;
  box-shadow: var(--shadow-card);
}

.hero__body {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  align-items: flex-start;
}

.hero__eyebrow {
  color: var(--color-accent-strong);
}

.hero__bio {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-lg);
  line-height: var(--leading-normal);
  color: var(--color-text-secondary);
  max-width: 65ch;
}

.hero__socials {
  display: flex;
  gap: var(--space-2);
  margin-top: var(--space-1);
}

.hero__social {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: var(--space-8);
  height: var(--space-8);
  border-radius: var(--radius-pill);
  background: var(--color-bg-inset);
  color: var(--color-text-secondary);
  transition:
    background-color var(--dur-fast) var(--ease),
    color var(--dur-fast) var(--ease);
}

.hero__social:hover {
  background: var(--color-accent-soft);
  color: var(--color-accent-strong);
}

.hero__social :deep(svg) {
  width: 1.1em;
  height: 1.1em;
}

.hero__actions {
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

  .hero__inner {
    flex-direction: column;
    text-align: center;
  }

  .hero__body {
    align-items: center;
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

.home__contact-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-5);
  flex-wrap: wrap;
}

.home__contact-title {
  font-family: var(--font-display), serif;
  font-size: var(--text-xl);
  margin-bottom: var(--space-2);
}

.home__contact-desc {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-base);
  color: var(--color-text-secondary);
  max-width: 60ch;
}
</style>