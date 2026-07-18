/**
 * Router — Vue Router 5.
 *
 * @description Каркас маршрутов фазы 7 (Home + NotFound). scrollBehavior,
 * auth-guard (заглушка для фазы 9), typed RouteMeta.
 */

import {
  createRouter,
  createWebHistory,
  type RouteRecordRaw,
} from 'vue-router'

export const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'home',
    component: () => import('@/features/home/pages/HomePage.vue'),
    meta: { title: 'Главная' },
  },
  {
    path: '/articles',
    name: 'articles',
    component: () => import('@/features/articles/pages/ArticlesListPage.vue'),
    meta: { title: 'Статьи' },
  },
  {
    path: '/articles/:slug',
    name: 'article',
    component: () => import('@/features/articles/pages/ArticleDetailPage.vue'),
  },
  {
    path: '/categories/:slug',
    name: 'category',
    component: () => import('@/features/categories/pages/CategoryPage.vue'),
  },
  {
    path: '/tags/:slug',
    name: 'tag',
    component: () => import('@/features/tags/pages/TagPage.vue'),
  },
  {
    path: '/contact',
    name: 'contact',
    component: () => import('@/features/contact/pages/ContactPage.vue'),
    meta: { title: 'Контакты' },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/pages/NotFoundPage.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(_to, _from, savedPosition) {
    if (savedPosition) return savedPosition
    if (_to.hash) return { el: _to.hash, behavior: 'smooth' }
    return { top: 0 }
  },
})

// Auth-guard каркас — наполняется в фазе 9 (authStore + /login route)
router.beforeEach(async (to) => {
  if (to.meta.requiresAuth) {
    const { useAuthStore } = await import('@/stores/authStore')
    if (!useAuthStore().isAuthenticated) {
      return { name: 'not-found' } // фаза 9: redirect на /login
    }
  }
  return true
})

// Type-safe route meta
declare module 'vue-router' {
  interface RouteMeta {
    title?: string
    requiresAuth?: boolean
    layout?: string
    hideFromMenu?: boolean
  }
}

export default router