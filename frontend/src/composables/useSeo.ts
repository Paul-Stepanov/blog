/**
 * useSeo — управление <head> через @unhead/vue.
 *
 * @description Тонкая обёртка над useHead: title, meta description, OG-теги,
 * canonical. Поля реактивны (MaybeRefOrGetter) — для динамических страниц
 * (статья) передавайте геттер, для статичных (Home/Contact) — строку.
 * Вызывать в setup страницы. SSR/SSG-friendly (Фаза 10).
 *
 * @example
 * ```ts
 * useSeo({ title: 'Статьи' })                          // статично
 * useSeo({ title: () => article.value?.title })        // реактивно
 * ```
 */

import { toValue, type MaybeRefOrGetter } from 'vue'
import { useHead } from '@unhead/vue'

interface SeoConfig {
  title?: MaybeRefOrGetter<string | undefined>
  description?: MaybeRefOrGetter<string | undefined>
  image?: MaybeRefOrGetter<string | undefined>
  canonical?: MaybeRefOrGetter<string | undefined>
}

export function useSeo(config: SeoConfig): void {
  // useHead с геттером → реактивно перевычисляется при изменении источников.
  useHead(() => {
    const title = toValue(config.title)
    const description = toValue(config.description)
    const image = toValue(config.image)
    const canonical = toValue(config.canonical)

    return {
      ...(title ? { title } : {}),
      meta: [
        ...(description
          ? [
              { name: 'description', content: description },
              { property: 'og:description', content: description },
            ]
          : []),
        ...(title ? [{ property: 'og:title', content: title }] : []),
        ...(image ? [{ property: 'og:image', content: image }] : []),
      ],
      ...(canonical ? { link: [{ rel: 'canonical', href: canonical }] } : {}),
    }
  })
}