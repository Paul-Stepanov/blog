/**
 * useBreakpoint — обёртка над @vueuse useBreakpoints.
 *
 * @description Точки из дизайн-токенов. Для CSS-раскладки используются
 * container queries (BentoGrid); этот composable — для JS-реакции на вьюпорт
 * (напр. условный рендер в компонентах).
 *
 * @example
 * ```ts
 * const { greaterOrEqual } = useBreakpoint()
 * const isDesktop = greaterOrEqual('lg')
 * ```
 */

import { useBreakpoints as useVueuseBreakpoints } from '@vueuse/core'

/** Точки останова (синхронизированы с --bp-* в variables.css) */
export const breakpoints = {
  sm: 375,
  md: 768,
  lg: 1024,
  xl: 1440,
} as const

export type BreakpointKey = keyof typeof breakpoints

export function useBreakpoint() {
  return useVueuseBreakpoints(breakpoints)
}