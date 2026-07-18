import { describe, it, expect, afterEach, vi } from 'vitest'
import { nextTick } from 'vue'
import { mount } from '@vue/test-utils'
import BaseModal from '../BaseModal.vue'

vi.mock('@vueuse/integrations/useFocusTrap', () => ({
  useFocusTrap: () => ({
    activate: vi.fn(),
    deactivate: vi.fn(),
    hasFocus: vi.fn(() => false),
  }),
}))

let wrapper: ReturnType<typeof mount> | null = null

afterEach(() => {
  if (wrapper) {
    wrapper.unmount()
    wrapper = null
  }
  document.body.innerHTML = ''
})

describe('BaseModal', () => {
  it('renders dialog when opened and emits update:open on Escape', async () => {
    wrapper = mount(BaseModal, { props: { open: false } })

    await wrapper.setProps({ open: true })
    await nextTick()

    const dialog = document.querySelector('[role="dialog"]')
    expect(dialog).not.toBeNull()

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }))

    expect(wrapper.emitted('update:open')?.[0]?.[0]).toBe(false)
    expect(wrapper.emitted('close')).toHaveLength(1)
  })
})