/**
 * Auth Store (каркас)
 *
 * @description Глобальное состояние аутентификации. В фазе 7 — каркас:
 * login/logout не реализованы (throw), наполняются в фазе 9 (Admin Panel).
 * handleUnauthorized() используется apiClient-interceptor'ом при 401.
 *
 * @example
 * ```ts
 * import { useAuthStore } from '@/stores/authStore'
 * const auth = useAuthStore()
 * if (auth.isAuthenticated) { ... }
 * ```
 */

import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { User } from '@/types/api'

export const useAuthStore = defineStore('auth', () => {

  const user = ref<User | null>(null)

  const isAuthenticated = computed(() => user.value !== null)

  /**
   * Вход — реализуется в фазе 9 (Sanctum cookie + CSRF).
   */
  async function login(_credentials: { email: string; password: string }): Promise<void> {
    throw new Error('authStore.login: not implemented (phase 9)')
  }

  /**
   * Выход — реализуется в фазе 9.
   */
  async function logout(): Promise<void> {
    throw new Error('authStore.logout: not implemented (phase 9)')
  }

  /**
   * Обработка 401 от API — обнуляет пользователя.
   * Редирект на /login добавляется в фазе 9 (router-guard).
   */
  function handleUnauthorized(): void {
    user.value = null
  }

  return {
    user,
    isAuthenticated,
    login,
    logout,
    handleUnauthorized,
  }
})