import { describe, it, expect, beforeEach, vi } from 'vitest'
import { defineComponent } from 'vue'
import { mount } from '@vue/test-utils'
import type { PaginatedResponse, ArticleListItem } from '@/types/api'

const { mockRoute, mockReplace } = vi.hoisted(() => ({
  mockRoute: { query: {} as Record<string, unknown> },
  mockReplace: vi.fn(),
}))

vi.mock('vue-router', () => ({
  useRoute: () => mockRoute,
  useRouter: () => ({ replace: mockReplace }),
}))

vi.mock('@/services/articleService')
import { articleService } from '@/services/articleService'
import { useArticles } from '../useArticles'

const listMock = vi.mocked(articleService.list)

/** Вызов composable внутри setup-контекста через mount. */
function withComposable<T>(fn: () => T): { result: T; unmount: () => void } {
  let result!: T
  const Comp = defineComponent({
    setup() {
      result = fn()
      return () => null
    },
  })
  const wrapper = mount(Comp)
  return { result, unmount: () => wrapper.unmount() }
}

function emptyPaged(): PaginatedResponse<ArticleListItem> {
  return {
    success: true,
    data: [],
    meta: {
      pagination: {
        total: 0,
        count: 0,
        per_page: 9,
        current_page: 1,
        total_pages: 1,
        has_more: false,
      },
    },
  }
}

describe('useArticles', () => {
  beforeEach(() => {
    for (const key of Object.keys(mockRoute.query)) {
      delete mockRoute.query[key]
    }
    mockReplace.mockReset()
    listMock.mockReset()
    listMock.mockResolvedValue(emptyPaged())
  })

  it('reads params from route.query', () => {
    mockRoute.query = { page: '2', category_id: 'cat-uuid', search: 'vue' }
    const { result, unmount } = withComposable(() => useArticles())

    expect(result.params.value.page).toBe(2)
    expect(result.params.value.category_id).toBe('cat-uuid')
    expect(result.params.value.search).toBe('vue')
    expect(result.params.value.per_page).toBe(9)
    unmount()
  })

  it('defaults page to 1 when query.page missing or invalid', () => {
    mockRoute.query = {}
    const { result, unmount } = withComposable(() => useArticles())
    expect(result.params.value.page).toBe(1)
    unmount()
  })

  it('setFilter resets page to 1 and calls router.replace', () => {
    mockRoute.query = { page: '3' }
    const { result, unmount } = withComposable(() => useArticles())

    result.setFilter({ category_id: 'cat-uuid' })

    expect(mockReplace).toHaveBeenCalledTimes(1)
    const callArg = mockReplace.mock.calls[0]?.[0] as { query: Record<string, unknown> }
    expect(callArg.query.page).toBe(1)
    expect(callArg.query.category_id).toBe('cat-uuid')
    unmount()
  })

  it('setPage updates ?page in query', () => {
    const { result, unmount } = withComposable(() => useArticles())

    result.setPage(5)

    expect(mockReplace).toHaveBeenCalledTimes(1)
    const callArg = mockReplace.mock.calls[0]?.[0] as { query: Record<string, unknown> }
    expect(callArg.query.page).toBe(5)
    unmount()
  })
})