<script setup lang="ts">
/**
 * AppFooter — подвал.
 *
 * @description Copyright из settings (site.author → site.title → 'Blog'),
 * social-ссылки из social.* ключей (white-listed). Fallback на статичные
 * значения, если settings ещё не загружены. Грузится в AppLayout.onMounted.
 */
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useSettingsStore } from '@/stores/settingsStore'

const settingsStore = useSettingsStore()

const author = computed(
  () =>
    settingsStore.get('site.author') ??
    settingsStore.get('site.title') ??
    'Blog',
)
const year = new Date().getFullYear()

const SOCIAL_LABELS: Record<string, string> = {
  github: 'GitHub',
  twitter: 'Twitter',
  x: 'X',
  linkedin: 'LinkedIn',
  telegram: 'Telegram',
  youtube: 'YouTube',
  instagram: 'Instagram',
  mastodon: 'Mastodon',
}

const socialLinks = computed(() =>
  Object.entries(settingsStore.settings)
    .filter(([key, url]) => key.startsWith('social.') && Boolean(url))
    .map(([key, url]) => {
      const name = key.replace('social.', '')
      return {
        key: name,
        url,
        label: SOCIAL_LABELS[name] ?? name.charAt(0).toUpperCase() + name.slice(1),
      }
    }),
)
</script>

<template>
  <footer class="footer">
    <div class="footer__inner container">
      <p class="footer__copy">© {{ year }} {{ author }}</p>

      <nav class="footer__links" aria-label="Подвал">
        <RouterLink to="/" class="footer__link">Главная</RouterLink>
        <RouterLink to="/articles" class="footer__link">Статьи</RouterLink>
        <RouterLink to="/contact" class="footer__link">Контакты</RouterLink>
      </nav>

      <nav
        v-if="socialLinks.length"
        class="footer__social"
        aria-label="Социальные сети"
      >
        <a
          v-for="link in socialLinks"
          :key="link.key"
          :href="link.url"
          class="footer__social-link"
          target="_blank"
          rel="noopener noreferrer"
        >
          {{ link.label }}
        </a>
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

.footer__social {
  display: flex;
  gap: var(--space-3);
}

.footer__social-link {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
  text-decoration: none;
  transition: color var(--dur-fast) var(--ease);
}

.footer__social-link:hover {
  color: var(--color-accent-strong);
}
</style>