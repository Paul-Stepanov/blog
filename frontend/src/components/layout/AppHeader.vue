<script setup lang="ts">
/**
 * AppHeader — sticky-шапка.
 *
 * @description Лого + desktop nav (≥768px) + burger (mobile) → BaseModal drawer
 * с nav-ссылками. nav-цели (/articles и т.д.) наполняются в фазе 8.
 *
 * @example — рендерится AppLayout.
 */

import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Menu } from 'lucide-vue-next'
import { useUiStore } from '@/stores/uiStore'
import BaseModal from '@/components/base/BaseModal.vue'

const ui = useUiStore()

const navLinks = [
  { to: '/', label: 'Главная' },
  { to: '/articles', label: 'Статьи' },
  { to: '/contact', label: 'Контакты' },
] as const

// v-model мост к store: BaseModal закрывается → closeMobileMenu
const mobileOpen = computed({
  get: () => ui.mobileMenuOpen,
  set: (value: boolean) => {
    if (!value) ui.closeMobileMenu()
  },
})
</script>

<template>
  <header class="header">
    <div class="header__inner container">
      <RouterLink to="/" class="header__logo">Blog</RouterLink>

      <nav class="header__nav" aria-label="Primary">
        <RouterLink
          v-for="link in navLinks"
          :key="link.to"
          :to="link.to"
          class="header__link"
        >
          {{ link.label }}
        </RouterLink>
      </nav>

      <button
        type="button"
        class="header__burger"
        aria-label="Open navigation menu"
        :aria-expanded="mobileOpen"
        @click="ui.toggleMobileMenu()"
      >
        <Menu aria-hidden="true" />
      </button>
    </div>

    <!-- Mobile drawer -->
    <BaseModal v-model:open="mobileOpen" title="Menu" size="sm">
      <nav class="drawer__nav" aria-label="Mobile">
        <RouterLink
          v-for="link in navLinks"
          :key="link.to"
          :to="link.to"
          class="drawer__link"
          @click="ui.closeMobileMenu()"
        >
          {{ link.label }}
        </RouterLink>
      </nav>
    </BaseModal>
  </header>
</template>

<style scoped>
.header {
  position: sticky;
  top: 0;
  z-index: var(--z-header);
  background: var(--color-bg-card);
  border-bottom: 1px solid var(--color-divider);
  backdrop-filter: blur(8px);
}

.header__inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: var(--header-height);
  gap: var(--space-5);
}

.header__logo {
  font-family: var(--font-display), serif;
  font-size: var(--text-lg);
  font-weight: var(--weight-bold);
  color: var(--color-text-primary);
  text-decoration: none;
}

.header__nav {
  display: none;
  gap: var(--space-5);
}

.header__link {
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-sm);
  font-weight: var(--weight-medium);
  color: var(--color-text-secondary);
  text-decoration: none;
  transition: color var(--dur-fast) var(--ease);
}

.header__link:hover,
.header__link.router-link-active {
  color: var(--color-accent-strong);
}

.header__burger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: var(--space-7);
  height: var(--space-7);
  color: var(--color-text-primary);
  border-radius: var(--radius-sm);
  transition: background-color var(--dur-fast) var(--ease);
}

.header__burger:hover {
  background: var(--color-bg-inset);
}

.drawer__nav {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.drawer__link {
  display: block;
  padding: var(--space-3);
  font-family: var(--font-body), sans-serif;
  font-size: var(--text-base);
  color: var(--color-text-primary);
  text-decoration: none;
  border-radius: var(--radius-md);
  transition: background-color var(--dur-fast) var(--ease);
}

.drawer__link:hover,
.drawer__link.router-link-active {
  background: var(--color-accent-soft);
  color: var(--color-accent-strong);
}

/* Desktop nav виден на ≥768px, burger скрыт */
@media (min-width: 768px) {
  .header__nav {
    display: flex;
  }

  .header__burger {
    display: none;
  }
}
</style>