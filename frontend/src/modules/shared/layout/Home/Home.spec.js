import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Home from './Home.vue'

function mountHome() {
  return mount(Home, {
    global: { stubs: { PatientMonitoring: true, PatientWorkList: true } }
  })
}

describe('Home.vue', () => {
  it('shows PatientMonitoring by default', () => {
    const wrapper = mountHome()
    expect(wrapper.findComponent({ name: 'PatientMonitoring' }).exists()).toBe(true)
    expect(wrapper.findComponent({ name: 'PatientWorkList' }).exists()).toBe(false)
  })

  it('switches to PatientWorkList when its tab is clicked', async () => {
    const wrapper = mountHome()
    const buttons = wrapper.findAll('.filter-tabs button')

    await buttons[1].trigger('click')

    expect(wrapper.vm.activeFilter).toBe('patientList')
    expect(wrapper.findComponent({ name: 'PatientWorkList' }).exists()).toBe(true)
    expect(wrapper.findComponent({ name: 'PatientMonitoring' }).exists()).toBe(false)
  })

  it('switches back to monitoring', async () => {
    const wrapper = mountHome()
    const buttons = wrapper.findAll('.filter-tabs button')
    await buttons[1].trigger('click')

    await buttons[0].trigger('click')

    expect(wrapper.vm.activeFilter).toBe('monitoring')
  })

  it('marks the active tab button', async () => {
    const wrapper = mountHome()
    const buttons = wrapper.findAll('.filter-tabs button')
    expect(buttons[0].classes()).toContain('active')
    expect(buttons[1].classes()).not.toContain('active')
  })
})
