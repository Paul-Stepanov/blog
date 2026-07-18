<script setup lang="ts">
/**
 * AppFooter — подвал.
 *
 * @description Копирайт + links placeholder. Наполняется из settings в фазе 8.
 */
</script>

<template>
  <footer class="footer">
    <div class="footer__inner container">
      <p class="footer__copy">© {{ new Date().getFullYear() }} Blog</p>
      <nav class="footer__links" aria-label="Footer">
        <RouterLink to="/" class="footer__link">Home</RouterLink>
        <RouterLink to="/articles" class="footer__link">Articles</RouterLink>
      </nav>
    </div>
  </footer>
</template>

<style scoped>
.footer {
  border-top: 1px solid var(--color-divider);
  background: var(--color-bg-card);
}

.footer__inner {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);
  padding-block: var(--space-6);
}

.footer__copy {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}

.footer__links {
  display: flex;
  gap: var(--space-4);
}

.footer__link {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
  text-decoration: none;
  transition: color var(--dur-fast) var(--ease);
}

.footer__link:hover {
  color: var(--color-accent-strong);
}
</style>