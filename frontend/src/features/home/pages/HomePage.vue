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

const authorInitials = computed(() => {
  const name = author.value.trim()
  if (!name) return 'Б'
  return name
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('')
})

const authorRole = 'Backend-инженер'

const nameParts = computed(() => {
  const parts = author.value.trim().split(/\s+/).filter(Boolean)
  const first = parts[0] ?? author.value
  const last = parts.length > 1 ? parts.slice(1).join(' ') : first
  return { first, last }
})

const focusAreas: readonly string[] = [
  'PHP & Laravel',
  'Domain-Driven Design',
  'Чистая архитектура',
  'Тестирование',
  'Производительность',
  'PostgreSQL · Redis',
]

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
    <BentoCard :col-span="12" :col-span-sm="12" padding="lg" class="home__hero">
      <div class="hero__atmosphere" aria-hidden="true">
        <span class="hero__glow"></span>
        <span class="hero__ring"></span>
        <span class="hero__grain"></span>
      </div>

      <div class="hero__inner">
        <div class="hero__portrait">
          <div class="hero__portrait-frame">
            <img
              v-if="authorPhoto"
              :src="authorPhoto"
              :alt="author"
              class="hero__portrait-img"
              loading="eager"
              decoding="async"
              fetchpriority="high"
            />
            <span v-else class="hero__monogram" aria-hidden="true">{{ authorInitials }}</span>
          </div>
        </div>

        <div class="hero__identity">
          <p class="hero__eyebrow text-caps">
            <span class="hero__eyebrow-dot" aria-hidden="true"></span>
            {{ authorRole }}
          </p>

          <h1 class="hero__name">
            <span class="hero__name-first">{{ nameParts.first }}</span>
            <span class="hero__name-last">{{ nameParts.last }}</span>
          </h1>

          <p v-if="authorBio" class="hero__bio">{{ authorBio }}</p>

          <div class="hero__actions">
            <BaseButton
              as="router-link"
              to="/articles"
              variant="primary"
              :icon-right="markRaw(ArrowRight)"
            >
              Читать статьи
            </BaseButton>
            <BaseButton as="router-link" to="/contact" variant="ghost">
              Связаться
            </BaseButton>
          </div>

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
        </div>

        <aside class="hero__focus">
          <h2 class="hero__focus-label text-caps">О чём пишу</h2>
          <ul class="hero__focus-list">
            <li
              v-for="(area, index) in focusAreas"
              :key="area"
              class="hero__focus-item"
            >
              <span class="hero__focus-index" aria-hidden="true">
                {{ String(index + 1).padStart(2, '0') }}
              </span>
              <span class="hero__focus-text">{{ area }}</span>
            </li>
          </ul>
        </aside>
      </div>

      <div class="hero__footnote">
        <span class="hero__rule" aria-hidden="true"></span>
        <span class="hero__signature text-caps">{{ siteTitle }}</span>
      </div>
    </BentoCard>

    <BentoCard :col-span="8" :col-span-md="12" :col-span-sm="12" padding="lg" class="home__latest">
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

    <BentoCard :col-span="4" :col-span-md="12" :col-span-sm="12" padding="lg" class="home__sidebar">
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

    <BentoCard :col-span="12" :col-span-sm="12" padding="lg" class="home__contact">
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
  position: relative;
  overflow: hidden;
  container-type: inline-size;
}

.hero__atmosphere {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 0;
}

.hero__glow {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(
      circle at 88% 4%,
      rgba(255, 252, 244, 0.95),
      rgba(255, 252, 244, 0) 42%
    ),
    radial-gradient(
      circle at 100% 100%,
      rgba(74, 108, 91, 0.1),
      rgba(74, 108, 91, 0) 52%
    );
}

.hero__ring {
  position: absolute;
  top: -96px;
  right: -96px;
  width: 320px;
  height: 320px;
  border: 1px solid var(--color-accent-soft);
  border-radius: 50%;
  opacity: 0.7;
}

.hero__grain {
  position: absolute;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
  background-size: 140px 140px;
  opacity: 0.04;
  mix-blend-mode: multiply;
}

.hero__inner {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: var(--space-7);
  align-items: center;
}

@container (min-width: 600px) {
  .hero__inner {
    grid-template-columns: auto minmax(0, 1fr);
    gap: var(--space-9);
  }

  .hero__focus {
    grid-column: 1 / -1;
  }
}

@container (min-width: 940px) {
  .hero__inner {
    grid-template-columns: auto minmax(0, 1fr) minmax(248px, 300px);
    gap: var(--space-9);
    align-items: stretch;
  }

  .hero__focus {
    grid-column: auto;
  }
}

.hero__portrait {
  position: relative;
  width: clamp(220px, 24vw, 340px);
  justify-self: center;
}

.hero__portrait::before {
  content: '';
  position: absolute;
  inset: 0;
  transform: translate(14px, 14px);
  background: linear-gradient(140deg, var(--color-accent), var(--color-accent-strong));
  border-radius: var(--radius-lg);
  transition: transform var(--dur) var(--ease);
}

.hero__portrait:hover::before {
  transform: translate(18px, 18px);
}

.hero__portrait-frame {
  position: relative;
  z-index: 1;
  aspect-ratio: 4 / 5;
  padding: var(--space-3);
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  box-shadow:
    0 2px 8px rgba(43, 40, 33, 0.06),
    0 28px 56px -20px rgba(43, 40, 33, 0.34);
}

