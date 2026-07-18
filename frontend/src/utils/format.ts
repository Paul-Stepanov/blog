/**
 * Format utils — форматирование отображаемых значений.
 */

/**
 * Форматировать ISO-дату в российский формат (напр. "18 июля 2026 г.").
 * Возвращает пустую строку для null/невалидной даты.
 */
export function formatDate(iso: string | null | undefined): string {
  if (!iso) return ''
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) return ''
  return new Intl.DateTimeFormat('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(date)
}