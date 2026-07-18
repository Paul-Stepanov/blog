import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseInput from '../BaseInput.vue'

describe('BaseInput', () => {
  it('binds modelValue and emits update:modelValue on user input', async () => {
    const wrapper = mount(BaseInput, { props: { modelValue: 'initial' } })
    const input = wrapper.find('input')

    expect(input.element.value).toBe('initial')

    await input.setValue('new value')
    const updates = wrapper.emitted('update:modelValue')
    expect(updates).toHaveLength(1)
    expect(updates?.[0]?.[0]).toBe('new value')
  })
})