import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import App from './App.vue'

function mountApp(isLoginPage) {
  return mount(App, {
    global: {
      mocks: { $route: { meta: { isLoginPage } } },
      stubs: { Sidebar: true, PatientListPanel: true, MainHeader: true, 'router-view': true }
    }
  })
}

describe('App.vue', () => {
  it('shows the sidebar, patient list panel and header on regular pages', () => {
    const wrapper = mountApp(false)
    expect(wrapper.findComponent({ name: 'Sidebar' }).exists()).toBe(true)
    expect(wrapper.findComponent({ name: 'PatientListPanel' }).exists()).toBe(true)
    expect(wrapper.findComponent({ name: 'MainHeader' }).exists()).toBe(true)
    expect(wrapper.find('.main').classes()).not.toContain('full-width')
  })

  it('hides the sidebar, patient list panel and header on the login page', () => {
    const wrapper = mountApp(true)
    expect(wrapper.findComponent({ name: 'Sidebar' }).exists()).toBe(false)
    expect(wrapper.findComponent({ name: 'PatientListPanel' }).exists()).toBe(false)
    expect(wrapper.findComponent({ name: 'MainHeader' }).exists()).toBe(false)
    expect(wrapper.find('.main').classes()).toContain('full-width')
  })
})
