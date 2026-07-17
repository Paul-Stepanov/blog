/**
 * useSeo — управление document title (заглушка фазы 7).
 *
 * @description Минимальный SSR-safe интерфейс. Фаза 8 подменит реализацию
 * на @unhead/vue useHead без изменения API (title, description, og, schema).
 *
 * @example
 * ```ts
 * useSeo({ title: 'Article — Blog' })
 * ```
 */

interface SeoConfig {
  title?: string
  description?: string
}

export function useSeo(config: SeoConfig): void {
  // SSR guard — на сервере document недоступен
  if (typeof document === 'undefined') return

  if (config.title !== undefined) {
    document.title = config.title
  }
  // description / OG / schema — фаза 8 (@unhead/vue)
}