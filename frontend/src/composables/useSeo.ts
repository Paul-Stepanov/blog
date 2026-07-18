/**
 * useSeo — управление <head> через @unhead/vue.
 *
 * @description Тонкая обёртка над useHead: title, meta description, OG-теги,
 * canonical. Вызывать в setup страницы. SSR/SSG-friendly (Фаза 10).
 *
 * @example
 * ```ts
 * useSeo({ title: 'Статья', description: 'excerpt', image: coverUrl })
 * ```
 */

import { useHead } from '@unhead/vue'

interface SeoConfig {
  title?: string
  description?: string
  image?: string
  canonical?: string
}

export function useSeo(config: SeoConfig): void {
  useHead({
    ...(config.title ? { title: config.title } : {}),
    meta: [
      ...(config.description
        ? [
            { name: 'description', content: config.description },
            { property: 'og:description', content: config.description },
          ]
        : []),
      ...(config.title ? [{ property: 'og:title', content: config.title }] : []),
      ...(config.image ? [{ property: 'og:image', content: config.image }] : []),
    ],
    ...(config.canonical
      ? { link: [{ rel: 'canonical', href: config.canonical }] }
      : {}),
  })
}