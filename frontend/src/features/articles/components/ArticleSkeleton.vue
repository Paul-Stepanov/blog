<script setup lang="ts">
/**
 * ArticleSkeleton — placeholder карточки при loading.
 *
 * @description Shimmer-блоки формы ArticleCard (cover + tag + title + lines).
 * count — число placeholder'ов.
 *
 * @example
 * ```vue
 * <ArticleSkeleton :count="3" />
 * ```
 */
interface Props {
  count?: number
}

withDefaults(defineProps<Props>(), {
  count: 3,
})
</script>

<template>
  <article
    v-for="i in count"
    :key="i"
    class="skeleton-card"
    aria-hidden="true"
  >
    <div class="skeleton-card__cover" />
    <div class="skeleton-card__body">
      <div class="skeleton-card__tag" />
      <div class="skeleton-card__title" />
      <div class="skeleton-card__line" />
      <div class="skeleton-card__line skeleton-card__line--short" />
    </div>
  </article>
</template>

<style scoped>
.skeleton-card {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border-radius: var(--radius-lg);
  background: var(--color-bg-card);
  box-shadow: var(--shadow-card);
}

.skeleton-card__cover,
.skeleton-card__tag,
.skeleton-card__title,
.skeleton-card__line {
  background: linear-gradient(
    90deg,
    var(--color-bg-inset) 25%,
    var(--color-bg-card) 50%,
    var(--color-bg-inset) 75%
  );
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.5s ease-in-out infinite;
  border-radius: var(--radius-sm);
}

.skeleton-card__cover {
  aspect-ratio: 16 / 9;
  border-radius: 0;
}

.skeleton-card__body {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  padding: var(--space-5);
}

.skeleton-card__tag {
  width: 80px;
  height: var(--space-4);
}

.skeleton-card__title {
  height: var(--space-7);
}

.skeleton-card__line {
  height: var(--space-4);
}

.skeleton-card__line--short {
  width: 60%;
}

@keyframes skeleton-shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .skeleton-card__cover,
  .skeleton-card__tag,
  .skeleton-card__title,
  .skeleton-card__line {
    animation: none;
  }
}
</style>