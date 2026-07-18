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

        <div class="hero__content">
          <p class="hero__eyebrow text-caps">
            <span class="hero__eyebrow-dot" aria-hidden="true"></span>
            Авторский блог
          </p>

          <h1 class="hero__name">{{ author }}</h1>

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
        </div>
      </div>

      <div class="hero__footnote">
        <span class="hero__rule" aria-hidden="true"></span>
        <span class="hero__signature text-caps">{{ siteTitle }}</span>
      </div>
    </BentoCard>

    <!-- Свежие публикации -->
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

    <!-- Облако категорий + популярные теги -->
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

    <!-- Превью контактов / CTA -->
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
}

/* === Атмосфера: свет + органичное кольцо + зерно «бумаги» === */
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
      circle at 84% 6%,
      rgba(255, 252, 244, 0.95),
      rgba(255, 252, 244, 0) 46%
    ),
    radial-gradient(
      circle at 102% 104%,
      rgba(74, 108, 91, 0.1),
      rgba(74, 108, 91, 0) 52%
    );
}

.hero__ring {
  position: absolute;
  top: -84px;
  right: -84px;
  width: 300px;
  height: 300px;
  border: 1px solid var(--color-accent-soft);
  border-radius: 50%;
  opacity: 0.75;
}

.hero__grain {
  position: absolute;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
  background-size: 140px 140px;
  opacity: 0.04;
  mix-blend-mode: multiply;
}

/* === Композиция === */
.hero__inner {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: auto 1fr;
  gap: var(--space-9);
  align-items: center;
}

/* === Портрет в рамке-матплате === */
.hero__portrait {
  position: relative;
  width: clamp(150px, 17vw, 210px);
}

.hero__portrait::before {
  content: '';
  position: absolute;
  inset: 0;
  transform: translate(12px, 12px);
  background: linear-gradient(140deg, var(--color-accent), var(--color-accent-strong));
  border-radius: var(--radius-lg);
  transition: transform var(--dur) var(--ease);
}

.hero__portrait:hover::before {
  transform: translate(16px, 16px);
}

.hero__portrait-frame {
  position: relative;
  z-index: 1;
  aspect-ratio: 4 / 5;
  padding: var(--space-2);
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  box-shadow:
    0 2px 6px rgba(43, 40, 33, 0.06),
    0 22px 44px -18px rgba(43, 40, 33, 0.32);
}

.hero__portrait-img,
.hero__monogram {
  width: 100%;
  height: 100%;
  border-radius: calc(var(--radius-lg) - var(--space-2));
  object-fit: cover;
}

.hero__monogram {
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg-inset);
  font-family: var(--font-display), serif;
  font-weight: var(--weight-bold);
  font-size: var(--text-2xl);
  letter-spacing: -0.02em;
  color: var(--color-accent-strong);
}

/* === Текстовая композиция === */
.hero__content {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-4);
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
  font-weight: var(--weight-bold);
  font-size: clamp(2.5rem, 6vw, var(--text-3xl));
  line-height: var(--leading-tight);
  letter-spacing: -0.025em;
  font-optical-sizing: auto;
  color: var(--color-text-primary);
}

.hero__bio {
  max-width: 52ch;
  font-family: var(--font-body), sans-serif;
  font-size: clamp(1.05rem, 1.5vw, 1.3rem);
  line-height: var(--leading-normal);
  color: var(--color-text-secondary);
}

/* === Соцсети === */
.hero__socials {
  display: flex;
  gap: var(--space-3);
  margin-top: var(--space-1);
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

/* === CTA === */
.hero__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
  margin-top: var(--space-2);
}

/* === Нижняя маст-линия с подписью === */
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

/* === Entrance — ступенчатое появление === */
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
.hero__socials,
.hero__actions,
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

.hero__socials {
  animation-delay: 0.34s;
}

.hero__actions {
  animation-delay: 0.42s;
}

.hero__footnote {
  animation-delay: 0.5s;
}

@media (prefers-reduced-motion: reduce) {
  .hero__portrait,
  .hero__eyebrow,
  .hero__name,
  .hero__bio,
  .hero__socials,
  .hero__actions,
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

  .hero__inner {
    grid-template-columns: 1fr;
    justify-items: center;
    text-align: center;
  }

  .hero__content {
    align-items: center;
  }

  .hero__portrait {
    width: clamp(130px, 42vw, 180px);
  }

  .hero__footnote {
    justify-content: center;
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