import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import MetricCard from './MetricCard.vue'

function mountCard(props = {}) {
  return mount(MetricCard, { props: { label: 'МНО', value: '2.5', ...props } })
}

describe('MetricCard.vue', () => {
  it('renders label and value', () => {
    const wrapper = mountCard()
    expect(wrapper.text()).toContain('МНО')
    expect(wrapper.text()).toContain('2.5')
  })

  it('renders a hint when provided', () => {
    const wrapper = mountCard({ hint: 'Целевой диапазон 2–3' })
    expect(wrapper.find('.metric-hint').text()).toBe('Целевой диапазон 2–3')
  })

  it('omits the hint element when not provided', () => {
    const wrapper = mountCard()
    expect(wrapper.find('.metric-hint').exists()).toBe(false)
  })

  it('applies the tone class', () => {
    const wrapper = mountCard({ tone: 'danger' })
    expect(wrapper.classes()).toContain('metric-card--danger')
  })
})
