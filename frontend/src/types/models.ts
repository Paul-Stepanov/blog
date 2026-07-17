/**
 * Утилитарные типы UI-слоя (не API-контракт — он в api.ts).
 */

export type ComponentVariant =
  | 'primary'
  | 'secondary'
  | 'ghost'
  | 'danger'
  | 'success'

export type ComponentSize = 'sm' | 'md' | 'lg'

export type NotificationType = 'success' | 'error' | 'warning' | 'info'

export interface Notification {
  id: string
  type: NotificationType
  title?: string
  message: string
  duration?: number // ms, 0 = без авто-закрытия
}

export interface BreadcrumbItem {
  title: string
  to?: string
  href?: string
  disabled?: boolean
}

export interface ContactPayload {
  name: string
  email: string
  subject: string
  message: string
}