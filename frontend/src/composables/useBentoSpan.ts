/**
 * useBentoSpan — расчёт CSS-переменных grid-span для BentoGridItem / BentoCard.
 *
 * @description Преобразует props colSpan/colSpanSm/Md/Lg/rowSpan в inline
 * CSS custom properties. Responsive-переключение — через @container queries
 * в styles/bento.css (родитель BentoGrid — container).
 *
 * @example
 * ```ts
 * const spanStyle = useBentoSpan(props)
 * // → { '--span-lg': '4', '--span-md': '4', '--span-sm': '1', '--row-span': '1' }
 * ```
 */

import { type CSSProperties } from 'vue'

export interface BentoSpanProps {
  /** Колонок на desktop (по умолчанию для всех точек, если sm/md/lg не заданы) */
  colSpan?: number
  /** Переопределение для mobile */
  colSpanSm?: number
  /** Переопределение для tablet */
  colSpanMd?: number
  /** Переопределение для desktop */
  colSpanLg?: number
  /** Строк */
  rowSpan?: number
}

const DEFAULT_SPAN = 4
const DEFAULT_ROW = 1

export function useBentoSpan(props: BentoSpanProps): () => CSSProperties {
  return () => ({
    '--span-lg': String(props.colSpanLg ?? props.colSpan ?? DEFAULT_SPAN),
    '--span-md': String(props.colSpanMd ?? props.colSpan ?? DEFAULT_SPAN),
    '--span-sm': String(props.colSpanSm ?? 1),
    '--row-span': String(props.rowSpan ?? DEFAULT_ROW),
  })
}