import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'

vi.mock('@/themes', () => ({ isBakulevo: true }))

import Home from './Home.vue'

function mountHome() {
  return mount(Home, {
    global: { stubs: { PatientMonitoring: true, PatientWorkList: true } },
  })
}

describe('Home.vue (Бакулево)', () => {
  it('скрывает вкладку «Мониторинг» и сразу показывает список пациентов', () => {
    const wrapper = mountHome()
    expect(wrapper.find('.filter-tabs').exists()).toBe(false)
    expect(wrapper.findComponent({ name: 'PatientWorkList' }).exists()).toBe(true)
    expect(wrapper.findComponent({ name: 'PatientMonitoring' }).exists()).toBe(false)
    expect(wrapper.vm.activeFilter).toBe('patientList')
  })
})
