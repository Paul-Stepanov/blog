/**
 * Settings Store — публичные настройки сайта.
 *
 * @description Key-value map (GET /api/settings → PublicSettings) для AppFooter
 * (copyright/social) и HomePage (hero). Idempotent load. SSR-safe (Pinia Фаза 10).
 *
 * @example
 * ```ts
 * const store = useSettingsStore()
 * await store.load()
 * store.get('site.title')   // string | undefined
 * store.get('social.github')
 * ```
 */

import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { PublicSettings } from '@/types/api'
import { settingsService } from '@/services/settingsService'

export const useSettingsStore = defineStore('settings', () => {
  const settings = ref<PublicSettings>({})
  const loaded = ref(false)
  const loading = ref(false)
  const error = ref<string | null>(null)

  /** Получить значение настройки по key (undefined если нет). */
  function get(key: string): string | undefined {
    return settings.value[key]
  }

  /** Idempotent load: пропускает запрос если уже загружено (если не force). */
  async function load(force = false): Promise<void> {
    if (loaded.value && !force) return
    loading.value = true
    error.value = null
    try {
      settings.value = await settingsService.getPublic()
      loaded.value = true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load settings'
    } finally {
      loading.value = false
    }
  }

  return { settings, loaded, loading, error, get, load }
})