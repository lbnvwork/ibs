import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'

const themeMock = vi.hoisted(() => ({ isBakulevo: false }))

vi.mock('@/themes', () => ({
  get isBakulevo() {
    return themeMock.isBakulevo
  },
  get FEATURES() {
    return {
      pharmacogenetics: !themeMock.isBakulevo,
      patientListPanel: !themeMock.isBakulevo,
      riskScale: themeMock.isBakulevo,
    }
  },
}))

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

  describe('3.58: фича-флаг бокового списка (СЦ-3.58.11/12)', () => {
    it('Алмазово: PatientListPanel виден (СЦ-3.58.12)', () => {
      themeMock.isBakulevo = false
      const wrapper = mountApp(false)
      expect(wrapper.findComponent({ name: 'PatientListPanel' }).exists()).toBe(true)
    })

    it('Бакулево: PatientListPanel скрыт (СЦ-3.58.11)', () => {
      themeMock.isBakulevo = true
      const wrapper = mountApp(false)
      expect(wrapper.findComponent({ name: 'PatientListPanel' }).exists()).toBe(false)
    })
  })
})