.hero__portrait-img,
.hero__monogram {
  width: 100%;
  height: 100%;
  border-radius: calc(var(--radius-lg) - var(--space-3));
  object-fit: cover;
  transition: transform var(--dur) var(--ease);
}

.hero__portrait:hover .hero__portrait-img {
  transform: scale(1.03);
}

.hero__monogram {
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg-inset);
  font-family: var(--font-display), serif;
  font-weight: var(--weight-bold);
  font-size: var(--text-3xl);
  letter-spacing: -0.02em;
  color: var(--color-accent-strong);
}

.hero__status {
  position: absolute;
  left: var(--space-3);
  bottom: var(--space-5);
  z-index: 2;
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-2) var(--space-3);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-pill);
  box-shadow: var(--shadow-card);
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-xs);
  font-weight: var(--weight-medium);
  color: var(--color-text-secondary);
}

.hero__status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--color-success);
  box-shadow: 0 0 0 3px rgba(74, 108, 91, 0.18);
}

.hero__identity {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-4);
  min-width: 0;
}

.hero__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  color: var(--color-accent-strong);
}

.hero__eyebrow-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--color-accent);
  box-shadow: 0 0 0 4px var(--color-accent-soft);
}

.hero__name {
  margin: 0;
  font-family: var(--font-display), serif;
  font-optical-sizing: auto;
  line-height: 0.92;
  letter-spacing: -0.03em;
  color: var(--color-text-primary);
}

.hero__name-first {
  display: block;
  font-weight: var(--weight-bold);
  font-size: clamp(1.75rem, 3.4vw, 2.75rem);
  opacity: 0.78;
}

.hero__name-last {
  display: block;
  font-style: italic;
  font-weight: var(--weight-bold);
  font-size: clamp(3rem, 6.8vw, 5.25rem);
}

.hero__bio {
  max-width: 40ch;
  font-family: var(--font-body), sans-serif;
  font-size: clamp(1rem, 1.25vw, 1.2rem);
  line-height: var(--leading-normal);
  color: var(--color-text-secondary);
}

.hero__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
  margin-top: var(--space-1);
}

.hero__socials {
  display: flex;
  gap: var(--space-3);
}

.hero__social {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-card);
  color: var(--color-text-secondary);
  transition:
    background-color var(--dur-fast) var(--ease),
    color var(--dur-fast) var(--ease),
    border-color var(--dur-fast) var(--ease),
    transform var(--dur-fast) var(--ease);
}

.hero__social:hover {
  background: var(--color-accent);
  border-color: var(--color-accent);
  color: var(--color-text-on-accent);
  transform: translateY(-2px);
}

.hero__social :deep(svg) {
  width: 18px;
  height: 18px;
}

.hero__focus {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  align-self: stretch;
  padding: var(--space-6);
  background: var(--color-bg-inset);
  border-radius: var(--radius-lg);
}

.hero__focus-label {
  color: var(--color-text-secondary);
}

.hero__focus-list {
  display: flex;
  flex-direction: column;
  list-style: none;
  margin: 0;
  padding: 0;
}

.hero__focus-item {
  display: flex;
  align-items: baseline;
  gap: var(--space-3);
  padding: var(--space-3) 0;
  border-bottom: 1px solid var(--color-divider);
}

.hero__focus-item:last-child {
  border-bottom: none;
}

.hero__focus-index {
  flex-shrink: 0;
  font-family: var(--font-mono), monospace;
  font-size: var(--text-xs);
  color: var(--color-accent-strong);
}

.hero__focus-text {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-sm);
  line-height: 1.4;
  color: var(--color-text-primary);
  transition: color var(--dur-fast) var(--ease);
}

.hero__focus-item:hover .hero__focus-text {
  color: var(--color-accent-strong);
}

.hero__footnote {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: var(--space-4);
  margin-top: var(--space-9);
}

.hero__rule {
  flex: 1;
  height: 1px;
  background: var(--color-divider);
}

.hero__signature {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  color: var(--color-text-secondary);
}

.hero__signature::before {
  content: '';
  width: 6px;
  height: 6px;
  background: var(--color-accent);
  transform: rotate(45deg);
}

@container (max-width: 599px) {
  .hero__inner {
    justify-items: center;
    text-align: center;
  }

  .hero__identity {
    align-items: center;
  }

  .hero__portrait {
    width: clamp(190px, 52vw, 250px);
  }

  .hero__footnote {
    justify-content: center;
  }
}

@keyframes hero-rise {
  from {
    opacity: 0;
    transform: translateY(16px);
  }

  to {
    opacity: 1;
    transform: none;
  }
}

.hero__portrait,
.hero__eyebrow,
.hero__name,
.hero__bio,
.hero__actions,
.hero__socials,
.hero__focus,
.hero__footnote {
  animation: hero-rise var(--dur-slow) var(--ease) both;
}

.hero__portrait {
  animation-delay: 0.05s;
}

.hero__eyebrow {
  animation-delay: 0.12s;
}

.hero__name {
  animation-delay: 0.18s;
}

.hero__bio {
  animation-delay: 0.26s;
}

.hero__actions {
  animation-delay: 0.34s;
}

.hero__socials {
  animation-delay: 0.42s;
}

.hero__focus {
  animation-delay: 0.5s;
}

.hero__footnote {
  animation-delay: 0.58s;
}

@media (prefers-reduced-motion: reduce) {
  .hero__portrait,
  .hero__eyebrow,
  .hero__name,
  .hero__bio,
  .hero__actions,
  .hero__socials,
  .hero__focus,
  .hero__footnote {
    animation: none;
  }
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
