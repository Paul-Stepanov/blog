import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Pagination from '../Pagination.vue'

const pagination = (current: number, total: number) => ({
  current_page: current,
  total_pages: total,
  has_more: current < total,
})

describe('Pagination', () => {
  it('disables prev on the first page', () => {
    const wrapper = mount(Pagination, {
      props: { pagination: pagination(1, 5) },
    })
    // Первая кнопка — prev (ChevronLeft, без текста).
    const prev = wrapper.findAll('button')[0]
    expect(prev).toBeTruthy()
    expect(prev!.attributes('disabled')).toBeDefined()
  })

  it('emits change with the clicked page number', async () => {
    const wrapper = mount(Pagination, {
      props: { pagination: pagination(1, 5) },
    })
    const page3 = wrapper.findAll('button').find((b) => b.text() === '3')
    expect(page3).toBeTruthy()
    await page3!.trigger('click')

    expect(wrapper.emitted('change')).toEqual([[3]])
  })

  it('does NOT emit when clicking the current page', async () => {
    const wrapper = mount(Pagination, {
      props: { pagination: pagination(1, 5) },
    })
    const current = wrapper
      .findAll('button')
      .find((b) => b.text() === '1')
    expect(current).toBeTruthy()
    await current!.trigger('click')

    expect(wrapper.emitted('change')).toBeUndefined()
  })

  it('emits previous page number when prev is clicked mid-list', async () => {
    const wrapper = mount(Pagination, {
      props: { pagination: pagination(3, 5) },
    })
    const prev = wrapper.findAll('button')[0]
    expect(prev).toBeTruthy()
    expect(prev!.attributes('disabled')).toBeUndefined()
    await prev!.trigger('click')

    expect(wrapper.emitted('change')).toEqual([[2]])
  })
})