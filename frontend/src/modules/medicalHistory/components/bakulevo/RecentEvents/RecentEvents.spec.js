import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import RecentEvents from './RecentEvents.vue'

describe('RecentEvents.vue', () => {
  it('renders one item per event', () => {
    const wrapper = mount(RecentEvents, {
      props: {
        items: [
          { title: 'Анализ МНО 2.5', date: '01.01.2024', tone: 'success' },
          { title: 'Назначена доза 5', date: '02.01.2024', tone: 'info' },
        ],
      },
    })
    expect(wrapper.findAll('.event-item')).toHaveLength(2)
    expect(wrapper.text()).toContain('Анализ МНО 2.5')
    expect(wrapper.text()).toContain('Назначена доза 5')
  })

  it('shows an empty placeholder when there are no items', () => {
    const wrapper = mount(RecentEvents, { props: { items: [] } })
    expect(wrapper.find('.events-empty').text()).toBe('Событий пока нет')
  })
})
