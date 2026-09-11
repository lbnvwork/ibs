import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import CurrentTherapy from './CurrentTherapy.vue'

function mountTherapy(props = {}) {
  return mount(CurrentTherapy, {
    props: {
      drugName: 'варфарин',
      diagnosis: 'Фибрилляция предсердий',
      mnoFrom: 2,
      mnoTo: 3,
      dose: 2.5,
      doseDate: '01.01.2024',
      ...props,
    },
  })
}

describe('CurrentTherapy.vue', () => {
  it('renders drug, diagnosis, range and dose', () => {
    const wrapper = mountTherapy()
    expect(wrapper.text()).toContain('варфарин')
    expect(wrapper.text()).toContain('Фибрилляция предсердий')
    expect(wrapper.text()).toContain('2.00–3.00')
    expect(wrapper.find('.therapy-dose').text()).toBe('2.5')
    expect(wrapper.find('.therapy-dose__date').text()).toBe('01.01.2024')
  })

  it('shows a dash for the range when boundaries are missing', () => {
    const wrapper = mountTherapy({ mnoFrom: null, mnoTo: null })
    expect(wrapper.vm.rangeText).toBe('—')
  })

  it('shows a dash for the dose when missing', () => {
    const wrapper = mountTherapy({ dose: null, doseDate: '' })
    expect(wrapper.vm.doseDisplay).toBe('—')
    expect(wrapper.find('.therapy-dose__date').exists()).toBe(false)
  })
})
