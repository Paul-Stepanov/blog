/**
 * UI Store — глобальное UI-состояние.
 *
 * @description mobile-меню, тема (подготовка dark mode), уведомления.
 *
 * @example
 * ```ts
 * import { useUiStore } from '@/stores/uiStore'
 * const ui = useUiStore()
 * ui.toggleMobileMenu()
 * ui.notify('success', 'Saved')
 * ```
 */

import {defineStore} from 'pinia'
import {ref} from 'vue'
import type {Notification, NotificationType} from '@/types/models'

export const useUiStore = defineStore('ui', () => {

    const mobileMenuOpen = ref(false)
    const theme = ref<'light' | 'dark'>('light')
    const notifications = ref<Notification[]>([])

    function toggleMobileMenu(): void {
        mobileMenuOpen.value = !mobileMenuOpen.value
    }

    function closeMobileMenu(): void {
        mobileMenuOpen.value = false
    }

    function notify(type: NotificationType, message: string, title?: string): string {
        const id = crypto.randomUUID()
        notifications.value.push({id, type, message, title, duration: 5000})
        return id
    }

    function dismiss(id: string): void {
        notifications.value = notifications.value.filter((n) => n.id !== id)
    }

    function setTheme(newTheme: 'light' | 'dark'): void {
        theme.value = newTheme
    }

    return {
        mobileMenuOpen,
        theme,
        notifications,
        toggleMobileMenu,
        closeMobileMenu,
        notify,
        dismiss,
        setTheme,
    }
})