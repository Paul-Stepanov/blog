import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ArticleContent from '../ArticleContent.vue'

describe('ArticleContent (DOMPurify sanitize)', () => {
  it('strips <script> tags (XSS protection)', () => {
    const html = '<p>hello</p><script>alert(1)</script>'
    const wrapper = mount(ArticleContent, { props: { html } })

    expect(wrapper.html()).not.toContain('<script')
    expect(wrapper.html()).not.toContain('alert(1)')
    expect(wrapper.text()).toContain('hello')
  })

  it('strips inline event handlers (onclick)', () => {
    const html = '<p onclick="alert(1)">click me</p>'
    const wrapper = mount(ArticleContent, { props: { html } })

    expect(wrapper.html()).not.toContain('onclick')
    expect(wrapper.text()).toContain('click me')
  })

  it('preserves safe HTML (headings, paragraphs, links)', () => {
    const html =
      '<h2>Title</h2><p>Text with <a href="https://example.com">link</a></p>'
    const wrapper = mount(ArticleContent, { props: { html } })

    expect(wrapper.find('h2').exists()).toBe(true)
    expect(wrapper.find('p').exists()).toBe(true)
    expect(wrapper.find('a').attributes('href')).toBe('https://example.com')
    expect(wrapper.text()).toContain('link')
  })
})