import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ComplicationRisk from './ComplicationRisk.vue'

function mountRisk(props = {}) {
  return mount(ComplicationRisk, { props: { mno: null, mnoFrom: 2, mnoTo: 3, ...props } })
}

describe('ComplicationRisk.vue', () => {
  it('shows «Нет данных» when there is no МНО', () => {
    const wrapper = mountRisk({ mno: null })
    expect(wrapper.vm.level).toBe('none')
    expect(wrapper.vm.label).toBe('Нет данных')
    expect(wrapper.text()).toContain('Нет данных')
  })

  it('marks МНО below the range as «Риск тромбоза» (info)', () => {
    const wrapper = mountRisk({ mno: 1.8 })
    expect(wrapper.vm.level).toBe('info')
    expect(wrapper.vm.label).toBe('Риск тромбоза')
  })

  it('marks МНО above the range as «Угроза кровотечения» (danger)', () => {
    const wrapper = mountRisk({ mno: 3.4 })
    expect(wrapper.vm.level).toBe('danger')
    expect(wrapper.vm.label).toBe('Угроза кровотечения')
  })

  it('marks МНО in range as «В норме» (success)', () => {
    const wrapper = mountRisk({ mno: 2.5 })
    expect(wrapper.vm.level).toBe('success')
    expect(wrapper.vm.label).toBe('В норме')
  })

  it('falls back to neutral when the target range is missing', () => {
    const wrapper = mountRisk({ mno: 2.5, mnoFrom: null, mnoTo: null })
    expect(wrapper.vm.level).toBe('neutral')
    expect(wrapper.vm.label).toBe('Диапазон не задан')
  })

  it('shows the «Текущий анализ» title (СЦ-1)', () => {
    const wrapper = mountRisk()
    expect(wrapper.text()).toContain('Текущий анализ')
    expect(wrapper.text()).not.toContain('Риск осложнений')
  })
})
