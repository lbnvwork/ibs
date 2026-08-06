import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import CollapsibleSection from './CollapsibleSection.vue'

function mountSection(props = {}) {
  return mount(CollapsibleSection, {
    props: { title: 'Персональные данные', ...props },
    slots: { default: '<p>content</p>' }
  })
}

describe('CollapsibleSection.vue', () => {
  it('starts collapsed by default', () => {
    const wrapper = mountSection()
    expect(wrapper.vm.isExpanded).toBe(false)
  })

  it('honours the expanded prop as the initial state', () => {
    const wrapper = mountSection({ expanded: true })
    expect(wrapper.vm.isExpanded).toBe(true)
  })

  it('toggle() flips the expanded state', () => {
    const wrapper = mountSection()
    wrapper.vm.toggle()
    expect(wrapper.vm.isExpanded).toBe(true)
    wrapper.vm.toggle()
    expect(wrapper.vm.isExpanded).toBe(false)
  })

  it('expand()/collapse() force a specific state', () => {
    const wrapper = mountSection()
    wrapper.vm.expand()
    expect(wrapper.vm.isExpanded).toBe(true)
    wrapper.vm.collapse()
    expect(wrapper.vm.isExpanded).toBe(false)
  })

  it('forces expansion when forceExpand becomes true', async () => {
    const wrapper = mountSection({ forceExpand: false })
    expect(wrapper.vm.isExpanded).toBe(false)

    await wrapper.setProps({ forceExpand: true })

    expect(wrapper.vm.isExpanded).toBe(true)
  })

  it('does not collapse when forceExpand becomes false again', async () => {
    const wrapper = mountSection({ forceExpand: false })
    await wrapper.setProps({ forceExpand: true })
    expect(wrapper.vm.isExpanded).toBe(true)

    await wrapper.setProps({ forceExpand: false })

    expect(wrapper.vm.isExpanded).toBe(true)
  })

  it('displays the title, preview and slot content', () => {
    const wrapper = mountSection({ preview: 'Иванов Пётр' })
    expect(wrapper.text()).toContain('Персональные данные')
    expect(wrapper.text()).toContain('Иванов Пётр')
  })
})
