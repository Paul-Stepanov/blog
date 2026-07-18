/**
 * Settings Service — публичные настройки сайта.
 *
 * @description GET /api/settings (PublicSettings — плоский key-value map,
 * НЕ массив SiteSetting), GET /api/settings/{key} ({key, value, value_type}).
 * White-listed: site.*, seo.*, social.*.
 *
 * @example
 * ```ts
 * const settings = await settingsService.getPublic()
 * settings['site.title'] // string | undefined
 * ```
 */

import { apiClient } from '@/services/apiClient'
import type { PublicSettings, SettingValueResponse } from '@/types/api'

interface SettingsResponse {
  success: true
  data: PublicSettings
}

interface SettingResponse {
  success: true
  data: SettingValueResponse
}

export const settingsService = {
  /** Все публичные настройки (white-listed: site.*, seo.*, social.*). */
  async getPublic(): Promise<PublicSettings> {
    const { data } = await apiClient.get<SettingsResponse>('/settings')
    return data.data
  },

  /** Конкретная настройка по key (404 если не public/не найдена). */
  async getByKey(key: string): Promise<SettingValueResponse> {
    const { data } = await apiClient.get<SettingResponse>(`/settings/${key}`)
    return data.data
  },
}